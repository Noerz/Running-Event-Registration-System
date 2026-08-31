/**
 * Validasi live sisi klien untuk form pendaftaran.
 * Format rules mengikuti gaya Laravel: "required|phone", "digits_between:10,16", dst.
 */

function normalizePhone(value) {
    let digits = String(value).replace(/[^0-9]/g, '');

    if (digits.startsWith('620')) {
        digits = '62' + digits.slice(3);
    } else if (!digits.startsWith('62')) {
        if (digits.startsWith('0')) digits = '62' + digits.slice(1);
        else if (digits.startsWith('8')) digits = '62' + digits;
    }

    return digits;
}

const validators = {
    required: (value) =>
        String(value ?? '').trim() !== '' || 'Kolom ini wajib diisi.',

    phone: (value) =>
        /^62[2-9]\d{7,13}$/.test(normalizePhone(value)) ||
        'Format nomor HP tidak valid (contoh: 081234567890).',

    email: (value) =>
        /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(value).trim()) ||
        'Format email tidak valid.',

    /** NIK opsional: hanya divalidasi jika diisi. */
    nik: (value) =>
        value === '' || /^\d{10,16}$/.test(value) ||
        'NIK harus terdiri dari 10 sampai 16 digit angka.',

    numeric: (value) =>
        value === '' || !isNaN(Number(value)) || 'Harus berupa angka.',

    minnum: (value, min) =>
        value === '' || Number(value) >= Number(min) || `Minimal ${min}.`,

    maxchar: (value, max) =>
        String(value).length <= Number(max) || `Maksimal ${max} karakter.`,

    notfuture: (value) =>
        value === '' || new Date(value) <= new Date() ||
        'Tanggal tidak boleh melebihi hari ini.',

    /** Untuk input file: daftar ekstensi yang diizinkan. */
    mimes: (file, extensions) => {
        if (!(file instanceof File)) return true;
        const ext = file.name.split('.').pop().toLowerCase();
        return extensions.split(',').map((e) => e.trim()).includes(ext) ||
            'Format file harus: ' + extensions.split(',').join(', ').toUpperCase() + '.';
    },

    /** Untuk input file: ukuran maksimal dalam KB. */
    maxkb: (file, kb) => {
        if (!(file instanceof File)) return true;
        return file.size <= Number(kb) * 1024 ||
            `Ukuran file maksimal ${(kb / 1024).toString().replace('.', ',')} MB.`;
    },
};

export function validateField(input, rulesString) {
    if (!rulesString) return '';

    const isFile = input.type === 'file';
    const value = isFile
        ? (input.files.length > 0 ? input.files[0] : '')
        : input.value;

    for (const rawRule of rulesString.split('|')) {
        if (!rawRule) continue;

        const [name, args] = rawRule.split(':');
        const validator = validators[name];
        if (!validator) continue;

        // Rule opsional: lewati bila kosong kecuali "required".
        if (name !== 'required' && String(value) === '') continue;

        const result = validator(isFile ? value : String(value ?? ''), args, input);
        if (result !== true) return result;
    }

    return '';
}

export function registerFieldValidator(Alpine) {
    Alpine.data(
        'fieldValidator',
        (rulesString = '', initialError = '') => ({
            err: initialError,
            check(input) {
                this.err = validateField(input, rulesString);
                input.setCustomValidity?.(this.err || '');
                return !this.err;
            },
        }),
    );
}

export function registerFormDraft(Alpine) {
    Alpine.data('formDraft', (key) => ({
        init() {
            let saved = {};

            try {
                saved = JSON.parse(localStorage.getItem(key) || '{}');
            } catch {
                saved = {};
            }

            for (const [name, value] of Object.entries(saved)) {
                const input = this.$el.elements[name];

                if (! input || input.type === 'file' || input.value) continue;

                if (input.type === 'checkbox') {
                    input.checked = value === input.value;
                } else {
                    input.value = value;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            this.$el.addEventListener('input', () => this.save());
            this.$el.addEventListener('change', () => this.save());
        },
        save() {
            const data = {};

            for (const input of Array.from(this.$el.elements)) {
                if (! input.name || input.type === 'file' || input.type === 'hidden' || input.disabled) continue;

                data[input.name] = input.type === 'checkbox'
                    ? (input.checked ? input.value : '')
                    : input.value;
            }

            try {
                localStorage.setItem(key, JSON.stringify(data));
            } catch {
                // Abaikan bila Safari menolak localStorage.
            }
        },
    }));
}
