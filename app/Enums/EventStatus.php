<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Open => 'Dibuka',
            self::Closed => 'Ditutup',
            self::Completed => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-100 text-gray-700 border-gray-300',
            self::Open => 'bg-green-50 text-green-700 border-green-300',
            self::Closed => 'bg-yellow-50 text-yellow-700 border-yellow-300',
            self::Completed => 'bg-blue-50 text-blue-700 border-blue-300',
        };
    }
}
