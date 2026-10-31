<?php

namespace App\Services;

use App\Enums\ReferensiStok;
use App\Enums\StatusBatch;
use App\Enums\StatusQc;
use App\Enums\TipePergerakan;
use App\Exceptions\StokBahanKurangException;
use App\Models\FinishedProduct;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use App\Models\User;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionService
{
    public function __construct(private StockService $stok, private FinanceService $keuangan)
    {
    }

    /**
     * Kebutuhan bahan = takaran BOM aktif × jumlah target (B.3.4 langkah 2).
     *
     * @return Collection<int, array{bahan: RawMaterial, takaran: float, kebutuhan: float, stok: float, kurang: float, cukup: bool}>
     */
    public function hitungKebutuhan(FinishedProduct $produk, string|float $jumlahTarget, bool $kunci = false): Collection
    {
        $bom = $produk->activeBom()->with('details')->first()
            ?? throw new DomainException("{$produk->nama_produk} belum memiliki resep (BOM) aktif.");

        $bahan = RawMaterial::whereIn('id', $bom->details->pluck('raw_material_id'))
            ->orderBy('id') // urutan kunci konsisten -> mencegah deadlock
            ->when($kunci, fn ($q) => $q->lockForUpdate())
            ->get()
            ->keyBy('id');

        return $bom->details->map(function ($d) use ($bahan, $jumlahTarget) {
            $b = $bahan[$d->raw_material_id];
            $kebutuhan = round((float) $d->takaran * (float) $jumlahTarget, 3);
            $stok = (float) $b->stok_saat_ini;

            return [
                'bahan' => $b,
                'takaran' => (float) $d->takaran,
                'kebutuhan' => $kebutuhan,
                'stok' => $stok,
                'kurang' => max(0, round($kebutuhan - $stok, 3)),
                'cukup' => round($stok - $kebutuhan, 3) >= 0,
            ];
        })->values();
    }

    /** Mulai batch: validasi -> snapshot bahan -> kurangi stok (B.3.4 langkah 3–4) */
    public function mulai(FinishedProduct $produk, string|float $jumlahTarget, string $tanggalMulai, User $oleh): ProductionBatch
    {
        return DB::transaction(function () use ($produk, $jumlahTarget, $tanggalMulai, $oleh) {
            $produk = FinishedProduct::lockForUpdate()->findOrFail($produk->id);

            if ($produk->status->value !== 'aktif') {
                throw new DomainException('Produk nonaktif tidak dapat diproduksi.');
            }

            $bom = $produk->activeBom()->first()
                ?? throw new DomainException("{$produk->nama_produk} belum memiliki resep (BOM) aktif.");

            $kebutuhan = $this->hitungKebutuhan($produk, $jumlahTarget, kunci: true);

            // B.1.7 & B.3.7 — BLOKIR TOTAL: semua bahan dicek dulu, sebelum satu pun stok dikurangi
            $kurang = $kebutuhan->reject(fn ($k) => $k['cukup']);
            if ($kurang->isNotEmpty()) {
                throw new StokBahanKurangException($kurang->map(fn ($k) => [
                    'nama' => $k['bahan']->nama_bahan,
                    'kurang' => $k['kurang'],
                    'satuan' => $k['bahan']->satuan->value,
                ])->values()->all());
            }

            $batch = ProductionBatch::create([
                'nomor_batch' => ProductionBatch::generateNomor($tanggalMulai),
                'finished_product_id' => $produk->id,
                'bom_header_id' => $bom->id, // versi BOM dikunci untuk batch ini (B.3.7)
                'jumlah_target' => $jumlahTarget,
                'tanggal_mulai' => $tanggalMulai,
                'status' => StatusBatch::SedangBerjalan,
                'dibuat_oleh' => $oleh->id,
            ]);

            foreach ($kebutuhan as $k) {
                if ($k['kebutuhan'] <= 0) {
                    continue;
                }

                // Snapshot bahan + harga saat ini (dasar HPP di Modul 6)
                $batch->materials()->create([
                    'raw_material_id' => $k['bahan']->id,
                    'jumlah_terpakai' => $k['kebutuhan'],
                    'harga_per_satuan' => $k['bahan']->harga_per_satuan_terakhir,
                ]);

                $this->stok->catat(
                    $k['bahan'], TipePergerakan::Keluar, $k['kebutuhan'],
                    ReferensiStok::Produksi, $batch->id, "Bahan untuk {$batch->nomor_batch}", $oleh,
                );
            }

            return $batch;
        });
    }

    /** Tutup batch + QC (B.3.4 langkah 5–7). Stok jadi hanya bertambah sebesar jumlah lolos QC. */
    public function selesaikan(
        ProductionBatch $batch, string|float $lolos, string|float $reject,
        string $tanggalSelesai, ?string $catatanQc, User $oleh,
    ): ProductionBatch {
        return DB::transaction(function () use ($batch, $lolos, $reject, $tanggalSelesai, $catatanQc, $oleh) {
            $batch = ProductionBatch::with('product')->lockForUpdate()->findOrFail($batch->id);

            if (! $batch->sedangBerjalan()) {
                throw new DomainException('Batch ini sudah ditutup.');
            }

            $lolos = round((float) $lolos, 3);
            $reject = round((float) $reject, 3);
            $aktual = round($lolos + $reject, 3);

            if ($lolos < 0 || $reject < 0) {
                throw new DomainException('Jumlah lolos/reject tidak boleh negatif.');
            }
            if ($aktual <= 0) {
                throw new DomainException('Output aktual harus lebih dari 0. Jika produksi gagal tanpa hasil, gunakan "Batalkan Batch".');
            }
            if ($reject > 0 && blank($catatanQc)) {
                throw new DomainException('Alasan reject QC wajib diisi.');
            }
            if ($tanggalSelesai < $batch->tanggal_mulai->toDateString()) {
                throw new DomainException('Tanggal selesai tidak boleh sebelum tanggal mulai.');
            }

            $batch->update([
                'jumlah_aktual' => $aktual,
                'jumlah_lolos_qc' => $lolos,
                'jumlah_reject_qc' => $reject,
                'tanggal_selesai' => $tanggalSelesai,
                'status' => StatusBatch::Selesai,
                'status_qc' => StatusQc::dariHasil($lolos, $reject),
                'catatan_qc' => $catatanQc,
            ]);

            // B.3.7: hanya yang lolos QC masuk stok barang jadi
            if ($lolos > 0) {
                $this->stok->catat(
                    $batch->product, TipePergerakan::Masuk, $lolos,
                    ReferensiStok::Produksi, $batch->id, "Hasil lolos QC {$batch->nomor_batch}", $oleh,
                );
            }

            // B.6.4-D: HPP batch dihitung otomatis & disimpan permanen
            $this->keuangan->hitungHppBatch($batch);
            return $batch;
        });
    }

    /** Batalkan batch yang sedang berjalan. Bahan TIDAK dikembalikan (B.3.7). */
    public function batalkan(ProductionBatch $batch, string $alasan): ProductionBatch
    {
        return DB::transaction(function () use ($batch, $alasan) {
            $batch = ProductionBatch::lockForUpdate()->findOrFail($batch->id);

            if (! $batch->sedangBerjalan()) {
                throw new DomainException('Hanya batch yang sedang berjalan yang dapat dibatalkan.');
            }
            if (blank($alasan)) {
                throw new DomainException('Alasan pembatalan wajib diisi.');
            }

            $batch->update([
                'status' => StatusBatch::Dibatalkan,
                'tanggal_selesai' => now()->toDateString(),
                'catatan_qc' => 'Dibatalkan: ' . $alasan,
            ]);

            return $batch;
        });
    }
}