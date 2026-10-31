@extends('layouts.admin')

@section('title', 'Daftar Peserta')
@section('page-title', 'Manajemen Peserta')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900">Daftar Peserta</h1>
            <p class="mt-1 text-sm text-slate-500">Cari, filter, dan verifikasi pembayaran peserta.</p>
        </div>
    </div>

    <!-- Filter & search -->
    <form method="GET" action="{{ route('admin.participants.index') }}" class="mt-6 border border-slate-200 bg-white p-4 sm:p-5">
        <div class="grid gap-3 sm:grid-cols-[1fr_auto_auto_auto]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / email / no. HP / kode registrasi..."
                aria-label="Cari peserta" class="field-control">
            <select name="category" aria-label="Filter kategori lomba" class="field-control">
                <option value="">Semua Kategori</option>
                @foreach ($filterCategories as $category)
                    <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }} ({{ $category->code }})</option>
                @endforeach
            </select>
            <select name="status" aria-label="Filter status pendaftaran" class="field-control">
                <option value="">Semua Status</option>
                @foreach (\App\Enums\RegistrationStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-primary">
                Filter
            </button>
        </div>
    </form>

    <!-- Kartu peserta (mobile) -->
    <div class="mt-6 space-y-3 md:hidden">
        @forelse ($participants as $participant)
            <article class="border border-slate-200 bg-white p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-mono text-xs font-bold tracking-wide text-slate-600">{{ $participant->registration_code }}</p>
                        <p class="mt-1 truncate font-semibold text-slate-900">{{ $participant->full_name }}</p>
                    </div>
                    <x-status-badge :status="$participant->registration_status" />
                </div>

                <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <dt class="text-slate-500">Kategori</dt>
                        <dd class="mt-0.5 font-semibold text-slate-800">{{ $participant->raceCategory?->code ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">BIB</dt>
                        <dd class="mt-0.5 font-mono font-semibold text-slate-800">{{ $participant->bib_number ?? '—' }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-slate-500">Daftar</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $participant->created_at->translatedFormat('d M Y, H:i') }}</dd>
                    </div>
                </dl>

                <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                    <a href="{{ route('admin.participants.show', $participant) }}"
                        aria-label="Lihat detail peserta {{ $participant->registration_code }}"
                        class="inline-flex min-h-9 items-center rounded-sm bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                        Detail
                    </a>
                    @if ($participant->payment?->payment_proof)
                        <button type="button"
                            data-proof="{{ route('admin.participants.payment-proof', $participant).'?inline=1' }}"
                            data-title="Bukti Pembayaran — {{ $participant->registration_code }} ({{ $participant->full_name }})"
                            aria-label="Lihat bukti pembayaran {{ $participant->registration_code }}"
                            @click="$dispatch('open-proof', { url: $el.dataset.proof, title: $el.dataset.title })"
                            class="inline-flex min-h-9 items-center rounded-sm bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-100">
                            Bukti
                        </button>
                    @endif
                    @if ($participant->registration_status === \App\Enums\RegistrationStatus::Pending)
                        <button type="button"
                            @click="$dispatch('open-confirm-modal', {
                                url: '{{ route('admin.participants.approve', $participant) }}',
                                heading: 'Approve Payment',
                                description: 'Setujui pembayaran untuk {{ $participant->registration_code }} — {{ $participant->full_name }}? BIB Number akan diterbitkan otomatis dan email notifikasi dikirim ke peserta.',
                                confirmText: 'Ya, Setujui Pembayaran'
                            })"
                            aria-label="Setujui pembayaran {{ $participant->registration_code }}"
                            class="inline-flex min-h-9 items-center rounded-sm bg-green-600 px-3 py-2 text-xs font-semibold text-white hover:bg-green-700">
                            Approve
                        </button>
                        <button type="button"
                            @click="$dispatch('open-confirm-modal', {
                                url: '{{ route('admin.participants.reject', $participant) }}',
                                heading: 'Reject Payment',
                                description: 'Tolak pembayaran untuk {{ $participant->registration_code }}? Alasan penolakan akan ditampilkan kepada peserta.',
                                confirmText: 'Tolak Pembayaran',
                                danger: true,
                                requireReason: true
                            })"
                            aria-label="Tolak pembayaran {{ $participant->registration_code }}"
                            class="inline-flex min-h-9 items-center rounded-sm bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">
                            Reject
                        </button>
                    @endif
                </div>
            </article>
        @empty
            <div class="border border-dashed border-slate-300 px-6 py-12 text-center">
                <p class="font-semibold text-slate-600">Tidak ada peserta ditemukan</p>
                <p class="mt-1 text-sm text-slate-600">Coba ubah kata kunci pencarian atau filter.</p>
            </div>
        @endforelse
    </div>

    <!-- Tabel -->
    <div class="mt-6 overflow-hidden border border-slate-200 bg-white">
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Kode Registrasi</th>
                        <th class="px-5 py-3.5">Nama</th>
                        <th class="px-5 py-3.5 hidden md:table-cell">No. HP</th>
                        <th class="px-5 py-3.5 hidden lg:table-cell">Email</th>
                        <th class="px-5 py-3.5">Kategori</th>
                        <th class="px-5 py-3.5 hidden sm:table-cell">Tanggal Daftar</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">BIB</th>
                        <th class="px-5 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($participants as $participant)
                        <tr class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs font-bold text-slate-700">{{ $participant->registration_code }}</td>
                            <td class="px-5 py-3.5 font-semibold text-slate-800">{{ $participant->full_name }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 tabular-nums text-slate-600 hidden md:table-cell">{{ $participant->phone }}</td>
                            <td class="px-5 py-3.5 text-slate-600 max-w-[220px] truncate hidden lg:table-cell" title="{{ $participant->email }}">{{ $participant->email }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5"><span class="inline-flex items-center rounded-md bg-primary-50 px-2 py-0.5 text-xs font-bold text-primary-700">{{ $participant->raceCategory?->code }}</span></td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-slate-500 hidden sm:table-cell">{{ $participant->created_at->translatedFormat('d M Y') }}</td>
                            <td class="px-5 py-3.5"><x-status-badge :status="$participant->registration_status" /></td>
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs font-bold text-slate-700">{{ $participant->bib_number ?? '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <a href="{{ route('admin.participants.show', $participant) }}"
                                    aria-label="Lihat detail peserta {{ $participant->registration_code }}"
                                    class="inline-flex items-center gap-1 rounded-sm bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                    Detail
                                </a>
                                @if ($participant->payment?->payment_proof)
                                    <button type="button"
                                        data-proof="{{ route('admin.participants.payment-proof', $participant).'?inline=1' }}"
                                        data-title="Bukti Pembayaran — {{ $participant->registration_code }} ({{ $participant->full_name }})"
                                        aria-label="Lihat bukti pembayaran {{ $participant->registration_code }}"
                                        @click="$dispatch('open-proof', { url: $el.dataset.proof, title: $el.dataset.title })"
                                        class="ml-1 inline-flex items-center gap-1 rounded-sm bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-100">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        Lihat Bukti
                                    </button>
                                @endif
                                @if ($participant->registration_status === \App\Enums\RegistrationStatus::Pending)
                                    <button type="button"
                                        @click="$dispatch('open-confirm-modal', {
                                            url: '{{ route('admin.participants.approve', $participant) }}',
                                            heading: 'Approve Payment',
                                            description: 'Setujui pembayaran untuk {{ $participant->registration_code }} — {{ $participant->full_name }}? BIB Number akan diterbitkan otomatis dan email notifikasi dikirim ke peserta.',
                                            confirmText: 'Ya, Setujui Pembayaran'
                                        })"
                                        aria-label="Setujui pembayaran {{ $participant->registration_code }}"
                                        class="ml-1 inline-flex items-center gap-1 rounded-sm bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700">
                                        Approve
                                    </button>
                                    <button type="button"
                                        @click="$dispatch('open-confirm-modal', {
                                            url: '{{ route('admin.participants.reject', $participant) }}',
                                            heading: 'Reject Payment',
                                            description: 'Tolak pembayaran untuk {{ $participant->registration_code }}? Alasan penolakan akan ditampilkan kepada peserta.',
                                            confirmText: 'Tolak Pembayaran',
                                            danger: true,
                                            requireReason: true
                                        })"
                                        aria-label="Tolak pembayaran {{ $participant->registration_code }}"
                                        class="ml-1 inline-flex items-center gap-1 rounded-sm bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">
                                        Reject
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center">
                                <p class="font-semibold text-slate-600">Tidak ada peserta ditemukan</p>
                                <p class="mt-1 text-sm text-slate-600">Coba ubah kata kunci pencarian atau filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($participants->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $participants->links() }}
            </div>
        @endif
    </div>

    <x-confirm-modal />
    <x-proof-lightbox />
@endsection


