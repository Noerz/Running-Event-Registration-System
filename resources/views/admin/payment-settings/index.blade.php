@extends('layouts.admin')

@section('title', 'Rekening & QRIS')
@section('page-title', 'Rekening & QRIS')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900">Rekening &amp; QRIS</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola nomor rekening tujuan transfer dan gambar QRIS pembayaran.</p>
        </div>
    </div>

    {{-- QRIS --}}
    <div class="mt-6 border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-display text-base font-bold uppercase tracking-tight text-slate-900">Gambar QRIS</h2>
            <p class="mt-0.5 text-xs text-slate-500">Unggah gambar QRIS (PNG/JPG/WEBP, maks 2 MB).</p>
        </div>

        <div class="grid gap-6 p-5 sm:grid-cols-[200px_1fr]">
            <div class="flex items-center justify-center border border-slate-200 bg-slate-50 p-3">
                @if ($qrisUrl)
                    <img src="{{ $qrisUrl }}" alt="Pratinjau gambar QRIS" class="max-h-48 w-full object-contain">
                @else
                    <p class="py-8 text-center text-xs text-slate-500">Belum ada gambar QRIS.</p>
                @endif
            </div>

            <div class="space-y-4">
                <p class="text-xs text-slate-500">
                    Status:
                    @if ($qrisIsCustom)
                        <span class="font-semibold text-green-700">Gambar kustom (diunggah admin)</span>
                    @else
                        <span class="font-semibold text-slate-700">Gambar default dari konfigurasi</span>
                    @endif
                </p>

                <form action="{{ route('admin.payment-settings.qris.update') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label for="qris_image" class="mb-1.5 block text-sm font-medium text-slate-700">
                            {{ $qrisIsCustom ? 'Ganti Gambar QRIS' : 'Unggah Gambar QRIS' }}
                        </label>
                        <input type="file" name="qris_image" id="qris_image" accept="image/png,image/jpeg,image/webp" required
                            class="block w-full cursor-pointer rounded-sm border border-slate-300 bg-white text-sm text-slate-700 file:mr-3 file:cursor-pointer file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                        @error('qris_image')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn-primary" data-loading-text="Mengunggah…">Simpan Gambar</button>
                </form>

                @if ($qrisIsCustom)
                    <button type="button"
                        @click="$dispatch('open-confirm-modal', {
                            url: '{{ route('admin.payment-settings.qris.destroy') }}',
                            method: 'DELETE',
                            heading: 'Hapus Gambar QRIS',
                            description: 'Hapus gambar QRIS kustom dan kembali menggunakan gambar default?',
                            confirmText: 'Ya, Hapus Gambar',
                            loadingText: 'Menghapus…',
                            danger: true
                        })"
                        class="text-xs font-semibold text-red-600 hover:underline">
                        Hapus gambar kustom
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Bank Accounts --}}
    <div class="mt-6 border border-slate-200 bg-white" x-data="{
        open: false,
        editing: null,
        form: { id: null, bank: '', number: '', holder: '', order: '', is_active: true },
        add() {
            this.editing = null;
            this.form = { id: null, bank: '', number: '', holder: '', order: '', is_active: true };
            this.open = true;
        },
        edit(account) {
            this.editing = account.id;
            this.form = { ...account, is_active: !!account.is_active };
            this.open = true;
        },
        action() {
            return this.editing
                ? '{{ route('admin.payment-settings.bank.update', '__ID__') }}'.replace('__ID__', this.editing)
                : '{{ route('admin.payment-settings.bank.store') }}';
        }
    }" @keydown.escape.window="open = false">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h2 class="font-display text-base font-bold uppercase tracking-tight text-slate-900">Nomor Rekening</h2>
                <p class="mt-0.5 text-xs text-slate-500">Rekening aktif ditampilkan pada modal pendaftaran.</p>
            </div>
            <button type="button" @click="add()" class="btn-primary">+ Tambah Rekening</button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Bank</th>
                        <th class="px-5 py-3.5">Nomor</th>
                        <th class="px-5 py-3.5">Atas Nama</th>
                        <th class="px-5 py-3.5">Urutan</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($accounts as $account)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-3.5 font-semibold text-slate-800">{{ $account->bank }}</td>
                            <td class="px-5 py-3.5 font-mono tabular-nums">{{ $account->number }}</td>
                            <td class="px-5 py-3.5">{{ $account->holder }}</td>
                            <td class="px-5 py-3.5 tabular-nums">{{ $account->order }}</td>
                            <td class="px-5 py-3.5">
                                @if ($account->is_active)
                                    <span class="inline-flex items-center rounded-sm bg-green-50 px-2 py-1 text-xs font-semibold text-green-700">Aktif</span>
                                @else
                                    <span class="inline-flex items-center rounded-sm bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">Nonaktif</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <button type="button"
                                    @click="edit({{ \Illuminate\Support\Js::from($account->only(['id', 'bank', 'number', 'holder', 'order', 'is_active'])) }})"
                                    class="inline-flex items-center gap-1 rounded-sm bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">Edit</button>
                                <button type="button"
                                    @click="$dispatch('open-confirm-modal', {
                                        url: '{{ route('admin.payment-settings.bank.destroy', $account) }}',
                                        method: 'DELETE',
                                        heading: 'Hapus Rekening',
                                        description: 'Hapus rekening {{ addslashes($account->bank) }} — {{ addslashes($account->number) }}? Rekening yang dihapus tidak dapat dikembalikan.',
                                        confirmText: 'Ya, Hapus Rekening',
                                        loadingText: 'Menghapus…',
                                        danger: true
                                    })"
                                    class="ml-1 inline-flex items-center gap-1 rounded-sm bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center text-sm text-slate-500">
                                Belum ada rekening. Klik "Tambah Rekening" untuk menambahkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Modal tambah/edit --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/50" @click="open = false"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="w-full max-w-md rounded-sm border border-slate-200 bg-white p-6 shadow-xl">
                    <h3 class="font-display text-lg font-bold uppercase tracking-tight text-slate-900"
                        x-text="editing ? 'Edit Rekening' : 'Tambah Rekening'"></h3>

                    <form :action="action()" method="POST" class="mt-4 space-y-4">
                        @csrf
                        <template x-if="editing">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div>
                            <label for="bank" class="mb-1.5 block text-sm font-medium text-slate-700">Nama Bank <span class="text-red-500">*</span></label>
                            <input type="text" name="bank" id="bank" x-model="form.bank" required maxlength="50"
                                placeholder="BCA / Mandiri / BRI" class="field-control">
                        </div>

                        <div>
                            <label for="number" class="mb-1.5 block text-sm font-medium text-slate-700">Nomor Rekening <span class="text-red-500">*</span></label>
                            <input type="text" name="number" id="number" x-model="form.number" required maxlength="50"
                                inputmode="numeric" placeholder="1234567890" class="field-control">
                        </div>

                        <div>
                            <label for="holder" class="mb-1.5 block text-sm font-medium text-slate-700">Atas Nama <span class="text-red-500">*</span></label>
                            <input type="text" name="holder" id="holder" x-model="form.holder" required maxlength="255"
                                placeholder="Nama pemilik rekening" class="field-control">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="order" class="mb-1.5 block text-sm font-medium text-slate-700">Urutan</label>
                                <input type="number" name="order" id="order" x-model="form.order" min="0" max="999"
                                    class="field-control" placeholder="0">
                            </div>
                            <div class="flex items-end pb-2.5">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                                        class="h-4 w-4 rounded-sm border-slate-300 text-primary-700 focus:ring-primary-500">
                                    Aktif
                                </label>
                            </div>
                        </div>

                        <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                            <button type="button" @click="open = false" class="btn-outline">Batal</button>
                            <button type="submit" class="btn-primary" data-loading-text="Menyimpan…" x-text="editing ? 'Simpan Perubahan' : 'Tambah'"></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
