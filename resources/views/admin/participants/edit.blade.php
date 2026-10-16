@extends('layouts.admin')

@section('title', 'Edit Peserta — '.$participant->full_name)
@section('page-title', 'Edit Peserta')

@section('content')
    <div>
        <a href="{{ route('admin.participants.show', $participant) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Kembali ke detail peserta
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="font-display text-2xl font-extrabold uppercase tracking-tight text-slate-900">Edit Data Peserta</h1>
            <x-status-badge :status="$participant->registration_status" />
        </div>
        <p class="mt-1 font-mono text-sm font-bold text-slate-500">{{ $participant->registration_code }}</p>
    </div>

    <form method="POST" action="{{ route('admin.participants.update', $participant) }}" class="mt-6 max-w-3xl">
        @csrf
        @method('PUT')

        <section class="border border-slate-200 bg-white p-6">
            <h2 class="border-b border-slate-900 pb-3 font-display text-sm font-bold uppercase tracking-tight text-slate-900">Data Pribadi</h2>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <x-form-field name="full_name" label="Nama Lengkap" required class="sm:col-span-2"
                    :value="old('full_name', $participant->full_name)" placeholder="Sesuai kartu identitas" />
                <x-form-field name="phone" label="Nomor HP / WhatsApp" type="tel" required
                    :value="old('phone', $participant->phone)" placeholder="081234567890" />
                <x-form-field name="email" label="Email" type="email" required
                    :value="old('email', $participant->email)" placeholder="nama@email.com" />
                <x-form-field name="gender" label="Jenis Kelamin" type="select" required
                    :options="['male' => 'Laki-laki', 'female' => 'Perempuan']"
                    :value="old('gender', $participant->gender->value)">
                    <option value="">-- Pilih --</option>
                </x-form-field>
                <x-form-field name="birth_place" label="Tempat Lahir" required
                    :value="old('birth_place', $participant->birth_place)" placeholder="Contoh: Bandung" />
                <x-form-field name="birth_date" label="Tanggal Lahir" type="date" required
                    :value="old('birth_date', $participant->birth_date?->format('Y-m-d'))" />
                <x-form-field name="identity_number" label="No. Identitas / NIK (opsional)"
                    :value="old('identity_number', $participant->identity_number)" placeholder="16 digit NIK" />
                <x-form-field name="address" label="Alamat Lengkap" type="textarea" required class="sm:col-span-2">{{ old('address', $participant->address) }}</x-form-field>
                <x-form-field name="city" label="Kota/Kabupaten" required
                    :value="old('city', $participant->city)" />
                <x-form-field name="province" label="Provinsi" required
                    :value="old('province', $participant->province)" />
            </div>
        </section>

        <section class="mt-6 border border-slate-200 bg-white p-6">
            <h2 class="border-b border-slate-900 pb-3 font-display text-sm font-bold uppercase tracking-tight text-slate-900">Data Perlombaan</h2>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <x-form-field name="jersey_size" label="Ukuran Jersey" type="select" required
                    :options="array_combine(config('registration.jersey_sizes'), config('registration.jersey_sizes'))"
                    :value="old('jersey_size', $participant->jersey_size)">
                    <option value="">-- Pilih --</option>
                </x-form-field>
                <x-form-field name="running_club" label="Komunitas / Running Club (opsional)"
                    :value="old('running_club', $participant->running_club)" />

                <div class="grid gap-5 border border-slate-200 bg-slate-50 p-5 sm:col-span-2 sm:grid-cols-2">
                    <p class="eyebrow text-slate-500 sm:col-span-2">Kontak Darurat</p>
                    <x-form-field name="emergency_contact_name" label="Nama Emergency Contact" required
                        :value="old('emergency_contact_name', $participant->emergency_contact_name)" />
                    <x-form-field name="emergency_contact_phone" label="Nomor HP Emergency Contact" type="tel" required
                        :value="old('emergency_contact_phone', $participant->emergency_contact_phone)" placeholder="081234567890" />
                </div>
            </div>

            <p class="mt-6 rounded-sm border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                Kategori lomba, BIB, dan status verifikasi tidak dapat diubah dari sini. Untuk mengubah kategori pada pendaftaran yang masih menunggu, hubungi prosedur admin terkait.
            </p>
        </section>

        <div class="mt-6 flex items-center justify-end gap-3">
            <a href="{{ route('admin.participants.show', $participant) }}" class="btn-outline">Batal</a>
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
        </div>
    </form>
@endsection
