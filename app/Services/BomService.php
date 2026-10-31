<?php

namespace App\Services;

use App\Models\BomHeader;
use App\Models\FinishedProduct;
use App\Models\RawMaterial;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BomService
{
    /**
     * Simpan resep sebagai VERSI BARU (PRD B.1.4-A).
     * Versi lama tidak pernah diubah — hanya dinonaktifkan — supaya riwayat batch produksi tetap akurat.
     *
     * @param  array<int, array{raw_material_id: int|string, takaran: int|float|string}>  $baris
     */
    public function simpanVersiBaru(FinishedProduct $produk, array $baris, ?string $catatan, User $oleh): BomHeader
    {
        return DB::transaction(function () use ($produk, $baris, $catatan, $oleh) {
            FinishedProduct::whereKey($produk->id)->lockForUpdate()->first();

            $versiTerakhir = (int) BomHeader::where('finished_product_id', $produk->id)->max('versi');

            // Nonaktifkan versi lama -> menjaga aturan "satu produk = satu resep aktif"
            BomHeader::where('finished_product_id', $produk->id)
                ->where('status', 'aktif')
                ->update(['status' => 'nonaktif']);

            $header = BomHeader::create([
                'finished_product_id' => $produk->id,
                'versi' => $versiTerakhir + 1,
                'status' => 'aktif',
                'catatan_perubahan' => $catatan,
                'created_by' => $oleh->id,
            ]);

            $bahan = RawMaterial::whereIn('id', array_column($baris, 'raw_material_id'))->get()->keyBy('id');

            foreach ($baris as $b) {
                $header->details()->create([
                    'raw_material_id' => $b['raw_material_id'],
                    'takaran' => $b['takaran'],
                    'satuan' => $bahan[$b['raw_material_id']]->satuan->value, // snapshot satuan
                ]);
            }

            return $header;
        });
    }
}