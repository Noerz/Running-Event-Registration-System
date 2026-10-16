<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Batalkan pendaftaran pending yang tidak kunjung diverifikasi,
// agar slot kuota kembali tersedia.
Schedule::command('participants:expire-pending')
    ->daily()
    ->environments(['production', 'staging']);
