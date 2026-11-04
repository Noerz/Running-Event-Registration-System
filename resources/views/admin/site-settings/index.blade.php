@extends('layouts.admin')

@section('title', 'Pengaturan Web')
@section('page-title', 'Pengaturan Web')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900">Pengaturan Web</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola gambar utama (hero banner) halaman beranda publik.</p>
        </div>
    </div>

    {{-- Gambar Hero Halaman Utama --}}
    <div class="mt-6 border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-display text-base font-bold uppercase tracking-tight text-slate-900">Gambar Halaman Utama (Hero)</h2>
            <p class="mt-0.5 text-xs text-slate-500">Unggah gambar banner utama untuk halaman beranda publik (PNG/JPG/WEBP, maks 4 MB, rasio ideal 4:5).</p>
        </div>

        <div class="grid gap-6 p-5 sm:grid-cols-[200px_1fr]">
            <div class="flex items-center justify-center border border-slate-200 bg-slate-50 p-3">
                <img src="{{ $heroUrl }}" alt="Pratinjau gambar halaman utama" class="aspect-[4/5] max-h-56 w-full object-cover">
            </div>

            <div class="space-y-4">
                <p class="text-xs text-slate-500">
                    Status:
                    @if ($heroIsCustom)
                        <span class="font-semibold text-green-700">Gambar kustom (diunggah admin)</span>
                    @else
                        <span class="font-semibold text-slate-700">Gambar default bawaan</span>
                    @endif
                </p>

                <form action="{{ route('admin.site-settings.hero-image.update') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label for="hero_image" class="mb-1.5 block text-sm font-medium text-slate-700">
                            {{ $heroIsCustom ? 'Ganti Gambar Halaman Utama' : 'Unggah Gambar Halaman Utama' }}
                        </label>
                        <input type="file" name="hero_image" id="hero_image" accept="image/png,image/jpeg,image/webp" required
                            class="block w-full cursor-pointer rounded-sm border border-slate-300 bg-white text-sm text-slate-700 file:mr-3 file:cursor-pointer file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                        @error('hero_image')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn-primary" data-loading-text="Mengunggah…">Simpan Gambar</button>
                </form>

                @if ($heroIsCustom)
                    <button type="button"
                        @click="$dispatch('open-confirm-modal', {
                            url: '{{ route('admin.site-settings.hero-image.destroy') }}',
                            method: 'DELETE',
                            heading: 'Hapus Gambar Hero',
                            description: 'Hapus gambar halaman utama kustom dan kembali ke gambar default?',
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
@endsection
