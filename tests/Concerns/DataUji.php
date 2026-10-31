<?php

namespace Tests\Concerns;

use App\Enums\Role;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\FinishedProduct;
use App\Models\Partner;
use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Throwable;

/** Pembuat data uji + assertion bersama untuk pengujian Fase 5 */
trait DataUji
{
    protected function pada(string $waktu): void
    {
        $this->travelTo(Carbon::parse($waktu));
    }

    protected function user(Role $role, bool $aktif = true): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => $aktif]);
    }

    protected function supplier(string $nama): Supplier
    {
        return Supplier::create(['kode_supplier' => Supplier::generateKode(), 'nama_supplier' => $nama, 'status' => 'aktif']);
    }

    protected function bahan(string $nama, string $satuan, float $minimum, ?Supplier $utama = null): RawMaterial
    {
        return RawMaterial::create([
            'kode_bahan' => RawMaterial::generateKode(),
            'nama_bahan' => $nama,
            'satuan' => $satuan,
            'stok_minimum' => $minimum,
            'supplier_utama_id' => $utama?->id,
            'status' => 'aktif',
        ]);
    }

    protected function produk(string $nama, float $harga, float $minimum): FinishedProduct
    {
        return FinishedProduct::create([
            'kode_produk' => FinishedProduct::generateKode(),
            'nama_produk' => $nama,
            'kategori' => 'Pembersih',
            'satuan_jual' => 'liter',
            'harga_jual_per_satuan' => $harga,
            'stok_minimum' => $minimum,
            'status' => 'aktif',
        ]);
    }

    protected function mitra(string $nama, string $ring): Partner
    {
        return Partner::create([
            'kode_mitra' => Partner::generateKode(),
            'nama_usaha' => $nama,
            'alamat' => 'Alamat uji',
            'wilayah_ring' => $ring,
            'status' => 'aktif',
        ]);
    }

    /** Wajib dipanggil saat login sebagai Owner (data gaji terkunci di level query) */
    protected function karyawan(string $nama, float $gajiPokok): Employee
    {
        $e = Employee::create([
            'kode_karyawan' => Employee::generateKode(),
            'nama_lengkap' => $nama,
            'posisi' => 'Staf Produksi',
            'tanggal_mulai_kerja' => '2026-01-01',
            'status' => 'aktif',
        ]);
        EmployeeSalaryComponent::create([
            'employee_id' => $e->id, 'gaji_pokok' => $gajiPokok, 'tunjangan' => 0, 'berlaku_sejak' => '2026-01-01',
        ]);

        return $e;
    }

    /** Aksi HARUS gagal dengan exception tertentu (opsional: pesan mengandung teks tertentu) */
    protected function assertDitolak(callable $aksi, string $exception = DomainException::class, ?string $pesan = null): void
    {
        try {
            $aksi();
        } catch (Throwable $e) {
            $this->assertInstanceOf($exception, $e, 'Exception tak terduga: ' . $e::class . ' — ' . $e->getMessage());
            if ($pesan !== null) {
                $this->assertStringContainsString($pesan, $e->getMessage());
            }

            return;
        }

        $this->fail("Aksi seharusnya ditolak ({$exception}), tetapi berhasil.");
    }

    /** B.1.7: stok saat ini HARUS sama dengan Σ masuk − Σ keluar di log pergerakan */
    protected function assertStokKonsisten(RawMaterial|FinishedProduct $item): void
    {
        $masuk = (float) $item->stockMovements()->where('tipe_pergerakan', 'masuk')->sum('jumlah');
        $keluar = (float) $item->stockMovements()->where('tipe_pergerakan', 'keluar')->sum('jumlah');
        $nama = $item instanceof RawMaterial ? $item->nama_bahan : $item->nama_produk;

        $this->assertEqualsWithDelta($masuk - $keluar, (float) $item->fresh()->stok_saat_ini, 0.0005,
            "Stok {$nama} tidak sama dengan riwayat pergerakannya");
    }
}