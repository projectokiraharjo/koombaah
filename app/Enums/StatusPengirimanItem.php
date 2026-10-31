<?php

namespace App\Enums;

enum StatusPengirimanItem: string
{
    case Menunggu = 'menunggu';
    case Terkirim = 'terkirim';
    case GagalKirim = 'gagal_kirim';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu',
            self::Terkirim => 'Terkirim',
            self::GagalKirim => 'Gagal Kirim',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Menunggu => 'outline',
            self::Terkirim => 'secondary',
            self::GagalKirim => 'destructive',
        };
    }
}