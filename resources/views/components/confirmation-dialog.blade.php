{{--
    Modal konfirmasi global berbasis Alpine. Dirender sekali di layout.

    Aksi server (form POST/DELETE):
        $dispatch('open-confirm-modal', {
            url: '...', method: 'DELETE',
            heading, description, confirmText, cancelText,
            danger: true, requireReason: true
        })

    Aksi lokal (tanpa request ke server), dipakai handler di resources/js/ux.js:
        detail: { local: true, heading, description, confirmText, onConfirm }
--}}
<div x-data="{
        open: false,
        config: {},
        submitting: false,
        request(event) {
            this.config = event.detail ?? {};
            this.submitting = false;
            this.open = true;
        },
        cancel() {
            if (this.submitting) return;
            this.open = false;
            this.config = {};
        },
        confirm() {
            if (this.submitting) return;

            if (typeof this.config.onConfirm === 'function') {
                this.open = false;
                this.config.onConfirm();
                return;
            }

            this.submitting = true;
            window.dispatchEvent(new CustomEvent('ux:loading-start'));
            this.$refs.form.submit();
        },
        closeAfterSuccess() {
            this.submitting = false;
            this.open = false;
        }
    }"
    x-on:open-confirm-modal.window="request($event)"
    x-on:close-confirm-modal.window="closeAfterSuccess()"
    x-cloak>

    <div x-show="open" x-transition.opacity class="fixed inset-0 z-50 bg-slate-900/50" @click="cancel()"></div>

    <div x-show="open" x-transition class="fixed inset-0 z-50 flex min-h-full items-center justify-center p-4" @keydown.escape.window="cancel()">
        <div class="w-full max-w-md rounded-sm border border-slate-200 bg-white p-6 shadow-xl sm:p-8" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title" aria-describedby="confirm-modal-description">
            <h3 id="confirm-modal-title" class="font-display text-lg font-bold uppercase tracking-tight text-slate-900" x-text="config.heading ?? 'Konfirmasi'"></h3>
            <p id="confirm-modal-description" class="mt-2 text-sm leading-relaxed text-slate-500" x-text="config.description ?? ''"></p>

            <form x-ref="form" :action="config.url" method="POST" class="mt-5" @submit.prevent="confirm()">
                @csrf

                <template x-if="config.method && ! ['POST', 'GET'].includes(String(config.method).toUpperCase())">
                    <input type="hidden" name="_method" :value="String(config.method).toUpperCase()">
                </template>

                <template x-if="config.requireReason">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Alasan Penolakan <span class="text-red-500">*</span></label>
                        <textarea name="rejection_reason" rows="3" required placeholder="Contoh: Bukti pembayaran tidak valid / nominal tidak sesuai"
                            class="field-control" :disabled="submitting"></textarea>
                    </div>
                </template>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="cancel()" :disabled="submitting" class="btn-outline !px-5"
                        x-text="config.cancelText ?? 'Batal'"></button>
                    <button type="submit"
                        :disabled="submitting"
                        :class="config.danger ? '!bg-red-600 hover:!bg-red-700 focus-visible:!outline-red-600' : '!bg-green-600 hover:!bg-green-700 focus-visible:!outline-green-600'"
                        class="btn-primary !px-5">
                        <x-loading-spinner x-show="submitting" x-cloak size="sm" />
                        <span x-text="submitting ? (config.loadingText ?? 'Memproses…') : (config.confirmText ?? 'Konfirmasi')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
