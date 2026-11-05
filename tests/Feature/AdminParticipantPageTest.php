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
        Storage::disk('local')->put('payment-proofs/proof.png', UploadedFile::fake()->create('proof.png', 10, 'image/png')->getContent());

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

    public function test_missing_payment_proof_returns_404_instead_of_error(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category);
        $participant->payment()->firstOrFail()->update(['payment_proof' => 'payment-proofs/hilang.png']);

        $this->actingAs($admin)
            ->get(route('admin.participants.payment-proof', $participant))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.participants.payment-proof', $participant).'?inline=1')
            ->assertNotFound();
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

    public function test_admin_with_permission_can_delete_participant_and_cleans_up_storage(): void
    {
        Storage::fake('local');
        $filePath = 'payment-proofs/test-delete-proof.png';
        Storage::disk('local')->put($filePath, UploadedFile::fake()->create('proof.png', 10, 'image/png')->getContent());

        $admin = $this->createAdmin();
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category, [
            'full_name' => 'Peserta Hapus',
        ]);
        $participant->payment()->firstOrFail()->update(['payment_proof' => $filePath]);

        $this->actingAs($admin)
            ->delete(route('admin.participants.destroy', $participant))
            ->assertRedirect(route('admin.participants.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('participants', ['id' => $participant->id]);
        $this->assertDatabaseMissing('payments', ['participant_id' => $participant->id]);
        Storage::disk('local')->assertMissing($filePath);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'participant_deleted',
            'admin_id' => $admin->id,
        ]);
    }

    public function test_admin_without_delete_permission_cannot_delete_participant(): void
    {
        $viewer = $this->createAdminWithRole(\App\Models\Role::VIEWER);
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category);

        $this->actingAs($viewer)
            ->delete(route('admin.participants.destroy', $participant))
            ->assertForbidden();

        $this->assertDatabaseHas('participants', ['id' => $participant->id]);
    }

    public function test_delete_button_visibility_based_on_permission(): void
    {
        $event = $this->createOpenEvent();
        $category = $event->raceCategories->firstWhere('code', '5K');
        $participant = $this->createPendingParticipant($event, $category);

        $superAdmin = $this->createAdmin();
        $deleteLabel = 'Hapus peserta '.$participant->registration_code;

        $this->actingAs($superAdmin)
            ->get(route('admin.participants.index'))
            ->assertOk()
            ->assertSee($deleteLabel);

        $this->actingAs($superAdmin)
            ->get(route('admin.participants.show', $participant))
            ->assertOk()
            ->assertSee('Hapus Peserta');

        $viewer = $this->createAdminWithRole(\App\Models\Role::VIEWER);
        $this->actingAs($viewer)
            ->get(route('admin.participants.index'))
            ->assertOk()
            ->assertDontSee($deleteLabel);

        $this->actingAs($viewer)
            ->get(route('admin.participants.show', $participant))
            ->assertOk()
            ->assertDontSee('Hapus Peserta');
    }
}
