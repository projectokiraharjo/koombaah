<?php

namespace App\Enums;

enum StatusBatch: string
{
    case SedangBerjalan = 'sedang_berjalan';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::SedangBerjalan => 'Sedang Berjalan',
            self::Selesai => 'Selesai',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::SedangBerjalan => 'default',
            self::Selesai => 'secondary',
            self::Dibatalkan => 'destructive',
        };
    }
}