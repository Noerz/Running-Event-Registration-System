<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    public const SUPER_ADMIN = 'super-admin';
    public const ADMIN = 'admin';
    public const VIEWER = 'viewer';

    public const DEFAULTS = [
        [
            'code' => self::SUPER_ADMIN,
            'name' => 'Super Admin',
            'description' => 'Akses penuh ke seluruh sistem dan pengelolaan permission.',
            'is_system' => true,
            'permissions' => '*',
        ],
        [
            'code' => self::ADMIN,
            'name' => 'Admin',
            'description' => 'Akses operasional (peserta, event, kategori, pembayaran). Tidak bisa kelola role/admin.',
            'is_system' => false,
            'permissions' => [
                'dashboard.view',
                'participants.view', 'participants.update', 'participants.delete', 'participants.verify',
                'events.view', 'events.create', 'events.update', 'events.delete',
                'race_categories.view', 'race_categories.create', 'race_categories.update', 'race_categories.delete',
                'payment_settings.view', 'payment_settings.update',
            ],
        ],
        [
            'code' => self::VIEWER,
            'name' => 'Viewer',
            'description' => 'Hanya melihat data tanpa izin menambah, mengubah, atau menghapus.',
            'is_system' => false,
            'permissions' => [
                'dashboard.view',
                'participants.view',
                'events.view',
                'race_categories.view',
                'payment_settings.view',
                'users.view',
                'roles.view',
            ],
        ],
    ];

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission')->withTimestamps();
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_roles', 'role_id', 'admin_id')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->code === self::SUPER_ADMIN;
    }
}
