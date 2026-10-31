<?php

namespace Tests\Feature\E2E;

use App\Enums\ReferensiStok;
use App\Enums\Role;
use App\Enums\StatusOrder;
use App\Enums\StatusPeriode;
use App\Enums\TipePergerakan;
use App\Exceptions\StokBahanKurangException;
use App\Exceptions\StokProdukKurangException;
use App\Exceptions\StokTidakCukupException;
use App\Models\ProductionBatch;
use App\Services\BomService;
use App\Services\DistributionService;
use App\Services\FinanceService;
use App\Services\ProductionService;
use App\Services\PurchaseOrderService;
use App\Services\SalesOrderService;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Concerns\DataUji;
use Tests\TestCase;

/** Fase 5 — aturan bisnis kunci tiap modul: aksi yang salah HARUS ditolak */
class AturanBisnisTest extends TestCase
{
    use RefreshDatabase, DataUji;

    private array $d;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->pada('2026-03-10 09:00');

        $owner = $this->user(Role::Owner);
        $admin = $this->user(Role::Admin);
        $this->actingAs($admin);

        $las = $this->bahan('LAS', 'kg', 5);
        $sles = $this->bahan('SLES', 'kg', 5);
        $sabun = $this->produk('Sabun Cuci Piring', 15000, 10);
        $deterjen = $this->produk('Deterjen Cair', 20000, 10); // sengaja tanpa resep

        app(BomService::class)->simpanVersiBaru($sabun, [
            ['raw_material_id' => $las->id, 'takaran' => 0.2],
            ['raw_material_id' => $sles->id, 'takaran' => 0.1],
        ], null, $owner);

        $stok = app(StockService::class);
        foreach ([[$las, 100], [$sles, 5], [$sabun, 50], [$deterjen, 5]] as [$item, $jumlah]) {
            $stok->catat($item, TipePergerakan::Masuk, $jumlah, ReferensiStok::PenyesuaianManual, null, 'Stok awal uji', $admin);
        }

        $this->d = compact('owner', 'admin', 'las', 'sles', 'sabun', 'deterjen') + ['mitra' => $this->mitra('Laundry Uji', 'ring_1')];
    }

    /** B.1.7 / B.3.7 — satu bahan kurang = seluruh produksi ditolak, tidak ada stok terpotong sebagian */
    public function test_produksi_diblokir_total_jika_satu_bahan_kurang(): void
    {
        // 100 L butuh LAS 20 kg (cukup) dan SLES 10 kg (stok hanya 5)
        $this->assertDitolak(
            fn () => app(ProductionService::class)->mulai($this->d['sabun'], 100, '2026-03-10', $this->d['admin']),
            StokBahanKurangException::class,
        );
        $this->assertEquals(100, (float) $this->d['las']->fresh()->stok_saat_ini, 'LAS tidak boleh ikut terpotong');
        $this->assertSame(0, ProductionBatch::count());
    }

    /** B.1.4 — produksi wajib memakai resep aktif */
    public function test_produk_tanpa_resep_tidak_bisa_diproduksi(): void
    {
        $this->assertDitolak(
            fn () => app(ProductionService::class)->mulai($this->d['deterjen'], 1, '2026-03-10', $this->d['admin']),
            pesan: 'belum memiliki resep',
        );
    }

    /** B.3.7 — batch dibatalkan: bahan tidak kembali, tanpa HPP, tidak bisa ditutup lagi */
    public function test_batch_dibatalkan_bahan_tidak_kembali(): void
    {
        $produksi = app(ProductionService::class);
        $batch = $produksi->mulai($this->d['sabun'], 20, '2026-03-10', $this->d['admin']); // LAS −4, SLES −2
        $produksi->batalkan($batch, 'Mesin mixer rusak');

        $this->assertEquals(96, (float) $this->d['las']->fresh()->stok_saat_ini);
        $this->assertEquals(3, (float) $this->d['sles']->fresh()->stok_saat_ini);
        $this->assertNull($batch->fresh()->hpp);
        $this->assertDitolak(fn () => $produksi->selesaikan($batch, 20, 0, '2026-03-10', null, $this->d['admin']), pesan: 'sudah ditutup');
    }

    /** B.6.7 — HPP batch adalah snapshot permanen */
    public function test_hpp_batch_tidak_berubah_saat_harga_bahan_naik(): void
    {
        $this->d['las']->update(['harga_per_satuan_terakhir' => 20000]);
        $this->d['sles']->update(['harga_per_satuan_terakhir' => 25000]);

        $produksi = app(ProductionService::class);
        $batch = $produksi->mulai($this->d['sabun'], 20, '2026-03-10', $this->d['admin']);
        $produksi->selesaikan($batch, 20, 0, '2026-03-10', null, $this->d['admin']);
        $this->assertEquals(6500, (float) $batch->fresh()->hpp->hpp_per_satuan, '(4×20.000 + 2×25.000) ÷ 20');

        $this->d['las']->update(['harga_per_satuan_terakhir' => 30000]);
        app(FinanceService::class)->hitungHppBatch($batch->fresh()); // dipicu ulang
        $this->assertEquals(6500, (float) $batch->fresh()->hpp->hpp_per_satuan);
    }

    /** B.4.7 — order diblokir total jika satu produk kurang */
    public function test_order_diblokir_total_jika_satu_produk_kurang(): void
    {
        $sales = app(SalesOrderService::class);
        $order = $sales->simpan(null, $this->d['mitra']->id, '2026-03-10', [
            ['finished_product_id' => $this->d['sabun']->id, 'jumlah_dipesan' => 10],   // stok 50 — cukup
            ['finished_product_id' => $this->d['deterjen']->id, 'jumlah_dipesan' => 8], // stok 5 — kurang
        ], null, $this->d['admin']);

        $this->assertDitolak(fn () => $sales->konfirmasi($order, $this->d['admin']), StokProdukKurangException::class);
        $this->assertEquals(50, (float) $this->d['sabun']->fresh()->stok_saat_ini);
        $this->assertSame(StatusOrder::Baru, $order->fresh()->status);
    }

    /** B.4.7 — harga order = snapshot; pembatalan mengembalikan stok */
    public function test_harga_order_snapshot_dan_pembatalan_mengembalikan_stok(): void
    {
        $sales = app(SalesOrderService::class);
        $order = $sales->simpan(null, $this->d['mitra']->id, '2026-03-10',
            [['finished_product_id' => $this->d['sabun']->id, 'jumlah_dipesan' => 10]], null, $this->d['admin']);

        $this->d['sabun']->update(['harga_jual_per_satuan' => 17000]); // harga master naik
        $order = $sales->simpan($order, $this->d['mitra']->id, '2026-03-10', [
            ['finished_product_id' => $this->d['sabun']->id, 'jumlah_dipesan' => 12],
            ['finished_product_id' => $this->d['deterjen']->id, 'jumlah_dipesan' => 1],
        ], null, $this->d['admin']);
        // Sabun tetap harga lama 15.000; deterjen baru ditambahkan → harga master 20.000
        $this->assertEquals(12 * 15000 + 20000, (float) $order->fresh()->total_nilai_order);

        $sales->konfirmasi($order, $this->d['admin']);
        $this->assertEquals(38, (float) $this->d['sabun']->fresh()->stok_saat_ini);
        $sales->batalkan($order, 'Mitra membatalkan', $this->d['admin']);
        $this->assertEquals(50, (float) $this->d['sabun']->fresh()->stok_saat_ini);
        $this->assertEquals(5, (float) $this->d['deterjen']->fresh()->stok_saat_ini);
    }

    /** B.2.7 — penerimaan tidak boleh melebihi pesanan; PO yang sudah menerima barang tidak bisa dibatalkan */
    public function test_aturan_penerimaan_dan_pembatalan_po(): void
    {
        $service = app(PurchaseOrderService::class);
        $po = $service->simpanDraft(null, $this->supplier('PT Kimia Jaya')->id, '2026-03-10',
            [['raw_material_id' => $this->d['las']->id, 'jumlah_dipesan' => 10, 'harga_per_satuan' => 20000]], $this->d['admin']);
        $service->pesan($po);
        $itemId = $po->items()->value('id');

        $this->assertDitolak(fn () => $service->terimaBarang($po, [$itemId => 12], '2026-03-10', null, $this->d['admin']), pesan: 'melebihi');
        $service->terimaBarang($po, [$itemId => 4], '2026-03-10', null, $this->d['admin']);
        $this->assertDitolak(fn () => $service->batalkan($po), pesan: 'tidak dapat dibatalkan');
        $this->assertEquals(104, (float) $this->d['las']->fresh()->stok_saat_ini);
    }

    /** B.5.7 — satu order di satu jadwal aktif; order di jadwal aktif tak bisa dibatalkan; gagal kirim wajib alasan */
    public function test_aturan_jadwal_pengiriman(): void
    {
        $sales = app(SalesOrderService::class);
        $dist = app(DistributionService::class);
        $order = $sales->simpan(null, $this->d['mitra']->id, '2026-03-10',
            [['finished_product_id' => $this->d['sabun']->id, 'jumlah_dipesan' => 5]], null, $this->d['admin']);
        $sales->konfirmasi($order, $this->d['admin']);
        $sales->lanjutkan($order);
        $sales->lanjutkan($order);

        $data = ['wilayah_ring' => 'ring_1', 'nama_kurir' => 'Pak Budi', 'tanggal_rencana_kirim' => '2026-03-11', 'estimasi_biaya_distribusi' => 50000];
        $jadwal = $dist->simpanJadwal(null, $data, [$order->id], $this->d['admin']);

        $this->assertDitolak(fn () => $dist->simpanJadwal(null, $data, [$order->id], $this->d['admin']), pesan: 'sudah masuk jadwal');
        $this->assertDitolak(fn () => $sales->batalkan($order, 'Batal', $this->d['admin']), pesan: 'jadwal pengiriman aktif');

        $dist->berangkat($jadwal);
        $itemId = $jadwal->items()->value('id');
        $this->assertDitolak(
            fn () => $dist->catatHasil($jadwal, [$itemId => ['status' => 'gagal_kirim', 'catatan' => '']], '2026-03-10 15:00:00'),
            pesan: 'wajib diisi',
        );
    }

    /** B.1.7 — penyesuaian manual wajib alasan, stok tidak boleh minus, jumlah harus > 0 */
    public function test_penyesuaian_stok_manual(): void
    {
        $stok = app(StockService::class);
        $las = $this->d['las'];
        $admin = $this->d['admin'];

        $this->assertDitolak(fn () => $stok->catat($las, TipePergerakan::Masuk, 1, ReferensiStok::PenyesuaianManual, null, null, $admin), InvalidArgumentException::class, 'wajib');
        $this->assertDitolak(fn () => $stok->catat($las, TipePergerakan::Keluar, 500, ReferensiStok::PenyesuaianManual, null, 'Stock opname', $admin), StokTidakCukupException::class);
        $this->assertDitolak(fn () => $stok->catat($las, TipePergerakan::Masuk, 0, ReferensiStok::PenyesuaianManual, null, 'Nol', $admin), InvalidArgumentException::class);
        $this->assertStokKonsisten($las);
    }

    /** B.6.4-C / B.7.7 — distribusi & beban gaji hanya tercatat otomatis */
    public function test_kategori_otomatis_tidak_bisa_diinput_manual(): void
    {
        $keu = $this->user(Role::AdminKeuangan);
        foreach (['distribusi', 'beban_gaji'] as $kategori) {
            $this->assertDitolak(fn () => app(FinanceService::class)->simpanBiaya(null,
                ['periode_bulan' => '2026-03-01', 'kategori' => $kategori, 'nominal' => 100000, 'catatan' => null], $keu), pesan: 'otomatis');
        }
    }

    /** B.6.7 — hanya Owner yang menutup periode, dan hanya bulan yang sudah berakhir */
    public function test_aturan_tutup_periode(): void
    {
        $keuangan = app(FinanceService::class);
        $this->assertDitolak(fn () => $keuangan->tutupPeriode(Carbon::parse('2026-02-01'), $this->user(Role::AdminKeuangan)), pesan: 'Hanya Owner');
        $this->assertDitolak(fn () => $keuangan->tutupPeriode(Carbon::parse('2026-03-01'), $this->d['owner']), pesan: 'sudah berakhir');
        $this->assertSame(StatusPeriode::Ditutup, $keuangan->tutupPeriode(Carbon::parse('2026-02-01'), $this->d['owner'])->status);
    }
}