<form action="{{ isset($admin) ? route('admin.users.update', $admin) : route('admin.users.store') }}" method="POST"
    class="mx-auto max-w-2xl border border-slate-200 bg-white p-6 sm:p-8">
    @csrf
    @if (isset($admin))
        @method('PUT')
    @endif

    <div class="space-y-5">
        <x-form-field name="name" label="Nama" required :value="$admin->name ?? null" placeholder="Contoh: Budi Santoso" />
        <x-form-field name="email" label="Email" type="email" required :value="$admin->email ?? null" placeholder="admin@example.com" />
        <x-form-field
            name="password"
            label="Password"
            type="password"
            :required="! isset($admin)"
            :hint="isset($admin) ? 'Biarkan kosong jika tidak ingin mengubah password.' : 'Minimal 8 karakter.'"
        />
        <x-form-field
            name="password_confirmation"
            label="Konfirmasi Password"
            type="password"
            :required="! isset($admin) && old('password')"
            :rules="old('password') ? 'required|min:8' : null"
        />
    </div>

    <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.users.index') }}" class="btn-outline w-full">Batal</a>
        <button type="submit" class="rounded-sm bg-primary-700 px-6 py-2.5 text-sm font-bold text-white hover:bg-primary-800">
            {{ isset($admin) ? 'Simpan Perubahan' : 'Tambah Admin' }}
        </button>
    </div>
</form>


