<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('storage_disk', 50)->default('object_storage');
            $table->string('object_key', 255)->index();
            $table->string('file_name', 255);
            $table->string('file_extension', 20);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->string('file_url', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
    }
};
