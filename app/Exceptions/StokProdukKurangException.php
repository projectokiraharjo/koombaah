<?php

namespace App\Exceptions;

use App\Support\Format;
use DomainException;

class StokProdukKurangException extends DomainException
{
    /** @param array<int, array{nama: string, kurang: float, satuan: string}> $kekurangan */
    public function __construct(public readonly array $kekurangan)
    {
        $daftar = collect($kekurangan)
            ->map(fn ($k) => "{$k['nama']} kurang " . Format::angka($k['kurang']) . " {$k['satuan']}")
            ->implode('; ');

        parent::__construct("Order tidak dapat dikonfirmasi — stok produk jadi tidak cukup: {$daftar}. Tunggu batch produksi berikutnya atau sesuaikan jumlah order.");
    }
}