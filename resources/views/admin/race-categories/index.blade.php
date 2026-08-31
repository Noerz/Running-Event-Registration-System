@extends('layouts.admin')

@section('title', 'Race Categories')
@section('page-title', 'Kategori Perlombaan')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900">Race Categories</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola kategori, harga, dan kuota perlombaan.</p>
        </div>
        <a href="{{ route('admin.race-categories.create') }}" class="btn-primary">
            + Tambah Kategori
        </a>
    </div>

    <div class="mt-6 overflow-hidden border border-slate-200 bg-white">
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
                                <p class="font-semibold text-slate-800">{{ $category->name }}</p>
                            </td>
                            <td class="px-5 py-3.5 hidden md:table-cell">{{ $category->event?->name }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 tabular-nums hidden sm:table-cell">{{ $category->formattedDistance() }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 font-semibold">{{ $category->formattedPrice() }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 tabular-nums">
                                {{ $category->registered_count }} / {{ number_format($category->quota) }}
                                @if ($category->remainingQuota() < 1)
                                    <span class="ml-1 text-xs font-bold text-red-600">PENUH</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center border px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide {{ $category->status === 'active' ? 'bg-green-50 text-green-700 border-green-300' : 'bg-gray-100 text-gray-700 border-gray-300' }}">
                                    {{ $category->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <a href="{{ route('admin.race-categories.edit', $category) }}" class="inline-flex items-center gap-1 rounded-sm bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">Edit</a>
                                <form action="{{ route('admin.race-categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kategori ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-1 inline-flex items-center gap-1 rounded-sm bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Hapus</button>
                                </form>
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



