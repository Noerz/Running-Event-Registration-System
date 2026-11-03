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

    public static function getQrisUrl(): ?string
    {
        $custom = static::get('qris_image');
        if ($custom && \Illuminate\Support\Facades\Storage::disk('public')->exists($custom)) {
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
        $custom = static::get('qris_image');

        return ! empty($custom) && \Illuminate\Support\Facades\Storage::disk('public')->exists($custom);
    }

    public static function getHeroUrl(): string
    {
        $custom = static::get('home_hero_image');
        if ($custom && \Illuminate\Support\Facades\Storage::disk('public')->exists($custom)) {
            return '/storage/' . ltrim($custom, '/');
        }

        return asset('images/hero-900.jpg');
    }

    public static function isCustomHero(): bool
    {
        $custom = static::get('home_hero_image');

        return ! empty($custom) && \Illuminate\Support\Facades\Storage::disk('public')->exists($custom);
    }
}
