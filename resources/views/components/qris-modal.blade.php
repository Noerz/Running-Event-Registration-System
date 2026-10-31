@props(['id' => 'qris-modal'])

@php
    $qrisUrl = \App\Models\Setting::getQrisUrl();
@endphp

<div id="{{ $id }}"
    x-data="{ open: false }"
    @open-qris.window="open = true"
    x-effect="document.body.style.overflow = open ? 'hidden' : ''"
    x-cloak>
    <div x-show="open" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/70" @click="open = false"></div>

        <div class="relative z-10 w-full max-w-sm rounded-sm border border-slate-200 bg-white p-6 shadow-xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Scan Kode QRIS</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Scan dengan aplikasi mobile banking / e-wallet Anda.</p>
                </div>
                <button type="button" @click="open = false"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center bg-slate-100 text-slate-600 transition hover:bg-slate-200 hover:text-slate-700">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span class="sr-only">Tutup</span>
                </button>
            </div>

            <div class="mt-4 rounded-sm border border-slate-200 bg-white p-3">
                @if ($qrisUrl)
                    <img src="{{ $qrisUrl }}" alt="Kode QRIS Pembayaran"
                        class="mx-auto max-h-80 w-full rounded-sm object-contain">
                @else
                    <div class="flex aspect-square w-full flex-col items-center justify-center gap-2 rounded-sm border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                        <svg class="h-10 w-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5z" />
                        </svg>
                        <p class="text-sm font-semibold text-slate-600">Gambar QRIS Belum Tersedia</p>
                        <p class="text-xs text-slate-500">Panitia belum mengunggah kode QRIS pembayaran.</p>
                    </div>
                @endif
            </div>

            <ol class="mt-4 space-y-1.5 text-left text-xs leading-relaxed text-slate-500">
                <li>1. Scan kode QRIS menggunakan aplikasi bank/e-wallet.</li>
                <li>2. Pastikan nominal sesuai biaya kategori lomba.</li>
                <li>3. Upload bukti pembayaran, panitia verifikasi manual.</li>
            </ol>

            <button type="button" @click="open = false"
                class="mt-5 inline-flex w-full items-center justify-center rounded-sm bg-primary-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary-800">
                Saya Sudah Bayar ? Tutup
            </button>
        </div>
    </div>
</div>
