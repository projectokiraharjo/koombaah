<?php

namespace App\Console\Commands;

use App\Enums\StatusPengirimanItem;
use App\Models\DeliverySchedule;
use App\Models\GoodsReceipt;
use App\Models\ProductionBatch;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\FinanceService;
use DomainException;
use Illuminate\Console\Command;

/**
 * Mencatat kas & HPP untuk data yang dibuat SEBELUM Modul Keuangan ada.
 * Aman dijalankan berulang — data yang sudah tercatat dilewati.
 */
class SinkronKeuangan extends Command
{
    protected $signature = 'keuangan:sinkron';
    protected $description = 'Catat kas & HPP untuk transaksi lama (idempoten)';

    public function handle(FinanceService $keuangan): int
    {
        $owner = User::where('role', 'owner')->firstOrFail();
        $hitung = ['Kas penjualan' => 0, 'Kas pembelian' => 0, 'Kas distribusi' => 0, 'HPP batch' => 0, 'Dilewati (periode ditutup)' => 0];

        $coba = function (string $jenis, callable $fn) use (&$hitung) {
            try {
                if ($fn()) {
                    $hitung[$jenis]++;
                }
            } catch (DomainException $e) {
                $hitung['Dilewati (periode ditutup)']++;
                $this->warn($e->getMessage());
            }
        };

        SalesOrder::where('status', 'selesai')->with('deliveryItems')->each(function ($o) use ($keuangan, $owner, $coba) {
            $waktu = $o->deliveryItems->where('status_pengiriman_item', StatusPengirimanItem::Terkirim)->max('waktu_terkirim');
            $tanggal = $waktu ? $waktu->toDateString() : $o->updated_at->toDateString();
            $coba('Kas penjualan', fn () => $keuangan->catatKasMasukPenjualan($o, $tanggal, $owner)->wasRecentlyCreated);
        });

        GoodsReceipt::with(['items.poItem', 'purchaseOrder'])->each(function ($gr) use ($keuangan, $owner, $coba) {
            $nominal = $gr->items->sum(fn ($i) => (float) $i->jumlah_diterima * (float) $i->poItem->harga_per_satuan);
            $coba('Kas pembelian', fn () => $keuangan->catatKasKeluarPembelian($gr->purchaseOrder, $gr, $nominal, $owner) !== null);
        });

        DeliverySchedule::where('status', '!=', 'dijadwalkan')->each(function ($j) use ($keuangan, $owner, $coba) {
            $coba('Kas distribusi', fn () => ($t = $keuangan->catatKasKeluarDistribusi($j, $owner)) && $t->wasRecentlyCreated);
        });

        ProductionBatch::where('status', 'selesai')->each(function ($b) use ($keuangan, $coba) {
            $coba('HPP batch', fn () => ($h = $keuangan->hitungHppBatch($b)) && $h->wasRecentlyCreated);
        });

        $this->table(['Jenis', 'Baru dicatat'], collect($hitung)->map(fn ($v, $k) => [$k, $v])->values());

        return self::SUCCESS;
    }
}