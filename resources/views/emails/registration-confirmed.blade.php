@component('mail::message')
# Halo {{ $participant->full_name }},

Pembayaran Anda telah **berhasil diverifikasi**. Nomor peserta (BIB) Anda sudah diterbitkan.

@component('mail::panel')
**Detail Pendaftaran**

- **Registration Code:** {{ $participant->registration_code }}
- **Nama:** {{ $participant->full_name }}
- **Kategori:** {{ $participant->raceCategory->name }} ({{ $participant->raceCategory->formattedDistance() }})
- **Nomor Peserta / BIB:** {{ $participant->bib_number }}

**Tanggal Event:** {{ $participant->event->event_date->translatedFormat('l, d F Y') }}
**Lokasi:** {{ $participant->event->location }}
@endcomponent

Simpan nomor peserta Anda dan tunjukkan ketika melakukan pengambilan *race pack*.

Terima kasih telah berpartisipasi.<br>
{{ config('app.name') }}
@endcomponent
