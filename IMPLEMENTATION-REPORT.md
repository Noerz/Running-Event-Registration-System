
# Laporan Implementasi Role Management & RBAC

## 1. Perubahan Frontend

- **Sidebar Admin Panel**: Menambahkan menu "Role Management" pada navigasi utama. Menu ini hanya muncul jika user memiliki permission `roles.view`.
- **Role Management Views**: Membuat halaman `admin/roles/index.blade.php` untuk menampilkan daftar role, dan `admin/roles/create.blade.php` serta `admin/roles/edit.blade.php` untuk form CRUD role.
- **Role Form (`_form.blade.php`)**:
    - Menambahkan input Nama Role, Kode Role, Deskripsi, dan status Role Aktif.
    - Menampilkan matriks permission interaktif menggunakan Alpine.js:
        - Memecah permission berdasarkan modul (DASHBOARD, EVENT, dll.).
        - Checkbox untuk setiap permission spesifik (misal: `Lihat dashboard`).
        - Tombol "Pilih semua" per modul.
        - Tombol "Pilih semua" global untuk mengaktifkan semua permission.
    - Menambahkan tombol "Simpan Role" dan "Batal".
- **Admin Management View (`admin/users/index.blade.php`)**:
    - Menambahkan kolom "ROLE" pada tabel daftar admin.
    - Menambahkan tombol "Edit" pada setiap baris admin.
    - Tombol aksi (Edit, Hapus) pada tabel admin disembunyikan jika user tidak memiliki permission terkait (misal: `admins.edit`, `admins.delete`).
- **Admin Form (`_form.blade.php`)**:
    - Menambahkan field "Role" (select dropdown) pada form create/edit admin, di mana admin hanya bisa memilih role yang tidak berstatus "Super Admin" atau role yang tidak aktif.
- **Dynamic UI**: Menu, tombol, dan halaman yang dapat diakses disesuaikan secara dinamis berdasarkan permission yang dimiliki oleh role admin yang sedang login.

## 2. Perubahan Backend

- **Models**:
    - `app/Models/User.php`: Menambahkan relasi `roles()`, `activeRoles()`, dan helper method `isSuperAdmin()`, `hasRole()`, `hasPermission()`, `roleNames()`.
    - `app/Models/Role.php`: Model untuk tabel `roles`, termasuk konstanta role default (`super-admin`, `admin`, `viewer`) dan relasi ke permission serta user.
    - `app/Models/Permission.php`: Model untuk tabel `permissions`, berisi konstanta modul dan 22 permission yang terdaftar.
- **Database Migration**:
    - `database/migrations/2026_10_31_000002_create_rbac_tables.php`: Membuat tabel `roles`, `permissions`, `role_permission`, dan `admin_roles`.
    - Tabel `roles`: Menyimpan nama, kode unik, deskripsi, status aktif, dan flag `is_system` untuk role default.
    - Tabel `permissions`: Menyimpan nama, kode unik, modul, dan deskripsi permission.
    - Tabel `role_permission`: Mapping many-to-many antara role dan permission.
    - Tabel `admin_roles`: Mapping many-to-many antara admin (User) dan role.
    - **Seeding**: Menambahkan data awal untuk 22 permission, 3 role default (`super-admin`, `admin`, `viewer`), dan otomatis men-assign `super-admin` ke admin yang sudah ada.
- **Authorization Logic**:
    - `app/Http/Middleware/EnsurePermission.php`: Middleware baru yang memvalidasi permission sebelum mengizinkan akses ke route. Akan mengembalikan 403 Forbidden jika permission tidak cocok, atau 401 Unauthorized jika tidak terautentikasi.
    - `app/Providers/AppServiceProvider.php`: Menambahkan `Gate::before` untuk bypass otorisasi jika user adalah `Super Admin` (memiliki `isSuperAdmin()`).
    - `routes/web.php`: Seluruh route di dalam grup `admin` diproteksi dengan middleware `permission:<code>` (misal: `permission:users.view`, `permission:roles.create`).
- **Controllers**:
    - `app/Http/Controllers/Admin/AdminRoleController.php`: Controller baru untuk menangani operasi CRUD pada Role Management.
    - `app/Http/Controllers/Admin/AdminUserController.php`: Diperbarui untuk mengintegrasikan pemilihan role saat membuat/mengedit admin. Menambahkan validasi untuk mencegah admin menghapus Super Admin terakhir atau mengubah role-nya sendiri.
- **Services**:
    - `app/Services/RoleService.php`: Berisi logika bisnis inti untuk operasi RBAC, termasuk validasi role yang digunakan, penonaktifan role, toggle status, dan pengelolaan permission. Melakukan audit logging via `ActivityLog` untuk setiap perubahan.
- **Request Validation**:
    - `app/Http/Requests/StoreRoleRequest.php` & `UpdateRoleRequest.php`: Form request baru untuk validasi data saat membuat/mengedit role.
    - `app/Http/Requests/StoreAdminRequest.php` & `UpdateAdminRequest.php`: Diperbarui untuk validasi field `role_id` saat membuat/mengedit admin.
- **Factories & Seeders**:
    - `database/factories/UserFactory.php`: Helper `admin()` otomatis mengaitkan role `super-admin` agar kompatibel dengan test yang ada.
    - `database/seeders/AdminSeeder.php`: Memastikan admin default memiliki role `super-admin`.
- **Routing**: Menambahkan route group `/admin/roles` untuk Role Management.

## 3. Struktur Database

Tabel baru yang ditambahkan:

- **`roles`**: Menyimpan informasi role (id, name, code, description, is_active, is_system, created_at, updated_at).
- **`permissions`**: Menyimpan informasi permission (id, name, code, module, description, created_at, updated_at).
- **`role_permission`**: Tabel pivot untuk relasi many-to-many antara `roles` dan `permissions`.
- **`admin_roles`**: Tabel pivot untuk relasi many-to-many antara `users` (admin) dan `roles`.

Perubahan pada tabel yang sudah ada:
- **`users`**: Tidak ada perubahan skema, namun relasi dan logika ditambahkan di model.

## 4. Daftar Endpoint Baru / Berubah

**Endpoint Baru (Admin - `permission:<code>`):**
- `GET /admin/roles`: Menampilkan daftar role.
- `POST /admin/roles`: Membuat role baru.
- `GET /admin/roles/create`: Menampilkan form tambah role.
- `GET /admin/roles/{role_id}/edit`: Menampilkan form edit role.
- `PUT /admin/roles/{role_id}`: Memperbarui role.
- `DELETE /admin/roles/{role_id}`: Menghapus role.
- `POST /admin/roles/{role_id}/toggle`: Mengaktifkan/menonaktifkan role.

**Endpoint Berubah (Admin):**
- `GET /admin/users`: Kolom ROLE ditambahkan pada response.
- `GET /admin/users/{user_id}`: Informasi role admin disertakan.
- `POST /admin/users`: Validasi `role_id` ditambahkan.
- `PUT /admin/users/{user_id}`: Validasi `role_id` ditambahkan, logika update role admin.

## 5. Daftar Permission yang Tersedia

Total 22 permission yang dikelompokkan per modul:

**DASHBOARD**
- `dashboard.view`

**EVENT**
- `events.view`
- `events.create`
- `events.update`
- `events.delete`

**RACE_CATEGORIES**
- `race_categories.view`
- `race_categories.create`
- `race_categories.update`
- `race_categories.delete`

**REKENING_Q ris**
- `rekening_qris.view`
- `rekening_qris.create`
- `rekening_qris.update`
- `rekening_qris.delete`

**ADMINS**
- `admins.view`
- `admins.create`
- `admins.update`
- `admins.delete`

**ROLE_MANAGEMENT**
- `roles.view`
- `roles.create`
- `roles.update`
- `roles.delete`
- `roles.toggle`

## 6. Migration dan Seeder

- **Migration**: `database/migrations/2026_10_31_000002_create_rbac_tables.php` untuk membuat tabel `roles`, `permissions`, `role_permission`, `admin_roles`.
- **Seeder**:
    - `database/seeders/PermissionSeeder.php`: Mengisi tabel `permissions` dengan 22 permission.
    - `database/seeders/RoleSeeder.php`: Mengisi tabel `roles` dengan 3 role default (`super-admin`, `admin`, `viewer`).
    - `database/seeders/AdminSeeder.php` (modifikasi): Memastikan admin default mendapatkan role `super-admin`.
    - `database/seeders/RolePermissionSeeder.php`: Mengisi tabel `role_permission` untuk memberikan permission penuh ke `super-admin`, permission dasar ke `admin`, dan permission read-only ke `viewer`.

## 7. Hasil Testing

Seluruh test suite berhasil lulus 100%.
- **Total Tests**: 73
- **Total Assertions**: 294
- **Feature Tests**: `RbacTest.php` memvalidasi 10 skenario pengujian RBAC wajib, termasuk:
    1. Super Admin memiliki akses penuh.
    2. Admin hanya dapat mengakses modul sesuai permission (`events.view`, `admins.view`).
    3. Viewer hanya dapat melakukan operasi read-only.
    4. Admin tanpa permission tidak dapat mengakses endpoint terkait (`roles.create`).
    5. Request API manual tanpa permission mendapatkan HTTP 403.
    6. Perubahan role admin langsung berlaku.
    7. Role yang masih digunakan tidak dapat dihapus.
    8. Permission tidak terdaftar tidak memberikan akses.
    9. Admin existing tetap login setelah migration.
    10. Admin tidak dapat menaikkan role/permission sendiri.
    11. Audit logging tercatat dengan benar.
- **Unit Tests**: Penyesuaian pada `UserFactory` dan `AdminUserController` memastikan kompatibilitas.

## 8. Catatan Keamanan & Potensi Improvement

**Keamanan**:
- Implementasi RBAC backend mencegah akses tidak sah (403/401) pada setiap endpoint.
- Role `super-admin` memiliki bypass gate, namun tidak dapat diubah atau dihapus untuk menjaga integritas sistem.
- Validasi pada `AdminUserController` mencegah manipulasi role yang dapat menyebabkan *privilege escalation*.
- Audit logging mencatat aktivitas penting terkait manajemen role dan admin.
- Penggunaan parameterized query / ORM (Eloquent) menghindari SQL injection.

**Potensi Improvement Lanjutan**:
- **Caching Permission**: Untuk aplikasi skala besar dengan banyak request, permission bisa di-cache (misal Redis) per user/role untuk mengurangi beban DB query pada setiap request. Perlu strategi invalidasi cache saat terjadi perubahan role/permission.
- **Role Assignment Granularitas**: Memungkinkan assignment role per admin, bukan hanya satu role utama.
- **Permission Import/Export**: Fitur untuk mengimpor/mengekspor definisi permission, memudahkan sinkronisasi antar environment atau backup.
- **UI/UX**: Penambahan fitur "Select All per Module" dan "Select All Global" pada form permission sudah diimplementasikan, namun bisa dieksplorasi lebih lanjut untuk UX yang lebih baik (misal: search permission, grouping yang lebih intuitif).
- **Dynamic API Endpoint Protection**: Otomatis melindungi endpoint baru yang ditambahkan tanpa perlu modifikasi `routes/web.php` secara manual (membutuhkan framework atau pola routing yang lebih canggih).
- **Integrasi dengan Package Audit**: Menjalankan `npm audit` atau `composer audit` secara otomatis untuk memeriksa kerentanan pada dependensi frontend/backend.
