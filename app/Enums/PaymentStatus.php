<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Verifikasi',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'bg-yellow-50 text-yellow-700 border-yellow-300',
            self::Approved => 'bg-green-50 text-green-700 border-green-300',
            self::Rejected => 'bg-red-50 text-red-700 border-red-300',
        };
    }
}
