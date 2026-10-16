<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Participant;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\InteractsWithRegistration;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use InteractsWithRegistration, RefreshDatabase;

    public function test_participant_can_register(): void
    {
        Storage::fake('local');
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '10K');

        $payload = $this->registrationData($category->getKey());
        $payload['amount'] = 200000; // sesuai harga kategori 10K

        $response = $this->post(route('register.store'), $payload);

        $response->assertRedirect();

        $participant = Participant::query()->where('email', 'budi@example.com')->firstOrFail();
        $this->assertSame('Budi Santoso', $participant->full_name);
        $this->assertSame('6281234567890', $participant->phone, 'Nomor HP dinormalisasi ke format internasional.');
        $this->assertSame(
            Carbon::parse('1995-05-17')->age,
            $participant->age,
            'Usia dihitung otomatis dari tanggal lahir.'
        );
        $this->assertSame(RegistrationStatus::Pending, $participant->registration_status);

        $payment = Payment::query()->where('participant_id', $participant->getKey())->firstOrFail();
        $this->assertSame(PaymentStatus::Pending, $payment->payment_status);
        $this->assertNotNull($payment->payment_proof);

        Storage::disk('local')->assertExists($payment->payment_proof);
    }

    public function test_registration_code_is_generated(): void
    {
        Storage::fake('local');
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');

        $this->post(route('register.store'), $this->registrationData($category->getKey()))
            ->assertRedirect();

        $participant = Participant::query()->firstOrFail();

        $this->assertMatchesRegularExpression(
            '/^REG-'.date('Y').'-\d{5}$/',
            $participant->registration_code
        );

        // Halaman sukses menampilkan Registration Code (dilindungi signed URL).
        $this->get(URL::temporarySignedRoute(
            'registration.success',
            now()->addMinutes(30),
            ['registrationCode' => $participant->registration_code],
        ))
            ->assertOk()
            ->assertSee($participant->registration_code);
    }

    public function test_registration_success_page_rejects_unsigned_url(): void
    {
        Storage::fake('local');
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');

        $this->post(route('register.store'), $this->registrationData($category->getKey()))
            ->assertRedirect();

        $participant = Participant::query()->firstOrFail();

        // URL tanpa signature ditolak dan diarahkan ke cek status (anti-enumeration registration code).
        $this->get(route('registration.success', $participant->registration_code))
            ->assertRedirect(route('check.show'))
            ->assertSessionHas('info');
    }

    public function test_registration_validation_fails_with_invalid_data(): void
    {
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');

        $response = $this->post(route('register.store'), [
            'race_category_id' => $category->getKey(),
            'amount' => 100000,
        ]);

        $response->assertSessionHasErrors([
            'full_name', 'phone', 'email', 'gender', 'birth_date',
            'address', 'city', 'province', 'jersey_size',
            'emergency_contact_name', 'emergency_contact_phone',
            'payment_method', 'payment_proof', 'agree_terms',
        ]);

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_registration_rejects_underpaid_amount(): void
    {
        Storage::fake('local');
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '10K');

        $payload = $this->registrationData($category->getKey());
        $payload['amount'] = 50000; // kurang dari harga 200000

        $this->post(route('register.store'), $payload)
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_payment_proof_must_be_an_image(): void
    {
        Storage::fake('local');
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');

        // PDF tidak lagi diterima sebagai bukti pembayaran.
        $payload = $this->registrationData($category->getKey());
        $payload['payment_proof'] = UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf');
        $this->post(route('register.store'), $payload)->assertSessionHasErrors('payment_proof');

        // Dokumen berkedok gambar (mime image, ekstensi .txt) juga ditolak.
        $payload = $this->registrationData($category->getKey());
        $payload['payment_proof'] = UploadedFile::fake()->createWithContent('proof.txt', 'bukan gambar')
            ->mimeType('text/plain');
        $this->post(route('register.store'), $payload)->assertSessionHasErrors('payment_proof');

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_mass_assignment_fields_are_ignored_on_registration(): void
    {
        Storage::fake('local');
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');

        $payload = $this->registrationData($category->getKey());
        $payload['registration_status'] = 'confirmed';
        $payload['bib_number'] = 'HACK-0001';
        $payload['age'] = 5;
        $payload['event_id'] = 999;

        $this->post(route('register.store'), $payload)->assertRedirect();

        $participant = Participant::query()->firstOrFail();
        $this->assertSame(RegistrationStatus::Pending, $participant->registration_status);
        $this->assertNull($participant->bib_number);
        $this->assertSame(Carbon::parse('1995-05-17')->age, $participant->age);
        $this->assertSame($event->getKey(), $participant->event_id);
    }

    public function test_registration_blocked_when_quota_full(): void
    {
        Storage::fake('local');
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K'); // kuota 2

        for ($i = 0; $i < 2; $i++) {
            $this->createPendingParticipant($event, $category);
        }

        $payload = $this->registrationData($category->getKey());
        unset($payload['full_name'], $payload['email'], $payload['phone']);
        $payload['full_name'] = 'Peserta Ketiga';
        $payload['email'] = 'ketiga@example.com';
        $payload['phone'] = '081299900011';

        $this->post(route('register.store'), $payload)
            ->assertSessionHasErrors('race_category_id');
    }
}
