@extends('layouts.public')

@section('title', 'Pendaftaran Berhasil')

@section('content')
    <div class="mx-auto max-w-xl px-4 py-16 sm:py-24">
        <div class="border border-slate-200 bg-white p-8 sm:p-12">
            <span class="flex h-14 w-14 items-center justify-center border border-green-600 text-green-600">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>

            <h1 class="mt-6 font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900 sm:text-3xl">Pendaftaran Berhasil</h1>
            <p class="mt-3 leading-relaxed text-slate-500">Terima kasih, <strong class="text-slate-800">{{ $participant->full_name }}</strong>. Pendaftaran Anda berhasil dikirim.</p>

            <div class="mt-8 border-y-2 border-slate-900 py-6 text-center">
                <p class="eyebrow text-slate-400">Registration Code Anda</p>
                <p class="mt-2 select-all font-display text-3xl font-bold tracking-wider text-slate-900">{{ $participant->registration_code }}</p>
                <div class="mt-4">
                    <x-status-badge :status="$participant->registration_status" />
                </div>
            </div>

            <dl class="mt-8 space-y-3 text-left text-sm">
                <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-3">
                    <dt class="text-slate-500">Kategori</dt>
                    <dd class="font-semibold text-slate-900">{{ $participant->raceCategory->name }} ({{ $participant->raceCategory->formattedDistance() }})</dd>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-3">
                    <dt class="text-slate-500">Status Pembayaran</dt>
                    <dd><x-status-badge :status="$participant->payment->payment_status" /></dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                    <dt class="shrink-0 text-slate-500">Langkah Selanjutnya</dt>
                    <dd class="text-right leading-relaxed text-slate-600">Panitia akan memverifikasi bukti pembayaran Anda. Nomor peserta (BIB) dikirim ke email setelah disetujui.</dd>
                </div>
            </dl>

            <div class="mt-10 flex flex-col gap-3 sm:flex-row-reverse">
                <a href="{{ route('check.show') }}" class="btn-primary flex-1">Cek Status</a>
                <a href="{{ route('home') }}" class="btn-outline flex-1">Kembali ke Beranda</a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        localStorage.removeItem('registration-form');
    </script>
@endpush
