<?php

namespace App\Services;

use App\Models\FinishedProduct;
use App\Models\RawMaterial;
use Illuminate\Support\Collection;

class LowStockService
{
    /** Bahan baku menipis, paling kritis (persentase stok terkecil) di atas */
    public function bahanBaku(): Collection
    {
        return RawMaterial::menipis()
            ->orderByRaw('stok_saat_ini / stok_minimum')
            ->orderBy('nama_bahan')
            ->get();
    }

    public function produkJadi(): Collection
    {
        return FinishedProduct::menipis()
            ->orderByRaw('stok_saat_ini / stok_minimum')
            ->orderBy('nama_produk')
            ->get();
    }

    /** @return array{bahan_baku: int, produk_jadi: int, total: int} */
    public function jumlah(): array
    {
        $bahan = RawMaterial::menipis()->count();
        $produk = FinishedProduct::menipis()->count();

        return ['bahan_baku' => $bahan, 'produk_jadi' => $produk, 'total' => $bahan + $produk];
    }
}