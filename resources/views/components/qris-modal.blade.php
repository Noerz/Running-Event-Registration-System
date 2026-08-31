@props(['id' => 'qris-modal'])

@php
    $qrisImage = (string) config('registration.qris_image');
    $qrisExists = $qrisImage !== '' && file_exists(public_path($qrisImage));
@endphp

<div id="{{ $id }}" x-data="{ open: false }"
    @open-qris.window="open = true"
    x-effect="document.body.style.overflow = open ? 'hidden' : ''"
    x-cloak>
    <div x-show="open" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <!-- Backdrop klik untuk menutup -->
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

            <div class="mt-4 rounded-sm bg-white p-3 border border-slate-200">
                @if ($qrisExists)
                    <img src="{{ asset($qrisImage) }}" alt="Kode QRIS pembayaran" class="mx-auto w-full rounded-sm object-contain">
                @else
                    <div class="flex aspect-square w-full flex-col items-center justify-center gap-2 rounded-sm border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                        <svg class="h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z"/></svg>
                        <p class="text-sm font-semibold text-slate-600">Gambar QRIS belum diunggah</p>
                        <p class="text-xs text-slate-400">Panitia perlu menaruh file QRIS di <code class="rounded bg-slate-200 px-1">public/{{ $qrisImage }}</code></p>
                    </div>
                @endif
            </div>

            <ol class="mt-4 space-y-1.5 text-left text-xs leading-relaxed text-slate-500">
                <li>1. Scan kode QRIS di atas atau screenshot lalu unggah di aplikasi bank/e-wallet.</li>
                <li>2. Bayar sesuai nominal kategori lomba.</li>
                <li>3. Upload bukti pembayaran pada form ini, panitia akan verifikasi manual.</li>
            </ol>

            <button type="button" @click="open = false"
                class="mt-5 inline-flex w-full items-center justify-center rounded-sm bg-primary-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary-700">
                Saya Sudah Bayar — Tutup
            </button>
        </div>
    </div>
</div>


