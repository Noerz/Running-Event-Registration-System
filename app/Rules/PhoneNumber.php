<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    /**
     * Validasi format nomor HP Indonesia:
     * diawali 08/628/+62, panjang 9-16 digit setelah normalisasi.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = self::normalize((string) $value);

        if (! preg_match('/^62[2-9]\d{7,13}$/', $normalized)) {
            $fail(':attribute harus berupa nomor HP Indonesia yang valid (contoh: 081234567890).');
        }
    }

    /**
     * Normalisasi ke format internasional tanpa tanda plus, contoh: 6281234567890.
     */
    public static function normalize(?string $value): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $value);

        if ($digits === '' || $digits === null) {
            return '';
        }

        return match (true) {
            str_starts_with($digits, '620') => '62'.substr($digits, 3),
            str_starts_with($digits, '62') => $digits,
            str_starts_with($digits, '0') => '62'.substr($digits, 1),
            str_starts_with($digits, '8') => '62'.$digits,
            default => $digits,
        };
    }
}
