@extends('layouts.admin')

@section('title', 'Manajemen Admin')
@section('page-title', 'Manajemen Admin')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900">Manajemen Admin</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola akun admin yang bisa mengakses panel.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn-primary">
            + Tambah Admin
        </a>
    </div>

    <div class="mt-6 overflow-hidden border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Nama</th>
                        <th class="px-5 py-3.5">Email</th>
                        <th class="px-5 py-3.5 hidden md:table-cell">Dibuat</th>
                        <th class="px-5 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($admins as $admin)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-slate-800">{{ $admin->name }}</p>
                                @if ($admin->is(auth()->user()))
                                    <span class="text-xs font-medium text-primary-700">Anda</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">{{ $admin->email }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 hidden md:table-cell">{{ $admin->created_at?->translatedFormat('d M Y H:i') }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <a href="{{ route('admin.users.edit', $admin) }}" class="inline-flex items-center gap-1 rounded-sm bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">Edit</a>
                                @unless ($admin->is(auth()->user()))
                                    <form action="{{ route('admin.users.destroy', $admin) }}" method="POST" class="inline" onsubmit="return confirm('Hapus admin ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-1 inline-flex items-center gap-1 rounded-sm bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Hapus</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-16 text-center text-sm text-slate-500">Belum ada admin.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($admins->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $admins->links() }}</div>
        @endif
    </div>
@endsection


