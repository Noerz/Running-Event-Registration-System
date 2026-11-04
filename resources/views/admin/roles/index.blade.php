@extends('layouts.admin')

@section('title', 'Role Management')
@section('page-title', 'Role Management')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900">Role Management</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola role dan hak akses permission untuk administrator.</p>
        </div>
        @can('roles.create')
            <a href="{{ route('admin.roles.create') }}" class="btn-primary">
                + Tambah Role
            </a>
        @endcan
    </div>

    <div class="mt-6 overflow-hidden border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Role</th>
                        <th class="px-5 py-3.5">Kode</th>
                        <th class="px-5 py-3.5 hidden md:table-cell">Deskripsi</th>
                        <th class="px-5 py-3.5 text-center">Admin</th>
                        <th class="px-5 py-3.5 text-center">Permission</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($roles as $role)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-900">{{ $role->name }}</span>
                                    @if ($role->is_system)
                                        <span class="inline-flex items-center rounded-sm bg-primary-50 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-primary-700">System</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-600">{{ $role->code }}</td>
                            <td class="px-5 py-3.5 text-xs text-slate-500 hidden md:table-cell">{{ $role->description ?: '—' }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                    {{ $role->admins_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if ($role->is_system)
                                    <span class="inline-flex items-center rounded-sm bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700">Semua (*)</span>
                                @else
                                    <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                        {{ $role->permissions_count }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if ($role->is_system)
                                    <span class="inline-flex items-center rounded-sm bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Aktif</span>
                                @else
                                    @can('roles.update')
                                        <form action="{{ route('admin.roles.toggle', $role) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center rounded-sm px-2 py-0.5 text-xs font-semibold transition-colors {{ $role->is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                                {{ $role->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="inline-flex items-center rounded-sm px-2 py-0.5 text-xs font-semibold {{ $role->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                            {{ $role->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    @endcan
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                @can('roles.update')
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="inline-flex items-center gap-1 rounded-sm bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                        Edit
                                    </a>
                                @endcan
                                @can('roles.delete')
                                    @if (! $role->is_system)
                                        <button type="button"
                                            @click="$dispatch('open-confirm-modal', {
                                                url: '{{ route('admin.roles.destroy', $role) }}',
                                                method: 'DELETE',
                                                heading: 'Hapus Role',
                                                description: 'Yakin ingin menghapus role {{ addslashes($role->name) }}? Tindakan tidak dapat dibatalkan.',
                                                confirmText: 'Ya, Hapus Role',
                                                loadingText: 'Menghapus…',
                                                danger: true
                                            })"
                                            @disabled($role->admins_count > 0)
                                            title="{{ $role->admins_count > 0 ? 'Role masih digunakan oleh admin' : 'Hapus role' }}"
                                            class="ml-1 inline-flex items-center gap-1 rounded-sm bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-40">
                                            Hapus
                                        </button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center text-sm text-slate-500">Belum ada role.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
