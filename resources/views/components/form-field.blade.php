@props(['name', 'label' => null, 'type' => 'text', 'hint' => null, 'required' => false, 'options' => [], 'placeholder' => null, 'rules' => null])

@php
    $serverError = $errors->first($name);
@endphp

<div x-data="fieldValidator(@js($rules), @js($serverError))">
    @if ($label)
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-slate-700">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    @if ($type === 'select')
        <select id="{{ $name }}" name="{{ $name }}"
            {{ $attributes->merge(['class' => 'field-control'.($serverError ? ' field-control-invalid' : '')]) }}
            x-bind:class="err && 'field-control-invalid'"
            @change="check($event.target)">
            {{ $slot }}
            @foreach ($options as $value => $optionLabel)
                <option value="{{ $value }}" @selected(old($name, $attributes->get('value')) == $value)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="3" placeholder="{{ $placeholder }}"
            {{ $attributes->merge(['class' => 'field-control'.($serverError ? ' field-control-invalid' : '')]) }}
            x-bind:class="err && 'field-control-invalid'"
            @input="check($event.target)"
            @blur="check($event.target)">{{ old($name, trim((string) $slot)) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
            value="{{ old($name, $attributes->get('value')) }}" placeholder="{{ $placeholder }}"
            @if ($required) required @endif
            {{ $attributes->except(['value'])->merge(['class' => 'field-control'.($serverError ? ' field-control-invalid' : '')]) }}
            x-bind:class="err && 'field-control-invalid'"
            @input="check($event.target)"
            @blur="check($event.target)">
    @endif

    @if ($hint && ! $serverError)
        <p class="mt-1.5 text-xs text-slate-500" x-show="!err" x-cloak>{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1.5 flex items-start gap-1 text-xs font-medium text-red-700">
            <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            {{ $message }}
        </p>
    @enderror

    {{-- Pesan error validasi live (client-side) --}}
    <p x-show="err && err !== @js($serverError)" x-text="err" x-cloak
        class="mt-1.5 flex items-start gap-1 text-xs font-medium text-red-700"></p>
</div>
