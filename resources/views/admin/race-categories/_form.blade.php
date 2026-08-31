<form action="{{ isset($category) ? route('admin.race-categories.update', $category) : route('admin.race-categories.store') }}" method="POST"
    class="mx-auto max-w-2xl border border-slate-200 bg-white p-6 sm:p-8">
    @csrf
    @if (isset($category))
        @method('PUT')
    @endif

    <div class="space-y-5">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form-field name="event_id" label="Event" type="select" required :options="$events->pluck('name', 'id')->all()" :value="$category->event_id ?? null">
                <option value="">-- Pilih event --</option>
            </x-form-field>
            <x-form-field name="status" label="Status" type="select" required :options="['active' => 'Aktif', 'inactive' => 'Nonaktif']" :value="$category->status ?? null" />
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form-field name="name" label="Nama Kategori" required :value="$category->name ?? null" placeholder="Contoh: 10K Run" />
            <x-form-field name="code" label="Kode (dipakai untuk BIB Number)" required :value="$category->code ?? null" placeholder="Contoh: 10K" hint="BIB akan berbentuk {KODE}-0001." />
        </div>
        <div class="grid gap-5 sm:grid-cols-3">
            <x-form-field name="distance" label="Jarak (km)" type="number" step="0.01" min="0.1" required :value="$category->distance ?? null" />
            <x-form-field name="price" label="Harga (Rp)" type="number" min="0" required :value="$category->price ?? null" />
            <x-form-field name="quota" label="Kuota Peserta" type="number" min="1" required :value="$category->quota ?? null" />
        </div>
    </div>

    <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.race-categories.index') }}" class="btn-outline w-full">Batal</a>
        <button type="submit" class="rounded-sm bg-primary-600 px-6 py-2.5 text-sm font-bold text-white  hover:bg-primary-700">
            {{ isset($category) ? 'Simpan Perubahan' : 'Buat Kategori' }}
        </button>
    </div>
</form>


