<?php

namespace Database\Seeders;

use App\Models\RawMaterial;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

/** DATA DUMMY — nama & kontak fiktif untuk pengujian */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // [nama, kontak, telepon, alamat, bahan yang disuplai sebagai supplier utama]
            ['PT Sumber Surfaktan Nusantara', 'Ibu Rina', '0812-0000-0001', 'Kawasan Industri Cikupa, Tangerang',
                ['LAS (Linear Alkylbenzene Sulfonate)', 'SLES (Sodium Lauryl Ether Sulfate)']],
            ['CV Kimia Jaya Abadi', 'Bapak Hendra', '0812-0000-0002', 'Jl. Raya Serpong, Tangerang Selatan',
                ['NaOH / Soda Api', 'Sodium Sulfat', 'Garam Industri (NaCl)', 'Natrium Hipoklorit']],
            ['Toko Kimia Sinar Ciputat', 'Bapak Agus', '0812-0000-0003', 'Jl. Ir. H. Juanda, Ciputat',
                ['Biang Pewangi (Fragrance Oil)', 'Pewarna Cair', 'Pengawet', 'Air Demineral']],
        ];

        foreach ($data as [$nama, $kontak, $telp, $alamat, $bahan]) {
            $supplier = Supplier::firstOrCreate(['nama_supplier' => $nama], [
                'kode_supplier' => Supplier::generateKode(),
                'kontak_person' => $kontak,
                'no_telepon' => $telp,
                'alamat' => $alamat,
                'status' => 'aktif',
            ]);

            RawMaterial::whereIn('nama_bahan', $bahan)
                ->whereNull('supplier_utama_id')
                ->update(['supplier_utama_id' => $supplier->id]);
        }
    }
}