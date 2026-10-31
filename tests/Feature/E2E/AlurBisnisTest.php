<?php

namespace Tests\Feature\E2E;

use App\Enums\Role;
use App\Enums\StatusJadwal;
use App\Enums\StatusOrder;
use App\Enums\StatusPO;
use App\Enums\StatusQc;
use App\Models\CashTransaction;
use App\Models\OperationalExpense;
use App\Services\BomService;
use App\Services\DistributionService;
use App\Services\FinanceService;
use App\Services\LaporanKeuanganService;
use App\Services\PayrollService;
use App\Services\ProductionService;
use App\Services\PurchaseOrderService;
use App\Services\SalesOrderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DataUji;
use Tests\TestCase;

/**
 * Fase 5 — satu skenario bisnis utuh melintasi 8 modul PRD,
 * tiap langkah dijalankan oleh role yang semestinya (A.4).
 */
class AlurBisnisTest extends TestCase
{
    use RefreshDatabase, DataUji;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_alur_lengkap_dari_pembelian_sampai_laporan(): void
    {
        $owner = $this->user(Role::Owner);
        $admin = $this->user(Role::Admin);
        $keu = $this->user(Role::AdminKeuangan);
        $maret = Carbon::parse('2026-03-01');

        // ── 1. Data master + BOM versioning (Modul 1, 2, 4, 7) ──────────────
        $this->pada('2026-03-01 08:00');
        $this->actingAs($owner);
        $supA = $this->supplier('PT Kimia Jaya');
        $supB = $this->supplier('CV Sumber Bersih');
        $las = $this->bahan('LAS', 'kg', 10, $supA);
        $sles = $this->bahan('SLES', 'kg', 10, $supA);
        $wangi = $this->bahan('Pewangi Lemon', 'liter', 2, $supA);
        $sabun = $this->produk('Sabun Cuci Piring', 15000, 20);
        $mitra1 = $this->mitra('Laundry Bersih Pamulang', 'ring_1');
        $mitra3 = $this->mitra('Toko Sinar BSD', 'ring_3');
        $karyawanA = $this->karyawan('Karyawan Dummy A', 3000000);
        $this->karyawan('Karyawan Dummy B', 2500000);

        $bom = app(BomService::class);
        $bom->simpanVersiBaru($sabun, [
            ['raw_material_id' => $las->id, 'takaran' => 0.25],
            ['raw_material_id' => $sles->id, 'takaran' => 0.1],
        ], 'Resep awal', $owner);
        $v2 = $bom->simpanVersiBaru($sabun, [
            ['raw_material_id' => $las->id, 'takaran' => 0.2],
            ['raw_material_id' => $sles->id, 'takaran' => 0.1],
            ['raw_material_id' => $wangi->id, 'takaran' => 0.01],
        ], 'Tambah pewangi, LAS dikurangi', $owner);
        $this->assertEquals(2, $v2->versi);
        $this->assertSame(1, $sabun->boms()->where('status', 'aktif')->count(), 'Satu produk = satu resep aktif');

        // ── 2. Purchasing (Modul 2) → stok masuk + kas keluar (Modul 6) ─────
        $this->actingAs($admin);
        $po = app(PurchaseOrderService::class);
        $po1 = $po->simpanDraft(null, $supA->id, '2026-03-02', [
            ['raw_material_id' => $las->id, 'jumlah_dipesan' => 50, 'harga_per_satuan' => 20000],
            ['raw_material_id' => $sles->id, 'jumlah_dipesan' => 30, 'harga_per_satuan' => 25000],
            ['raw_material_id' => $wangi->id, 'jumlah_dipesan' => 5, 'harga_per_satuan' => 80000],
        ], $admin);
        $this->assertEquals(2150000, (float) $po1->fresh()->total_nilai_po, 'Total PO dihitung otomatis');
        $po->pesan($po1);

        $item = $po1->items()->get()->keyBy('raw_material_id');
        $po->terimaBarang($po1, [$item[$las->id]->id => 30, $item[$sles->id]->id => 30, $item[$wangi->id]->id => 5], '2026-03-03', 'Kiriman pertama', $admin);
        $this->assertSame(StatusPO::SebagianDiterima, $po1->fresh()->status);
        $po->terimaBarang($po1, [$item[$las->id]->id => 20], '2026-03-04', 'Sisa LAS', $admin);
        $this->assertSame(StatusPO::DiterimaPenuh, $po1->fresh()->status);

        $po2 = $po->simpanDraft(null, $supB->id, '2026-03-05', [
            ['raw_material_id' => $las->id, 'jumlah_dipesan' => 10, 'harga_per_satuan' => 18000],
        ], $admin);
        $po->pesan($po2);
        $po->terimaBarang($po2, [$po2->items()->value('id') => 10], '2026-03-05', null, $admin);

        $this->assertEquals(60, (float) $las->fresh()->stok_saat_ini);
        $this->assertEquals(18000, (float) $las->fresh()->harga_per_satuan_terakhir, 'Harga terakhir mengikuti penerimaan terbaru');
        $this->assertSame(3, CashTransaction::where('kategori', 'pembelian_bahan_baku')->count(), 'Satu kas keluar per penerimaan');
        $this->assertEquals(2330000, (float) CashTransaction::where('kategori', 'pembelian_bahan_baku')->sum('nominal'));

        // ── 3. Produksi + QC (Modul 3) → HPP (Modul 6) ──────────────────────
        $this->pada('2026-03-06 08:00');
        $produksi = app(ProductionService::class);
        $batch = $produksi->mulai($sabun, 100, '2026-03-06', $admin);
        $this->assertEquals($v2->id, $batch->bom_header_id, 'Batch mengunci versi resep yang aktif');
        $this->assertEquals(40, (float) $las->fresh()->stok_saat_ini);
        $this->assertEquals(20, (float) $sles->fresh()->stok_saat_ini);
        $this->assertEquals(4, (float) $wangi->fresh()->stok_saat_ini);

        $produksi->selesaikan($batch, 92, 8, '2026-03-07', 'Kemasan bocor', $admin);
        $batch = $batch->fresh();
        $this->assertSame(StatusQc::SebagianLolos, $batch->status_qc);
        $this->assertEquals(92, (float) $sabun->fresh()->stok_saat_ini, 'Hanya hasil lolos QC yang masuk stok');
        $this->assertEquals(690000, (float) $batch->hpp->total_biaya_bahan_baku);
        $this->assertEquals(7500, (float) $batch->hpp->hpp_per_satuan, 'HPP = 690.000 ÷ 92');

        // ── 4. Sales (Modul 4) ───────────────────────────────────────────────
        $this->pada('2026-03-08 09:00');
        $sales = app(SalesOrderService::class);
        $baris = fn (float $jumlah) => [['finished_product_id' => $sabun->id, 'jumlah_dipesan' => $jumlah]];
        $o1 = $sales->simpan(null, $mitra1->id, '2026-03-08', $baris(40), null, $admin);
        $o2 = $sales->simpan(null, $mitra3->id, '2026-03-08', $baris(30), null, $admin);
        $o4 = $sales->simpan(null, $mitra1->id, '2026-03-08', $baris(10), 'Order uji pembatalan', $admin);
        $this->assertEquals(600000, (float) $o1->fresh()->total_nilai_order);

        foreach ([$o1, $o2, $o4] as $o) {
            $sales->konfirmasi($o, $admin);
        }
        $this->assertEquals(12, (float) $sabun->fresh()->stok_saat_ini);

        $sales->batalkan($o4, 'Mitra membatalkan', $admin);
        $this->assertEquals(22, (float) $sabun->fresh()->stok_saat_ini, 'Pembatalan mengembalikan stok otomatis');

        foreach ([$o1, $o2] as $o) {
            $sales->lanjutkan($o); // diproses
            $sales->lanjutkan($o); // siap kirim
        }
        $this->assertSame(StatusOrder::SiapKirim, $o1->fresh()->status);
        $this->assertNotNull($o1->fresh()->siap_kirim_pada);

        // ── 5. Distribusi (Modul 5) → order selesai + kas (Modul 6) ─────────
        $this->pada('2026-03-09 08:00');
        $dist = app(DistributionService::class);
        $jadwal = fn (string $ring, string $kurir, string $tgl, int $biaya) =>
            ['wilayah_ring' => $ring, 'nama_kurir' => $kurir, 'tanggal_rencana_kirim' => $tgl, 'estimasi_biaya_distribusi' => $biaya];

        $j1 = $dist->simpanJadwal(null, $jadwal('ring_1', 'Pak Budi', '2026-03-09', 50000), [$o1->id], $admin);
        $j2 = $dist->simpanJadwal(null, $jadwal('ring_3', 'Pak Andi', '2026-03-09', 75000), [$o2->id], $admin);
        $dist->berangkat($j1);
        $dist->berangkat($j2);
        $dist->catatHasil($j1, [$j1->items()->value('id') => ['status' => 'terkirim']], '2026-03-09 11:00:00');
        $dist->catatHasil($j2, [$j2->items()->value('id') => ['status' => 'gagal_kirim', 'catatan' => 'Toko tutup']], '2026-03-09 15:00:00');

        $this->assertSame(StatusOrder::Selesai, $o1->fresh()->status);
        $this->assertSame(StatusOrder::SiapKirim, $o2->fresh()->status, 'Gagal kirim → order kembali siap kirim');
        $this->assertSame(StatusJadwal::Selesai, $j2->fresh()->status);

        $this->pada('2026-03-10 08:00');
        $j3 = $dist->simpanJadwal(null, $jadwal('ring_3', 'Pak Andi', '2026-03-10', 75000), [$o2->id], $admin);
        $dist->berangkat($j3);
        $dist->catatHasil($j3, [$j3->items()->value('id') => ['status' => 'terkirim']], '2026-03-10 14:00:00');
        $this->assertSame(StatusOrder::Selesai, $o2->fresh()->status);

        $this->assertEquals(1050000, (float) CashTransaction::where('kategori', 'penjualan')->sum('nominal'));
        $this->assertEquals(200000, (float) CashTransaction::where('kategori', 'distribusi')->sum('nominal'));

        // ── 6. Biaya manual (Admin Keuangan) + payroll (Owner, Modul 7) ─────
        $this->actingAs($keu);
        $keuangan = app(FinanceService::class);
        $keuangan->simpanBiaya(null, ['periode_bulan' => '2026-03-01', 'kategori' => 'sewa', 'nominal' => 1000000, 'catatan' => 'Sewa Maret'], $keu);
        $keuangan->simpanBiaya(null, ['periode_bulan' => '2026-03-01', 'kategori' => 'listrik', 'nominal' => 250000, 'catatan' => null], $keu);

        $this->actingAs($owner);
        $payroll = app(PayrollService::class);
        [$run] = $payroll->siapkanDraft($maret, $owner);
        $detailA = $run->details()->where('employee_id', $karyawanA->id)->first();
        $payroll->simpanPenyesuaian($run, [$detailA->id => ['bonus_produksi' => 200000, 'lembur' => 0, 'thr' => 0, 'potongan' => 0]], $owner);
        $payroll->finalisasi($run, $owner);
        $this->assertEquals(5700000, (float) OperationalExpense::where('kategori', 'beban_gaji')->value('nominal'), 'Hanya total agregat yang masuk Keuangan');

        // ── 7. Laporan keuangan Maret (Modul 6) ─────────────────────────────
        $laporan = app(LaporanKeuanganService::class);
        $lr = $laporan->hitungLabaRugi($maret);
        $this->assertEquals(1050000, $lr['pendapatan']);
        $this->assertEquals(525000, $lr['total_hpp'], 'HPP terjual = 70 L × Rp 7.500');
        $this->assertEquals(525000, $lr['laba_kotor']);
        $this->assertEquals(7150000, $lr['total_biaya'], 'Sewa + listrik + beban gaji + distribusi');
        $this->assertEquals(-6625000, $lr['laba_rugi']);

        $kas = $laporan->arusKas($maret);
        $this->assertEquals(1050000, $kas['total_masuk']);
        $this->assertEquals(9480000, $kas['total_keluar'], '2.330.000 + 200.000 + 1.000.000 + 250.000 + 5.700.000');
        $this->assertEquals(-8430000, $kas['bersih']);

        // ── 8. Integritas stok: stok = Σ masuk − Σ keluar (B.1.7) ───────────
        foreach ([$las, $sles, $wangi, $sabun] as $x) {
            $this->assertStokKonsisten($x);
        }

        // ── 9. Tutup periode (Owner) → snapshot & kunci transaksi ───────────
        $this->pada('2026-04-02 09:00');
        $periode = $keuangan->tutupPeriode($maret, $owner);
        $this->assertEquals(-6625000, (float) $periode->laba_rugi);
        $this->assertTrue($laporan->ringkasan($maret)['snapshot']);

        $this->actingAs($keu);
        $this->assertDitolak(fn () => $keuangan->simpanBiaya(null, ['periode_bulan' => '2026-03-01', 'kategori' => 'lainnya', 'nominal' => 1, 'catatan' => 'Telat'], $keu), pesan: 'sudah ditutup');

        // ── 10. Dashboard & ekspor per role (Modul 8) ───────────────────────
        $this->actingAs($owner)->get('/dashboard?bulan=2026-03')->assertOk()
            ->assertSee('Snapshot periode ditutup')->assertSee('Rugi Bersih')
            ->assertSee('Laundry Bersih Pamulang')->assertSee('Sabun Cuci Piring')
            ->assertSee('Ringkasan SDM')->assertSee('CV Sumber Bersih'); // termurah untuk LAS
        $this->actingAs($admin)->get('/dashboard?bulan=2026-03')->assertOk()
            ->assertSee('Sabun Cuci Piring')->assertSee('CV Sumber Bersih')
            ->assertDontSee('Rugi Bersih')->assertDontSee('Ringkasan SDM');
        $this->actingAs($keu)->get('/dashboard?bulan=2026-03')->assertOk()
            ->assertSee('Rugi Bersih')
            ->assertDontSee('Ringkasan SDM')->assertDontSee('Perbandingan Harga Supplier');

        $this->actingAs($keu)->get('/ekspor/laba-rugi/xlsx?bulan=2026-03')->assertOk();
        $this->actingAs($keu)->get('/ekspor/laba-rugi/pdf?bulan=2026-03')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($owner)->get('/ekspor/stok/pdf')->assertOk();

        // ── 11. Buka kembali periode: Owner + wajib alasan ──────────────────
        $this->assertDitolak(fn () => $keuangan->bukaKembaliPeriode($periode, 'Koreksi', $keu), pesan: 'Hanya Owner');
        $this->assertDitolak(fn () => $keuangan->bukaKembaliPeriode($periode, '', $owner), pesan: 'wajib');
        $keuangan->bukaKembaliPeriode($periode, 'Koreksi biaya listrik', $owner);
        $this->assertFalse($laporan->ringkasan($maret)['snapshot']);

        // ── 12. Semua halaman detail tampil normal dengan data nyata ─────────
        $this->actingAs($owner);
        $halaman = [
            route('bom.detail', $sabun), route('po.detail', $po1), route('produksi.detail', $batch),
            route('mitra.detail', $mitra1), route('order.detail', $o1), route('distribusi.detail', $j2),
            route('karyawan.detail', $karyawanA), route('payroll.detail', $run),
            route('riwayat-stok'), route('harga-supplier'), route('riwayat-pengiriman'),
            route('keuangan', ['bulan' => '2026-03']), route('arus-kas', ['bulan' => '2026-03']), route('hpp'), route('periode'),
        ];
        foreach ($halaman as $url) {
            $this->get($url)->assertOk();
        }
    }
}