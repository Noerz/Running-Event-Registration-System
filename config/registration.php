<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Pendaftaran
    |--------------------------------------------------------------------------
    */

    // Batas maksimal ukuran bukti pembayaran dalam KB (2 MB).
    'payment_proof_max_kb' => env('PAYMENT_PROOF_MAX_KB', 2048),

    // Pendaftaran pending yang tidak diverifikasi dalam N hari akan
    // otomatis dibatalkan oleh command `participants:expire-pending`,
    // sehingga slot kuota kembali tersedia.
    'pending_expiry_days' => env('PENDING_EXPIRY_DAYS', 7),

    // Disk penyimpanan bukti pembayaran. Gunakan disk privat, bukan public.
    'payment_proof_disk' => env('PAYMENT_PROOF_DISK', 'local'),

    // Metode pembayaran yang tersedia pada form pendaftaran.
    // Verifikasi dilakukan manual oleh admin (tanpa payment gateway).
    'payment_methods' => [
        'transfer_bank' => 'Transfer Bank',
        'qris' => 'QRIS',
    ],

    // Daftar rekening bank tujuan untuk metode Transfer Bank.
    'bank_accounts' => [
        [
            'bank' => 'BCA',
            'number' => '1234567890',
            'holder' => 'Panitia Running Event',
        ],
        [
            'bank' => 'Mandiri',
            'number' => '9876543210',
            'holder' => 'Panitia Running Event',
        ],
    ],

    // Gambar kode QRIS untuk pembayaran manual, relatif terhadap folder public.
    // Ganti dengan file QRIS Anda sendiri (PNG/JPG/SVG), contoh di .env:
    // QRIS_IMAGE=images/qris-saya.png
    'qris_image' => env('QRIS_IMAGE', 'images/qris.svg'),

    // Ukuran jersey yang tersedia.
    'jersey_sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
];
