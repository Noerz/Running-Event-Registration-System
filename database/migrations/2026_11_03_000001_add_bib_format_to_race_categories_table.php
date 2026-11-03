<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('race_categories', function (Blueprint $table) {
            $table->string('bib_format', 25)->default('{code}-{number}')->after('code');
            $table->unsignedTinyInteger('bib_digits')->default(4)->after('bib_format');
        });
    }

    public function down(): void
    {
        Schema::table('race_categories', function (Blueprint $table) {
            $table->dropColumn(['bib_format', 'bib_digits']);
        });
    }
};
