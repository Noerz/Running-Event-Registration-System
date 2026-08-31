<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('race_category_id')->constrained()->restrictOnDelete();
            $table->string('registration_code', 20)->nullable()->unique();
            $table->string('bib_number', 30)->nullable();

            $table->string('full_name', 255)->index();
            $table->string('phone', 20)->index();
            $table->string('email', 255)->index();
            $table->string('gender', 10);
            $table->string('birth_place', 100);
            $table->date('birth_date');
            $table->unsignedTinyInteger('age');
            $table->string('identity_number', 30)->nullable();

            $table->text('address');
            $table->string('city', 100);
            $table->string('province', 100);

            $table->string('jersey_size', 5);
            $table->string('running_club', 100)->nullable();

            $table->string('emergency_contact_name', 255);
            $table->string('emergency_contact_phone', 20);

            $table->string('registration_status', 20)->default('pending')->index();
            // BIB Number unik dalam satu event (kode kategori bisa sama antar event).
            $table->unique(['event_id', 'bib_number']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
