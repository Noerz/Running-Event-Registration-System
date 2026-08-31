@extends('layouts.admin')

@section('title', 'Events')
@section('page-title', 'Event Management')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900">Events</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola event lari dan periode pendaftarannya.</p>
        </div>
        <a href="{{ route('admin.events.create') }}" class="btn-primary">
            + Tambah Event
        </a>
    </div>

    <div class="mt-6 overflow-hidden border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Nama Event</th>
                        <th class="px-5 py-3.5 hidden md:table-cell">Tanggal</th>
                        <th class="px-5 py-3.5 hidden lg:table-cell">Periode Pendaftaran</th>
                        <th class="px-5 py-3.5">Kategori</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($events as $event)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-slate-800">{{ $event->name }}</p>
                                <p class="text-xs text-slate-400">{{ $event->location }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 hidden md:table-cell">{{ $event->event_date->translatedFormat('d M Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 hidden lg:table-cell">{{ $event->registration_start->translatedFormat('d M') }} — {{ $event->registration_end->translatedFormat('d M Y') }}</td>
                            <td class="px-5 py-3.5 tabular-nums">{{ $event->race_categories_count }}</td>
                            <td class="px-5 py-3.5"><x-status-badge :status="$event->status" /></td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <a href="{{ route('admin.events.edit', $event) }}" class="inline-flex items-center gap-1 rounded-sm bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">Edit</a>
                                <form action="{{ route('admin.events.destroy', $event) }}" method="POST" class="inline"
                                    x-data onsubmit="return confirm('Hapus event ini? Tindakan tidak dapat dibatalkan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-1 inline-flex items-center gap-1 rounded-sm bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-16 text-center text-sm text-slate-500">Belum ada event.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($events->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $events->links() }}</div>
        @endif
    </div>
@endsection


