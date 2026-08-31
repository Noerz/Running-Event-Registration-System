<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Verifikasi Pembayaran',
            self::Confirmed => 'Terdaftar (Dikonfirmasi)',
            self::Rejected => 'Pendaftaran Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'bg-yellow-50 text-yellow-700 border-yellow-300',
            self::Confirmed => 'bg-green-50 text-green-700 border-green-300',
            self::Rejected => 'bg-red-50 text-red-700 border-red-300',
        };
    }
}
