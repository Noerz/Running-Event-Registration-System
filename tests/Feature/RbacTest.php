<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRegistration;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithRegistration;

    /**
     * Skenario 1: Super Admin dapat mengakses seluruh fitur (bypass Gate).
     */
    public function test_scenario_1_super_admin_can_access_all_features(): void
    {
        $superAdmin = $this->createAdminWithRole(Role::SUPER_ADMIN);

        $this->actingAs($superAdmin);

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.roles.index'))->assertOk();
        $this->get(route('admin.roles.create'))->assertOk();
        $this->get(route('admin.users.index'))->assertOk();
        $this->get(route('admin.events.index'))->assertOk();
        $this->get(route('admin.participants.index'))->assertOk();
        $this->get(route('admin.payment-settings.index'))->assertOk();
    }

    /**
     * Skenario 2: Admin biasa hanya dapat mengakses modul sesuai permission yang diberikan.
     */
    public function test_scenario_2_admin_only_accesses_permitted_modules(): void
    {
        $admin = $this->createAdminWithRole(Role::ADMIN);

        $this->actingAs($admin);

        // Diizinkan oleh default role 'admin'
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.events.index'))->assertOk();
        $this->get(route('admin.participants.index'))->assertOk();

        // Tidak diizinkan (Role Management hanya untuk Super Admin)
        $this->get(route('admin.roles.index'))->assertForbidden();
        $this->get(route('admin.roles.create'))->assertForbidden();
    }

    /**
     * Skenario 3: Viewer hanya dapat melakukan operasi read-only.
     */
    public function test_scenario_3_viewer_is_read_only(): void
    {
        $viewer = $this->createAdminWithRole(Role::VIEWER);

        $this->actingAs($viewer);

        // Read OK
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.events.index'))->assertOk();
        $this->get(route('admin.participants.index'))->assertOk();

        // Write dicegah (403)
        $this->post(route('admin.events.store'), [
            'name' => 'Event Viewer Coba Buat',
            'location' => 'Surabaya',
            'event_date' => '2030-01-01',
            'registration_start' => today()->toDateString(),
            'registration_end' => today()->addDays(5)->toDateString(),
            'status' => 'open',
        ])->assertForbidden();

        $this->assertDatabaseMissing('events', ['name' => 'Event Viewer Coba Buat']);
    }

    /**
     * Skenario 4: Admin tanpa permission ditolak (403) pada endpoint terkait.
     */
    public function test_scenario_4_admin_without_permission_is_denied(): void
    {
        // Buat role kustom hanya punya dashboard.view
        $role = Role::create([
            'name' => 'Dashboard Only',
            'code' => 'dashboard-only',
            'description' => 'Hanya dashboard',
            'is_active' => true,
            'is_system' => false,
        ]);
        $dashPerm = Permission::where('code', 'dashboard.view')->first();
        $role->permissions()->attach($dashPerm);

        $user = User::factory()->create(['is_admin' => true]);
        $user->roles()->attach($role);

        $this->actingAs($user);

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.events.index'))->assertForbidden();
        $this->get(route('admin.participants.index'))->assertForbidden();
        $this->get(route('admin.payment-settings.index'))->assertForbidden();
    }

    /**
     * Skenario 5: Request langsung API / URL tanpa permission mendapatkan HTTP 403.
     */
    public function test_scenario_5_direct_request_without_permission_returns_403(): void
    {
        $viewer = $this->createAdminWithRole(Role::VIEWER);

        $this->actingAs($viewer);

        // Coba direct POST ke payment toggle
        $response = $this->post(route('admin.payment-settings.bank.store'), [
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Panitia',
            'is_active' => true,
        ]);

        $response->assertForbidden();
    }

    /**
     * Skenario 6: Admin yang role-nya diubah langsung mendapatkan hak akses terbaru.
     */
    public function test_scenario_6_role_change_takes_effect_immediately(): void
    {
        $admin = $this->createAdminWithRole(Role::VIEWER);

        $this->actingAs($admin);

        // Awalnya viewer -> tidak boleh membuat role
        $this->get(route('admin.roles.create'))->assertForbidden();

        // Promote jadi super admin
        $superAdminRole = Role::where('code', Role::SUPER_ADMIN)->first();
        $admin->roles()->sync([$superAdminRole->id]);

        // Request berikutnya langsung diizinkan tanpa re-login
        $this->get(route('admin.roles.create'))->assertOk();
    }

    /**
     * Skenario 7: Role yang masih digunakan tidak dapat dihapus tanpa penanganan yang tepat.
     */
    public function test_scenario_7_in_use_role_cannot_be_deleted(): void
    {
        $superAdmin = $this->createAdminWithRole(Role::SUPER_ADMIN);

        // Buat custom role lalu pasang ke seorang admin
        $role = Role::create([
            'name' => 'Finance Staff',
            'code' => 'finance-staff',
            'description' => 'Staff keuangan',
            'is_active' => true,
            'is_system' => false,
        ]);

        $staff = User::factory()->create(['is_admin' => true]);
        $staff->roles()->attach($role);

        $this->actingAs($superAdmin);

        // Coba delete via service / HTTP
        $response = $this->from(route('admin.roles.index'))
            ->delete(route('admin.roles.destroy', $role));

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHasErrors(['role']);

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    /**
     * Skenario 8: Permission yang tidak terdaftar tidak memberikan akses.
     */
    public function test_scenario_8_unregistered_permission_denies_access(): void
    {
        $admin = $this->createAdminWithRole(Role::ADMIN);

        // hasPermission dengan nama permission asal
        $this->assertFalse($admin->hasPermission('non.existent.permission'));
        $this->assertFalse($admin->hasPermission('events.super_destroy_all'));
    }

    /**
     * Skenario 9: Admin existing tetap dapat login dan akses dashboard setelah database migration.
     */
    public function test_scenario_9_existing_admin_can_login_and_access(): void
    {
        // Simulasikan admin existing yang dibuat oleh migration seeder
        $admin = User::create([
            'name' => 'Existing Admin',
            'email' => 'existing-admin@test.com',
            'password' => bcrypt('password123'),
            'is_admin' => true,
        ]);

        // Dipasangi super-admin sebagaimana dilakukan migration 2026_10_31_000002
        $superRole = Role::where('code', Role::SUPER_ADMIN)->first();
        $admin->roles()->attach($superRole);

        // Login via form
        $response = $this->post(route('admin.login.attempt'), [
            'email' => 'existing-admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);

        $this->get(route('admin.dashboard'))->assertOk();
    }

    /**
     * Skenario 10: Admin tidak dapat menaikkan role atau permission dirinya sendiri melalui manipulasi request.
     */
    public function test_scenario_10_admin_cannot_escalate_own_role(): void
    {
        // Admin biasa yang punya permission users.update (kita beri role custom)
        $role = Role::create([
            'name' => 'User Manager',
            'code' => 'user-manager',
            'description' => 'Hanya kelola user',
            'is_active' => true,
            'is_system' => false,
        ]);
        $perms = Permission::whereIn('code', [
            'dashboard.view',
            'users.view',
            'users.update',
        ])->get();
        $role->permissions()->attach($perms);

        $admin = User::factory()->create(['is_admin' => true]);
        $admin->roles()->attach($role);

        $superRole = Role::where('code', Role::SUPER_ADMIN)->first();

        $this->actingAs($admin);

        // Coba edit diri sendiri menjadi Super Admin
        $response = $this->from(route('admin.users.edit', $admin))
            ->put(route('admin.users.update', $admin), [
                'name' => 'Hacker Admin',
                'email' => $admin->email,
                'role_id' => $superRole->id,
            ]);

        $response->assertRedirect(route('admin.users.edit', $admin));
        $response->assertSessionHasErrors(['role_id']);

        // Pastikan role tetap user-manager, bukan super-admin
        $admin->refresh();
        $this->assertFalse($admin->isSuperAdmin());
        $this->assertEquals('user-manager', $admin->roles()->first()->code);
    }

    /**
     * Test tambahan: validasi audit trail saat role dibuat/diupdate/dihapus.
     */
    public function test_role_management_records_activity_logs(): void
    {
        $superAdmin = $this->createAdminWithRole(Role::SUPER_ADMIN);

        $this->actingAs($superAdmin);

        $response = $this->post(route('admin.roles.store'), [
            'name' => 'Audited Role',
            'code' => 'audited-role',
            'description' => 'Untuk test log',
            'is_active' => 1,
            'permissions' => ['dashboard.view', 'events.view'],
        ]);

        $response->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseHas('activity_logs', [
            'admin_id' => $superAdmin->id,
            'action' => 'role_created',
        ]);
    }
}
