@props(['id' => 'proof-lightbox'])

<div id="{{ $id }}" x-data="{ open: false, url: '', title: 'Bukti Pembayaran', zoom: false }"
    @open-proof.window="url = $event.detail.url; title = $event.detail.title || 'Bukti Pembayaran'; zoom = false; open = true"
    x-effect="document.body.style.overflow = open ? 'hidden' : ''"
    x-cloak>
    <div x-show="open" class="fixed inset-0 z-[70] flex flex-col bg-slate-950/90">
        <!-- Backdrop klik untuk menutup -->
        <div class="absolute inset-0 cursor-zoom-out" @click="open = false"></div>

        <!-- Header -->
        <div class="relative z-10 flex items-center justify-between gap-3 border-b border-white/10 bg-slate-950/80 px-4 py-3 backdrop-blur sm:px-6">
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-white sm:text-base" x-text="title"></p>
                <p class="mt-0.5 text-xs text-slate-600">Klik bukti untuk memperbesar</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <button type="button" @click="zoom = !zoom"
                    class="inline-flex items-center gap-1.5 rounded-sm bg-white/10 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/70"
                    x-text="zoom ? 'Perkecil' : 'Perbesar'"></button>
                <a :href="url" download
                    class="inline-flex items-center gap-1.5 rounded-sm bg-white/10 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/70">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Download
                </a>
            </div>
        </div>

        <!-- Area gambar: scrollable penuh -->
        <div class="relative z-10 min-h-0 flex-1 overflow-auto p-4 sm:p-8" @click.self="open = false">
            <div class="flex min-h-full w-max min-w-full items-center justify-center">
                <img :src="url" :alt="title" @click="zoom = !zoom"
                    class="rounded-sm bg-white p-2 shadow-xl ring-1 ring-white/20 transition-transform duration-200"
                    :class="zoom ? 'max-w-none cursor-zoom-out' : 'max-h-[75vh] max-w-full cursor-zoom-in object-contain'">
            </div>
        </div>

        <!-- Tombol silang: selalu tampil di pojok kanan atas -->
        <button type="button" @click="open = false"
            class="absolute right-3 top-14 z-20 inline-flex h-11 w-11 items-center justify-center bg-red-600 text-white shadow-lg ring-2 ring-white/30 hover:bg-red-500 sm:right-6 sm:top-16">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            <span class="sr-only">Tutup</span>
        </button>
    </div>
</div>



