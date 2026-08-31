{{--
    Modal konfirmasi global berbasis Alpine.
    Pemakaian:
    $dispatch('open-confirm-modal', {
        url: '...',
        heading: 'Judul',
        description: 'Penjelasan',
        confirmText: 'Konfirmasi',
        danger: true,          // opsional: tombol merah
        requireReason: true    // opsional: wajib isi alasan (dikirim sebagai rejection_reason)
    })
--}}
<div x-data="{ open: false, config: {}, reason: '' }"
     @open-confirm-modal.window="config = $event.detail; reason = ''; open = true"
     x-cloak>

    <div x-show="open" x-transition.opacity class="fixed inset-0 z-50 bg-slate-900/50" @click="open = false"></div>

    <div x-show="open" x-transition class="fixed inset-0 z-50 flex min-h-full items-center justify-center p-4" @keydown.escape.window="open = false">
        <div class="w-full max-w-md rounded-sm border border-slate-200 bg-white p-6 shadow-xl sm:p-8" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title" aria-describedby="confirm-modal-description">
            <h3 id="confirm-modal-title" class="font-display text-lg font-bold uppercase tracking-tight text-slate-900" x-text="config.heading ?? 'Konfirmasi'"></h3>
            <p id="confirm-modal-description" class="mt-2 text-sm leading-relaxed text-slate-500" x-text="config.description ?? ''"></p>

            <form :action="config.url" method="POST" class="mt-5">
                @csrf

                <template x-if="config.requireReason">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Alasan Penolakan <span class="text-red-500">*</span></label>
                        <textarea name="rejection_reason" rows="3" required placeholder="Contoh: Bukti pembayaran tidak valid / nominal tidak sesuai"
                            class="field-control"></textarea>
                    </div>
                </template>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="open = false"
                        class="btn-outline !px-5">
                        Batal
                    </button>
                    <button type="submit"
                        :class="config.danger ? '!bg-red-600 hover:!bg-red-700 focus-visible:!outline-red-600' : '!bg-green-600 hover:!bg-green-700 focus-visible:!outline-green-600'"
                        class="btn-primary !px-5"
                        x-text="config.confirmText ?? 'Konfirmasi'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
