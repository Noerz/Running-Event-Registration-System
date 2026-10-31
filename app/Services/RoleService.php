<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleService
{
    /**
     * @param  array{name: string, code: string, description?: ?string, is_active?: bool, permissions?: array<int, string>}  $data
     */
    public function create(array $data, ?int $actorId = null): Role
    {
        return DB::transaction(function () use ($data, $actorId): Role {
            $role = Role::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_system' => false,
            ]);

            $this->syncPermissions($role, $data['permissions'] ?? []);

            ActivityLog::record('role_created', $role, $actorId, "Buat role {$role->name} ({$role->code})");

            return $role;
        });
    }

    /**
     * @param  array{name: string, code: string, description?: ?string, is_active?: bool, permissions?: array<int, string>}  $data
     */
    public function update(Role $role, array $data, ?int $actorId = null): Role
    {
        return DB::transaction(function () use ($role, $data, $actorId): Role {
            $role->fill([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            // Role sistem (super-admin) dikunci: code & status aktif tidak boleh berubah.
            if (! $role->is_system) {
                $role->code = $data['code'];
                $role->is_active = $data['is_active'] ?? $role->is_active;
            }

            $role->save();

            if (! $role->is_system) {
                $this->syncPermissions($role, $data['permissions'] ?? []);
            }

            ActivityLog::record('role_updated', $role, $actorId, "Ubah role {$role->name} ({$role->code})");

            return $role;
        });
    }

    public function delete(Role $role, ?int $actorId = null): void
    {
        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => 'Role sistem tidak dapat dihapus.',
            ]);
        }

        $adminsCount = $role->admins()->count();

        if ($adminsCount > 0) {
            throw ValidationException::withMessages([
                'role' => "Role masih digunakan oleh {$adminsCount} admin. Pindahkan admin tersebut ke role lain sebelum menghapus.",
            ]);
        }

        DB::transaction(function () use ($role, $actorId): void {
            $role->permissions()->detach();
            $name = $role->name;
            $code = $role->code;
            $role->delete();

            ActivityLog::record('role_deleted', null, $actorId, "Hapus role {$name} ({$code})");
        });
    }

    public function toggleActive(Role $role, ?int $actorId = null): Role
    {
        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => 'Role sistem tidak dapat dinonaktifkan.',
            ]);
        }

        $role->update(['is_active' => ! $role->is_active]);

        ActivityLog::record(
            'role_toggled',
            $role,
            $actorId,
            ($role->is_active ? 'Aktifkan' : 'Nonaktifkan')." role {$role->name}",
        );

        return $role;
    }

    /**
     * @param  array<int, string>  $permissionCodes
     */
    public function syncPermissions(Role $role, array $permissionCodes): void
    {
        $codes = array_values(array_unique(array_filter($permissionCodes)));

        $ids = $codes === []
            ? []
            : Permission::query()->whereIn('code', $codes)->pluck('id')->all();

        $role->permissions()->sync($ids);
    }

    /**
     * Assign satu role ke seorang admin (satu admin satu role sesuai spesifikasi UI).
     */
    public function assignRoleToAdmin(int $adminId, ?int $roleId, ?int $actorId = null): void
    {
        $admin = \App\Models\User::query()->findOrFail($adminId);

        DB::transaction(function () use ($admin, $roleId, $actorId): void {
            $admin->roles()->sync($roleId ? [$roleId] : []);

            $label = $roleId ? (Role::query()->find($roleId)?->name ?? '-') : 'tanpa role';

            ActivityLog::record('admin_role_updated', $admin, $actorId, "Ubah role {$admin->name} menjadi {$label}");
        });
    }
}
