<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_roles', 'admin_id', 'role_id')->withTimestamps();
    }

    /**
     * @return Collection<int, Role>
     */
    public function activeRoles(): Collection
    {
        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();

        return $roles->where('is_active', true)->values();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function permissions(): Collection
    {
        $roles = $this->relationLoaded('roles')
            ? $this->roles->loadMissing('permissions')
            : $this->roles()->with('permissions')->get();

        return $roles
            ->where('is_active', true)
            ->flatMap(fn (Role $role) => $role->permissions)
            ->unique('code')
            ->values();
    }

    public function isSuperAdmin(): bool
    {
        return $this->activeRoles()->contains(fn (Role $role) => $role->isSuperAdmin());
    }

    public function hasRole(string ...$codes): bool
    {
        return $this->activeRoles()->pluck('code')->intersect($codes)->isNotEmpty();
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_admin) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->permissions()->contains('code', $permission);
    }

    public function roleNames(): string
    {
        return $this->activeRoles()->pluck('name')->join(', ') ?: '—';
    }
}
