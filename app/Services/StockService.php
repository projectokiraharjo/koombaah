<?php

namespace App\Services;

use App\Enums\ReferensiStok;
use App\Enums\TipePergerakan;
use App\Exceptions\StokTidakCukupException;
use App\Models\FinishedProduct;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\Format;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * SATU-SATUNYA pintu untuk mengubah stok (PRD B.1.7).
 * Nanti dipakai juga oleh Purchasing (masuk), Produksi (keluar & masuk), dan Sales (keluar).
 */
class StockService
{
    public function catat(
        RawMaterial|FinishedProduct $item,
        TipePergerakan $tipe,
        string|float $jumlah,
        ReferensiStok $referensi,
        ?int $referensiId = null,
        ?string $catatan = null,
        ?User $oleh = null,
    ): StockMovement {
        if ((float) $jumlah <= 0) {
            throw new InvalidArgumentException('Jumlah harus lebih dari 0.');
        }

        if ($referensi === ReferensiStok::PenyesuaianManual && blank($catatan)) {
            throw new InvalidArgumentException('Penyesuaian manual wajib disertai catatan alasan.');
        }

        return DB::transaction(function () use ($item, $tipe, $jumlah, $referensi, $referensiId, $catatan, $oleh) {
            // Kunci baris agar dua transaksi bersamaan tidak saling menimpa stok
            $item = $item->newQuery()->lockForUpdate()->findOrFail($item->getKey());

            if ($tipe === TipePergerakan::Keluar && round((float) $item->stok_saat_ini - (float) $jumlah, 3) < 0) {
                $nama = $item instanceof RawMaterial ? $item->nama_bahan : $item->nama_produk;
                $satuan = $item instanceof RawMaterial ? $item->satuan->value : $item->satuan_jual->value;

                throw new StokTidakCukupException(sprintf(
                    'Stok %s tidak cukup: tersedia %s %s, diminta %s %s.',
                    $nama, Format::angka($item->stok_saat_ini), $satuan, Format::angka($jumlah), $satuan,
                ));
            }

            $tipe === TipePergerakan::Masuk
                ? $item->increment('stok_saat_ini', $jumlah)
                : $item->decrement('stok_saat_ini', $jumlah);

            return $item->stockMovements()->create([
                'tipe_pergerakan' => $tipe,
                'jumlah' => $jumlah,
                'referensi_tipe' => $referensi,
                'referensi_id' => $referensiId,
                'catatan' => $catatan,
                'dilakukan_oleh' => ($oleh ?? auth()->user())->id,
            ]);
        });
    }
}