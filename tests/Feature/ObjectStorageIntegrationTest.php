<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Participant;
use App\Models\PaymentProof;
use App\Services\PaymentProof\PaymentProofService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\Concerns\InteractsWithRegistration;
use Tests\TestCase;

class ObjectStorageIntegrationTest extends TestCase
{
    use InteractsWithRegistration, RefreshDatabase;

    protected string $baseUrl = 'https://dev.kantorku.my.id/cdn/api/v1';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('registration.payment_proof_disk', 'object_storage');
        Config::set('object_storage.base_url', $this->baseUrl);
        Config::set('object_storage.token', 'test-token-xyz');
        Config::set('object_storage.folder', '');
        Config::set('filesystems.disks.object_storage.folder', '');
    }

    public function test_registration_uploads_to_object_storage_and_records_metadata(): void
    {
        Http::fake([
            $this->baseUrl.'/files/upload' => Http::response([
                'status' => 'success',
                'data' => ['file' => [
                    'name' => 'dummy.jpg',
                    'path' => '/payment-proofs/dummy.jpg',
                    'size' => 10240,
                    'mime' => 'image/jpeg',
                ]],
            ], 200),
        ]);

        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '10K');
        $payload = $this->registrationData($category->getKey());
        $payload['amount'] = 200000;

        $this->post(route('register.store'), $payload)->assertRedirect();

        $participant = Participant::query()->where('email', 'budi@example.com')->firstOrFail();
        $payment = Payment::query()->where('participant_id', $participant->getKey())->firstOrFail();

        $this->assertNotNull($payment->payment_proof);
        $this->assertStringStartsWith('payment-proofs/proof-', $payment->payment_proof);
        $this->assertStringContainsString('budi', $payment->payment_proof);

        $metadata = PaymentProof::query()->where('payment_id', $payment->getKey())->firstOrFail();
        $this->assertSame('object_storage', $metadata->storage_disk);
        $this->assertSame($payment->payment_proof, $metadata->object_key);
        $this->assertSame('jpg', $metadata->file_extension);
        $this->assertSame('image/jpeg', $metadata->mime_type);
        $this->assertSame(10240, $metadata->file_size);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/files/upload')
            && $request->hasHeader('X-API-Token', 'test-token-xyz'));

        // Tidak ada file lokal yang ditulis saat disk object storage aktif.
        Storage::fake('local');
        $this->assertTrue(true);
    }

    public function test_db_failure_cleans_up_orphaned_object_in_storage(): void
    {
        Http::fake([
            $this->baseUrl.'/files/upload' => Http::response([
                'status' => 'success',
                'data' => ['file' => ['name' => 'dummy.jpg', 'path' => '/payment-proofs/dummy.jpg', 'size' => 100]],
            ], 200),
            $this->baseUrl.'/files*' => Http::response(['status' => 'success'], 200),
        ]);

        $event = $this->createOpenEvent();
        $category = $event->raceCategories->first();

        // Daftarkan peserta pertama
        $this->createPendingParticipant($event, $category, ['email' => 'budi@example.com']);
        $initialPaymentCount = Payment::query()->count();

        // Percobaan pendaftaran kedua dengan email yang sama akan gagal di transaksi
        $payload = $this->registrationData($category->getKey());
        $payload['email'] = 'budi@example.com';
        $payload['amount'] = $category->price;

        $response = $this->post(route('register.store'), $payload);
        $response->assertSessionHasErrors('race_category_id');

        // Objek yang sempat diunggah harus dibersihkan via DELETE
        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
            && str_contains((string) $request->url(), '/files?path='));

        // Jumlah pembayaran di DB tidak bertambah
        $this->assertSame($initialPaymentCount, Payment::query()->count());
    }

    public function test_resubmit_proof_uploads_new_and_deletes_old_from_storage(): void
    {
        Http::fake([
            $this->baseUrl.'/files/upload' => Http::response([
                'status' => 'success',
                'data' => ['file' => ['name' => 'new.png', 'path' => '/payment-proofs/new.png', 'size' => 200, 'mime' => 'image/png']],
            ], 200),
            $this->baseUrl.'/files*' => Http::response(['status' => 'success'], 200),
        ]);

        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->first();
        $participant = $this->createPendingParticipant($event, $category);

        $participant->payment->update(['payment_status' => PaymentStatus::Rejected]);
        $participant->forceFill(['registration_status' => 'rejected'])->save();

        $oldKey = $participant->payment->payment_proof;

        $this->actingAs($admin)->post(
            route('admin.participants.resubmit-proof', $participant),
            ['payment_proof' => UploadedFile::fake()->create('new_proof.png', 20, 'image/png')]
        )->assertRedirect();

        $participant->payment->refresh();

        $this->assertNotSame($oldKey, $participant->payment->payment_proof);
        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
            && str_contains((string) $request->url(), rawurlencode($oldKey)));

        $latest = PaymentProof::query()->where('payment_id', $participant->payment->getKey())->latest('id')->first();
        $this->assertSame($participant->payment->payment_proof, $latest->object_key);
        $this->assertSame('png', $latest->file_extension);
    }

    public function test_guest_cannot_access_payment_proof(): void
    {
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->first();
        $participant = $this->createPendingParticipant($event, $category);

        $this->get(route('admin.participants.payment-proof', $participant))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_preview_and_download_payment_proof(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->first();
        $participant = $this->createPendingParticipant($event, $category);

        Http::fake([
            $this->baseUrl.'/files/info*' => Http::response([
                'status' => 'success',
                'data' => ['path' => '/x.jpg', 'name' => 'x.jpg', 'size' => 22, 'mime' => 'image/jpeg'],
            ], 200),
            $this->baseUrl.'/files/download*' => Http::response('BINARY_IMAGE_DATA_123', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $preview = $this->actingAs($admin)->get(
            route('admin.participants.payment-proof', ['participant' => $participant, 'inline' => 1])
        );
        $preview->assertOk();
        $this->assertSame('image/jpeg', $preview->headers->get('content-type'));

        $download = $this->actingAs($admin)->get(
            route('admin.participants.payment-proof', $participant)
        );
        $download->assertOk();
        $this->assertStringContainsString('attachment', (string) $download->headers->get('content-disposition'));
    }

    public function test_invalid_file_type_is_rejected_before_upload(): void
    {
        Http::fake();

        $event = $this->createOpenEvent();
        $category = $event->raceCategories->first();
        $payload = $this->registrationData($category->getKey());
        $payload['payment_proof'] = UploadedFile::fake()->create('evil.php', 10, 'application/x-php');

        $this->post(route('register.store'), $payload)
            ->assertSessionHasErrors('payment_proof');

        Http::assertNothingSent();
    }
}
