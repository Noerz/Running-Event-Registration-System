/**
 * UX global: loading feedback & pencegahan submit ganda.
 * Dipasang sekali untuk semua <form> dan <a> agar tidak perlu markup per halaman.
 */

const SPINNER =
    '<svg class="h-4 w-4 shrink-0 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
    '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>' +
    '<path d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" fill="currentColor" class="opacity-90"/></svg>';

let busy = false;
let progressTimer = null;
let disabledByUs = [];

function progressElement() {
    let el = document.getElementById('page-progress');

    if (!el) {
        el = document.createElement('div');
        el.id = 'page-progress';
        el.className = 'page-progress';
        el.setAttribute('role', 'progressbar');
        el.setAttribute('aria-label', 'Memuat');
        document.body.appendChild(el);
    }

    return el;
}

export function startProgress() {
    const el = progressElement();

    clearTimeout(progressTimer);
    el.style.width = '15%';
    progressTimer = setTimeout(() => {
        el.style.width = '80%';
    }, 150);
}

export function stopProgress() {
    clearTimeout(progressTimer);

    const el = document.getElementById('page-progress');

    if (!el) return;

    el.style.width = '100%';
    setTimeout(() => {
        el.style.width = '0';
    }, 200);
}

/** Kembalikan semua tombol ke kondisi normal (dipakai juga saat halaman di-restore dari bfcache). */
export function resetLoading() {
    busy = false;
    stopProgress();

    disabledByUs.forEach((button) => {
        button.disabled = false;
    });
    disabledByUs = [];

    document.querySelectorAll('[data-loading="1"]').forEach((button) => {
        if (button.dataset.originalHtml !== undefined) {
            button.innerHTML = button.dataset.originalHtml;
            delete button.dataset.originalHtml;
        }

        delete button.dataset.loading;
        button.classList.remove('pointer-events-none', 'opacity-80');
        button.removeAttribute('aria-busy');
    });
}

function markLoading(button) {
    if (button.dataset.loading === '1') return;

    button.dataset.loading = '1';
    button.dataset.originalHtml = button.innerHTML;
    button.classList.add('pointer-events-none', 'opacity-80');
    button.setAttribute('aria-busy', 'true');
    button.innerHTML = SPINNER + '<span>' + (button.dataset.loadingText || 'Memproses…') + '</span>';
}

document.addEventListener(
    'submit',
    (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-loading')) return;

        // Form bertanda data-confirm minta konfirmasi dulu; submit diulang setelah disetujui.
        if (form.hasAttribute('data-confirm') && form.dataset.confirmed !== '1') {
            event.preventDefault();

            const submitter = event.submitter || null;

            window.dispatchEvent(
                new CustomEvent('open-confirm-modal', {
                    detail: {
                        local: true,
                        heading: form.dataset.confirmHeading || 'Konfirmasi',
                        description: form.dataset.confirm,
                        confirmText: form.dataset.confirmText || 'Ya, Lanjutkan',
                        onConfirm: () => {
                            form.dataset.confirmed = '1';
                            if (submitter && submitter.isConnected) {
                                form.requestSubmit(submitter);
                            } else {
                                form.requestSubmit();
                            }
                        },
                    },
                }),
            );

            return;
        }

        // Request duplikat (klik/Enter berulang) diabaikan selama request pertama berjalan.
        if (busy) {
            event.preventDefault();
            return;
        }

        busy = true;
        startProgress();

        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
            if (button === event.submitter) return;

            if (!button.disabled) {
                button.disabled = true;
                disabledByUs.push(button);
            }
        });

        if (event.submitter instanceof HTMLButtonElement) {
            markLoading(event.submitter);
        }

        // Pastikan state dilepas bila handler lain membatalkan submit.
        setTimeout(() => {
            if (event.defaultPrevented) resetLoading();
        }, 0);
    },
    true,
);

document.addEventListener(
    'click',
    (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const link = event.target instanceof Element ? event.target.closest('a[href]') : null;

        if (!link || link.target === '_blank' || link.hasAttribute('download')) return;

        let url;

        try {
            url = new URL(link.href, location.href);
        } catch {
            return;
        }

        if (url.protocol !== 'http:' && url.protocol !== 'https:') return;
        if (url.origin !== location.origin) return;
        if (link.href.split('#')[0] === location.href.split('#')[0]) return;

        startProgress();
    },
    true,
);

window.addEventListener('pageshow', () => resetLoading());
window.addEventListener('pagehide', () => stopProgress());
window.addEventListener('ux:loading-start', () => {
    busy = true;
    startProgress();
});
