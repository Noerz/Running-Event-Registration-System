<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Percayai proxy hanya yang terdaftar eksplisit (VS Code tunnel / ngrok / load balancer).
        // Jangan '*': spoof X-Forwarded-For bisa memalsukan $request->ip() dan membobol rate limiter.
        $trustedProxies = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))));

        if ($trustedProxies !== []) {
            $middleware->trustProxies(at: $trustedProxies);
        }

        // Batasi Host header agar tidak bisa dipoisoning (mencegah URL host palsu pada link & mail).
        $trustedHosts = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_HOSTS', '')))));

        if ($trustedHosts !== []) {
            $middleware->trustHosts($trustedHosts);
        }

        // Tamu (belum login) yang menyentuh area admin diarahkan ke halaman login admin.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin/*') ? route('admin.login') : null);

        // Pengguna yang sudah login tidak melihat halaman login lagi.
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            return back()
                ->withInput($request->except('payment_proof'))
                ->withErrors([
                    'payment_proof' => 'Ukuran bukti pembayaran terlalu besar. Maksimal '.number_format(config('registration.payment_proof_max_kb') / 1024, 0).' MB.',
                ]);
        });

        // Tautan sukses pendaftaran kadaluarsa / tidak valid: arahkan ke cek
        // status dengan pesan ramah, bukan halaman 403 kosong.
        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            if ($request->routeIs('registration.success')) {
                return redirect()
                    ->route('check.show')
                    ->with('info', 'Tautan pendaftaran kadaluarsa atau tidak valid. Gunakan email/nomor HP Anda untuk melihat status di halaman ini.');
            }

            return abort(403);
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
