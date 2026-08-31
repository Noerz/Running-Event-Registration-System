<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Percayai proxy (VS Code tunnel / ngrok) agar URL yang digenerate mengikuti URL tunnel.
        $middleware->trustProxies(at: '*');

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

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
