<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Participant;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRegistration;
use Tests\TestCase;

class StatusCheckTest extends TestCase
{
    use RefreshDatabase, InteractsWithRegistration;

    public function test_user_can_check_status_by_email(): void
    {
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category, [
            'email' => 'cari@example.com',
            'phone' => '6281234500001',
            'full_name' => 'Andi Pencari',
        ]);

        $this->post(route('check.check'), ['identifier' => 'cari@example.com'])
            ->assertOk()
            ->assertSee($participant->registration_code)
            ->assertSee('Andi Pencari')
            ->assertSee('Menunggu Verifikasi Pembayaran')
            ->assertDontSee('Jl. Merdeka No. 10');
    }

    public function test_status_check_returns_all_participants_with_same_identifier(): void
    {
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $first = $this->createPendingParticipant($event, $category, ['email' => 'shared@example.com']);
        $second = $this->createPendingParticipant($event, $category, ['email' => 'shared@example.com']);

        $this->post(route('check.check'), ['identifier' => 'shared@example.com'])
            ->assertOk()
            ->assertSee($first->registration_code)
            ->assertSee($second->registration_code);
    }

    public function test_user_can_check_status_by_phone_with_normalization(): void
    {
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category, [
            'email' => 'telepon@example.com',
            'phone' => '6281234500002',
            'full_name' => 'Bella Telepon',
        ]);

        // Nomor diinput dengan format lokal 08xx — tetap ditemukan.
        $this->post(route('check.check'), ['identifier' => '0812-3450-0002'])
            ->assertOk()
            ->assertSee($participant->registration_code)
            ->assertSee('Bella Telepon');
    }

    public function test_check_page_shows_bib_and_rejection_reason(): void
    {
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $admin = $this->createAdmin();

        // Peserta approved: BIB tampil.
        $approved = $this->createPendingParticipant($event, $category, [
            'email' => 'approved@example.com',
            'phone' => '6281234500003',
        ]);
        app(PaymentService::class)->approve($approved, $admin);

        $this->post(route('check.check'), ['identifier' => 'approved@example.com'])
            ->assertOk()
            ->assertSee($approved->bib_number)
            ->assertSee(RegistrationStatus::Confirmed->label());

        // Peserta rejected: alasan penolakan tampil, tanpa BIB.
        $rejected = $this->createPendingParticipant($event, $category, [
            'email' => 'rejected@example.com',
            'phone' => '6281234500004',
        ]);
        app(PaymentService::class)->reject($rejected, 'Nominal pembayaran tidak sesuai.', $admin);

        $this->post(route('check.check'), ['identifier' => 'rejected@example.com'])
            ->assertOk()
            ->assertSee('Nominal pembayaran tidak sesuai')
            ->assertSee(RegistrationStatus::Rejected->label());

        $this->assertNull($rejected->fresh()->bib_number);
    }

    public function test_check_returns_empty_state_when_not_found(): void
    {
        $this->post(route('check.check'), ['identifier' => 'tidak-ada@example.com'])
            ->assertOk()
            ->assertSee('Pendaftaran tidak ditemukan');
    }
}
