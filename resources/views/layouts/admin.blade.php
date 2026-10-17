<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard Admin') — KATAR FUN RUN REG</title>
    <meta name="description" content="Panel administrasi pendaftaran event lari — kelola peserta, verifikasi pembayaran, dan terbitkan BIB.">

    {{-- Font di-host sendiri lewat app.css: tidak ada request render-blocking ke Google Fonts. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
<div x-data="{ sidebarOpen: false }" class="min-h-screen">

    <!-- Overlay mobile -->
    <div x-show="sidebarOpen" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" @click="sidebarOpen = false"></div>

    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 z-40 w-60 -translate-x-full border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="flex h-16 items-center gap-3 border-b border-slate-200 px-5">
            <img src="{{ asset('images/logo-160.png') }}" alt="Logo KATAR FUN RUN REG" width="32" height="40" class="h-8 w-auto shrink-0 object-contain">
            <span class="font-display text-base font-bold uppercase tracking-tight text-slate-900">RunEvent<span class="text-primary-700">Reg</span></span>
        </div>

        <nav class="space-y-0.5 px-3 py-4">
            @foreach ([
                ['route' => 'admin.dashboard', 'label' => 'Dashboard'],
                ['route' => 'admin.participants.*', 'label' => 'Peserta'],
                ['route' => 'admin.events.*', 'label' => 'Events'],
                ['route' => 'admin.race-categories.*', 'label' => 'Race Categories'],
                ['route' => 'admin.users.*', 'label' => 'Admins'],
            ] as $item)
                <a href="{{ route($item['route'] === 'admin.dashboard' ? 'admin.dashboard' : str_replace('.*', '', $item['route']).'.index') }}"
                    class="-ml-3 flex items-center border-l-2 px-4 py-2 text-sm font-medium transition-colors {{ request()->routeIs($item['route']) ? 'border-primary-600 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-900' }}">
                    {{ $item['label'] }}
                    @if (request()->routeIs($item['route']))
                        <span class="ml-auto h-1.5 w-1.5 rounded-full bg-primary-700"></span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="absolute inset-x-0 bottom-0 border-t border-slate-200 p-3">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2 px-2 py-2 text-sm font-medium text-slate-500 hover:text-slate-900">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    <!-- Konten -->
    <div class="lg:pl-60">
        <!-- Topbar -->
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8">
            <button type="button" @click="sidebarOpen = true" class="p-2 text-slate-600 hover:text-slate-900 lg:hidden">
                <span class="sr-only">Buka sidebar</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>

            <div class="hidden eyebrow text-slate-600 lg:block">@yield('page-title', 'Dashboard')</div>

            <div class="flex items-center gap-3">
                <div class="hidden text-right">
                    <p class="text-sm font-semibold leading-tight text-slate-800">{{ auth()->user()?->name }}</p>
                    <p class="text-xs leading-tight text-slate-600">Administrator</p>
                </div>
                <span class="flex h-9 w-9 items-center justify-center border border-slate-300 bg-white font-display text-sm font-bold uppercase text-slate-700">
                    {{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }}
                </span>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            <x-flash-alert type="success" />
            <x-flash-alert type="error" />
            <x-flash-alert type="info" />

            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
