@extends('layouts.public')

@section('title', $event ? $event->name.' — Pendaftaran Online' : 'KATAR FUN RUN REG')

@section('content')
    @if ($event)
        <!-- Hero -->
        <section class="border-b border-slate-200">
            <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.1fr_1fr] lg:gap-16 lg:py-20 lg:px-8">
                <div>
                    @if ($isRegistrationOpen)
                        <p class="eyebrow text-primary-700">Pendaftaran Dibuka</p>
                    @else
                        <p class="eyebrow text-red-700">Pendaftaran Ditutup</p>
                    @endif

                    <h1 class="mt-3 font-display text-4xl font-extrabold uppercase leading-[0.95] tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                        {{ $event->name }}
                    </h1>

                    <p class="mt-5 max-w-xl leading-relaxed text-slate-600">{{ $event->description }}</p>

                    <div class="mt-6 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
                        <span>{{ $event->event_date->translatedFormat('l, d F Y') }}</span>
                        <span aria-hidden="true" class="text-slate-300">·</span>
                        <span>{{ $event->location }}</span>
                    </div>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('register.create') }}" class="btn-primary">Daftar Sekarang</a>
                        <a href="{{ route('check.show') }}" class="btn-outline">Cek Status Pendaftaran</a>
                    </div>
                </div>

                <figure class="hidden lg:block">
                    {{-- Ganti public/images/hero.jpg dengan foto event Anda --}}
                    <img src="{{ asset('images/hero-900.jpg') }}" alt="Foto suasana lomba" width="900" height="837"
                        class="aspect-[4/5] w-full object-cover grayscale-[15%]">
                    <figcaption class="mt-2 text-right text-xs text-slate-600">{{ $event->location }} — {{ $event->event_date->translatedFormat('Y') }}</figcaption>
                </figure>
            </div>
        </section>

        <!-- Countdown -->
        @if ($isRegistrationOpen && $event->event_date->isFuture())
            <section class="border-b border-slate-200 bg-white">
                <div x-data="countdown('{{ $event->event_date->toIso8601String() }}')" class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                    <p class="eyebrow text-slate-600">Menuju Hari-H</p>
                    <div class="mt-3 grid max-w-lg grid-cols-4 divide-x divide-slate-200 border border-slate-200">
                        @foreach ([['Hari', 'days'], ['Jam', 'hours'], ['Menit', 'minutes'], ['Detik', 'seconds']] as [$labelText, $key])
                            <div class="px-2 py-4 text-center">
                                <div class="font-display text-3xl font-bold tabular-nums text-slate-900 sm:text-4xl" x-text="String({{ $key }}).padStart(2, '0')">--</div>
                                <div class="mt-1 text-[11px] font-medium uppercase tracking-widest text-slate-600">{{ $labelText }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <!-- Kategori Lomba -->
        <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="eyebrow text-primary-700">Kategori</p>
                    <h2 class="mt-2 font-display text-2xl font-bold uppercase tracking-tight text-slate-900 sm:text-3xl">Kategori Perlombaan</h2>
                </div>
                <p class="hidden max-w-xs text-right text-sm text-slate-500 sm:block">Setiap peserta mendapatkan jersey finisher, medali, dan e-certificate.</p>
            </div>

            <div class="mt-8">
                @forelse ($categories as $category)
                    <article class="grid grid-cols-[auto_1fr_auto] items-center gap-x-4 gap-y-2 border-t border-slate-200 py-5 transition-colors last:border-b hover:bg-slate-50 sm:grid-cols-[auto_1fr_auto_auto_auto] sm:gap-x-6 sm:px-4 {{ $loop->last ? 'border-b' : '' }}">
                        <span class="inline-flex min-w-[52px] justify-center border border-slate-900 px-2 py-1 font-display text-sm font-bold uppercase tracking-wide text-slate-900">
                            {{ $category->code }}
                        </span>

                        <div class="min-w-0">
                            <h3 class="truncate font-display text-base font-bold text-slate-900 sm:text-lg">{{ $category->name }}</h3>
                            <p class="mt-0.5 text-sm text-slate-500">
                                {{ number_format($category->distance, $category->distance == floor($category->distance) ? 0 : 2, ',', '.') }} km
                                · Sisa kuota {{ $category->remainingQuota() }}/{{ $category->quota }}
                            </p>
                        </div>

                        <p class="col-start-3 row-span-2 self-center font-display text-xl font-bold tabular-nums text-slate-900 sm:col-start-3 sm:row-span-1">
                            {{ $category->formattedPrice() }}
                        </p>

                        <span class="sr-only">Biaya pendaftaran kategori {{ $category->name }}</span>

                        @if ($category->remainingQuota() > 0)
                            <a href="{{ route('register.create') }}"
                                class="col-start-2 row-start-2 w-fit border border-slate-900 px-4 py-2 text-sm font-semibold text-slate-900 transition-colors hover:bg-slate-900 hover:text-white sm:col-start-auto sm:row-start-auto">
                                Daftar
                            </a>
                        @else
                            <span class="col-start-2 row-start-2 w-fit cursor-not-allowed border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 sm:col-start-auto sm:row-start-auto">
                                Penuh
                            </span>
                        @endif
                    </article>
                @empty
                    <div class="border-y border-dashed border-slate-300 py-16 text-center">
                        <p class="text-sm text-slate-500">Belum ada kategori yang tersedia untuk saat ini.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <!-- Tata Cara & Pembayaran -->
        <section class="border-t border-slate-200 bg-slate-50">
            <div class="mx-auto grid max-w-6xl gap-12 px-4 py-14 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div>
                    <p class="eyebrow text-primary-700">Alur</p>
                    <h2 class="mt-2 font-display text-2xl font-bold uppercase tracking-tight text-slate-900">Tata Cara Pendaftaran</h2>
                    <ol class="mt-7 space-y-6">
                        @foreach ([
                            'Isi form pendaftaran' => 'Lengkapi biodata dan data perlombaan pada halaman pendaftaran.',
                            'Upload bukti pembayaran' => 'Lakukan pembayaran sesuai nominal kategori lalu unggah bukti transfer.',
                            'Tunggu verifikasi panitia' => 'Registration Code Anda aktif langsung; status awal Menunggu Verifikasi.',
                            'Terima nomor peserta (BIB)' => 'Setelah pembayaran disetujui, BIB Number dikirim ke email Anda.',
                        ] as $step => $desc)
                            <li class="flex items-baseline gap-4 border-b border-slate-200 pb-5 last:border-b-0 last:pb-0">
                                <span class="font-display text-xl font-bold tabular-nums text-primary-700">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $step }}</p>
                                    <p class="mt-0.5 text-sm leading-relaxed text-slate-500">{{ $desc }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div>
                    <p class="eyebrow text-primary-700">Pembayaran</p>
                    <h2 class="mt-2 font-display text-2xl font-bold uppercase tracking-tight text-slate-900">Informasi Pembayaran</h2>
                    <dl class="mt-7 divide-y divide-slate-200 border-y border-slate-200 text-sm">
                        <div class="flex flex-col gap-1 py-4 sm:flex-row sm:justify-between">
                            <dt class="font-semibold text-slate-900">Periode pendaftaran</dt>
                            <dd class="text-slate-500 sm:text-right">{{ $event->registration_start->translatedFormat('d M Y') }} s/d {{ $event->registration_end->translatedFormat('d M Y') }}</dd>
                        </div>
                        <div class="flex flex-col gap-1 py-4 sm:flex-row sm:justify-between">
                            <dt class="font-semibold text-slate-900">Metode pembayaran</dt>
                            <dd class="text-slate-500 sm:text-right">Transfer Bank atau QRIS (verifikasi manual oleh panitia)</dd>
                        </div>
                        <div class="flex flex-col gap-1 py-4 sm:flex-row sm:justify-between">
                            <dt class="font-semibold text-slate-900">Bukti pembayaran</dt>
                            <dd class="text-slate-500 sm:text-right">Gambar JPG/JPEG/PNG, maksimal 2 MB</dd>
                        </div>
                        <div class="flex flex-col gap-1 py-4 sm:flex-row sm:justify-between">
                            <dt class="font-semibold text-slate-900">Verifikasi</dt>
                            <dd class="text-slate-500 sm:text-right">Maksimal 1×24 jam pada hari kerja</dd>
                        </div>
                        <div class="flex flex-col gap-1 py-4 sm:flex-row sm:justify-between">
                            <dt class="font-semibold text-slate-900">Nomor peserta (BIB)</dt>
                            <dd class="text-slate-500 sm:text-right">Diterbitkan setelah pembayaran terverifikasi &amp; dikirim via email</dd>
                        </div>
                    </dl>
                    <p class="mt-4 text-xs text-slate-600">Nominal pembayaran harus sama persis dengan harga kategori yang dipilih.</p>
                </div>
            </div>
        </section>
    @else
        <section class="mx-auto max-w-6xl px-4 py-24 sm:px-6 lg:px-8">
            <p class="eyebrow text-slate-600">Info</p>
            <h1 class="mt-2 font-display text-3xl font-extrabold uppercase tracking-tight text-slate-900 sm:text-4xl">Belum Ada Event Aktif</h1>
            <p class="mt-4 max-w-md text-slate-500">Saat ini belum ada event lari yang tersedia. Silakan kembali lagi nanti untuk informasi event terbaru.</p>
        </section>
    @endif
@endsection

@push('scripts')
@once
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('countdown', (targetIso) => ({
                target: new Date(targetIso),
                days: 0, hours: 0, minutes: 0, seconds: 0,
                init() {
                    this.tick();
                    setInterval(() => this.tick(), 1000);
                },
                tick() {
                    const diff = Math.max(0, this.target - new Date());
                    this.days = Math.floor(diff / 86400000);
                    this.hours = Math.floor(diff % 86400000 / 3600000);
                    this.minutes = Math.floor(diff % 3600000 / 60000);
                    this.seconds = Math.floor(diff % 60000 / 1000);
                },
            }));
        });
    </script>
@endonce
@endpush
