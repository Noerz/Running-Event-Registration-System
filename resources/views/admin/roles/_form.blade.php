@php
    $isEdit = isset($role) && $role->exists;
    $isSystem = $isEdit && $role->is_system;
    $assigned = old('permissions', $assignedPermissionCodes ?? []);
    $allCodes = $groupedPermissions->flatten()->pluck('code')->all();
@endphp

<form action="{{ $isEdit ? route('admin.roles.update', $role) : route('admin.roles.store') }}" method="POST"
    class="mx-auto max-w-4xl space-y-6"
    x-data="{
        selected: @js(array_values($assigned)),
        modules: @js($groupedPermissions->map(fn ($items) => $items->pluck('code')->values())->toArray()),
        all: @js($allCodes),
        isChecked(code) { return this.selected.includes(code); },
        toggle(code) {
            const i = this.selected.indexOf(code);
            if (i > -1) this.selected.splice(i, 1); else this.selected.push(code);
        },
        moduleChecked(codes) { return codes.every(c => this.selected.includes(c)); },
        moduleIndeterminate(codes) { const n = codes.filter(c => this.selected.includes(c)).length; return n > 0 && n < codes.length; },
        toggleModule(codes) {
            if (this.moduleChecked(codes)) this.selected = this.selected.filter(c => !codes.includes(c));
            else this.selected = [...new Set([...this.selected, ...codes])];
        },
        allChecked() { return this.all.length > 0 && this.all.every(c => this.selected.includes(c)); },
        toggleAll() { this.selected = this.allChecked() ? [] : [...this.all]; }
    }">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    {{-- Inputs tersembunyi agar checkbox Alpine tetap terkirim sebagai array permissions[] --}}
    <template x-for="code in selected" :key="code">
        <input type="hidden" name="permissions[]" :value="code">
    </template>

    <div class="border border-slate-200 bg-white p-6 sm:p-8">
        <div class="space-y-5">
            <x-form-field name="name" label="Nama Role" required :value="$role->name ?? null" placeholder="Contoh: Operator Event" />

            <div>
                <label for="code" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                    Kode Role <span class="text-red-500">*</span>
                </label>
                <input type="text" name="code" id="code" required
                    value="{{ old('code', $role->code ?? '') }}"
                    placeholder="contoh: operator-event"
                    @readonly($isSystem)
                    class="mt-1 block w-full rounded-sm border border-slate-300 bg-white px-3 py-2 text-sm font-mono text-slate-900 focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600 @readonly($isSystem) @error('code') border-red-500 @enderror">
                @error('code')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-slate-500">Huruf kecil, angka, strip, atau underscore. Digunakan sebagai identitas unik role.</p>
            </div>

            <div>
                <label for="description" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Deskripsi</label>
                <textarea name="description" id="description" rows="2"
                    class="mt-1 block w-full rounded-sm border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600 @error('description') border-red-500 @enderror"
                    placeholder="Jelaskan fungsi role ini...">{{ old('description', $role->description ?? '') }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-start gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                    @checked(old('is_active', $role->is_active ?? true))
                    @disabled($isSystem)
                    class="mt-0.5 h-4 w-4 rounded-sm border-slate-300 text-primary-700 focus:ring-primary-600">
                <div>
                    <label for="is_active" class="text-sm font-semibold text-slate-800">Role aktif</label>
                    <p class="text-xs text-slate-500">Role nonaktif tidak memberi akses apa pun kepada admin yang memakainya.</p>
                    @if ($isSystem)
                        <p class="mt-1 text-xs text-amber-600">Role sistem selalu aktif dan tidak dapat dinonaktifkan.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Permissions --}}
    <div class="border border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h2 class="font-display text-base font-bold uppercase tracking-tight text-slate-900">Permission</h2>
                <p class="mt-0.5 text-xs text-slate-500">Centang aksi yang diizinkan untuk role ini.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold text-slate-500"><span x-text="selected.length"></span> dipilih</span>
                @unless ($isSystem)
                    <button type="button" @click="toggleAll()"
                        class="rounded-sm border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                        <span x-text="allChecked() ? 'Batal Pilih Semua' : 'Pilih Semua'"></span>
                    </button>
                @endunless
            </div>
        </div>

        @if ($isSystem)
            <p class="border-b border-slate-200 bg-amber-50 px-5 py-3 text-xs font-medium text-amber-800">
                Role sistem memiliki akses penuh ke seluruh permission dan tidak dapat diubah.
            </p>
        @endif

        <div class="divide-y divide-slate-100" @if ($isSystem) style="pointer-events:none;opacity:.6" @endif>
            @foreach ($groupedPermissions as $module => $items)
                @php $moduleCodes = $items->pluck('code')->values()->all(); @endphp
                <div class="px-5 py-4" x-data="{ open: true }">
                    <div class="flex items-center justify-between gap-3">
                        <button type="button" @click="open = ! open" class="flex items-center gap-2 text-left">
                            <span class="font-display text-sm font-bold uppercase tracking-tight text-slate-900">
                                {{ $moduleLabels[$module] ?? $module }}
                            </span>
                            <span class="text-xs text-slate-500">({{ count($moduleCodes) }})</span>
                        </button>
                        @unless ($isSystem)
                            <label class="flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-600">
                                <input type="checkbox" class="h-4 w-4 rounded-sm border-slate-300 text-primary-700 focus:ring-primary-600"
                                    :checked="moduleChecked(@js($moduleCodes))"
                                    :indeterminate="moduleIndeterminate(@js($moduleCodes))"
                                    @change="toggleModule(@js($moduleCodes))">
                                Pilih semua
                            </label>
                        @endunless
                    </div>

                    <div x-show="open" class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ($items as $permission)
                            <label class="flex cursor-pointer items-start gap-2 rounded-sm px-2 py-1.5 hover:bg-slate-50">
                                <input type="checkbox" class="mt-0.5 h-4 w-4 rounded-sm border-slate-300 text-primary-700 focus:ring-primary-600"
                                    value="{{ $permission->code }}"
                                    :checked="isChecked(@js($permission->code))"
                                    @change="toggle(@js($permission->code))"
                                    @disabled($isSystem)>
                                <span class="text-sm text-slate-700">
                                    {{ $permission->name }}
                                    <span class="block font-mono text-[11px] text-slate-400">{{ $permission->code }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        @error('permissions')
            <p class="px-5 pb-4 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.roles.index') }}" class="btn-outline w-full sm:w-auto">Batal</a>
        <button type="submit" class="rounded-sm bg-primary-700 px-6 py-2.5 text-sm font-bold text-white hover:bg-primary-800" data-loading-text="Menyimpan…">
            {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Role' }}
        </button>
    </div>
</form>
