<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithRegistration;
use Tests\TestCase;

class AdminParticipantPageTest extends TestCase
{
    use RefreshDatabase, InteractsWithRegistration;

    public function test_admin_can_view_participants_index_and_detail(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category, [
            'full_name' => 'Rina Halaman',
            'email' => 'rina@example.com',
            'phone' => '6281234599999',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.participants.index'))
            ->assertOk()
            ->assertSee($participant->registration_code)
            ->assertSee('Rina Halaman');

        $this->actingAs($admin)
            ->get(route('admin.participants.show', $participant))
            ->assertOk()
            ->assertSee('Personal Information')
            ->assertSee('Race Information')
            ->assertSee('Payment Information')
            ->assertSee('Approve Payment');
    }

    public function test_admin_can_download_private_payment_proof(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('payment-proofs/proof.png', UploadedFile::fake()->image('proof.png')->getContent());

        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category);
        $participant->payment()->firstOrFail()->update(['payment_proof' => 'payment-proofs/proof.png']);

        // Bukti pembayaran hanya bisa diakses admin yang sudah login.
        $this->get(route('admin.participants.payment-proof', $participant))
            ->assertRedirect(route('admin.login'));

        $this->actingAs($admin)
            ->get(route('admin.participants.payment-proof', $participant))
            ->assertOk();

        // File tidak boleh berada di disk publik.
        Storage::disk('public')->assertMissing('payment-proofs/proof.png');
    }

    public function test_participant_list_supports_search_and_filter(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category5k = $event->raceCategories->firstWhere('code', '5K');
        $category10k = $event->raceCategories->firstWhere('code', '10K');

        $target = $this->createPendingParticipant($event, $category10k, [
            'full_name' => 'Zelda Pencarian',
            'email' => 'zelda@example.com',
            'phone' => '6281288800001',
        ]);
        $other = $this->createPendingParticipant($event, $category5k);

        $this->actingAs($admin)
            ->get(route('admin.participants.index', ['search' => 'Zelda']))
            ->assertOk()
            ->assertSee('Zelda Pencarian')
            ->assertDontSee($other->registration_code);

        $this->actingAs($admin)
            ->get(route('admin.participants.index', ['category' => $category10k->getKey()]))
            ->assertOk()
            ->assertSee('Zelda Pencarian');

        $this->actingAs($admin)
            ->get(route('admin.participants.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee($target->registration_code);
    }
}
