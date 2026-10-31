<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * AKUN DEFAULT KHUSUS DEVELOPMENT.
     * WAJIB ganti email & password ini sebelum deploy ke production.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        $superAdminId = Role::query()->where('code', Role::SUPER_ADMIN)->value('id');

        if ($superAdminId) {
            $admin->roles()->syncWithoutDetaching([$superAdminId]);
        }
    }
}
