<?php

namespace App\Services;

use App\Enums\KategoriBiaya;
use App\Enums\KategoriKas;
use App\Enums\StatusPeriode;
use App\Models\CashTransaction;
use App\Models\FinancialPeriod;
use App\Models\OperationalExpense;
use App\Models\ProductionBatchHpp;
use App\Models\SalesOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LaporanKeuanganService
{
    /** [tanggal awal, tanggal akhir] bulan dalam format Y-m-d */
    private function rentang(Carbon $bulan): array
    {
        return [$bulan->copy()->startOfMonth()->toDateString(), $bulan->copy()->endOfMonth()->toDateString()];
    }

    /**
     * 🔶 HPP rata-rata tertimbang per produk dari seluruh batch yang selesai s/d tanggal tertentu.
     * @return Collection<int, float> [finished_product_id => hpp per satuan]
     */
    public function hppRataRata(Carbon $sampai): Collection
    {
        return ProductionBatchHpp::query()
            ->join('production_batches as pb', 'pb.id', '=', 'production_batch_hpp.production_batch_id')
            ->where('pb.tanggal_selesai', '<=', $sampai->toDateString())
            ->groupBy('pb.finished_product_id')
            ->selectRaw('pb.finished_product_id,
                SUM(production_batch_hpp.total_biaya_bahan_baku) / NULLIF(SUM(production_batch_hpp.jumlah_lolos_qc), 0) AS hpp')
            ->toBase()
            ->pluck('hpp', 'finished_product_id')
            ->map(fn ($v) => (float) $v);
    }

    /** Laba-Rugi = Pendapatan − HPP − Biaya Operasional (B.6.4-E), dihitung real-time */
    public function hitungLabaRugi(Carbon $bulan): array
    {
        [$awal, $akhir] = $this->rentang($bulan);

        // 1. Pendapatan = kas masuk penjualan di bulan ini
        $pendapatan = (float) CashTransaction::where('tipe', 'masuk')->where('kategori', 'penjualan')
            ->whereBetween('tanggal_transaksi', [$awal, $akhir])->sum('nominal');

        // 2. HPP produk terjual (order yang kas penjualannya tercatat di bulan ini)
        $hppRata = $this->hppRataRata($bulan->copy()->endOfMonth());
        $terjual = SalesOrderItem::query()
            ->join('cash_transactions as ct', function ($j) {
                $j->on('ct.referensi_id', '=', 'sales_order_items.sales_order_id')
                    ->where('ct.referensi_tipe', 'sales_order')
                    ->where('ct.kategori', 'penjualan');
            })
            ->join('finished_products as fp', 'fp.id', '=', 'sales_order_items.finished_product_id')
            ->whereBetween('ct.tanggal_transaksi', [$awal, $akhir])
            ->groupBy('fp.id', 'fp.nama_produk', 'fp.satuan_jual')
            ->selectRaw('fp.id, fp.nama_produk, fp.satuan_jual,
                SUM(sales_order_items.jumlah_dipesan) AS jumlah, SUM(sales_order_items.subtotal) AS penjualan')
            ->orderBy('fp.nama_produk')
            ->toBase()
            ->get();

        $rincianHpp = $terjual->map(function ($r) use ($hppRata) {
            $hppSatuan = $hppRata->get($r->id);
            return [
                'nama' => $r->nama_produk,
                'satuan' => $r->satuan_jual,
                'jumlah' => (float) $r->jumlah,
                'penjualan' => (float) $r->penjualan,
                'hpp_satuan' => $hppSatuan,
                'total_hpp' => round((float) $r->jumlah * ($hppSatuan ?? 0), 2),
            ];
        });
        $totalHpp = round($rincianHpp->sum('total_hpp'), 2);

        // 3. Biaya operasional = input manual + distribusi otomatis dari Modul 5 (B.6.4-C)
        $biaya = OperationalExpense::where('periode_bulan', $awal)
            ->groupBy('kategori')
            ->selectRaw('kategori, SUM(nominal) AS total')
            ->toBase()->pluck('total', 'kategori')
            ->map(fn ($v) => (float) $v)->all();

        $distribusi = (float) CashTransaction::where('tipe', 'keluar')->where('kategori', 'distribusi')
            ->whereBetween('tanggal_transaksi', [$awal, $akhir])->sum('nominal');
        if ($distribusi > 0) {
            $biaya[KategoriBiaya::Distribusi->value] = ($biaya[KategoriBiaya::Distribusi->value] ?? 0) + $distribusi;
        }

        $totalBiaya = round(array_sum($biaya), 2);

        return [
            'pendapatan' => $pendapatan,
            'rincian_hpp' => $rincianHpp,
            'total_hpp' => $totalHpp,
            'laba_kotor' => round($pendapatan - $totalHpp, 2),
            'biaya' => $biaya,
            'total_biaya' => $totalBiaya,
            'laba_rugi' => round($pendapatan - $totalHpp - $totalBiaya, 2),
            'tanpa_hpp' => $rincianHpp->whereNull('hpp_satuan')->pluck('nama'),
        ];
    }

    /** Angka total satu bulan — pakai SNAPSHOT jika periode sudah ditutup (B.8.4) */
    public function ringkasan(Carbon $bulan): array
    {
        $periode = FinancialPeriod::where('periode_bulan', $bulan->copy()->startOfMonth()->toDateString())->first();

        if ($periode?->status === StatusPeriode::Ditutup) {
            return [
                'pendapatan' => (float) $periode->total_pendapatan,
                'hpp' => (float) $periode->total_hpp,
                'biaya' => (float) $periode->total_biaya_operasional,
                'laba_rugi' => (float) $periode->laba_rugi,
                'snapshot' => true,
            ];
        }

        $lr = $this->hitungLabaRugi($bulan);

        return [
            'pendapatan' => $lr['pendapatan'],
            'hpp' => $lr['total_hpp'],
            'biaya' => $lr['total_biaya'],
            'laba_rugi' => $lr['laba_rugi'],
            'snapshot' => false,
        ];
    }

    /** Arus kas: kas masuk vs keluar per kategori (B.6.2) */
    public function arusKas(Carbon $bulan): array
    {
        [$awal, $akhir] = $this->rentang($bulan);

        $kas = CashTransaction::whereBetween('tanggal_transaksi', [$awal, $akhir])
            ->groupBy('tipe', 'kategori')
            ->selectRaw('tipe, kategori, SUM(nominal) AS total')
            ->toBase()->get();

        $masuk = [];
        $keluar = [];
        foreach ($kas as $r) {
            $label = KategoriKas::from($r->kategori)->label();
            if ($r->tipe === 'masuk') {
                $masuk[$label] = ($masuk[$label] ?? 0) + (float) $r->total;
            } else {
                $keluar[$label] = ($keluar[$label] ?? 0) + (float) $r->total;
            }
        }

        // 🔶 Biaya operasional manual dianggap kas keluar pada bulannya (cash-basis)
        $biayaManual = OperationalExpense::with('pencatat:id,name')
            ->where('periode_bulan', $awal)->orderBy('kategori')->get();
        foreach ($biayaManual as $b) {
            $label = $b->kategori->label();
            $keluar[$label] = ($keluar[$label] ?? 0) + (float) $b->nominal;
        }

        $totalMasuk = array_sum($masuk);
        $totalKeluar = array_sum($keluar);

        return [
            'masuk' => $masuk,
            'keluar' => $keluar,
            'total_masuk' => $totalMasuk,
            'total_keluar' => $totalKeluar,
            'bersih' => $totalMasuk - $totalKeluar,
            'transaksi' => CashTransaction::with('pencatat:id,name')
                ->whereBetween('tanggal_transaksi', [$awal, $akhir])
                ->orderBy('tanggal_transaksi')->orderBy('id')->get(),
            'biaya_manual' => $biayaManual,
        ];
    }
}