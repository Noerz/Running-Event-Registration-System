@extends('layouts.admin')

@section('title', 'Edit Role')
@section('page-title', 'Edit Role')

@section('content')
    <div class="mb-6">
        <h1 class="font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900">Edit Role</h1>
        <p class="mt-1 text-sm text-slate-500">Perbarui informasi role dan permission yang diberikan.</p>
    </div>

    @include('admin.roles._form')
@endsection
