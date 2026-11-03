<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    /**
     * Definisi permission default: code => [module, label].
     * Sumber tunggal agar migration & seeder tidak duplikat.
     */
    public const DEFAULTS = [
        'dashboard.view' => ['dashboard', 'Lihat dashboard'],
        'participants.view' => ['participants', 'Lihat data peserta'],
        'participants.update' => ['participants', 'Ubah data peserta'],
        'participants.delete' => ['participants', 'Hapus data peserta'],
        'participants.verify' => ['participants', 'Verifikasi / tolak pembayaran'],
        'events.view' => ['events', 'Lihat event'],
        'events.create' => ['events', 'Tambah event'],
        'events.update' => ['events', 'Ubah event'],
        'events.delete' => ['events', 'Hapus event'],
        'race_categories.view' => ['race_categories', 'Lihat kategori lomba'],
        'race_categories.create' => ['race_categories', 'Tambah kategori lomba'],
        'race_categories.update' => ['race_categories', 'Ubah kategori lomba'],
        'race_categories.delete' => ['race_categories', 'Hapus kategori lomba'],
        'payment_settings.view' => ['payment_settings', 'Lihat rekening & QRIS'],
        'payment_settings.update' => ['payment_settings', 'Ubah rekening & QRIS'],
        'users.view' => ['users', 'Lihat admin'],
        'users.create' => ['users', 'Tambah admin'],
        'users.update' => ['users', 'Ubah admin'],
        'users.delete' => ['users', 'Hapus admin'],
        'roles.view' => ['roles', 'Lihat role'],
        'roles.create' => ['roles', 'Tambah role'],
        'roles.update' => ['roles', 'Ubah role & permission'],
        'roles.delete' => ['roles', 'Hapus role'],
    ];

    public const MODULE_LABELS = [
        'dashboard' => 'Dashboard',
        'participants' => 'Peserta',
        'events' => 'Event',
        'race_categories' => 'Kategori Lomba',
        'payment_settings' => 'Rekening & QRIS',
        'users' => 'Manajemen Admin',
        'roles' => 'Role Management',
    ];

    protected $fillable = ['code', 'name', 'module'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission')->withTimestamps();
    }
}
