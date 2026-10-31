<?php

namespace App\Enums;

enum StatusJadwal: string
{
    case Dijadwalkan = 'dijadwalkan';
    case DalamPerjalanan = 'dalam_perjalanan';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Dijadwalkan => 'Dijadwalkan',
            self::DalamPerjalanan => 'Dalam Perjalanan',
            self::Selesai => 'Selesai',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Dijadwalkan => 'outline',
            self::DalamPerjalanan => 'default',
            self::Selesai => 'secondary',
        };
    }
}