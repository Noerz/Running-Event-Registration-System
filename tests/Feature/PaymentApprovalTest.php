<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Mail\RegistrationConfirmedMail;
use App\Models\Event;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\InteractsWithRegistration;
use Tests\TestCase;

class PaymentApprovalTest extends TestCase
{
    use InteractsWithRegistration, RefreshDatabase;

    public function test_admin_can_approve_payment_via_http(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category);

        $this->actingAs($admin)
            ->post(route('admin.participants.approve', $participant))
            ->assertRedirect()
            ->assertSessionHas('success');

        $participant->refresh();
        $payment = $participant->payment()->firstOrFail();

        $this->assertSame(PaymentStatus::Approved, $payment->payment_status);
        $this->assertSame($admin->getKey(), $payment->verified_by);
        $this->assertNotNull($payment->verified_at);
        $this->assertNotNull($participant->bib_number);
        $this->assertSame(RegistrationStatus::Confirmed, $participant->registration_status);

        // BIB pertama kategori 5K harus 5K-0001.
        $this->assertSame('5K-0001', $participant->bib_number);
    }

    public function test_bib_number_is_issued_and_mailable_queued_after_approval(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '21K');
        $participant = $this->createPendingParticipant($event, $category);

        app(PaymentService::class)->approve($participant, $admin);

        Mail::assertQueued(RegistrationConfirmedMail::class, function ($mail) use ($participant) {
            return $mail->hasTo($participant->email);
        });

        $this->assertSame('21K-0001', $participant->fresh()->bib_number);
    }

    public function test_bib_numbers_are_unique_and_sequential_per_category(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '10K');

        $first = $this->createPendingParticipant($event, $category);
        $second = $this->createPendingParticipant($event, $category);

        app(PaymentService::class)->approve($first, $admin);
        app(PaymentService::class)->approve($second, $admin);

        $this->assertSame('10K-0001', $first->fresh()->bib_number);
        $this->assertSame('10K-0002', $second->fresh()->bib_number);
        $this->assertNotSame($first->fresh()->bib_number, $second->fresh()->bib_number);
    }

    public function test_bib_format_matches_code_and_sequence(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category);

        app(PaymentService::class)->approve($participant, $admin);

        // Format resmi: {KODE}-0001 tanpa event id di tengah.
        $this->assertSame('5K-0001', $participant->fresh()->bib_number);
    }

    public function test_same_category_code_in_different_events_does_not_collide(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();
        $eventA = $this->createOpenEvent();
        $eventB = $this->createOpenEvent();
        $categoryA = $eventA->raceCategories->firstWhere('code', '5K');
        $categoryB = $eventB->raceCategories->firstWhere('code', '5K');

        $participantA = $this->createPendingParticipant($eventA, $categoryA, ['email' => 'a@example.com']);
        $participantB = $this->createPendingParticipant($eventB, $categoryB, ['email' => 'b@example.com']);

        // Kedua event menerbitkan 5K-0001 — valid karena BIB unik per event.
        app(PaymentService::class)->approve($participantA, $admin);
        app(PaymentService::class)->approve($participantB, $admin);

        $this->assertSame('5K-0001', $participantA->fresh()->bib_number);
        $this->assertSame('5K-0001', $participantB->fresh()->bib_number);
        $this->assertNotSame($participantA->getKey(), $participantB->getKey());
    }

    public function test_second_approval_is_idempotent_and_does_not_issue_new_bib(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category);

        $service = app(PaymentService::class);
        $this->assertTrue($service->approve($participant, $admin));
        $bibAfterFirstApproval = $participant->fresh()->bib_number;

        // Approve kedua (double click / request ganda) tidak mengubah apa pun.
        $this->assertFalse($service->approve($participant, $admin));
        $this->assertSame($bibAfterFirstApproval, $participant->fresh()->bib_number);

        Mail::assertQueued(RegistrationConfirmedMail::class, 1);
    }

    public function test_rejected_payment_does_not_get_bib(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category);

        app(PaymentService::class)->reject($participant, 'Bukti pembayaran tidak valid.', $admin);

        $participant->refresh();
        $payment = $participant->payment()->firstOrFail();

        $this->assertSame(RegistrationStatus::Rejected, $participant->registration_status);
        $this->assertNull($participant->bib_number);
        $this->assertSame(PaymentStatus::Rejected, $payment->payment_status);
        $this->assertSame('Bukti pembayaran tidak valid.', $payment->rejection_reason);

        // Tidak ada email konfirmasi yang dikirim.
        Mail::assertNothingQueued();
    }

    public function test_approve_after_reject_is_blocked(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category);
        $service = app(PaymentService::class);

        $service->reject($participant, 'Pembayaran belum diterima.', $admin);
        $this->assertFalse($service->approve($participant, $admin));

        $this->assertNull($participant->fresh()->bib_number);
    }

    public function test_approval_writes_activity_logs(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category);

        app(PaymentService::class)->approve($participant, $admin);

        foreach (['payment_approved', 'bib_issued', 'email_queued'] as $action) {
            $this->assertDatabaseHas('activity_logs', [
                'action' => $action,
                'admin_id' => $admin->getKey(),
                'subject_id' => $participant->getKey(),
            ]);
        }
    }

    public function test_guest_cannot_approve_payment(): void
    {
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category);

        $this->post(route('admin.participants.approve', $participant))
            ->assertRedirect(route('admin.login'));

        $this->assertSame(RegistrationStatus::Pending, $participant->fresh()->registration_status);
        $this->assertNull($participant->fresh()->bib_number);
    }
}
