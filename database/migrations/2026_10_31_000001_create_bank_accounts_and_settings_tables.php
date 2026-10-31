<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank', 50);
            $table->string('number', 50);
            $table->string('holder', 255);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed default dari konfigurasi bila ada
        $initialAccounts = config('registration.bank_accounts', []);
        if (is_array($initialAccounts)) {
            foreach ($initialAccounts as $index => $acc) {
                DB::table('bank_accounts')->insert([
                    'bank' => (string) ($acc['bank'] ?? 'Bank'),
                    'number' => (string) ($acc['number'] ?? '0'),
                    'holder' => (string) ($acc['holder'] ?? 'Panitia'),
                    'is_active' => true,
                    'order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('settings');
    }
};
