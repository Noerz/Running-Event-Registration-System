@extends('layouts.admin')

@section('title', 'Edit Event — '.$event->name)
@section('page-title', 'Event Management')

@section('content')
    <div class="mx-auto max-w-4xl">
        <a href="{{ route('admin.events.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Kembali
        </a>
        <h1 class="mt-2 font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900">Edit: {{ $event->name }}</h1>

        <div class="mt-6">@include('admin.events._form', ['event' => $event])</div>

        <div class="mt-8 border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-6 py-4">
                <div>
                    <h2 class="font-display text-base font-bold uppercase tracking-tight text-slate-900">
                        Kategori Perlombaan ({{ $event->raceCategories->count() }})
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-500">Daftar kategori yang terhubung dengan event ini.</p>
                </div>
                <a href="{{ route('admin.race-categories.create', ['event_id' => $event->id]) }}" class="btn-primary text-xs">
                    + Tambah Kategori
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Kategori</th>
                            <th class="px-5 py-3">Jarak</th>
                            <th class="px-5 py-3">Harga</th>
                            <th class="px-5 py-3">Terisi / Kuota</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($event->raceCategories as $category)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-3">
                                    <span class="font-mono text-xs font-bold text-primary-700">{{ $category->code }}</span>
                                    <span class="ml-1.5 font-mono text-[11px] text-slate-400">(BIB: {{ $category->formatBibNumber(1) }})</span>
                                    <p class="font-semibold text-slate-800">{{ $category->name }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 tabular-nums">{{ $category->formattedDistance() }}</td>
                                <td class="whitespace-nowrap px-5 py-3 font-semibold">{{ $category->formattedPrice() }}</td>
                                <td class="whitespace-nowrap px-5 py-3 tabular-nums">
                                    {{ $category->registered_count }} / {{ number_format($category->quota) }}
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center border px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide {{ $category->status === 'active' ? 'bg-green-50 text-green-700 border-green-300' : 'bg-gray-100 text-gray-700 border-gray-300' }}">
                                        {{ $category->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right">
                                    <a href="{{ route('admin.race-categories.edit', $category) }}" class="inline-flex items-center gap-1 rounded-sm bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">
                                    Belum ada kategori perlombaan untuk event ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection


