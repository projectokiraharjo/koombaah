<?php

namespace Database\Seeders;

use App\Models\FinishedProduct;
use App\Models\RawMaterial;
use App\Models\User;
use App\Services\BomService;
use Illuminate\Database\Seeder;

/**
 * RESEP DUMMY untuk menguji sistem (PRD B.1.8) — bukan formulasi riil.
 * Takaran = jumlah bahan untuk menghasilkan 1 satuan jual produk.
 */
class BomSeeder extends Seeder
{
    public function run(BomService $service): void
    {
        $owner = User::where('role', 'owner')->firstOrFail();

        $resep = [
            'Deterjen Cair' => [
                ['LAS (Linear Alkylbenzene Sulfonate)', 0.08],
                ['SLES (Sodium Lauryl Ether Sulfate)', 0.05],
                ['NaOH / Soda Api', 0.01],
                ['Garam Industri (NaCl)', 0.02],
                ['Pewarna Cair', 0.5],
                ['Pengawet', 1],
                ['Air Demineral', 0.8],
            ],
            'Pewangi Sakura' => [
                ['Biang Pewangi (Fragrance Oil)', 0.03],
                ['Pewarna Cair', 0.3],
                ['Pengawet', 1],
                ['Air Demineral', 0.95],
            ],
            'Pemutih Pakaian' => [
                ['Natrium Hipoklorit', 0.1],
                ['Air Demineral', 0.9],
            ],
        ];

        foreach ($resep as $namaProduk => $bahan) {
            $produk = FinishedProduct::where('nama_produk', $namaProduk)->first();

            if (! $produk || $produk->boms()->exists()) {
                continue; // jangan buat versi ganda saat seeder diulang
            }

            $baris = collect($bahan)->map(fn ($b) => [
                'raw_material_id' => RawMaterial::where('nama_bahan', $b[0])->value('id'),
                'takaran' => $b[1],
            ])->all();

            $service->simpanVersiBaru($produk, $baris, 'Resep awal (data dummy)', $owner);
        }
    }
}