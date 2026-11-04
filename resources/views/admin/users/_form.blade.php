<form action="{{ isset($admin) ? route('admin.users.update', $admin) : route('admin.users.store') }}"
    method="POST"
    class="mx-auto max-w-2xl border border-slate-200 bg-white p-6 sm:p-8">
    @csrf
    @if (isset($admin))
        @method('PUT')
    @endif

    <div class="space-y-5">
        <x-form-field name="name" label="Nama" required :value="$admin->name ?? null" placeholder="Contoh: Budi Santoso" />

        <x-form-field name="email" label="Email" type="email" required :value="$admin->email ?? null" placeholder="admin@example.com" />

        <div>
            <label for="role_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                Role <span class="text-red-500">*</span>
            </label>
            <select name="role_id" id="role_id" required
                class="mt-1 block w-full rounded-sm border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600">
                <option value="">-- Pilih Role --</option>
                @foreach ($roles as $roleOption)
                    <option value="{{ $roleOption->id }}"
                        @selected((string) old('role_id', $currentRoleId ?? '') === (string) $roleOption->id)>
                        {{ $roleOption->name }} ({{ $roleOption->code }}) — {{ $roleOption->description }}
                    </option>
                @endforeach
            </select>
            @error('role_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
            @if (isset($admin) && $admin->is(auth()->user()))
                <p class="mt-1 text-xs text-amber-600">Anda tidak dapat mengubah role akun Anda sendiri.</p>
            @endif
        </div>

        <x-form-field name="password" label="Password" type="password" :required="! isset($admin)" :hint="isset($admin) ? 'Biarkan kosong jika tidak ingin mengubah password.' : 'Minimal 8 karakter.'" />

        <x-form-field name="password_confirmation" label="Konfirmasi Password" type="password" :required="! isset($admin) && old('password')" :rules="old('password') ? 'required|min:8' : null" />
    </div>

    <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.users.index') }}" class="btn-outline w-full sm:w-auto">Batal</a>
        <button type="submit" class="rounded-sm bg-primary-700 px-6 py-2.5 text-sm font-bold text-white hover:bg-primary-800" data-loading-text="Menyimpan…">
            {{ isset($admin) ? 'Simpan Perubahan' : 'Tambah Admin' }}
        </button>
    </div>
</form>
