<!--
    Placeholder bertahap (skeleton) untuk area yang menunggu data.
    Pemakaian di dalam x-show / x-if:
        <x-skeleton-loader :lines="3" />
-->
@props([
    'lines' => 3,
    'height' => 'h-4',
])

<div {{ $attributes->merge(['class' => 'animate-pulse space-y-3']) }} aria-hidden="true">
    @for ($i = 0; $i < max(1, (int) $lines); $i++)
        <div class="rounded-sm bg-slate-200 {{ $height }} {{ $i === (int) $lines - 1 ? 'w-2/3' : 'w-full' }}"></div>
    @endfor
</div>
