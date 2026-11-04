<!--
    Tombol submit dengan state loading bawaan (Alpine).
    Bentuk otomatis mengikuti atribut pada <x-loading-button> dan isinya jadi slot.

    <x-loading-button class="btn-primary" :loading="'Menyimpan…'">Simpan</x-loading-button>

    Bila tombol berada di dalam <form>, state loading juga di-set otomatis oleh
    handler global di resources/js/ux.js — atribut Alpine di bawah cukup
    untuk tombol yang submit-nya ditangani manual (mis. x-on:click).
-->
@props([
    'type' => 'submit',
    'loading' => 'Memproses…',
    'size' => 'sm',
])

<button
    type="{{ $type }}"
    x-data="{ loading: false }"
    x-on:loading.start="loading = true"
    x-on:loading.stop="loading = false"
    :disabled="loading"
    :aria-busy="loading.toString()"
    x-bind:class="loading ? 'pointer-events-none opacity-80' : ''"
    {{ $attributes->merge(['class' => 'btn-primary']) }}
>
    <x-loading-spinner x-show="loading" x-cloak :size="$size" />
    <span x-show="!loading">{!! $slot !!}</span>
    <span x-show="loading" x-cloak>{{ $loading }}</span>
</button>
