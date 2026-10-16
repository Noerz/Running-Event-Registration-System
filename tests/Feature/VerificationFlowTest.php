<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Participant;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithRegistration;
use Tests\TestCase;

class VerificationFlowTest extends TestCase
{
    use InteractsWithRegistration, RefreshDatabase;

    public function test_duplicate_email_blocked_on_same_event(): void
    {
        Storage::fake('local');
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');

        $this->post(route('register.store'), $this->registrationData($category->getKey()))
            ->assertRedirect();

        $this->post(route('register.store'), $this->registrationData($category->getKey()))
            ->assertSessionHasErrors('race_category_id');

        $this->assertDatabaseCount('participants', 1);
    }

    public function test_rejected_participant_can_resubmit_proof(): void
    {
        Storage::fake('local');
        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category);

        // Reject dulu.
        $this->actingAs($admin)->post(route('admin.participants.reject', $participant), [
            'rejection_reason' => 'Bukti transfer tidak terbaca jelas.',
        ])->assertRedirect();

        $participant->refresh();
        $this->assertSame(RegistrationStatus::Rejected, $participant->registration_status);

        $oldProof = $participant->payment->payment_proof;
        $newProof = UploadedFile::fake()->create('proof-baru.jpg', 10, 'image/jpeg');

        $this->actingAs($admin)->post(route('admin.participants.resubmit-proof', $participant), [
            'payment_proof' => $newProof,
        ])->assertRedirect();

        $participant->refresh();
        $payment = $participant->payment;

        $this->assertSame(RegistrationStatus::Pending, $participant->registration_status);
        $this->assertSame(PaymentStatus::Pending, $payment->payment_status);
        $this->assertNull($payment->rejection_reason);
        $this->assertNotSame($oldProof, $payment->payment_proof);
        Storage::disk('local')->assertExists($payment->payment_proof);
        Storage::disk('local')->assertMissing($oldProof);
    }

    public function test_resubmit_rejected_for_confirmed_payment(): void
    {
        Storage::fake('local');
        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category);

        // Approve langsung (confirmed), lalu coba resubmit harus ditolak.
        $this->actingAs($admin)->post(route('admin.participants.approve', $participant))->assertRedirect();

        $this->actingAs($admin)->post(route('admin.participants.resubmit-proof', $participant), [
            'payment_proof' => UploadedFile::fake()->create('x.jpg', 10, 'image/jpeg'),
        ])->assertRedirect();

        $participant->refresh();
        $this->assertSame(PaymentStatus::Approved, $participant->payment->payment_status);
    }

    public function test_admin_can_edit_participant_data(): void
    {
        Storage::fake('local');
        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category);

        $this->actingAs($admin)->put(route('admin.participants.update', $participant), [
            'full_name' => 'Budi Santoso Baru',
            'phone' => '081234567890',
            'email' => 'budibaru@example.com',
            'gender' => 'male',
            'birth_place' => 'Bandung',
            'birth_date' => '1995-05-17',
            'identity_number' => null,
            'address' => 'Jl. Baru No. 1',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'jersey_size' => 'XL',
            'running_club' => null,
            'emergency_contact_name' => 'Siti Santoso',
            'emergency_contact_phone' => '081298765432',
        ])->assertRedirect(route('admin.participants.show', $participant));

        $participant->refresh();
        $this->assertSame('Budi Santoso Baru', $participant->full_name);
        $this->assertSame('budibaru@example.com', $participant->email);
        $this->assertSame('XL', $participant->jersey_size);
        $this->assertDatabaseHas('activity_logs', ['action' => 'participant_updated']);
    }

    public function test_expire_pending_command_binds_quota(): void
    {
        Storage::fake('local');
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K'); // kuota 2
        $pending = $this->createPendingParticipant($event, $category);
        $confirmed = $this->createPendingParticipant($event, $category, ['email' => 'oke@example.com']);

        // Satu pending jadi confirmed, satu tetap pending tapi sudah lewat batas.
        $this->actingAs($this->createAdmin())
            ->post(route('admin.participants.approve', $confirmed))
            ->assertRedirect();

        // Backdate pendaftaran pending 8 hari lalu (lewat batas 7 hari).
        Participant::query()->whereKey($pending->getKey())->update([
            'created_at' => now()->subDays(8),
        ]);
        Payment::query()->where('participant_id', $pending->getKey())->update([
            'created_at' => now()->subDays(8),
        ]);

        $this->artisan('participants:expire-pending')->assertSuccessful();

        $pending->refresh();
        $this->assertSame(RegistrationStatus::Rejected, $pending->registration_status);
        $this->assertSame(PaymentStatus::Rejected, $pending->payment->payment_status);

        // Kuota kembali: tersisa 1 slot (confirmed masih terpakai, pending expired dilepas).
        $this->assertSame(1, $category->refresh()->remainingQuota());
    }
}
