<?php

namespace App\Enums;

enum ReferensiStok: string
{
    case Purchasing = 'purchasing';
    case Produksi = 'produksi';
    case Sales = 'sales';
    case PenyesuaianManual = 'penyesuaian_manual';

    public function label(): string
    {
        return match ($this) {
            self::Purchasing => 'Purchasing',
            self::Produksi => 'Produksi',
            self::Sales => 'Sales',
            self::PenyesuaianManual => 'Penyesuaian Manual',
        };
    }
}