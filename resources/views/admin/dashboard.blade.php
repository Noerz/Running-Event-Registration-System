@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Admin')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Ringkasan statistik pendaftaran event lari.</p>
        </div>
        <a href="{{ route('admin.participants.index') }}" class="btn-primary">Kelola Peserta</a>
    </div>

    <!-- Statistik -->
    <dl class="mt-6 grid grid-cols-2 border border-slate-200 bg-white lg:grid-cols-5">
        @foreach ([
            ['label' => 'Total Peserta', 'value' => $stats['total']],
            ['label' => 'Daftar Hari Ini', 'value' => $stats['today']],
            ['label' => 'Pending', 'value' => $stats['pending']],
            ['label' => 'Approved', 'value' => $stats['approved']],
            ['label' => 'Ditolak', 'value' => $stats['rejected']],
        ] as $stat)
            <div class="border-b border-r border-slate-200 px-5 py-6 last:border-r-0 max-lg:[&:nth-child(2n)]:border-r-0 lg:border-b-0">
                <dd class="font-display text-3xl font-bold tabular-nums tracking-tight text-slate-900">{{ number_format($stat['value']) }}</dd>
                <dt class="eyebrow mt-1 text-slate-600">{{ $stat['label'] }}</dt>
            </div>
        @endforeach
    </dl>

    <!-- Chart + per kategori -->
    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="border border-slate-200 bg-white p-6">
            <h2 class="font-display text-sm font-bold uppercase tracking-tight text-slate-900">Peserta per Kategori</h2>
            @if ($chartData->isEmpty())
                <div class="mt-6 border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500">
                    Belum ada data kategori.
                </div>
            @else
                <div class="mt-6 space-y-4">
                    @foreach ($chartData as $bar)
                        <div>
                            <div class="mb-1.5 flex items-baseline justify-between gap-4 text-sm">
                                <span class="font-semibold text-slate-700">
                                    {{ $bar['label'] }} <span class="font-normal text-slate-600">— {{ $bar['name'] }}</span>
                                </span>
                                <span class="tabular-nums font-bold text-slate-900">{{ number_format($bar['total']) }}</span>
                            </div>
                            <div class="h-2.5 bg-slate-100" role="img" aria-label="{{ $bar['name'] }}: {{ $bar['total'] }} peserta">
                                <div class="h-full bg-primary-700"
                                    style="width: {{ max(4, (int) round($bar['total'] / $maxChart * 100)) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-display text-sm font-bold uppercase tracking-tight text-slate-900">Kuota Kategori</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($categories as $event)
                    @foreach ($event->raceCategories as $category)
                        <div class="flex items-center justify-between gap-4 px-6 py-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $category->name }}</p>
                                <p class="text-xs text-slate-500">{{ $event->name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold tabular-nums text-slate-900">{{ $category->registered_count }} / {{ number_format($category->quota) }}</p>
                                @if ($category->remainingQuota() < 1)
                                    <p class="text-xs font-semibold text-red-700">Kuota Penuh</p>
                                @else
                                    <p class="text-xs text-slate-600">sisa {{ $category->remainingQuota() }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @empty
                    <div class="px-6 py-10 text-center text-sm text-slate-500">Belum ada kategori.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection

