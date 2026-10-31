<?php

namespace App\Enums;

enum StatusPO: string
{
    case Draft = 'draft';
    case Dipesan = 'dipesan';
    case SebagianDiterima = 'sebagian_diterima';
    case DiterimaPenuh = 'diterima_penuh';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Dipesan => 'Dipesan',
            self::SebagianDiterima => 'Sebagian Diterima',
            self::DiterimaPenuh => 'Diterima Penuh',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    /** Varian badge UI */
    public function badge(): string
    {
        return match ($this) {
            self::Draft => 'outline',
            self::Dipesan => 'default',
            self::SebagianDiterima, self::DiterimaPenuh => 'secondary',
            self::Dibatalkan => 'destructive',
        };
    }

    /** Status PO yang dihitung sebagai transaksi pembelian nyata */
    public static function terkonfirmasi(): array
    {
        return [self::Dipesan->value, self::SebagianDiterima->value, self::DiterimaPenuh->value];
    }
}