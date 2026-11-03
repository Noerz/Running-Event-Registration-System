@php
    $hasParticipants = isset($category) && $category->participants()->exists();
    $selectedEventId = $category->event_id ?? $selectedEventId ?? request('event_id');
    $usedQuota = isset($category) ? $category->usedQuota() : 0;
    $minQuota = max(1, $usedQuota);
@endphp

<form action="{{ isset($category) ? route('admin.race-categories.update', $category) : route('admin.race-categories.store') }}" method="POST"
    class="mx-auto max-w-2xl border border-slate-200 bg-white p-6 sm:p-8">
    @csrf
    @if (isset($category))
        @method('PUT')
    @endif

    <div class="space-y-5">
        <div class="grid gap-5 sm:grid-cols-2">
            @if ($hasParticipants)
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Event <span class="text-red-500">*</span>
                    </label>
                    <input type="hidden" name="event_id" value="{{ $category->event_id }}">
                    <input type="text" class="field-control cursor-not-allowed bg-slate-100 text-slate-600" value="{{ $category->event?->name }}" disabled>
                    <p class="mt-1.5 text-xs text-slate-500">Event dikunci karena kategori sudah memiliki peserta terdaftar.</p>
                </div>
            @else
                <x-form-field name="event_id" label="Event" type="select" required :options="$events->pluck('name', 'id')->all()" :value="$selectedEventId">
                    <option value="">-- Pilih event --</option>
                </x-form-field>
            @endif
            <x-form-field name="status" label="Status" type="select" required :options="['active' => 'Aktif', 'inactive' => 'Nonaktif']" :value="$category->status ?? null" />
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form-field name="name" label="Nama Kategori" required :value="$category->name ?? null" placeholder="Contoh: 10K Run" />
            <x-form-field name="code" label="Kode Kategori" required :value="$category->code ?? null" placeholder="Contoh: 10K" hint="Dipakai sebagai placeholder {code} pada format BIB." />
        </div>
        <div class="grid gap-5 sm:grid-cols-3">
            <x-form-field name="distance" label="Jarak (km)" type="number" step="0.01" min="0.1" required :value="$category->distance ?? null" />
            <x-form-field name="price" label="Harga (Rp)" type="number" min="0" required :value="$category->price ?? null" />
            <x-form-field name="quota" label="Kuota Peserta" type="number" min="{{ $minQuota }}" required :value="$category->quota ?? null"
                :hint="$usedQuota > 0 ? 'Minimal '.$minQuota.' (terisi '.$usedQuota.' peserta)' : null" />
        </div>

        <div class="border-t border-slate-200 pt-5">
            <h3 class="font-display text-sm font-bold uppercase tracking-wide text-slate-800">Format Nomor BIB</h3>
            <p class="mt-1 text-xs text-slate-500">
                Placeholder: <code class="rounded bg-slate-100 px-1 font-mono">{code}</code> (kode kategori),
                <code class="rounded bg-slate-100 px-1 font-mono">{number}</code> (nomor urut, wajib),
                <code class="rounded bg-slate-100 px-1 font-mono">{year}</code> (2 digit tahun event).
            </p>
            <div class="mt-3 grid gap-5 sm:grid-cols-2">
                <x-form-field name="bib_format" label="Pola Format BIB" required :value="$category->bib_format ?? '{code}-{number}'"
                    placeholder="{code}-{number}" hint="Contoh: {code}-{number} atau {year}{code}{number}." />
                <x-form-field name="bib_digits" label="Jumlah Digit Nomor" type="number" min="3" max="8" required
                    :value="$category->bib_digits ?? 4" hint="Padding nol, misal 4 = 0001." />
            </div>
            @if (isset($category))
                <p class="mt-3 text-xs text-slate-600">
                    Nomor berikutnya untuk kategori ini:
                    <span class="font-mono font-bold text-slate-900">{{ $category->formatBibNumber($category->bib_counter + 1) }}</span>
                </p>
            @endif
        </div>
    </div>

    <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.race-categories.index') }}" class="btn-outline w-full">Batal</a>
        <button type="submit" class="rounded-sm bg-primary-700 px-6 py-2.5 text-sm font-bold text-white hover:bg-primary-800">
            {{ isset($category) ? 'Simpan Perubahan' : 'Buat Kategori' }}
        </button>
    </div>
</form>


