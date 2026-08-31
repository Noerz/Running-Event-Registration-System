@extends('layouts.public')

@section('title', 'Form Pendaftaran — '.$event->name)

@section('content')
    <!-- Header -->
    <div class="border-b border-slate-200">
        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
            <p class="eyebrow text-primary-600">Pendaftaran</p>
            <h1 class="mt-2 font-display text-3xl font-extrabold uppercase tracking-tight text-slate-900 sm:text-4xl">Form Pendaftaran</h1>
            <p class="mt-3 text-sm text-slate-500">{{ $event->name }} — {{ $event->event_date->translatedFormat('l, d F Y') }}, {{ $event->location }}</p>
        </div>
    </div>

    <form action="{{ route('register.store') }}" method="POST" enctype="multipart/form-data" x-data="formDraft('registration-form')" class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        @csrf

        <x-flash-alert type="error" />

        <!-- Data Pribadi -->
        <section>
            <h2 class="flex items-baseline gap-3 border-b border-slate-900 pb-3">
                <span class="font-display text-sm font-bold tabular-nums text-primary-600">01</span>
                <span class="font-display text-lg font-bold uppercase tracking-tight text-slate-900">Data Pribadi</span>
            </h2>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <x-form-field name="full_name" label="Nama Lengkap" required rules="required|maxchar:255" class="sm:col-span-2" placeholder="Sesuai kartu identitas" />
                <x-form-field name="phone" label="Nomor HP / WhatsApp" type="tel" required rules="required|phone" placeholder="081234567890" hint="Digunakan untuk pencarian status pendaftaran." />
                <x-form-field name="email" label="Email" type="email" required rules="required|email" placeholder="nama@email.com" hint="Nomor peserta dikirim ke email ini." />
                <x-form-field name="gender" label="Jenis Kelamin" type="select" required rules="required" :options="['male' => 'Laki-laki', 'female' => 'Perempuan']">
                    <option value="">-- Pilih --</option>
                </x-form-field>
                <x-form-field name="birth_place" label="Tempat Lahir" required rules="required|maxchar:100" placeholder="Contoh: Bandung" />
                <x-form-field name="birth_date" label="Tanggal Lahir" type="date" required rules="required|notfuture" hint="Usia dihitung otomatis dari tanggal lahir." />
                <x-form-field name="identity_number" label="No. Identitas / NIK (opsional)" rules="nik" placeholder="16 digit NIK" />
                <x-form-field name="address" label="Alamat Lengkap" type="textarea" required rules="required" class="sm:col-span-2" />
                <x-form-field name="city" label="Kota/Kabupaten" required rules="required|maxchar:100" />
                <x-form-field name="province" label="Provinsi" required rules="required|maxchar:100" />
            </div>
        </section>

        <!-- Data Perlombaan -->
        <section id="kategori" class="mt-12">
            <h2 class="flex items-baseline gap-3 border-b border-slate-900 pb-3">
                <span class="font-display text-sm font-bold tabular-nums text-primary-600">02</span>
                <span class="font-display text-lg font-bold uppercase tracking-tight text-slate-900">Data Perlombaan</span>
            </h2>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div x-data="fieldValidator('required')">
                    @php $selectedCategory = collect($categories)->firstWhere('id', old('race_category_id')); @endphp
                    <label for="race_category_id" class="mb-1.5 block text-sm font-medium text-slate-700">Kategori Lomba <span class="text-red-500">*</span></label>
                    <select id="race_category_id" name="race_category_id" required
                        class="field-control {{ $errors->has('race_category_id') ? 'field-control-invalid' : '' }}"
                        x-bind:class="err && 'field-control-invalid'"
                        @change="check($event.target); const amount = document.getElementById('amount'); if (amount) { amount.value = $event.target.selectedOptions[0]?.dataset.price ?? ''; amount.dispatchEvent(new Event('input', { bubbles: true })); }">
                        <option value="">-- Pilih kategori --</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" data-price="{{ $category->price }}"
                                @disabled($category->remainingQuota() < 1)
                                @selected(old('race_category_id') == $category->id)>
                                {{ $category->name }} ({{ $category->formattedDistance() }}) — {{ $category->formattedPrice() }}{{ $category->remainingQuota() < 1 ? ' — PENUH' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('race_category_id')
                        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                    <p x-show="err && err !== @js($errors->first('race_category_id'))" x-text="err" x-cloak
                        class="mt-1.5 text-xs font-medium text-red-600"></p>
                </div>

                <x-form-field name="jersey_size" label="Ukuran Jersey" type="select" required rules="required" :options="array_combine(config('registration.jersey_sizes'), config('registration.jersey_sizes'))">
                    <option value="">-- Pilih --</option>
                </x-form-field>
                <x-form-field name="running_club" label="Komunitas / Running Club (opsional)" rules="maxchar:100" />

                <div class="grid gap-5 border border-slate-200 bg-slate-50 p-5 sm:col-span-2 sm:grid-cols-2">
                    <p class="eyebrow text-slate-500 sm:col-span-2">Kontak Darurat</p>
                    <x-form-field name="emergency_contact_name" label="Nama Emergency Contact" required rules="required|maxchar:255" />
                    <x-form-field name="emergency_contact_phone" label="Nomor HP Emergency Contact" type="tel" required rules="required|phone" placeholder="081234567890" />
                </div>
            </div>
        </section>

        <!-- Pembayaran -->
        <section class="mt-12">
            <h2 class="flex items-baseline gap-3 border-b border-slate-900 pb-3">
                <span class="font-display text-sm font-bold tabular-nums text-primary-600">03</span>
                <span class="font-display text-lg font-bold uppercase tracking-tight text-slate-900">Pembayaran</span>
            </h2>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div x-data="{ bankOpen: false, qrisOpen: false, selected: @js(old('payment_method')) }"
                    @change.capture="if ($event.target.name === 'payment_method') { selected = $event.target.value; if (selected === 'transfer_bank') $dispatch('open-bank'); if (selected === 'qris') $dispatch('open-qris'); }">
                    <x-form-field name="payment_method" label="Metode Pembayaran" type="select" required rules="required" :options="config('registration.payment_methods')">
                        <option value="">-- Pilih metode --</option>
                    </x-form-field>
                    <button type="button" x-show="selected === 'transfer_bank'" @click="$dispatch('open-bank')"
                        class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700 hover:underline">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z"/></svg>
                        Lihat Rekening Tujuan
                    </button>
                    <button type="button" x-show="selected === 'qris'" x-cloak @click="$dispatch('open-qris')"
                        class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700 hover:underline">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5z"/></svg>
                        Lihat Kode QRIS
                    </button>
                    <x-bank-modal :accounts="config('registration.bank_accounts')" />
                    <x-qris-modal />
                </div>
                <x-form-field name="amount" label="Nominal Pembayaran" type="number" required rules="required|numeric|minnum:1"
                    :value="$selectedCategory?->price" readonly
                    class="cursor-default bg-slate-50 text-slate-500"
                    hint="Otomatis mengikuti harga kategori lomba yang dipilih." />

                @php $maxKb = config('registration.payment_proof_max_kb'); @endphp
                <div class="sm:col-span-2" x-data="fieldValidator('required|mimes:jpg,jpeg,png|maxkb:{{ $maxKb }}')">
                    <label for="payment_proof" class="mb-1.5 block text-sm font-medium text-slate-700">Upload Bukti Pembayaran <span class="text-red-500">*</span></label>
                    <input id="payment_proof" name="payment_proof" type="file" required accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                        class="field-control !py-2 cursor-pointer file:mr-4 file:cursor-pointer file:border file:border-solid file:border-slate-300 file:bg-white file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 hover:file:border-slate-900 {{ $errors->has('payment_proof') ? 'field-control-invalid' : '' }}"
                        x-bind:class="err && 'field-control-invalid'"
                        @change="check($event.target)">
                    <p class="mt-1.5 text-xs text-slate-500" x-show="!err" x-cloak>Format JPG, JPEG, atau PNG. Maksimal {{ number_format($maxKb / 1024, $maxKb % 1024 === 0 ? 0 : 1) }} MB. Jika pakai iPhone, pilih ukuran file kecil/sedang saat upload.</p>
                    @error('payment_proof')
                        <p class="mt-1.5 flex items-start gap-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                    <p x-show="err && err !== @js($errors->first('payment_proof'))" x-text="err" x-cloak
                        class="mt-1.5 flex items-start gap-1 text-xs font-medium text-red-600"></p>
                </div>
            </div>
        </section>

        <!-- Syarat & Ketentuan -->
        <section class="mt-12 border-t border-slate-200 pt-6">
            <label class="flex items-start gap-3">
                <input type="checkbox" name="agree_terms" value="1" required
                    class="mt-0.5 h-4 w-4 shrink-0 rounded-none border-slate-300 text-primary-600 focus:ring-primary-600">
                <span class="text-sm leading-relaxed text-slate-600">
                    Saya menyatakan bahwa data yang saya masukkan benar dan menyetujui syarat dan ketentuan event.
                </span>
            </label>
            @error('agree_terms')<p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
        </section>

        <div class="sticky bottom-0 mt-10 flex flex-col gap-3 border-t border-slate-200 bg-white py-4 sm:flex-row-reverse sm:items-center sm:justify-between">
            <button type="submit" class="btn-primary !px-8 !py-3 !text-base">
                Kirim Pendaftaran
            </button>
            <p class="text-xs text-slate-400">Data akan diverifikasi panitia sebelum BIB Number diterbitkan.</p>
        </div>
    </form>
@endsection
