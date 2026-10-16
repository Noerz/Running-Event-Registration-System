# Running Event Registration System

Aplikasi web untuk mengelola **pendaftaran peserta event lari** — dari pendaftaran online, upload bukti pembayaran, verifikasi panitia, hingga penerbitan **Nomor Peserta (BIB Number)** dan pengirimannya via email.

| Aspek        | Teknologi                              |
|--------------|----------------------------------------|
| Backend      | Laravel 13 (PHP ≥ 8.2)                 |
| Database     | MySQL (dev lokal dapat pakai SQLite)   |
| Frontend     | Blade Template + Tailwind CSS v4       |
| Interaktivitas| Alpine.js                             |
| Auth         | Laravel Authentication (khusus admin)  |
| Email        | Laravel Mailable + Queue               |
| Testing      | PHPUnit                                |

---

## 1. Fitur Utama

### Publik (tanpa login)
- Landing page: info event, countdown, daftar kategori + harga dinamis dari database.
- Form pendaftaran lengkap (data pribadi, data perlombaan, pembayaran, upload bukti).
- **Registration Code otomatis** (`REG-2026-00001`) diberikan langsung setelah submit.
- Halaman cek status `/check-registration` menggunakan **email atau nomor HP**, dilindungi rate limiting.
- BIB Number tampil di halaman cek status setelah pembayaran diverifikasi.
- Alasan penolakan pembayaran ditampilkan kepada peserta yang ditolak.

### Admin (wajib login di `/admin/login`)
- Dashboard statistik: total peserta, pendaftar hari ini, pending/approved/rejected, grafik per kategori, sisa kuota.
- Manajemen peserta: pencarian (nama/email/HP/kode), filter kategori & status, pagination.
- Detail peserta: personal, race, dan payment information + lihat/unduh bukti pembayaran (storage privat).
- **Approve payment** — dalam satu transaksi: update status, generate BIB (race-safe), catat admin & waktu, kirim email via queue.
- **Reject payment** — wajib mengisi alasan; tidak pernah menerbitkan BIB.
- CRUD Events (draft/open/closed/completed) & Race Categories (nama, kode, jarak, harga, kuota, status).
- Audit log aktivitas penting ke tabel `activity_logs`.

### Jaminan Integritas Data
- Approval **idempotent**: klik ulang/request ganda tidak membuat BIB kedua (row locking `FOR UPDATE`).
- BIB Number unik per kategori (`5K-0001`, `5K-0002`, …) dengan counter terkunci per baris kategori + `UNIQUE` constraint.
- Registration Code diturunkan dari primary key → bebas race condition tanpa tabel sequence tambahan.
- Kuota kategori dicek saat registrasi; event hanya bisa didaftar saat status `open` dan periode aktif.

---

## 2. ERD (Mermaid)

```mermaid
erDiagram
    USERS ||--o{ PARTICIPANTS : "verified_by (via payments)"
    USERS ||--o{ PAYMENTS : verifikasi
    EVENTS ||--|{ RACE_CATEGORIES : memiliki
    EVENTS ||--o{ PARTICIPANTS : menerima
    RACE_CATEGORIES ||--o{ PARTICIPANTS : dipilih
    PARTICIPANTS ||--|| PAYMENTS : membayar
    USERS ||--o{ ACTIVITY_LOGS : melakukan

    USERS {
        bigint id PK
        string name
        string email UK
        string password
        timestamps timestamps
    }
    EVENTS {
        bigint id PK
        string name
        string slug UK
        text description
        date event_date
        string location
        date registration_start
        date registration_end
        string status "draft|open|closed|completed"
        timestamps timestamps
    }
    RACE_CATEGORIES {
        bigint id PK
        bigint event_id FK
        string name
        string code "5K/10K/21K"
        decimal distance
        bigint price
        int quota
        int bib_counter "counter BIB per kategori"
        string status "active|inactive"
        timestamps timestamps
    }
    PARTICIPANTS {
        bigint id PK
        bigint event_id FK
        bigint race_category_id FK
        string registration_code UK "REG-2026-00001"
        string bib_number UK nullable "5K-0001"
        string full_name
        string phone "index"
        string email "index"
        string gender "male|female"
        string birth_place
        date birth_date
        tinyint age
        string identity_number nullable
        text address
        string city
        string province
        string jersey_size
        string running_club nullable
        string emergency_contact_name
        string emergency_contact_phone
        string registration_status "pending|confirmed|rejected"
        timestamps timestamps
    }
    PAYMENTS {
        bigint id PK
        bigint participant_id FK
        string payment_method
        bigint amount
        string payment_proof "path storage privat"
        string payment_status "pending|approved|rejected"
        text rejection_reason nullable
        bigint verified_by FK users nullable
        timestamp verified_at nullable
        timestamps timestamps
    }
    ACTIVITY_LOGS {
        bigint id PK
        bigint admin_id FK users nullable
        string action "payment_approved|bib_issued|..."
        string subject_type nullable
        bigint subject_id nullable
        text description nullable
        timestamp created_at
    }
```

Aturan email/HP: **tidak globally unique** — satu orang boleh mendaftarkan lebih dari satu peserta/event. Email + nomor HP hanya digunakan sebagai identifikasi pencarian pada halaman cek status.

---

## 3. Struktur Folder Penting

```
app/
├── Enums/
│   ├── EventStatus.php            # draft|open|closed|completed
│   ├── Gender.php                 # male|female
│   ├── PaymentStatus.php          # pending|approved|rejected (+ label & warna)
│   └── RegistrationStatus.php     # pending|confirmed|rejected
├── Http/
│   ├── Controllers/
│   │   ├── HomeController.php             # landing page
│   │   ├── RegistrationController.php     # form, store, halaman sukses
│   │   ├── StatusCheckController.php      # cek status publik
│   │   └── Admin/
│   │       ├── Auth/AdminLoginController.php
│   │       ├── AdminDashboardController.php
│   │       ├── AdminParticipantController.php      # index/detail/bukti/hapus
│   │       ├── ParticipantVerificationController.php # approve/reject
│   │       ├── AdminEventController.php
│   │       └── AdminRaceCategoryController.php
│   ├── Requests/
│   │   ├── StoreRegistrationRequest.php   # validasi form pendaftaran
│   │   ├── CheckRegistrationRequest.php
│   │   ├── AdminLoginRequest.php
│   │   ├── RejectPaymentRequest.php
│   │   ├── StoreEventRequest.php / UpdateEventRequest.php
│   │   └── StoreRaceCategoryRequest.php / UpdateRaceCategoryRequest.php
│   └── Rules/PhoneNumber.php              # normalisasi & validasi nomor HP ID
├── Mail/RegistrationConfirmedMail.php     # ShouldQueue
├── Models/                                 # User, Event, RaceCategory,
│                                           # Participant, Payment, ActivityLog
└── Services/
    ├── RegistrationService.php            # transaksi pendaftaran+payment
    └── PaymentService.php                 # approve/reject idempotent + BIB

database/
├── migrations/2026_01_01_*                # events, race_categories, participants,
│                                          # payments, activity_logs
└── seeders/                               # AdminSeeder, EventSeeder

resources/views/
├── layouts/{public,admin}.blade.php
├── components/{status-badge,flash-alert,form-field,confirm-modal}.blade.php
├── home.blade.php  register/create.blade.php  registration/success.blade.php
├── check/index.blade.php
└── admin/{auth,dashboard,participants,events,race-categories}/...

routes/web.php                             # route grouping public + admin (named routes)
config/registration.php                    # batas upload, metode bayar, jersey size
lang/id/validation.php                     # pesan error Bahasa Indonesia
tests/Feature/                             # 26 feature tests
```

---

## 4. Cara Menjalankan

### Prasyarat
- PHP >= 8.2 (ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `zip`)
- Composer
- Node.js >= 20 & NPM
- MySQL 8 (atau SQLite untuk development cepat)

### Langkah Instalasi

```bash
# 1. Clone / download project
git clone <url-repo> running-event
cd running-event

# 2. Install dependency PHP
composer install

# 3. Salin file environment
copy .env.example .env        # Windows
cp .env.example .env          # Linux/Mac

# 4. Generate application key
php artisan key:generate

# 5. Konfigurasi database MySQL di .env (lihat bagian 5),
#    lalu jalankan migrasi + seeder
php artisan migrate --seed

# 6. Install & build aset frontend
npm install
npm run build

# 7. Storage link (opsional; bukti pembayaran disimpan privat sehingga
#    TIDAK memerlukan storage:link — hanya jika ingin menambah aset publik)
php artisan storage:link

# 8. Jalankan aplikasi
php artisan serve
```

Buka `http://localhost:8000`.

### Konfigurasi Email SMTP (.env)

Jangan hardcode credential SMTP di source code. Contoh konfigurasi:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io      # ganti dengan provider Anda
MAIL_PORT=2525
MAIL_USERNAME=                  # dari dashboard provider
MAIL_PASSWORD=                  # dari dashboard provider — jangan di-commit!
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@runevent.test"
MAIL_FROM_NAME="${APP_NAME}"
```

Email dikirim melalui queue (`QUEUE_CONNECTION=database`). Jalankan worker:

```bash
php artisan queue:work
```

> Saat development, `MAIL_MAILER=log` cukup untuk melihat isi email di `storage/logs/laravel.log`.

### Akun Admin Default (development)

| Field    | Nilai              |
|----------|--------------------|
| URL      | `/admin/login`     |
| Email    | `admin@example.com`|
| Password | `password`         |

> ⚠️ **PENTING:** Credential default ini hanya untuk development. **Wajib diganti** sebelum production — login lalu ubah password, atau ubah `AdminSeeder` sebelum seeding server production.

---

## 5. Contoh Konfigurasi `.env`

```env
APP_NAME="KATAR FUN RUN REG"
APP_ENV=production          # local saat development
APP_KEY=                    # php artisan key:generate
APP_DEBUG=false             # true hanya saat development
APP_URL=https://runevent.example.com

APP_LOCALE=id
APP_FALLBACK_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=running_event
DB_USERNAME=running_event_user
DB_PASSWORD=                # isi password DB Anda

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local

PAYMENT_PROOF_MAX_KB=2048   # batas ukuran bukti pembayaran (KB)

# --- SMTP (lihat bagian 4) ---
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

> Alternatif development cepat tanpa MySQL: biarkan `DB_CONNECTION=sqlite`; Laravel otomatis memakai `database/database.sqlite`.

---

## 6. Alur Bisnis

```
Peserta                          Admin
────────                         ─────
Landing Page ──► Pilih Kategori
      │
Isi Form + Upload Bukti Bayar
      │
Submit ──► REG-2026-XXXXX (pending)
      │                            Login ──► Dashboard
Cek Status (/check-registration)   │
      │                       Daftar Peserta ──► Detail ──► Cek Bukti
Status: Menunggu Verifikasi ◄────┼──── Approve ──► BIB Number dibuat (locked)
      │                          │           └─► Email BIB dikirim (queue)
Email berisi BIB Number ◄────────┘      Reject ──► alasan wajib, tanpa BIB
      │
Tunjukkan BIB saat ambil race pack
```

---

## 7. Keamanan

- CSRF protection di semua form POST (`@csrf`).
- Validasi server-side via Form Request; MIME + ekstensi file bukti divalidasi (`jpg,jpeg,png`, maksimal `PAYMENT_PROOF_MAX_KB`).
- Bukti pembayaran disimpan di **disk privat** (`storage/app/private`) dan hanya diakses lewat route admin `GET /admin/participants/{id}/payment-proof`.
- Rate limiting: `10 req/menit/IP` untuk POST cek status; `5 req/menit` untuk percobaan login admin.
- Password hashing (bcrypt), proteksi SQL injection lewat Eloquent/Query Builder, escaping output Blade (anti-XSS).
- Semua route `/admin/*` (kecuali login) dilindungi middleware `auth`.

---

## 8. Menjalankan Test

```bash
php artisan test          # atau: vendor/bin/phpunit
```

Test mencakup (26 test):
- Pendaftaran sukses, validasi gagal, format Registration Code, nominal kurang, kuota penuh.
- Cek status via email/nomor HP (termasuk normalisasi `08xx` → `628xx`), alasan penolakan tampil, data sensitif disembunyikan.
- Guest tidak bisa akses dashboard, admin login sukses/gagal.
- Approve via HTTP: status, verified_by/at, BIB `5K-0001`.
- BIB unik & sekuensial per kategori, approval ganda idempotent (hanya 1 email).
- Reject: tanpa BIB, tanpa email, approve-setelah-reject diblokir.
- Audit log tertulis, halaman detail/list admin render, download bukti privat.

---

## 9. Catatan Production

1. Ganti semua credential default (admin, database, SMTP).
2. Set `APP_DEBUG=false`, `APP_ENV=production`, `APP_URL` sesuai domain.
3. Jalankan `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
4. Jalankan queue worker via supervisor/systemd: `php artisan queue:work --tries=3`.
5. Backup `storage/app/private/payment-proofs/` bersama database.
"# Running-Event-Registration-System" 
