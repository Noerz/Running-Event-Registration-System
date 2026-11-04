@extends('layouts.admin')

@section('title', 'Race Categories')
@section('page-title', 'Kategori Perlombaan')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900">Race Categories</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola kategori, harga, dan kuota perlombaan.</p>
        </div>
        <a href="{{ route('admin.race-categories.create', request()->filled('event_id') ? ['event_id' => request('event_id')] : []) }}" class="btn-primary">
            + Tambah Kategori
        </a>
    </div>

    <form method="GET" action="{{ route('admin.race-categories.index') }}" class="mt-6 border border-slate-200 bg-white p-4 sm:p-5">
        <div class="flex flex-wrap items-center gap-3">
            <label for="event_id" class="text-xs font-bold uppercase tracking-wider text-slate-500">Filter Event:</label>
            <select id="event_id" name="event_id" onchange="this.form.submit()" class="field-control max-w-xs py-1.5 text-sm">
                <option value="">Semua Event</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected((string) request('event_id') === (string) $event->id)>
                        {{ $event->name }} ({{ $event->event_date->format('Y') }})
                    </option>
                @endforeach
            </select>
            @if (request()->filled('event_id'))
                <a href="{{ route('admin.race-categories.index') }}" class="text-xs font-semibold text-slate-500 underline hover:text-slate-800">
                    Reset Filter
                </a>
            @endif
        </div>
    </form>

    <div class="mt-4 overflow-hidden border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Kategori</th>
                        <th class="px-5 py-3.5 hidden md:table-cell">Event</th>
                        <th class="px-5 py-3.5 hidden sm:table-cell">Jarak</th>
                        <th class="px-5 py-3.5">Harga</th>
                        <th class="px-5 py-3.5">Terisi / Kuota</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-3.5">
                                <span class="font-mono text-xs font-bold text-primary-700">{{ $category->code }}</span>
                                <span class="ml-1.5 font-mono text-[11px] text-slate-400">(BIB: {{ $category->formatBibNumber(1) }})</span>
                                <p class="font-semibold text-slate-800">{{ $category->name }}</p>
                            </td>
                            <td class="px-5 py-3.5 hidden md:table-cell">{{ $category->event?->name }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 tabular-nums hidden sm:table-cell">{{ $category->formattedDistance() }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 font-semibold">{{ $category->formattedPrice() }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 tabular-nums">
                                {{ $category->registered_count }} / {{ number_format($category->quota) }}
                                @if ($category->remainingQuota() < 1)
                                    <span class="ml-1 text-xs font-bold text-red-700">PENUH</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center border px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide {{ $category->status === 'active' ? 'bg-green-50 text-green-700 border-green-300' : 'bg-gray-100 text-gray-700 border-gray-300' }}">
                                    {{ $category->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <a href="{{ route('admin.race-categories.edit', $category) }}" class="inline-flex items-center gap-1 rounded-sm bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">Edit</a>
                                <button type="button"
                                    @click="$dispatch('open-confirm-modal', {
                                        url: '{{ route('admin.race-categories.destroy', $category) }}',
                                        method: 'DELETE',
                                        heading: 'Hapus Kategori Lomba',
                                        description: 'Hapus kategori {{ addslashes($category->name) }}? Tindakan tidak dapat dibatalkan.',
                                        confirmText: 'Ya, Hapus Kategori',
                                        loadingText: 'Menghapus…',
                                        danger: true
                                    })"
                                    class="ml-1 inline-flex items-center gap-1 rounded-sm bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-16 text-center text-sm text-slate-500">Belum ada kategori.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($categories->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $categories->links() }}</div>
        @endif
    </div>
@endsection



