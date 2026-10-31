<?php

namespace Database\Seeders;

use App\Models\FinishedProduct;
use App\Models\RawMaterial;
use Illuminate\Database\Seeder;

/**
 * DATA DUMMY untuk menguji sistem (PRD B.1.8).
 * Nama, harga & stok minimum bukan data riil — ganti saat formulasi final.
 * Stok sengaja 0: stok hanya boleh bertambah lewat stock_movements (B.1.7).
 */
class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $bahanBaku = [
            // [nama, satuan, harga per satuan, stok minimum]
            ['LAS (Linear Alkylbenzene Sulfonate)', 'kg', 28000, 25],
            ['SLES (Sodium Lauryl Ether Sulfate)', 'kg', 32000, 25],
            ['NaOH / Soda Api', 'kg', 18000, 10],
            ['Sodium Sulfat', 'kg', 9000, 20],
            ['Garam Industri (NaCl)', 'kg', 4000, 20],
            ['Natrium Hipoklorit', 'liter', 7000, 20],
            ['Biang Pewangi (Fragrance Oil)', 'liter', 250000, 2],
            ['Pewarna Cair', 'ml', 150, 500],
            ['Pengawet', 'ml', 120, 500],
            ['Air Demineral', 'liter', 1000, 100],
        ];

        foreach ($bahanBaku as [$nama, $satuan, $harga, $min]) {
            RawMaterial::firstOrCreate(['nama_bahan' => $nama], [
                'kode_bahan' => RawMaterial::generateKode(),
                'satuan' => $satuan,
                'harga_per_satuan_terakhir' => $harga,
                'stok_minimum' => $min,
                'status' => 'aktif',
            ]);
        }

        $produkJadi = [
            // [nama, kategori, satuan jual, harga jual, stok minimum]
            ['Deterjen Cair', 'deterjen', 'liter', 18000, 20],
            ['Deterjen Bubuk', 'deterjen', 'kg', 22000, 20],
            ['Sabun Pre-Wash', 'deterjen', 'liter', 15000, 10],
            ['Pewangi Sakura', 'pewangi', 'liter', 35000, 10],
            ['Pewangi Lavender', 'pewangi', 'liter', 35000, 10],
            ['Pewangi Fresh Ocean', 'pewangi', 'liter', 35000, 10],
            ['Pelembut Pakaian', 'pelembut', 'liter', 20000, 10],
            ['Pemutih Pakaian', 'pemutih', 'liter', 12000, 10],
            ['Penghilang Noda', 'penghilang noda', 'liter', 30000, 5],
            ['Pelicin Setrika', 'pelicin', 'liter', 16000, 10],
        ];

        foreach ($produkJadi as [$nama, $kategori, $satuan, $harga, $min]) {
            FinishedProduct::firstOrCreate(['nama_produk' => $nama], [
                'kode_produk' => FinishedProduct::generateKode(),
                'kategori' => $kategori,
                'satuan_jual' => $satuan,
                'harga_jual_per_satuan' => $harga,
                'stok_minimum' => $min,
                'status' => 'aktif',
            ]);
        }
    }
}