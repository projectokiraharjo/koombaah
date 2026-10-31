<?php

namespace App\Services;

use App\Enums\KategoriBiaya;
use App\Enums\StatusBatch;
use App\Enums\StatusPeriode;
use App\Models\CashTransaction;
use App\Models\DeliverySchedule;
use App\Models\FinancialPeriod;
use App\Models\GoodsReceipt;
use App\Models\OperationalExpense;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchHpp;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    public function __construct(private LaporanKeuanganService $laporan)
    {
    }

    // ───────────────────────── Periode ─────────────────────────

    public function periodeDitutup(string|Carbon $tanggal): bool
    {
        return FinancialPeriod::where('periode_bulan', Carbon::parse($tanggal)->startOfMonth()->toDateString())
            ->where('status', StatusPeriode::Ditutup->value)
            ->exists();
    }

    /** B.6.7: transaksi di periode yang sudah ditutup tidak boleh dibuat / diubah / dihapus */
    public function pastikanPeriodeTerbuka(string|Carbon $tanggal): void
    {
        if ($this->periodeDitutup($tanggal)) {
            $label = Carbon::parse($tanggal)->translatedFormat('F Y');
            throw new DomainException("Periode {$label} sudah ditutup. Owner harus membuka kembali periode tersebut terlebih dahulu.");
        }
    }

    public function tutupPeriode(Carbon $bulan, User $oleh): FinancialPeriod
    {
        if (! $oleh->isOwner()) {
            throw new DomainException('Hanya Owner yang dapat menutup periode.');
        }

        $awal = $bulan->copy()->startOfMonth();
        if ($awal->gte(now()->startOfMonth())) {
            throw new DomainException('Hanya bulan yang sudah berakhir yang dapat ditutup.');
        }

        return DB::transaction(function () use ($awal, $oleh) {
            $periode = FinancialPeriod::where('periode_bulan', $awal->toDateString())->lockForUpdate()->first()
                ?? new FinancialPeriod(['periode_bulan' => $awal->toDateString()]);

            if ($periode->status === StatusPeriode::Ditutup) {
                throw new DomainException('Periode ini sudah ditutup.');
            }

            // Snapshot angka resmi saat ditutup (dihitung ulang setiap kali ditutup kembali)
            $lr = $this->laporan->hitungLabaRugi($awal);

            $periode->fill([
                'status' => StatusPeriode::Ditutup,
                'total_pendapatan' => $lr['pendapatan'],
                'total_hpp' => $lr['total_hpp'],
                'total_biaya_operasional' => $lr['total_biaya'],
                'laba_rugi' => $lr['laba_rugi'],
                'ditutup_oleh' => $oleh->id,
                'ditutup_pada' => now(),
            ])->save();

            return $periode;
        });
    }

    public function bukaKembaliPeriode(FinancialPeriod $periode, string $alasan, User $oleh): void
    {
        if (! $oleh->isOwner()) {
            throw new DomainException('Hanya Owner yang dapat membuka kembali periode.');
        }
        if (blank($alasan)) {
            throw new DomainException('Alasan membuka kembali periode wajib diisi.');
        }

        DB::transaction(function () use ($periode, $alasan, $oleh) {
            $periode = FinancialPeriod::lockForUpdate()->findOrFail($periode->id);

            if ($periode->status !== StatusPeriode::Ditutup) {
                throw new DomainException('Periode ini tidak dalam status ditutup.');
            }

            $periode->update([
                'status' => StatusPeriode::Terbuka,
                'dibuka_kembali_oleh' => $oleh->id,
                'dibuka_kembali_pada' => now(),
                'alasan_buka_kembali' => $alasan,
            ]);
        });
    }

    // ─────────────────── Kas otomatis (B.6.4-A, B, C) ───────────────────

    /** Kas masuk saat order selesai — sekali per order */
    public function catatKasMasukPenjualan(SalesOrder $order, string $tanggal, ?User $oleh = null): CashTransaction
    {
        $this->pastikanPeriodeTerbuka($tanggal);

        return CashTransaction::firstOrCreate(
            ['referensi_tipe' => 'sales_order', 'referensi_id' => $order->id, 'kategori' => 'penjualan'],
            [
                'tipe' => 'masuk',
                'nominal' => $order->total_nilai_order,
                'tanggal_transaksi' => $tanggal,
                'catatan' => "Penjualan {$order->nomor_order}",
                'dicatat_oleh' => ($oleh ?? auth()->user())->id,
            ],
        );
    }

    /** Kas keluar per penerimaan barang (bisa beberapa kali per PO karena partial receipt) */
    public function catatKasKeluarPembelian(PurchaseOrder $po, GoodsReceipt $penerimaan, float $nominal, ?User $oleh = null): ?CashTransaction
    {
        if ($nominal <= 0) {
            return null;
        }

        $tanggal = $penerimaan->tanggal_terima->toDateString();
        $this->pastikanPeriodeTerbuka($tanggal);

        // Penanda unik per penerimaan agar tidak tercatat ganda
        $penanda = "Penerimaan barang GR#{$penerimaan->id}";
        if (CashTransaction::where('referensi_tipe', 'purchase_order')->where('catatan', 'like', $penanda . ' %')->exists()) {
            return null;
        }

        return CashTransaction::create([
            'tipe' => 'keluar',
            'kategori' => 'pembelian_bahan_baku',
            'nominal' => round($nominal, 2),
            'tanggal_transaksi' => $tanggal,
            'referensi_tipe' => 'purchase_order',
            'referensi_id' => $po->id,
            'catatan' => "{$penanda} — {$po->nomor_po}",
            'dicatat_oleh' => ($oleh ?? auth()->user())->id,
        ]);
    }

    /** Biaya distribusi per jadwal, dicatat saat kurir berangkat */
    public function catatKasKeluarDistribusi(DeliverySchedule $jadwal, ?User $oleh = null): ?CashTransaction
    {
        $biaya = (float) $jadwal->estimasi_biaya_distribusi;
        if ($biaya <= 0) {
            return null;
        }

        $tanggal = ($jadwal->tanggal_aktual_kirim ?? now())->toDateString();
        $this->pastikanPeriodeTerbuka($tanggal);

        return CashTransaction::firstOrCreate(
            ['referensi_tipe' => 'delivery_schedule', 'referensi_id' => $jadwal->id, 'kategori' => 'distribusi'],
            [
                'tipe' => 'keluar',
                'nominal' => $biaya,
                'tanggal_transaksi' => $tanggal,
                'catatan' => "Biaya distribusi {$jadwal->kode_jadwal} ({$jadwal->wilayah_ring->label()}, kurir {$jadwal->nama_kurir})",
                'dicatat_oleh' => ($oleh ?? auth()->user())->id,
            ],
        );
    }

    // ───────────────────────── HPP (B.6.4-D) ─────────────────────────

    /** HPP batch = total biaya bahan (harga saat batch dimulai) ÷ jumlah lolos QC. Snapshot permanen. */
    public function hitungHppBatch(ProductionBatch $batch): ?ProductionBatchHpp
    {
        $lolos = (float) $batch->jumlah_lolos_qc;

        if ($batch->status !== StatusBatch::Selesai || $lolos <= 0) {
            return null; // 🔶 reject penuh: tidak ada HPP per satuan
        }

        $this->pastikanPeriodeTerbuka($batch->tanggal_selesai);

        $batch->loadMissing('materials');
        $total = round($batch->materials->sum(fn ($m) => (float) $m->jumlah_terpakai * (float) $m->harga_per_satuan), 2);

        // firstOrCreate: HPP yang sudah ada TIDAK dihitung ulang (B.6.7 — historis)
        return ProductionBatchHpp::firstOrCreate(
            ['production_batch_id' => $batch->id],
            [
                'total_biaya_bahan_baku' => $total,
                'jumlah_lolos_qc' => $lolos,
                'hpp_per_satuan' => round($total / $lolos, 2),
                'dihitung_pada' => now(),
            ],
        );
    }

    // ───────────────── Biaya operasional manual (B.6.4-C) ─────────────────

    public function simpanBiaya(?OperationalExpense $biaya, array $data, User $oleh): OperationalExpense
    {
        return DB::transaction(function () use ($biaya, $data, $oleh) {
            if (in_array($data['kategori'], [KategoriBiaya::Distribusi->value, KategoriBiaya::BebanGaji->value], true)
                || $biaya?->kategori === KategoriBiaya::BebanGaji) {
                throw new DomainException('Kategori ini tercatat otomatis (Distribusi dari Modul 5, Beban Gaji dari payroll Modul 7).');
            }

            $this->pastikanPeriodeTerbuka($data['periode_bulan']);

            if ($biaya) {
                $biaya = OperationalExpense::lockForUpdate()->findOrFail($biaya->id);
                $this->pastikanPeriodeTerbuka($biaya->periode_bulan); // periode lama juga harus terbuka
                $biaya->update($data);

                return $biaya;
            }

            return OperationalExpense::create($data + ['dicatat_oleh' => $oleh->id]);
        });
    }

    public function hapusBiaya(OperationalExpense $biaya): void
    {
        if ($biaya->kategori === KategoriBiaya::BebanGaji) {
            throw new DomainException('Beban gaji berasal dari payroll final dan tidak dapat dihapus manual.');
        }
        $this->pastikanPeriodeTerbuka($biaya->periode_bulan);
        $biaya->delete();
    }
        /**
     * Dipanggil saat payroll final (B.7.4 langkah 4). B.6.7: satu angka agregat per bulan,
     * tanpa rincian / nama karyawan — Admin Keuangan hanya melihat angka ini.
     */
    public function catatBebanGajiAgregat(Carbon $periode, float $total, int $jumlahKaryawan, User $oleh): OperationalExpense
    {
        $this->pastikanPeriodeTerbuka($periode);
        $awal = $periode->copy()->startOfMonth()->toDateString();

        // Ganti beban gaji lama di bulan ini (mis. input manual saat pengujian Modul 6)
        OperationalExpense::where('periode_bulan', $awal)->where('kategori', KategoriBiaya::BebanGaji->value)->delete();

        return OperationalExpense::create([
            'periode_bulan' => $awal,
            'kategori' => KategoriBiaya::BebanGaji,
            'nominal' => $total,
            'catatan' => 'Payroll ' . $periode->translatedFormat('F Y') . " — agregat {$jumlahKaryawan} karyawan",
            'dicatat_oleh' => $oleh->id,
        ]);
    }
}