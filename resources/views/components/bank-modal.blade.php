@props(['id' => 'bank-modal', 'accounts' => []])

<div id="{{ $id }}" x-data="{
        open: false,
        copied: null,
        async copy(number, index) {
            try {
                await navigator.clipboard.writeText(number);
            } catch {
                // Fallback untuk browser tanpa Clipboard API (mis. HTTP non-localhost).
                const textarea = document.createElement('textarea');
                textarea.value = number;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                textarea.remove();
            }
            this.copied = index;
            clearTimeout(this._reset);
            this._reset = setTimeout(() => this.copied = null, 2000);
        }
    }"
    @open-bank.window="open = true"
    x-effect="document.body.style.overflow = open ? 'hidden' : ''"
    x-cloak>
    <div x-show="open" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <!-- Backdrop klik untuk menutup -->
        <div class="absolute inset-0 bg-slate-950/70" @click="open = false"></div>

        <div class="relative z-10 w-full max-w-sm rounded-sm border border-slate-200 bg-white p-6 shadow-xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Rekening Tujuan Transfer</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Transfer sesuai nominal kategori lomba ke salah satu rekening berikut.</p>
                </div>
                <button type="button" @click="open = false"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center bg-slate-100 text-slate-600 transition hover:bg-slate-200 hover:text-slate-700">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span class="sr-only">Tutup</span>
                </button>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($accounts as $index => $account)
                    <div class="flex items-center justify-between gap-3 rounded-sm border border-slate-200 bg-slate-50 p-4">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $account['bank'] }}</p>
                            <p class="mt-0.5 truncate font-mono text-base font-bold tabular-nums text-slate-900">{{ $account['number'] }}</p>
                            <p class="truncate text-xs text-slate-500">a.n. {{ $account['holder'] }}</p>
                        </div>
                        <button type="button"
                            @click="copy(@js($account['number']), {{ $index }})"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-sm px-3 py-2 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                            :class="copied === {{ $index }} ? 'bg-green-100 text-green-700' : 'bg-primary-600 text-white hover:bg-primary-700'">
                            <svg x-show="copied !== {{ $index }}" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"/></svg>
                            <svg x-show="copied === {{ $index }}" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            <span x-text="copied === {{ $index }} ? 'Tersalin!' : 'Salin'"></span>
                        </button>
                    </div>
                @empty
                    <p class="rounded-sm border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">
                        Daftar rekening belum diisi oleh panitia.
                    </p>
                @endforelse
            </div>

            <ol class="mt-4 space-y-1.5 text-left text-xs leading-relaxed text-slate-500">
                <li>1. Transfer sesuai nominal kategori lomba (nominal harus sama persis).</li>
                <li>2. Simpan bukti transfer Anda.</li>
                <li>3. Upload bukti pembayaran pada form ini, panitia akan verifikasi manual.</li>
            </ol>

            <button type="button" @click="open = false"
                class="mt-5 inline-flex w-full items-center justify-center rounded-sm bg-primary-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary-700">
                Saya Sudah Bayar — Tutup
            </button>
        </div>
    </div>
</div>

