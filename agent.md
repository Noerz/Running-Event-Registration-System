# PROMPT: Running Event Registration System

Buatkan sebuah aplikasi website **Running Event Registration System** menggunakan **Laravel dan MySQL**. Aplikasi digunakan untuk mengelola pendaftaran peserta event lari, proses pembayaran, verifikasi admin, penerbitan nomor peserta/BIB Number, dan pengiriman nomor peserta melalui email.

Buat aplikasi dengan tampilan **modern, responsive, clean, dan profesional** yang nyaman digunakan melalui desktop maupun smartphone.

## 1. Teknologi

Gunakan teknologi berikut:

* Backend: **Laravel**
* Database: **MySQL**
* Frontend: **Blade Template**
* Styling: **Tailwind CSS**
* JavaScript: Alpine.js jika diperlukan
* Authentication: Laravel Authentication
* Email: Laravel Mail / Mailable
* Database ORM: Eloquent
* Validation: Laravel Form Request Validation
* File Storage: Laravel Storage
* Pagination: Laravel Pagination

Gunakan struktur kode Laravel yang rapi dan mengikuti best practice seperti:

* Controller
* Model
* Migration
* Middleware
* Form Request
* Service jika diperlukan
* Mail/Mailable
* Blade Components
* Route grouping
* Database transaction untuk proses penting

---

# 2. Role Aplikasi

Aplikasi mempunyai dua sisi utama:

### User / Public

User tidak wajib membuat akun.

User dapat:

1. Melihat informasi event lari.
2. Melihat kategori perlombaan.
3. Melakukan pendaftaran.
4. Mengisi biodata.
5. Memilih kategori perlombaan.
6. Mengupload bukti pembayaran.
7. Mengecek status pendaftaran.
8. Mendapatkan nomor peserta setelah pembayaran dikonfirmasi admin.

### Admin

Admin wajib login.

Admin dapat:

1. Melihat dashboard.
2. Melihat daftar peserta.
3. Melihat detail peserta.
4. Melihat bukti pembayaran.
5. Mengkonfirmasi pembayaran.
6. Menolak pembayaran.
7. Memberikan nomor peserta secara otomatis.
8. Mengirim nomor peserta ke email peserta.
9. Melakukan pencarian dan filter peserta.
10. Melihat statistik pendaftaran.

---

# 3. Halaman User / Public

## Landing Page

Buat halaman utama yang menampilkan:

* Navbar
* Logo/nama Running Event Registration System
* Hero section event
* Nama event
* Tanggal event
* Lokasi
* Deskripsi event
* Countdown menuju event jika memungkinkan
* Daftar kategori perlombaan
* Harga pendaftaran
* Tombol "Daftar Sekarang"
* Tombol "Cek Status Pendaftaran"
* Informasi tata cara pendaftaran
* Informasi pembayaran
* Footer

Contoh kategori:

* 5K
* 10K
* Half Marathon 21K

Kategori tersebut harus berasal dari database agar nantinya dapat dikelola secara dinamis.

---

# 4. Form Pendaftaran Peserta

Ketika user menekan **Daftar Sekarang**, tampilkan form pendaftaran.

Data yang perlu dimasukkan:

### Data Pribadi

* Nama lengkap
* Nomor HP / WhatsApp
* Email
* Jenis kelamin
* Tempat lahir
* Tanggal lahir
* Usia
* Nomor identitas / NIK (opsional jika diperlukan)
* Alamat lengkap
* Kota/Kabupaten
* Provinsi

### Data Perlombaan

* Kategori lomba
* Ukuran jersey
* Nama emergency contact
* Nomor HP emergency contact
* Golongan darah jika diperlukan
* Nama komunitas / running club (opsional)

### Pembayaran

* Metode pembayaran
* Nominal pembayaran
* Upload bukti pembayaran

Format bukti pembayaran:

* JPG
* JPEG
* PNG

Berikan batas maksimal ukuran file.

Tambahkan checkbox:

**"Saya menyatakan bahwa data yang saya masukkan benar dan menyetujui syarat dan ketentuan event."**

User harus mencentangnya sebelum melakukan pendaftaran.

---

# 5. Nomor Registrasi

Setelah form berhasil dikirim, sistem harus membuat **Registration Code** otomatis.

Contoh:

`REG-2026-00001`

Nomor ini berbeda dengan nomor peserta/BIB.

Registration Code diberikan langsung setelah user melakukan pendaftaran.

Sedangkan **BIB Number / Nomor Peserta hanya diberikan setelah pembayaran diverifikasi admin.**

Setelah registrasi berhasil tampilkan halaman:

### Pendaftaran Berhasil

Terima kasih, pendaftaran Anda berhasil.

Registration Code:
`REG-2026-00001`

Status:
`Menunggu Verifikasi Pembayaran`

Berikan tombol:

* Cek Status
* Kembali ke Beranda

---

# 6. Cek Status Pendaftaran

Buat halaman:

`/check-registration`

User dapat mencari pendaftaran menggunakan salah satu:

* Email
* Nomor HP

Email dan nomor HP digunakan sebagai data identifikasi pencarian.

Setelah user memasukkan data, tampilkan informasi:

* Registration Code
* Nama peserta
* Email
* Nomor HP
* Kategori lomba
* Tanggal pendaftaran
* Status pembayaran
* Status pendaftaran
* Nomor peserta / BIB Number

Status dapat berupa:

### Pending

`Menunggu verifikasi pembayaran`

### Approved

`Pembayaran berhasil dikonfirmasi`

Kemudian tampilkan:

`Nomor Peserta: BIB-5K-0001`

### Rejected

`Pembayaran ditolak`

Jika ditolak, tampilkan alasan penolakan dari admin dan berikan instruksi kepada peserta.

Jangan tampilkan informasi sensitif yang tidak diperlukan pada halaman pengecekan status.

---

# 7. Admin Authentication

Buat halaman:

`/admin/login`

Admin login menggunakan:

* Email
* Password

Semua halaman `/admin/*` selain login harus dilindungi authentication middleware.

User biasa tidak boleh mengakses halaman admin.

---

# 8. Dashboard Admin

Buat dashboard admin yang menampilkan statistik:

* Total peserta
* Total pendaftaran hari ini
* Total peserta pending
* Total peserta approved
* Total pembayaran ditolak
* Jumlah peserta per kategori

Contoh:

Total Peserta: 1.250

5K: 500

10K: 450

21K: 300

Tambahkan chart sederhana jika memungkinkan.

---

# 9. Manajemen Peserta

Buat halaman:

`/admin/participants`

Gunakan tabel dengan kolom:

* Registration Code
* Nama
* Nomor HP
* Email
* Kategori
* Tanggal daftar
* Status pembayaran
* BIB Number
* Action

Action:

* Detail
* Approve
* Reject

Tambahkan:

* Search nama
* Search email
* Search nomor HP
* Filter kategori
* Filter status
* Pagination

---

# 10. Detail Peserta

Halaman:

`/admin/participants/{id}`

Tampilkan seluruh informasi peserta:

### Personal Information

* Nama
* Email
* Nomor HP
* Jenis kelamin
* Tanggal lahir
* Usia
* Alamat

### Race Information

* Kategori
* Jersey
* Running Club
* Emergency Contact

### Payment Information

* Metode pembayaran
* Nominal
* Bukti pembayaran
* Status pembayaran

Admin dapat melihat/download bukti pembayaran sesuai hak akses.

Sediakan tombol:

**Approve Payment**

dan

**Reject Payment**

---

# 11. Approval Pembayaran

Ketika admin menekan:

**Approve Payment**

tampilkan confirmation modal.

Jika dikonfirmasi, sistem harus melakukan proses berikut dalam satu transaction:

1. Update payment status menjadi `approved`.
2. Update registration status menjadi `confirmed`.
3. Generate BIB Number.
4. Simpan BIB Number ke database.
5. Catat admin yang melakukan approval.
6. Catat waktu approval.
7. Kirim email kepada peserta.

Pastikan proses approval bersifat **idempotent**, sehingga klik ulang atau request ganda tidak menghasilkan dua BIB Number.

---

# 12. Generate BIB Number

BIB Number harus unik.

Contoh:

### 5K

`5K-0001`

`5K-0002`

### 10K

`10K-0001`

`10K-0002`

### Half Marathon

`21K-0001`

`21K-0002`

Nomor harus dibuat berdasarkan kategori.

Gunakan mekanisme yang aman terhadap race condition, misalnya database transaction + locking atau sequence/counter per kategori.

Tambahkan UNIQUE constraint pada kolom BIB Number.

---

# 13. Email Notification

Setelah admin mengkonfirmasi pembayaran, kirim email otomatis.

Subject:

**Pendaftaran Running Event Berhasil Dikonfirmasi**

Isi email kurang lebih:

Halo [Nama Peserta],

Pembayaran Anda telah berhasil diverifikasi.

Detail pendaftaran:

Nama:
[Nama]

Kategori:
[5K / 10K / 21K]

Registration Code:
[Registration Code]

Nomor Peserta / BIB:
[BIB Number]

Tanggal Event:
[Tanggal]

Lokasi:
[Lokasi]

Simpan nomor peserta Anda dan tunjukkan ketika melakukan pengambilan race pack.

Terima kasih telah berpartisipasi.

Running Event Registration System

Gunakan Laravel Mail / Mailable.

Konfigurasikan email menggunakan `.env`.

Jangan hardcode SMTP credential di source code.

Jika memungkinkan gunakan Laravel Queue untuk pengiriman email agar proses approval tidak lambat.

---

# 14. Reject Payment

Admin juga dapat menolak pembayaran.

Ketika admin memilih **Reject**, admin wajib memasukkan alasan.

Contoh:

* Bukti pembayaran tidak valid.
* Nominal pembayaran tidak sesuai.
* Bukti transfer tidak terbaca.
* Pembayaran belum diterima.

Status menjadi:

`rejected`

User dapat melihat alasan tersebut melalui halaman cek status.

Jangan generate BIB Number jika pembayaran ditolak.

---

# 15. Database Design

Buat database minimal dengan tabel:

### admins / users

* id
* name
* email
* password
* timestamps

### events

* id
* name
* slug
* description
* event_date
* location
* registration_start
* registration_end
* status
* timestamps

### race_categories

* id
* event_id
* name
* code
* distance
* price
* quota
* bib_counter
* status
* timestamps

### participants

* id
* event_id
* race_category_id
* registration_code
* bib_number
* full_name
* phone
* email
* gender
* birth_place
* birth_date
* age
* identity_number nullable
* address
* city
* province
* jersey_size
* running_club nullable
* emergency_contact_name
* emergency_contact_phone
* registration_status
* timestamps

### payments

* id
* participant_id
* payment_method
* amount
* payment_proof
* payment_status
* rejection_reason nullable
* verified_by nullable
* verified_at nullable
* timestamps

Gunakan foreign key dengan benar.

Tambahkan index untuk kolom yang sering digunakan untuk pencarian seperti:

* email
* phone
* registration_code
* bib_number
* payment_status

Tambahkan unique constraint untuk:

* registration_code
* bib_number

Tentukan aturan yang masuk akal jika satu email/nomor HP boleh mendaftarkan lebih dari satu peserta atau event. Jangan menjadikan email/nomor HP globally unique apabila sistem perlu mendukung event berikutnya.

---

# 16. Security

Implementasikan keamanan Laravel dengan benar:

* CSRF protection
* XSS protection
* Server-side validation
* Authentication middleware
* Authorization
* Password hashing
* File upload validation
* Rate limiting pada halaman pengecekan status
* SQL Injection protection melalui Eloquent/Query Builder
* Validasi MIME type file
* Batasi ukuran upload
* Jangan expose file bukti pembayaran secara public tanpa kontrol akses

Gunakan Laravel Storage untuk file upload.

---

# 17. Validation

Contoh validation:

Nama:

* required
* string
* max 255

Email:

* required
* valid email

Nomor HP:

* required
* valid format
* normalisasi format nomor sebelum disimpan jika diperlukan

Tanggal lahir:

* required
* date
* tidak boleh melebihi tanggal hari ini

Kategori:

* required
* harus tersedia dan aktif

Bukti pembayaran:

* required
* image (jpg, jpeg, png)
* maksimal sesuai konfigurasi aplikasi

Emergency contact:

* required

Tambahkan pesan validation error dalam Bahasa Indonesia.

---

# 18. UI / UX

Gunakan desain modern dengan Tailwind CSS.

Tema visual:

* Running / Marathon
* Energetic
* Professional
* Modern

Halaman user harus mobile-friendly karena kemungkinan besar peserta mendaftar melalui smartphone.

Gunakan komponen:

* Navbar
* Hero
* Cards
* Form
* Badge status
* Alert
* Modal confirmation
* Responsive table
* Pagination
* Loading state
* Empty state

Status badge:

Pending → kuning

Approved → hijau

Rejected → merah

---

# 19. Admin Layout

Buat layout admin dengan:

### Sidebar

* Dashboard
* Participants
* Events
* Race Categories
* Logout

### Topbar

Tampilkan:

* Nama admin
* Profile
* Logout

Sidebar harus responsive.

---

# 20. Event Management

Buat CRUD sederhana untuk admin agar dapat mengatur event:

* Nama event
* Deskripsi
* Tanggal
* Lokasi
* Registration start
* Registration end
* Status

Status:

* Draft
* Open
* Closed
* Completed

User hanya dapat mendaftar jika registration period masih aktif dan event berstatus `open`.

---

# 21. Race Category Management

Admin dapat mengelola kategori:

* Nama kategori
* Code
* Distance
* Harga
* Kuota peserta
* Status

Contoh:

5K Fun Run
Code: `5K`

10K Run
Code: `10K`

Half Marathon
Code: `21K`

Sistem harus mencegah pendaftaran jika kuota kategori sudah penuh.

---

# 22. Seeder

Buat database seeder.

Buat akun admin default untuk development.

Contoh:

Name:
`Administrator`

Email:
`admin@example.com`

Password development:
`password`

Pastikan dijelaskan bahwa credential default harus diganti untuk production.

Buat juga sample:

* 1 Running Event
* kategori 5K
* kategori 10K
* kategori 21K

---

# 23. Routing

Contoh struktur route:

### Public

`GET /`

`GET /register`

`POST /register`

`GET /registration/success/{registrationCode}`

`GET /check-registration`

`POST /check-registration`

### Admin

`GET /admin/login`

`POST /admin/login`

`POST /admin/logout`

`GET /admin/dashboard`

`GET /admin/participants`

`GET /admin/participants/{id}`

`POST /admin/participants/{id}/approve`

`POST /admin/participants/{id}/reject`

Tambahkan route untuk Event Management dan Race Category Management.

Gunakan route name dengan konsisten.

---

# 24. Business Flow

Flow utama aplikasi harus seperti berikut:

### Participant

Landing Page

↓

Pilih Event / Kategori

↓

Isi Form Pendaftaran

↓

Upload Bukti Pembayaran

↓

Submit

↓

Generate Registration Code

↓

Status Pending

↓

Admin melakukan verifikasi

↓

Jika Approved

↓

Generate BIB Number

↓

Kirim email

↓

Peserta dapat cek status

↓

Nomor peserta ditampilkan

---

### Admin

Login

↓

Dashboard

↓

Daftar Peserta

↓

Detail Peserta

↓

Cek Bukti Pembayaran

↓

Approve / Reject

↓

Jika Approve

↓

Generate BIB

↓

Email dikirim ke peserta

---

# 25. Logging

Buat audit/log sederhana untuk aktivitas penting seperti:

* Admin approve pembayaran
* Admin reject pembayaran
* BIB Number dibuat
* Email notification dikirim/gagal

Jika memungkinkan buat tabel:

`activity_logs`

dengan:

* id
* admin_id
* action
* subject_type
* subject_id
* description
* created_at

---

# 26. Testing

Buat Feature Test minimal untuk:

* Participant dapat melakukan pendaftaran.
* Validation form berjalan.
* Registration Code berhasil dibuat.
* Admin tidak terautentikasi tidak dapat membuka dashboard.
* Admin dapat login.
* Admin dapat approve pembayaran.
* BIB Number dibuat setelah approval.
* BIB Number tidak duplicate.
* Approval kedua tidak membuat BIB baru.
* Rejected payment tidak mendapatkan BIB.
* User dapat mengecek status menggunakan email/nomor HP.
* Email/Mailable dipanggil setelah approval.

Gunakan Laravel testing tools seperti PHPUnit/Pest sesuai setup project.

---

# 27. README

Buat README lengkap yang menjelaskan cara menjalankan project.

Minimal:

1. Clone/download project.
2. `composer install`
3. Copy `.env.example` menjadi `.env`.
4. Konfigurasi database MySQL.
5. `php artisan key:generate`
6. `php artisan migrate --seed`
7. `npm install`
8. `npm run build`
9. `php artisan storage:link` jika memang diperlukan untuk asset public yang aman.
10. Konfigurasi email SMTP.
11. Jalankan queue worker jika menggunakan queue.
12. `php artisan serve`

Berikan contoh konfigurasi `.env` tanpa password/secret asli.

---

# 28. Output yang Saya Inginkan

Jangan hanya memberikan penjelasan atau pseudo-code.

Saya ingin kamu **membangun source code aplikasi secara bertahap dan lengkap**.

Mulai dengan:

1. Analisis requirement.
2. Struktur database dan ERD dalam bentuk Mermaid.
3. Struktur folder/file penting.
4. Migration.
5. Model dan relationship.
6. Seeder.
7. Authentication admin.
8. Public controller.
9. Registration flow.
10. Payment flow.
11. Admin controller.
12. Approval/rejection.
13. BIB Number generator.
14. Email notification.
15. Routes.
16. Blade templates.
17. Tailwind styling.
18. Validation.
19. Security.
20. Testing.
21. README.

Untuk setiap file source code, selalu tuliskan **lokasi/path file** sebelum source code.

Contoh:

`app/Models/Participant.php`

kemudian berikan source code lengkap file tersebut.

Jangan memberikan potongan kode yang tidak jelas harus diletakkan di mana.

Pastikan seluruh kode saling terintegrasi dan dapat dijalankan.

---

# 29. Coding Standard

Gunakan:

* Laravel conventions
* Clean Code
* Service layer untuk business logic yang kompleks
* Eloquent relationships
* Dependency Injection
* Form Request Validation
* Named Routes
* Database Transactions
* Enum PHP atau Laravel casts untuk status jika sesuai
* Reusable Blade Components

Hindari:

* Query database langsung di Blade.
* Business logic besar di route.
* Controller yang terlalu besar.
* Hardcoded credential.
* Duplicate code.
* BIB Number yang dibuat hanya berdasarkan `count() + 1`, karena berisiko duplicate pada concurrent request.

---

# 30. Catatan Penting

Aplikasi harus mempunyai dua area yang jelas:

**PUBLIC / USER**

Digunakan peserta untuk melakukan pendaftaran dan mengecek status.

**ADMIN**

Digunakan panitia untuk mengelola peserta dan melakukan verifikasi pembayaran.

Peserta **tidak perlu login**.

Admin **wajib login**.

BIB Number hanya boleh diterbitkan ketika pembayaran sudah di-approve admin.

Setelah BIB diterbitkan:

* BIB disimpan di database.
* BIB ditampilkan pada halaman pengecekan status.
* BIB dikirim ke email peserta.

Pastikan sistem aman terhadap duplicate approval dan duplicate BIB Number.

Prioritaskan aplikasi yang **benar-benar dapat dijalankan**, bukan sekadar demonstrasi kode.

Gunakan Bahasa Indonesia untuk teks UI aplikasi, sedangkan penamaan class, function, variable, database table, dan source code menggunakan Bahasa Inggris agar mengikuti coding convention.
