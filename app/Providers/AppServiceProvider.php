<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSendingFailed;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Rate limiting untuk halaman pengecekan status pendaftaran.
        RateLimiter::for('status-check', function (Request $request) {
            return Limit::perMinute(10)->by('status-check:'.$request->ip());
        });

        Gate::define('access-admin', fn (\App\Models\User $user): bool => (bool) $user->is_admin);

        // Audit sederhana pengiriman email notifikasi.
        Event::listen(MessageSent::class, function (MessageSent $event) {
            Log::info('Email terkirim.', [
                'subject' => $event->message?->getSubject(),
                'to' => array_keys($event->sentTo ?? []),
            ]);
        });

        Event::listen(MessageSendingFailed::class, function (MessageSendingFailed $event) {
            Log::error('Email gagal terkirim.', [
                'subject' => $event->message?->getSubject(),
            ]);
        });
    }
}

