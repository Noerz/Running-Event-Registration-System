<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever("setting:{$key}", function () use ($key, $default) {
            $value = static::query()->whereKey($key)->value('value');

            return $value ?? $default;
        });
    }

    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }

    public static function forget(string $key): void
    {
        static::query()->whereKey($key)->delete();
        Cache::forget("setting:{$key}");
    }

    protected static function storageExists(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        try {
            return \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
        } catch (\Throwable) {
            return file_exists(public_path('storage/' . ltrim($path, '/')))
                || file_exists(storage_path('app/public/' . ltrim($path, '/')));
        }
    }

    public static function getQrisUrl(): ?string
    {
        $custom = static::get('qris_image');
        if ($custom && static::storageExists($custom)) {
            return '/storage/' . ltrim($custom, '/');
        }

        $config = (string) config('registration.qris_image');
        if ($config !== '' && file_exists(public_path($config))) {
            return '/' . ltrim($config, '/');
        }

        return null;
    }

    public static function isCustomQris(): bool
    {
        return static::storageExists(static::get('qris_image'));
    }

    public static function getHeroUrl(): string
    {
        $custom = static::get('home_hero_image');
        if ($custom && static::storageExists($custom)) {
            return '/storage/' . ltrim($custom, '/');
        }

        return asset('images/hero-900.jpg');
    }

    public static function isCustomHero(): bool
    {
        return static::storageExists(static::get('home_hero_image'));
    }
}
