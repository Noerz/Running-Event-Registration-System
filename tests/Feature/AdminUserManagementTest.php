<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRegistration;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase, InteractsWithRegistration;

    public function test_admin_can_view_admin_list(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($admin->name);
    }

    public function test_admin_can_create_new_admin(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Admin Baru',
                'email' => 'new-admin@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Admin Baru',
            'email' => 'new-admin@example.com',
            'is_admin' => true,
        ]);
    }

    public function test_admin_can_update_admin_details(): void
    {
        $admin = $this->createAdmin();
        $other = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $other), [
                'name' => 'Nama Baru',
                'email' => $other->email,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $other->id,
            'name' => 'Nama Baru',
        ]);
    }

    public function test_cannot_delete_own_account(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_other_admin(): void
    {
        $admin = $this->createAdmin();
        $other = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $other))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }

    public function test_guest_and_non_admin_cannot_access(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->get(route('admin.users.index'))->assertRedirect(route('admin.login'));
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }
}
