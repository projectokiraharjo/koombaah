<?php

use App\Livewire\Inventory\BahanBaku;
use App\Livewire\Inventory\ProdukJadi;
use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Inventory\Bom;
use App\Livewire\Inventory\BomDetail;
use App\Livewire\Inventory\StokBahanBaku;
use Illuminate\Support\Facades\Route;
use App\Livewire\Inventory\StokProdukJadi;
use App\Livewire\Inventory\PeringatanStok;
use App\Livewire\Inventory\RiwayatStok;
use App\Livewire\Purchasing\DaftarSupplier;
use App\Livewire\Purchasing\HargaSupplier;
use App\Livewire\Purchasing\PoDetail;
use App\Livewire\Purchasing\PoForm;
use App\Livewire\Purchasing\PoIndex;
use App\Livewire\Produksi\BatchDetail;
use App\Livewire\Produksi\BatchForm;
use App\Livewire\Produksi\ProduksiIndex;
use App\Livewire\Sales\DaftarMitra;
use App\Livewire\Sales\MitraDetail;
use App\Livewire\Sales\OrderDetail;
use App\Livewire\Sales\OrderForm;
use App\Livewire\Sales\OrderIndex;
use App\Livewire\Distribusi\JadwalDetail;
use App\Livewire\Distribusi\JadwalForm;
use App\Livewire\Distribusi\JadwalIndex;
use App\Livewire\Distribusi\RiwayatPengiriman;
use App\Livewire\Keuangan\ArusKas;
use App\Livewire\Keuangan\BiayaOperasional;
use App\Livewire\Keuangan\HppProduksi;
use App\Livewire\Keuangan\LabaRugi;
use App\Livewire\Keuangan\PeriodeKeuangan;
use App\Livewire\Sdm\DaftarKaryawan;
use App\Livewire\Sdm\KaryawanDetail;
use App\Livewire\Sdm\PayrollIndex;
use App\Livewire\Sdm\PayrollRunDetail;
use App\Http\Controllers\EksporController;
use App\Http\Controllers\UnduhBackupController;
use App\Livewire\Pengaturan\AkunPengguna;
use App\Livewire\Pengaturan\Cadangan;
use App\Livewire\Profil;

Route::redirect('/', '/dashboard');

// Hanya untuk yang BELUM login
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// Wajib login
Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::get('/dashboard', Dashboard::class)->middleware('can:dashboard')->name('dashboard');

    // Placeholder modul — diganti komponen Livewire asli saat modulnya dikerjakan
    // Modul 1 — BOM & Inventory
    Route::middleware('can:inventory')->prefix('inventory')->group(function () {
        Route::redirect('/', '/inventory/bahan-baku')->name('inventory');
        Route::get('/bahan-baku', BahanBaku::class)->name('bahan-baku');
        Route::get('/produk-jadi', ProdukJadi::class)->name('produk-jadi');
        Route::get('/bom', Bom::class)->name('bom');
        Route::get('/bom/{product}', BomDetail::class)->name('bom.detail');
        Route::get('/stok-bahan-baku', StokBahanBaku::class)->name('stok-bahan-baku');
        Route::get('/stok-produk-jadi', StokProdukJadi::class)->name('stok-produk-jadi');
        Route::get('/peringatan-stok', PeringatanStok::class)->name('peringatan-stok');
        Route::get('/riwayat-stok', RiwayatStok::class)->name('riwayat-stok');
     });
        // Modul 2 — Purchasing
    Route::middleware('can:purchasing')->prefix('purchasing')->group(function () {
        Route::redirect('/', '/purchasing/po')->name('purchasing');
        Route::get('/supplier', DaftarSupplier::class)->name('supplier');
        Route::get('/po', PoIndex::class)->name('po');
        Route::get('/po/buat', PoForm::class)->name('po.buat');       // harus di atas /po/{po}
        Route::get('/po/{po}', PoDetail::class)->name('po.detail');
        Route::get('/po/{po}/edit', PoForm::class)->name('po.edit');
        Route::get('/harga-supplier', HargaSupplier::class)->name('harga-supplier');   
    });   

        // Modul 3 — Produksi
    Route::middleware('can:produksi')->prefix('produksi')->group(function () {
        Route::get('/', ProduksiIndex::class)->name('produksi');
        Route::get('/batch/buat', BatchForm::class)->name('produksi.buat');   // harus di atas /batch/{batch}
        Route::get('/batch/{batch}', BatchDetail::class)->name('produksi.detail');
    });
        // Modul 4 — Sales & Order Fulfillment
    Route::middleware('can:sales')->prefix('sales')->group(function () {
        Route::redirect('/', '/sales/order')->name('sales');
        Route::get('/mitra', DaftarMitra::class)->name('mitra');
        Route::get('/mitra/{partner}', MitraDetail::class)->name('mitra.detail');
        Route::get('/order', OrderIndex::class)->name('order');
        Route::get('/order/buat', OrderForm::class)->name('order.buat');       // harus di atas /order/{order}
        Route::get('/order/{order}', OrderDetail::class)->name('order.detail');
        Route::get('/order/{order}/edit', OrderForm::class)->name('order.edit');
    });

        // Modul 5 — Distribusi / Pengiriman
    Route::middleware('can:distribusi')->prefix('distribusi')->group(function () {
        Route::get('/', JadwalIndex::class)->name('distribusi');
        Route::get('/jadwal/buat', JadwalForm::class)->name('distribusi.buat');          // harus di atas /jadwal/{jadwal}
        Route::get('/jadwal/{jadwal}', JadwalDetail::class)->name('distribusi.detail');
        Route::get('/jadwal/{jadwal}/edit', JadwalForm::class)->name('distribusi.edit');
        Route::get('/riwayat', RiwayatPengiriman::class)->name('riwayat-pengiriman');
    });

        // Modul 6 — Keuangan (Owner & Admin Keuangan)
    Route::middleware('can:keuangan')->prefix('keuangan')->group(function () {
        Route::get('/', LabaRugi::class)->name('keuangan');
        Route::get('/arus-kas', ArusKas::class)->name('arus-kas');
        Route::get('/biaya-operasional', BiayaOperasional::class)->name('biaya-operasional');
        Route::get('/hpp', HppProduksi::class)->name('hpp');
        Route::get('/periode', PeriodeKeuangan::class)->name('periode');
    });
    // Modul 7 — SDM (HANYA Owner)
    Route::middleware('can:sdm')->prefix('sdm')->group(function () {
        Route::redirect('/', '/sdm/karyawan')->name('sdm');
        Route::get('/karyawan', DaftarKaryawan::class)->name('karyawan');
        Route::get('/karyawan/{employee}', KaryawanDetail::class)->name('karyawan.detail');
        Route::get('/payroll', PayrollIndex::class)->name('payroll');
        Route::get('/payroll/{run}', PayrollRunDetail::class)->name('payroll.detail');
    });

    // Modul 8 — Ekspor laporan (B.8.7)
    Route::prefix('ekspor')->group(function () {
        Route::get('/laba-rugi/{format}', [EksporController::class, 'labaRugi'])
            ->whereIn('format', ['pdf', 'xlsx'])->middleware('can:keuangan')->name('ekspor.laba-rugi');
        Route::get('/stok/{format}', [EksporController::class, 'stok'])
            ->whereIn('format', ['pdf', 'xlsx'])->middleware('can:ringkasan.stok')->name('ekspor.stok');
    });

        // Profil — semua role
    Route::get('/profil', Profil::class)->name('profil');

    // Pengaturan — khusus Owner (PRD A.4)
    Route::middleware('can:pengaturan')->prefix('pengaturan')->group(function () {
        Route::get('/', AkunPengguna::class)->name('pengaturan');
        Route::get('/backup', Cadangan::class)->name('pengaturan.backup');
        Route::get('/backup/{nama}/unduh', UnduhBackupController::class)->name('pengaturan.backup.unduh');
    });
});