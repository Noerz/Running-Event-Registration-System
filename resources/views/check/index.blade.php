@extends('layouts.public')

@section('title', 'Cek Status Pendaftaran')

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-12 sm:py-16">
        <div>
            <p class="eyebrow text-primary-600">Pencarian</p>
            <h1 class="mt-2 font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900 sm:text-3xl">Cek Status Pendaftaran</h1>
            <p class="mt-3 max-w-md text-sm leading-relaxed text-slate-500">Masukkan email atau nomor HP yang Anda gunakan saat mendaftar untuk melihat status pendaftaran dan nomor peserta (BIB).</p>
        </div>

        <form method="POST" action="{{ route('check.check') }}" class="mt-8 border border-slate-200 bg-white p-6 sm:p-8">
            @csrf
            <div class="flex flex-col gap-4 sm:flex-row">
                <div class="flex-1">
                    <label for="identifier" class="sr-only">Email atau Nomor HP</label>
                    <input id="identifier" name="identifier" type="text" value="{{ old('identifier', $identifier ?? '') }}" required
                        placeholder="Email atau nomor HP, contoh: 081234567890"
                        class="field-control !py-2.5">
                    @error('identifier')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-primary">
                    Cari
                </button>
            </div>
        </form>

        @isset($searched)
            @if ($participants->isEmpty())
                <!-- Empty state -->
                <div class="mt-8 border-y border-dashed border-slate-300 py-16 text-center">
                    <p class="font-semibold text-slate-700">Pendaftaran tidak ditemukan</p>
                    <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">Tidak ada pendaftaran dengan email/nomor HP tersebut. Pastikan data sesuai dengan yang digunakan saat mendaftar.</p>
                </div>
            @else
                <div class="mt-8 space-y-6">
                    @foreach ($participants as $participant)
                        <article class="overflow-hidden border border-slate-200 bg-white">
                            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-6 py-4">
                                <div>
                                    <p class="font-mono text-sm font-bold tracking-wide text-slate-900">{{ $participant->registration_code }}</p>
                                    <p class="text-xs text-slate-500">Daftar: {{ $participant->created_at->translatedFormat('d M Y, H:i') }}</p>
                                </div>
                                <x-status-badge :status="$participant->registration_status" />
                            </header>

                            <dl class="grid grid-cols-1 gap-x-8 gap-y-4 px-6 py-5 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Nama Peserta</dt>
                                    <dd class="mt-1 font-semibold text-slate-800">{{ $participant->full_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Kategori Lomba</dt>
                                    <dd class="mt-1 font-semibold text-slate-800">{{ $participant->raceCategory->name }} ({{ $participant->raceCategory->formattedDistance() }})</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Status Pembayaran</dt>
                                    <dd class="mt-1"><x-status-badge :status="$participant->payment->payment_status" /></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Nomor Peserta / BIB</dt>
                                    <dd class="mt-1">
                                        @if ($participant->bib_number)
                                            <span class="inline-flex items-center bg-slate-900 px-3 py-1 font-mono text-sm font-bold tracking-widest text-white">{{ $participant->bib_number }}</span>
                                        @else
                                            <span class="text-slate-400">Belum tersedia</span>
                                        @endif
                                    </dd>
                                </div>

                                @if ($participant->registration_status === \App\Enums\RegistrationStatus::Rejected && $participant->payment?->rejection_reason)
                                    <div class="sm:col-span-2 rounded-sm border border-red-200 bg-red-50 p-4">
                                        <p class="text-sm font-semibold text-red-800">Pembayaran ditolak</p>
                                        <p class="mt-1 text-sm text-red-700">{{ $participant->payment->rejection_reason }}</p>
                                        <p class="mt-2 text-xs text-red-600/80">Pendaftaran ini sudah final. Untuk mengikuti event, lakukan pendaftaran baru setelah memperbaiki pembayaran.</p>
                                    </div>
                                @endif

                                @if ($participant->registration_status === \App\Enums\RegistrationStatus::Pending)
                                    <div class="sm:col-span-2 rounded-sm border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                                        Pembayaran berhasil dikonfirmasi setelah diverifikasi panitia. BIB Number akan muncul di halaman ini dan dikirim ke email Anda.
                                    </div>
                                @endif
                            </dl>
                        </article>
                    @endforeach
                </div>
            @endif
        @endisset
    </div>
@endsection

