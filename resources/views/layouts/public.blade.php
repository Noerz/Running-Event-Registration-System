<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'KATAR FUN RUN REG — Pendaftaran Event Lari')</title>
    <meta name="description" content="Pendaftaran event lari — daftar, bayar, dan terima nomor peserta (BIB) Anda secara online.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-white font-sans text-slate-800 antialiased">

    <!-- Navbar -->
    <header x-data="{ mobileOpen: false }" class="sticky top-0 z-40 border-b border-slate-200 bg-white">
        <nav class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8" aria-label="Main">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/Logo.png') }}" alt="Logo KATAR FUN RUN REG" class="h-100 w-auto max-w-[48px] shrink-0 object-contain">
                <span class="font-display text-lg font-bold uppercase tracking-tight text-slate-900">KatarFun<span class="text-primary-600">Run</span></span>
            </a>

            <div class="hidden items-center gap-6 md:flex">
                <a href="{{ route('home') }}" class="text-sm font-medium text-slate-500 hover:text-slate-900">Beranda</a>
                <a href="{{ route('check.show') }}" class="text-sm font-medium text-slate-500 hover:text-slate-900">Cek Status</a>
                <a href="{{ route('register.create') }}" class="btn-primary !px-4 !py-2">Daftar</a>
            </div>

            <!-- Tombol menu mobile -->
            <button type="button" @click="mobileOpen = !mobileOpen" class="inline-flex items-center justify-center p-2 text-slate-600 hover:text-slate-900 md:hidden" :aria-expanded="mobileOpen.toString()" aria-controls="mobile-menu">
                <span class="sr-only" x-text="mobileOpen ? 'Tutup menu' : 'Buka menu'">Buka menu</span>
                <svg x-show="!mobileOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                <svg x-show="mobileOpen" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </nav>

        <!-- Menu mobile -->
        <div id="mobile-menu" x-show="mobileOpen" x-cloak class="border-t border-slate-200 bg-white px-4 pb-4 pt-2 md:hidden" @click.outside="mobileOpen = false">
            <a href="{{ route('home') }}" class="block px-1 py-2.5 text-sm font-medium text-slate-700 hover:text-slate-900">Beranda</a>
            <a href="{{ route('check.show') }}" class="block px-1 py-2.5 text-sm font-medium text-slate-700 hover:text-slate-900">Cek Status</a>
            <a href="{{ route('register.create') }}" class="btn-primary mt-2 w-full">Daftar Sekarang</a>
        </div>
    </header>

    <main class="min-h-[70vh]">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/Logo.png') }}" alt="Logo KATAR FUN RUN REG" class="h-100 w-auto max-w-[40px] shrink-0 object-contain">
                <span class="font-display text-sm font-bold uppercase tracking-tight text-slate-900">RunEvent<span class="text-primary-600">Reg</span></span>
            </div>
            <p class="text-xs text-slate-400">&copy; {{ date('Y') }} KATAR FUN RUN REG By Noerz</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
