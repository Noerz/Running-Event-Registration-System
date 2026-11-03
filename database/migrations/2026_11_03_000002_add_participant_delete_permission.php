<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CODE = 'participants.delete';

    public function up(): void
    {
        $now = now();

        if (DB::table('permissions')->where('code', self::CODE)->exists()) {
            return;
        }

        $permissionId = DB::table('permissions')->insertGetId([
            'code' => self::CODE,
            'name' => 'Hapus data peserta',
            'module' => 'participants',
            'description' => 'Hapus data peserta',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $roleIds = DB::table('roles')->whereIn('code', ['super-admin', 'admin'])->pluck('id');

        $rows = $roleIds->map(fn ($roleId) => [
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows !== []) {
            DB::table('role_permission')->insert($rows);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('code', self::CODE)->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('role_permission')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};
