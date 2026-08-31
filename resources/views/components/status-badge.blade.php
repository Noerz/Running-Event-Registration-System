@props(['status'])

@php
    /** @var \App\Enums\PaymentStatus|\App\Enums\RegistrationStatus|\App\Enums\EventStatus $status */
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center whitespace-nowrap border px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide '.$status->color()]) }}>
    {{ $status->label() }}
</span>
