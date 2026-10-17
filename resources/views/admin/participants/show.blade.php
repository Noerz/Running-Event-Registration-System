@extends('layouts.admin')

@section('title', 'Detail Peserta — '.$participant->full_name)
@section('page-title', 'Detail Peserta')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.participants.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                Kembali ke daftar peserta
            </a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900">{{ $participant->full_name }}</h1>
                <x-status-badge :status="$participant->registration_status" />
            </div>
            <p class="mt-1 font-mono text-sm font-bold text-slate-500">{{ $participant->registration_code }}</p>
        </div>

        <div class="flex flex-col items-start gap-2 sm:items-end">
            <a href="{{ route('admin.participants.edit', $participant) }}"
                class="inline-flex items-center gap-2 rounded-sm bg-slate-800 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                Edit Data Peserta
            </a>

        @if ($participant->payment->payment_status === \App\Enums\PaymentStatus::Pending)
            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="button"
                    @click="$dispatch('open-confirm-modal', {
                        url: '{{ route('admin.participants.approve', $participant) }}',
                        heading: 'Approve Payment',
                        description: 'Setujui pembayaran untuk {{ $participant->registration_code }}? BIB Number akan diterbitkan otomatis dan email notifikasi dikirim ke peserta.',
                        confirmText: 'Ya, Setujui Pembayaran'
                    })"
                    class="inline-flex items-center justify-center gap-2 rounded-sm bg-green-600 px-5 py-2.5 text-sm font-bold text-white  hover:bg-green-700">
                    Approve Payment
                </button>
                <button type="button"
                    @click="$dispatch('open-confirm-modal', {
                        url: '{{ route('admin.participants.reject', $participant) }}',
                        heading: 'Reject Payment',
                        description: 'Tolak pembayaran untuk {{ $participant->registration_code }}? Alasan penolakan akan ditampilkan kepada peserta pada halaman cek status.',
                        confirmText: 'Tolak Pembayaran',
                        danger: true,
                        requireReason: true
                    })"
                    class="inline-flex items-center justify-center gap-2 rounded-sm bg-red-600 px-5 py-2.5 text-sm font-bold text-white  hover:bg-red-700">
                    Reject Payment
                </button>
            </div>
        @elseif ($participant->payment->payment_status === \App\Enums\PaymentStatus::Rejected)
            <details class="rounded-sm border border-red-200 bg-red-50/50 px-4 py-3">
                <summary class="cursor-pointer text-sm font-semibold text-red-800">Peserta kirim ulang bukti?</summary>
                <form method="POST" action="{{ route('admin.participants.resubmit-proof', $participant) }}" enctype="multipart/form-data" class="mt-3 space-y-2">
                    @csrf
                    <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required
                        class="block w-full text-xs text-slate-600 file:mr-3 file:cursor-pointer file:border file:border-solid file:border-slate-300 file:bg-white file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700">
                    @error('payment_proof')
                        <p class="text-xs font-medium text-red-700">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-sm bg-red-600 px-4 py-2 text-xs font-bold text-white hover:bg-red-700">
                        Simpan Bukti Baru (kembali ke Pending)
                    </button>
                </form>
            </details>
        @else
            <div class="rounded-sm bg-slate-100 px-4 py-2.5 text-sm text-slate-600">
                Diverifikasi oleh <strong>{{ $participant->payment->verifiedBy?->name ?? '-' }}</strong>
                @if ($participant->payment->verified_at)
                    • {{ $participant->payment->verified_at->translatedFormat('d M Y H:i') }}
                @endif
            </div>
        @endif
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <!-- Kolom kiri: info peserta -->
        <div class="space-y-6 xl:col-span-2">
            <!-- Personal Information -->
            <section class="border border-slate-200 bg-white">
                <header class="border-b border-slate-100 px-6 py-4">
                    <h2 class="font-bold text-slate-900">Personal Information</h2>
                </header>
                <dl class="grid grid-cols-1 gap-x-8 gap-y-5 px-6 py-6 sm:grid-cols-2">
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Nama</dt><dd class="mt-1 font-semibold text-slate-800">{{ $participant->full_name }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Email</dt><dd class="mt-1 font-semibold text-slate-800">{{ $participant->email }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Nomor HP</dt><dd class="mt-1 tabular-nums font-semibold text-slate-800">{{ $participant->phone }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Jenis Kelamin</dt><dd class="mt-1 font-semibold text-slate-800">{{ $participant->gender->label() }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Tempat, Tanggal Lahir</dt><dd class="mt-1 font-semibold text-slate-800">{{ $participant->birth_place }}, {{ $participant->birth_date->translatedFormat('d F Y') }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Usia</dt><dd class="mt-1 font-semibold text-slate-800">{{ $participant->age }} tahun</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">No. Identitas/NIK</dt><dd class="mt-1 tabular-nums font-semibold text-slate-800">{{ $participant->identity_number ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Alamat</dt><dd class="mt-1 leading-relaxed text-slate-700">{{ $participant->address }}, {{ $participant->city }}, {{ $participant->province }}</dd></div>
                </dl>
            </section>

            <!-- Race Information -->
            <section class="border border-slate-200 bg-white">
                <header class="border-b border-slate-100 px-6 py-4">
                    <h2 class="font-bold text-slate-900">Race Information</h2>
                </header>
                <dl class="grid grid-cols-1 gap-x-8 gap-y-5 px-6 py-6 sm:grid-cols-2">
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Event</dt><dd class="mt-1 font-semibold text-slate-800">{{ $participant->event->name }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Kategori</dt><dd class="mt-1 font-semibold text-slate-800">{{ $participant->raceCategory->name }} ({{ $participant->raceCategory->formattedDistance() }})</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Ukuran Jersey</dt><dd class="mt-1 font-semibold text-slate-800">{{ $participant->jersey_size }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Running Club</dt><dd class="mt-1 font-semibold text-slate-800">{{ $participant->running_club ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Emergency Contact</dt><dd class="mt-1 font-semibold text-slate-800">{{ $participant->emergency_contact_name }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">No. HP Emergency</dt><dd class="mt-1 tabular-nums font-semibold text-slate-800">{{ $participant->emergency_contact_phone }}</dd></div>
                </dl>
            </section>
        </div>

        <!-- Kolom kanan: payment -->
        <div class="space-y-6">
            <section class="border border-slate-200 bg-white">
                <header class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="font-bold text-slate-900">Payment Information</h2>
                    <x-status-badge :status="$participant->payment->payment_status" />
                </header>
                <dl class="space-y-5 px-6 py-6">
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Metode Pembayaran</dt><dd class="mt-1 font-semibold text-slate-800">{{ config('registration.payment_methods.'.$participant->payment->payment_method, $participant->payment->payment_method) }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Nominal</dt><dd class="mt-1 font-display text-lg font-bold text-slate-900">Rp {{ number_format($participant->payment->amount, 0, ',', '.') }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-600">BIB Number</dt><dd class="mt-1">@if ($participant->bib_number)<span class="inline-flex items-center rounded-sm bg-night-900 px-3 py-1 font-mono text-sm font-bold tracking-widest text-primary-400">{{ $participant->bib_number }}</span>@else<span class="text-slate-600">Belum diterbitkan</span>@endif</dd></div>

                    @if ($participant->payment->rejection_reason)
                        <div class="rounded-sm border border-red-200 bg-red-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-red-500">Alasan Penolakan</p>
                            <p class="mt-1 text-sm leading-relaxed text-red-700">{{ $participant->payment->rejection_reason }}</p>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-600">Bukti Pembayaran</dt>
                        <dd class="mt-2 overflow-hidden rounded-sm border border-slate-200 bg-slate-50 p-3 text-center">
                            @php($proofPath = $participant->payment->payment_proof)
                            @php($proofExists = $proofPath && \Storage::exists($proofPath))

                            @if ($proofExists)
                                <button type="button"
                                    data-proof="{{ route('admin.participants.payment-proof', $participant).'?inline=1' }}"
                                    data-title="Bukti Pembayaran — {{ $participant->registration_code }}"
                                    @click="$dispatch('open-proof', { url: $el.dataset.proof, title: $el.dataset.title })"
                                    class="group block w-full rounded-sm bg-white p-3 ring-1 ring-slate-200 transition hover:ring-primary-300 focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    <img src="{{ route('admin.participants.payment-proof', $participant).'?inline=1' }}" alt="Bukti pembayaran" class="mx-auto max-h-72 rounded-sm bg-white object-contain transition group-hover:opacity-80">
                                    <span class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-primary-700 group-hover:text-primary-700">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        Klik untuk preview besar
                                    </span>
                                </button>

                                <a href="{{ route('admin.participants.payment-proof', $participant) }}"
                                    class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-sm bg-slate-800 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    Download Bukti Pembayaran
                                </a>
                            @else
                                <p class="py-6 text-sm text-slate-600">File tidak ditemukan.</p>
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>

    <x-confirm-modal />
    <x-proof-lightbox />
@endsection



