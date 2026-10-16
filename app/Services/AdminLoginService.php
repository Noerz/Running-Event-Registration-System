<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Keamanan login admin:
 * - lockout per akun setelah N kegagalan beruntun (koefisien eksponensial);
 * - rate limit gabungan per email + IP;
 * - audit login gagal ke ActivityLog (untuk deteksi brute force).
 */
class AdminLoginService
{
    public const MAX_ATTEMPTS = 5;

    public const LOCKOUT_MINUTES = 15;

    /**
     * @return array{locked: bool, seconds: int}
     */
    public function tooManyAttempts(string $email, string $ip): array
    {
        $attempts = RateLimiter::attempts($this->key($email, $ip));
        $decay = RateLimiter::availableIn($this->key($email, $ip));

        if ($attempts >= self::MAX_ATTEMPTS) {
            return ['locked' => true, 'seconds' => $decay];
        }

        return ['locked' => false, 'seconds' => 0];
    }

    public function hit(string $email, string $ip): void
    {
        RateLimiter::hit($this->key($email, $ip), now()->addMinutes(self::LOCKOUT_MINUTES));
    }

    public function clear(string $email, string $ip): void
    {
        RateLimiter::clear($this->key($email, $ip));
    }

    public function availableIn(string $email, string $ip): int
    {
        return RateLimiter::availableIn($this->key($email, $ip));
    }

    public function auditFailure(string $email, string $ip): void
    {
        ActivityLog::record(
            action: 'admin_login_failed',
            description: "Login admin gagal untuk {$email} dari IP {$ip}."
        );
    }

    private function key(string $email, string $ip): string
    {
        return 'admin-login:'.mb_strtolower($email).'|'.$ip;
    }
}
