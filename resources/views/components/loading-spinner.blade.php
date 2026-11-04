@props([
    'size' => 'md',
    'class' => '',
])

@php
    $sizeClasses = [
        'xs' => 'h-3.5 w-3.5',
        'sm' => 'h-4 w-4',
        'md' => 'h-5 w-5',
        'lg' => 'h-6 w-6',
        'xl' => 'h-8 w-8',
    ][$size] ?? 'h-5 w-5';
@endphp

<svg {{ $attributes->merge(['class' => "animate-spin shrink-0 text-current {$sizeClasses} {$class}"]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
    <path fill="currentColor" class="opacity-90" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
</svg>
