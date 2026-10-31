<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Object Storage (FileGator CDN)
    |--------------------------------------------------------------------------
    |
    | Konfigurasi terpusat untuk layanan object storage. Tidak ada credential
    | yang di-hardcode; semuanya diambil dari environment. Disk `object_storage`
    | pada config/filesystems.php memakai nilai-nilai di bawah ini.
    |
    | Dokumentasi: https://dev.kantorku.my.id/cdn/api/docs
    |
    */

    'base_url' => env('OBJECT_STORAGE_BASE_URL', 'https://dev.kantorku.my.id/cdn/api/v1'),

    // Token API (header X-API-Token). Buat lewat panel FileGator dan simpan
    // hanya di .env. Token ini bersifat rahasia.
    'token' => env('OBJECT_STORAGE_TOKEN'),

    // Kredensial opsional untuk menukar token baru via POST /auth/token bila
    // OBJECT_STORAGE_TOKEN tidak diisi.
    'username' => env('OBJECT_STORAGE_USERNAME'),
    'password' => env('OBJECT_STORAGE_PASSWORD'),

    // Folder dasar (relatif terhadap homedir user FileGator) untuk semua file (opsional).
    'folder' => env('OBJECT_STORAGE_FOLDER', ''),

    // Timeout HTTP dalam detik.
    'timeout' => env('OBJECT_STORAGE_TIMEOUT', 30),

    // Jumlah percobaan ulang untuk kegagalan koneksi/5xx.
    'retry' => env('OBJECT_STORAGE_RETRY', 2),

    // Batas ukuran file dalam KB (dipakai validator & upload).
    'max_kb' => env('OBJECT_STORAGE_MAX_KB', env('PAYMENT_PROOF_MAX_KB', 2048)),

    // Ekstensi & MIME yang diizinkan untuk bukti pembayaran.
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
    'allowed_mimes' => ['image/jpeg', 'image/png', 'application/pdf'],
];
