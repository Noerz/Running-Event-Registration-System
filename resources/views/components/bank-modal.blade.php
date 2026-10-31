@props(['id' => 'bank-modal', 'accounts' => null])

@php
    if ($accounts === null) {
        $accounts = \App\Models\BankAccount::query()->active()->orderBy('order')->orderBy('id')->get();
        if ($accounts->isEmpty()) {
            $accounts = config('registration.bank_accounts', []);
        }
    }
@endphp

<div id="{{ $id }}"
    x-data="{
        open: false,
        copied: null,
        async copy(number, index) {
            try {
                await navigator.clipboard.writeText(number);
            } catch {
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
                @forelse ($accounts as $index => $acc)
                    <div class="flex items-center justify-between gap-3 rounded-sm border border-slate-200 bg-slate-50 p-4">
                        <div class="min-w-0">
                            <p class="text-xs font-bold tracking-wide text-slate-500">{{ data_get($acc, 'bank') }}</p>
                            <p class="mt-0.5 font-mono text-base font-bold tabular-nums text-slate-900">{{ data_get($acc, 'number') }}</p>
                            <p class="text-xs text-slate-500">a.n. {{ data_get($acc, 'holder') }}</p>
                        </div>
                        <button type="button"
                            @click="copy(@js((string) data_get($acc, 'number')), {{ $index }})"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-sm px-3 py-2 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                            :class="copied === {{ $index }} ? 'bg-green-100 text-green-700' : 'bg-primary-700 text-white hover:bg-primary-800'">
                            <svg x-show="copied !== {{ $index }}" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/></svg>
                            <svg x-show="copied === {{ $index }}" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            <span x-text="copied === {{ $index }} ? 'Tersalin!' : 'Salin'"></span>
                        </button>
                    </div>
                @empty
                    <p class="rounded-sm border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">
                        Nomor rekening belum diisi oleh panitia.
                    </p>
                @endforelse
            </div>

            <ol class="mt-4 space-y-1.5 text-left text-xs leading-relaxed text-slate-500">
                <li>1. Transfer sesuai nominal kategori lomba (nominal harus sama persis).</li>
                <li>2. Simpan bukti transfer Anda.</li>
                <li>3. Upload bukti pembayaran pada form ini, panitia akan verifikasi manual.</li>
            </ol>

            <button type="button" @click="open = false"
                class="mt-5 inline-flex w-full items-center justify-center rounded-sm bg-primary-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary-800">
                Saya Sudah Bayar ? Tutup
            </button>
        </div>
    </div>
</div>
