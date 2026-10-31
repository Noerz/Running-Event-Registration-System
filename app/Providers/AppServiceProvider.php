<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ObjectStorage\FileGatorAdapter;
use App\Services\ObjectStorage\FileGatorService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSendingFailed;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FileGatorService::class, function ($app) {
            return new FileGatorService([
                'base_url' => config('object_storage.base_url'),
                'token' => config('object_storage.token'),
                'username' => config('object_storage.username'),
                'password' => config('object_storage.password'),
                'folder' => config('object_storage.folder'),
                'timeout' => config('object_storage.timeout'),
                'retry' => config('object_storage.retry'),
            ]);
        });

        $this->app->bind(
            \App\Services\ObjectStorage\Contracts\ObjectStorageInterface::class,
            FileGatorService::class
        );

        Storage::extend('filegator', function ($app, array $config) {
            $service = $app->make(FileGatorService::class);

            $adapter = new FileGatorAdapter($service, (string) ($config['folder'] ?? ''));

            return new \Illuminate\Filesystem\FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Rate limiting untuk halaman pengecekan status pendaftaran.
        // Dibatasi per IP dan per identifier agar email/nomor HP tidak bisa
        // ditebak berulang (enumeration).
        RateLimiter::for('status-check', function (Request $request) {
            $identifier = mb_strtolower(trim((string) $request->input('identifier')));

            return [
                Limit::perMinute(10)->by('status-check:'.$request->ip()),
                Limit::perMinute(5)->by('status-check-id:'.$request->ip().'|'.$identifier),
            ];
        });

        Gate::define('access-admin', fn (User $user): bool => (bool) $user->is_admin);

        // RBAC: Super admin bypass semua permission. Permission lain dicek via User::hasPermission.
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            if ($user->hasPermission($ability)) {
                return true;
            }

            return null;
        });

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
