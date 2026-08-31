@extends('layouts.admin')

@section('title', 'Edit Kategori — '.$category->name)
@section('page-title', 'Kategori Perlombaan')

@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.race-categories.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Kembali
        </a>
        <h1 class="mt-2 font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900">Edit: {{ $category->name }}</h1>

        <div class="mt-6">@include('admin.race-categories._form', ['category' => $category, 'events' => $events])</div>
    </div>
@endsection


