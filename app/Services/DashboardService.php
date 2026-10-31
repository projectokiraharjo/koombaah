<?php

namespace App\Services;

use App\Enums\StatusOrder;
use App\Enums\StatusPO;
use App\Models\DeliverySchedule;
use App\Models\DeliveryScheduleItem;
use App\Models\Employee;
use App\Models\FinishedProduct;
use App\Models\PayrollRun;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PRD B.8 — murni agregasi lintas modul, tanpa tabel sendiri (B.8.5).
 */
class DashboardService
{
    public function __construct(
        private LaporanKeuanganService $laporan,
        private LowStockService $lowStock,
    ) {
    }

    private function rentang(Carbon $bulan): array
    {
        return [$bulan->copy()->startOfMonth()->toDateString(), $bulan->copy()->endOfMonth()->toDateString()];
    }

    // ─────────────── TAHAP 1 ───────────────

    /** Ringkasan Stok — kondisi SAAT INI (stok tidak bersifat periodik) */
    public function stok(Carbon $bulan): array
    {
        return [
            'menipis' => $this->lowStock->jumlah(),
            'total_bahan' => RawMaterial::where('status', 'aktif')->count(),
            'total_produk' => FinishedProduct::where('status', 'aktif')->count(),
            'nilai_bahan' => (float) RawMaterial::where('status', 'aktif')
                ->sum(DB::raw('stok_saat_ini * harga_per_satuan_terakhir')),
            'kritis_bahan' => $this->lowStock->bahanBaku()->take(5),
            'kritis_produk' => $this->lowStock->produkJadi()->take(5),
        ];
    }

    /** Ringkasan Produksi — batch yang dimulai di bulan terpilih */
    public function produksi(Carbon $bulan): array
    {
        [$awal, $akhir] = $this->rentang($bulan);
        $batch = ProductionBatch::whereBetween('tanggal_mulai', [$awal, $akhir]);

        return [
            'per_status' => (clone $batch)->groupBy('status')->selectRaw('status, COUNT(*) AS n')->toBase()->pluck('n', 'status'),
            'per_qc' => (clone $batch)->where('status', 'selesai')->groupBy('status_qc')
                ->selectRaw('status_qc, COUNT(*) AS n')->toBase()->pluck('n', 'status_qc'),
            'per_produk' => ProductionBatch::query()
                ->join('finished_products as fp', 'fp.id', '=', 'production_batches.finished_product_id')
                ->where('production_batches.status', 'selesai')
                ->whereBetween('production_batches.tanggal_mulai', [$awal, $akhir])
                ->groupBy('fp.id', 'fp.nama_produk', 'fp.satuan_jual')
                ->selectRaw('fp.nama_produk, fp.satuan_jual, COUNT(*) AS batch,
                    SUM(production_batches.jumlah_lolos_qc) AS lolos, SUM(production_batches.jumlah_aktual) AS aktual,
                    SUM(production_batches.jumlah_reject_qc) AS reject')
                ->orderByDesc('lolos')->limit(5)->toBase()->get(),
            'berjalan_sekarang' => ProductionBatch::where('status', 'sedang_berjalan')->count(),
        ];
    }

    /** Ringkasan Penjualan — order terkonfirmasi di bulan terpilih */
    public function penjualan(Carbon $bulan): array
    {
        [$awal, $akhir] = $this->rentang($bulan);
        $terhitung = StatusOrder::terhitung();

        $ringkas = SalesOrder::whereBetween('tanggal_order', [$awal, $akhir])->whereIn('status', $terhitung)
            ->selectRaw('COUNT(*) AS jumlah, COALESCE(SUM(total_nilai_order), 0) AS nilai')->toBase()->first();

        return [
            'jumlah_order' => (int) $ringkas->jumlah,
            'nilai' => (float) $ringkas->nilai,
            'dibatalkan' => SalesOrder::whereBetween('tanggal_order', [$awal, $akhir])->where('status', 'dibatalkan')->count(),
            // US 4: produk terlaris (berdasarkan nilai) & mitra paling aktif
            'terlaris' => SalesOrderItem::query()
                ->join('sales_orders as so', 'so.id', '=', 'sales_order_items.sales_order_id')
                ->join('finished_products as fp', 'fp.id', '=', 'sales_order_items.finished_product_id')
                ->whereBetween('so.tanggal_order', [$awal, $akhir])->whereIn('so.status', $terhitung)
                ->groupBy('fp.id', 'fp.nama_produk', 'fp.satuan_jual')
                ->selectRaw('fp.nama_produk, fp.satuan_jual, SUM(sales_order_items.jumlah_dipesan) AS jumlah, SUM(sales_order_items.subtotal) AS nilai')
                ->orderByDesc('nilai')->limit(5)->toBase()->get(),
            'mitra_aktif' => SalesOrder::query()
                ->join('partners as p', 'p.id', '=', 'sales_orders.partner_id')
                ->whereBetween('sales_orders.tanggal_order', [$awal, $akhir])->whereIn('sales_orders.status', $terhitung)
                ->groupBy('p.id', 'p.nama_usaha', 'p.wilayah_ring')
                ->selectRaw('p.nama_usaha, p.wilayah_ring, COUNT(*) AS jumlah, SUM(sales_orders.total_nilai_order) AS nilai')
                ->orderByDesc('jumlah')->orderByDesc('nilai')->limit(5)->toBase()->get(),
            // US 6: pekerjaan operasional yang menunggu (kondisi saat ini)
            'order_baru' => SalesOrder::where('status', 'baru')->count(),
            'siap_kirim' => SalesOrder::bisaDijadwalkan()->count(),
        ];
    }

    /** Ringkasan Keuangan — snapshot untuk periode yang sudah ditutup (B.8.4) */
    public function keuangan(Carbon $bulan): array
    {
        $arusKas = $this->laporan->arusKas($bulan);

        return [
            'ringkasan' => $this->laporan->ringkasan($bulan),
            'kas_masuk' => $arusKas['total_masuk'],
            'kas_keluar' => $arusKas['total_keluar'],
            'kas_bersih' => $arusKas['bersih'],
            // US 3: grafik laba-rugi beberapa bulan terakhir
            'tren' => collect(range(5, 0))->map(function ($i) use ($bulan) {
                $b = $bulan->copy()->subMonthsNoOverflow($i);
                return ['label' => $b->translatedFormat('M y'), 'laba_rugi' => $this->laporan->ringkasan($b)['laba_rugi']];
            }),
        ];
    }

    // ─────────────── TAHAP 2 ───────────────

    /** Ringkasan Distribusi — efisiensi per Ring di bulan terpilih */
    public function distribusi(Carbon $bulan): array
    {
        [$awal, $akhir] = $this->rentang($bulan);

        $perRing = DeliveryScheduleItem::query()
            ->join('delivery_schedules as ds', 'ds.id', '=', 'delivery_schedule_items.delivery_schedule_id')
            ->join('sales_orders as so', 'so.id', '=', 'delivery_schedule_items.sales_order_id')
            ->join('partners as p', 'p.id', '=', 'so.partner_id')
            ->whereBetween('ds.tanggal_rencana_kirim', [$awal, $akhir])
            ->groupBy('p.wilayah_ring')
            ->selectRaw("p.wilayah_ring, COUNT(*) AS jumlah,
                SUM(CASE WHEN delivery_schedule_items.status_pengiriman_item = 'terkirim' THEN 1 ELSE 0 END) AS terkirim,
                SUM(CASE WHEN delivery_schedule_items.status_pengiriman_item = 'gagal_kirim' THEN 1 ELSE 0 END) AS gagal,
                AVG(CASE WHEN delivery_schedule_items.status_pengiriman_item = 'terkirim' AND so.siap_kirim_pada IS NOT NULL
                    THEN EXTRACT(EPOCH FROM (delivery_schedule_items.waktu_terkirim - so.siap_kirim_pada)) / 3600 END) AS rata_jam")
            ->orderBy('p.wilayah_ring')->toBase()->get();

        $jadwal = DeliverySchedule::whereBetween('tanggal_rencana_kirim', [$awal, $akhir])
            ->selectRaw('COUNT(*) AS jumlah, COALESCE(SUM(estimasi_biaya_distribusi), 0) AS biaya')->toBase()->first();

        return [
            'per_ring' => $perRing,
            'jumlah_jadwal' => (int) $jadwal->jumlah,
            'biaya' => (float) $jadwal->biaya,
            'terkirim' => (int) $perRing->sum('terkirim'),
            'gagal' => (int) $perRing->sum('gagal'),
        ];
    }

    /** Ringkasan SDM — jumlah karyawan saja, TANPA rincian gaji (B.8.2) */
    public function sdm(Carbon $bulan): array
    {
        [$awal, $akhir] = $this->rentang($bulan);

        return [
            'aktif' => Employee::where('status', 'aktif')->count(),
            'resign_bulan_ini' => Employee::whereBetween('tanggal_berhenti', [$awal, $akhir])->count(),
            'payroll' => PayrollRun::where('periode_bulan', $awal)->first(),
        ];
    }

    /** Perbandingan harga supplier — harga TERAKHIR tiap supplier per bahan (B.2.7) */
    public function supplier(Carbon $bulan): Collection
    {
        $status = StatusPO::terkonfirmasi();
        $terakhir = collect(DB::select(
            'SELECT DISTINCT ON (poi.raw_material_id, po.supplier_id)
                    poi.raw_material_id, po.supplier_id, poi.harga_per_satuan
               FROM purchase_order_items poi
               JOIN purchase_orders po ON po.id = poi.purchase_order_id
              WHERE po.status IN (' . implode(',', array_fill(0, count($status), '?')) . ')
              ORDER BY poi.raw_material_id, po.supplier_id, po.tanggal_po DESC, po.id DESC',
            $status,
        ));

        $bahan = RawMaterial::get(['id', 'nama_bahan', 'satuan'])->keyBy('id');
        $supplier = Supplier::pluck('nama_supplier', 'id');

        return $terakhir->groupBy('raw_material_id')
            ->filter(fn ($g) => $g->count() >= 2) // hanya bahan yang dibeli dari ≥ 2 supplier
            ->map(function ($g, $bahanId) use ($bahan, $supplier) {
                $murah = $g->sortBy(fn ($r) => (float) $r->harga_per_satuan)->first();
                $mahal = $g->sortByDesc(fn ($r) => (float) $r->harga_per_satuan)->first();
                $hMurah = (float) $murah->harga_per_satuan;
                $hMahal = (float) $mahal->harga_per_satuan;

                return [
                    'bahan_id' => $bahanId,
                    'bahan' => $bahan[$bahanId]->nama_bahan,
                    'satuan' => $bahan[$bahanId]->satuan->value,
                    'jumlah_supplier' => $g->count(),
                    'termurah' => $supplier[$murah->supplier_id],
                    'harga_murah' => $hMurah,
                    'termahal' => $supplier[$mahal->supplier_id],
                    'harga_mahal' => $hMahal,
                    'selisih_persen' => $hMahal > 0 ? ($hMahal - $hMurah) / $hMahal * 100 : 0,
                ];
            })
            ->sortByDesc('selisih_persen')->take(8)->values();
    }
}