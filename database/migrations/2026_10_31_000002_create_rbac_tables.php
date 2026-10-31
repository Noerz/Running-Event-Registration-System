<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 80)->unique();
            $table->string('module', 50)->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['admin_id', 'role_id']);
        });

        $this->seedRbac();
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_roles');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }

    private function seedRbac(): void
    {
        $now = now();

        // 1. Seed Permissions
        $permissionMap = [];
        foreach (Permission::DEFAULTS as $code => [$module, $name]) {
            $id = DB::table('permissions')->insertGetId([
                'code' => $code,
                'name' => $name,
                'module' => $module,
                'description' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $permissionMap[$code] = $id;
        }

        // 2. Seed Default Roles
        $superAdminRoleId = null;
        foreach (Role::DEFAULTS as $def) {
            $roleId = DB::table('roles')->insertGetId([
                'code' => $def['code'],
                'name' => $def['name'],
                'description' => $def['description'],
                'is_system' => $def['is_system'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($def['code'] === Role::SUPER_ADMIN) {
                $superAdminRoleId = $roleId;
            }

            $permIds = $def['permissions'] === '*'
                ? array_values($permissionMap)
                : array_values(array_intersect_key($permissionMap, array_flip($def['permissions'])));

            $pivotRows = array_map(fn ($pId) => [
                'role_id' => $roleId,
                'permission_id' => $pId,
                'created_at' => $now,
                'updated_at' => $now,
            ], $permIds);

            if ($pivotRows !== []) {
                DB::table('role_permission')->insert($pivotRows);
            }
        }

        // 3. Kaitkan admin existing ke Super Admin agar tidak kehilangan akses
        if ($superAdminRoleId) {
            $existingAdminIds = DB::table('users')->where('is_admin', true)->pluck('id');
            $adminRoleRows = $existingAdminIds->map(fn ($uId) => [
                'admin_id' => $uId,
                'role_id' => $superAdminRoleId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if ($adminRoleRows !== []) {
                DB::table('admin_roles')->insert($adminRoleRows);
            }
        }
    }
};
