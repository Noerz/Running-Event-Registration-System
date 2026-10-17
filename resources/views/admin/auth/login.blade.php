<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin — KATAR FUN RUN REG</title>
    <meta name="description" content="Masuk ke panel administrasi pendaftaran event lari.">

    {{-- Font di-host sendiri lewat app.css: tidak ada request render-blocking ke Google Fonts. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-slate-50 px-4 font-sans antialiased">
    <main class="w-full max-w-sm">
        <div class="mb-8">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo-160.png') }}" alt="Logo KATAR FUN RUN REG" width="36" height="45" class="h-9 w-auto shrink-0 object-contain">
                <span class="font-display text-lg font-bold uppercase tracking-tight text-slate-900">RunEvent<span class="text-primary-700">Reg</span></span>
            </div>
            <h1 class="mt-6 font-display text-xl font-bold uppercase tracking-tight text-slate-900">Login Panitia</h1>
            <p class="mt-1 text-sm text-slate-500">Masuk untuk mengelola pendaftaran peserta.</p>
        </div>

        <div class="border border-slate-200 bg-white p-8">
            @if ($errors->any())
                <div class="mb-6 rounded-sm border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                        class="field-control"
                        placeholder="admin@example.com">
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                    <input id="password" name="password" type="password" required
                        class="field-control"
                        placeholder="••••••••">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded-none border-slate-300 text-primary-700 focus:ring-primary-600">
                    Ingat saya
                </label>
                <button type="submit" class="btn-dark w-full !py-2.5">
                    Masuk
                </button>
            </form>
        </div>

        <p class="mt-6 text-xs text-slate-600">
            <a href="{{ route('home') }}" class="hover:text-slate-900">&larr; Kembali ke beranda publik</a>
        </p>
    </main>
</body>
</html>

