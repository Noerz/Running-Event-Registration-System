@php
    $statusOptions = [];
    foreach (\App\Enums\EventStatus::cases() as $s) {
        $statusOptions[$s->value] = $s->label();
    }
@endphp

<form action="{{ isset($event) ? route('admin.events.update', $event) : route('admin.events.store') }}" method="POST"
    class="mx-auto max-w-2xl border border-slate-200 bg-white p-6 sm:p-8">
    @csrf
    @if (isset($event))
        @method('PUT')
    @endif

    <div class="space-y-5">
        <x-form-field name="name" label="Nama Event" required :value="$event->name ?? null" placeholder="Contoh: Bali Marathon 2026" />
        <x-form-field name="description" label="Deskripsi" type="textarea" required>{{ $event->description ?? '' }}</x-form-field>
        <x-form-field name="location" label="Lokasi" required :value="$event->location ?? null" placeholder="Contoh: Denpasar, Bali" />
        <div class="grid gap-5 sm:grid-cols-3">
            <x-form-field name="event_date" label="Tanggal Event" type="date" required :value="($event?->event_date ?? old('event_date'))?->format('Y-m-d')" />
            <x-form-field name="registration_start" label="Pendaftaran Dibuka" type="date" required :value="($event?->registration_start ?? old('registration_start'))?->format('Y-m-d')" />
            <x-form-field name="registration_end" label="Pendaftaran Ditutup" type="date" required :value="($event?->registration_end ?? old('registration_end'))?->format('Y-m-d')" />
        </div>
        <x-form-field name="status" label="Status" type="select" required :options="$statusOptions" :value="$event->status->value ?? null">
            <option value="">-- Pilih status --</option>
        </x-form-field>
    </div>

    <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.events.index') }}" class="btn-outline w-full">Batal</a>
        <button type="submit" class="rounded-sm bg-primary-600 px-6 py-2.5 text-sm font-bold text-white  hover:bg-primary-700">
            {{ isset($event) ? 'Simpan Perubahan' : 'Buat Event' }}
        </button>
    </div>
</form>


