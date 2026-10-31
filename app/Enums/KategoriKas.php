<?php

namespace App\Enums;

enum KategoriKas: string
{
    case Penjualan = 'penjualan';
    case PembelianBahanBaku = 'pembelian_bahan_baku';
    case Gaji = 'gaji';
    case Distribusi = 'distribusi';
    case OperasionalLain = 'operasional_lain';

    public function label(): string
    {
        return match ($this) {
            self::Penjualan => 'Penjualan',
            self::PembelianBahanBaku => 'Pembelian Bahan Baku',
            self::Gaji => 'Gaji',
            self::Distribusi => 'Distribusi',
            self::OperasionalLain => 'Operasional Lain',
        };
    }
}