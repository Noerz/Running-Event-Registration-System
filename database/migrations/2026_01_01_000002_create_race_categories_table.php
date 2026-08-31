<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('race_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 10);
            $table->decimal('distance', 6, 2);
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('quota');
            $table->unsignedInteger('bib_counter')->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();

            $table->unique(['event_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_categories');
    }
};
