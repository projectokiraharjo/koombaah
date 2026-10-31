<?php

namespace App\Enums;

enum StatusPembayaran: string
{
    case BelumDibayar = 'belum_dibayar';
    case Lunas = 'lunas';

    public function label(): string
    {
        return $this === self::Lunas ? 'Lunas' : 'Belum Dibayar';
    }
}