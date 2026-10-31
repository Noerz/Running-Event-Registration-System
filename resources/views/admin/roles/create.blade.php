@extends('layouts.admin')

@section('title', 'Tambah Role')
@section('page-title', 'Tambah Role')

@section('content')
    <div class="mb-6">
        <h1 class="font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900">Tambah Role</h1>
        <p class="mt-1 text-sm text-slate-500">Buat role baru dan tentukan hak akses permission-nya.</p>
    </div>

    @include('admin.roles._form')
@endsection
