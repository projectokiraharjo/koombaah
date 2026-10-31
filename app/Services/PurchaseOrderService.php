<?php

namespace App\Services;

use App\Enums\MetodePembayaran;
use App\Enums\ReferensiStok;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPO;
use App\Enums\TipePergerakan;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Support\Format;
use DomainException;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(private StockService $stok, private FinanceService $keuangan)
    {
    }

    /** Buat PO baru / ubah PO draft (B.2.4-A). Total dihitung otomatis. */
    public function simpanDraft(?PurchaseOrder $po, int $supplierId, string $tanggal, array $baris, User $oleh): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $supplierId, $tanggal, $baris, $oleh) {
            if ($po) {
                $po = PurchaseOrder::lockForUpdate()->findOrFail($po->id);
                if (! $po->bisaDiedit()) {
                    throw new DomainException('Hanya PO berstatus draft yang dapat diubah.');
                }
                $po->items()->delete();
            } else {
                $po = new PurchaseOrder([
                    'nomor_po' => PurchaseOrder::generateNomor($tanggal),
                    'status' => StatusPO::Draft,
                    'metode_pembayaran' => MetodePembayaran::Tunai, // B.2.7: seluruh pembelian tunai
                    'status_pembayaran' => StatusPembayaran::BelumDibayar,
                    'dibuat_oleh' => $oleh->id,
                ]);
            }

            $po->fill(['supplier_id' => $supplierId, 'tanggal_po' => $tanggal, 'total_nilai_po' => 0])->save();

            $total = 0;
            foreach ($baris as $b) {
                $subtotal = round((float) $b['jumlah_dipesan'] * (float) $b['harga_per_satuan'], 2);
                $po->items()->create([
                    'raw_material_id' => $b['raw_material_id'],
                    'jumlah_dipesan' => $b['jumlah_dipesan'],
                    'jumlah_diterima' => 0,
                    'harga_per_satuan' => $b['harga_per_satuan'],
                    'subtotal' => $subtotal,
                ]);
                $total += $subtotal;
            }

            $po->update(['total_nilai_po' => $total]);

            return $po;
        });
    }

    /** draft -> dipesan */
    public function pesan(PurchaseOrder $po): void
    {
        DB::transaction(function () use ($po) {
            $po = PurchaseOrder::lockForUpdate()->findOrFail($po->id);

            if ($po->status !== StatusPO::Draft) {
                throw new DomainException('Hanya PO draft yang dapat dipesan.');
            }
            if ($po->items()->doesntExist()) {
                throw new DomainException('PO belum memiliki item.');
            }

            $po->update(['status' => StatusPO::Dipesan]);
        });
    }

    public function batalkan(PurchaseOrder $po): void
    {
        DB::transaction(function () use ($po) {
            $po = PurchaseOrder::lockForUpdate()->findOrFail($po->id);

            if (! $po->bisaDibatalkan()) {
                throw new DomainException('PO yang sudah menerima barang tidak dapat dibatalkan.');
            }

            $po->update(['status' => StatusPO::Dibatalkan]);
        });
    }

    /**
     * Penerimaan barang (B.2.4-B), boleh sebagian.
     *
     * @param  array<int|string, int|float|string|null>  $jumlahPerItem  [purchase_order_item_id => jumlah]
     */
    public function terimaBarang(PurchaseOrder $po, array $jumlahPerItem, string $tanggal, ?string $catatan, User $oleh): GoodsReceipt
    {
        return DB::transaction(function () use ($po, $jumlahPerItem, $tanggal, $catatan, $oleh) {
            $po = PurchaseOrder::lockForUpdate()->findOrFail($po->id);

            if (! $po->bisaDiterima()) {
                throw new DomainException('PO ini tidak dalam status yang dapat menerima barang.');
            }

            $jumlahPerItem = array_filter($jumlahPerItem, fn ($j) => is_numeric($j) && (float) $j > 0);
            if (empty($jumlahPerItem)) {
                throw new DomainException('Isi minimal satu jumlah barang yang diterima.');
            }

            $items = $po->items()->with('rawMaterial')->lockForUpdate()->get()->keyBy('id');

            $penerimaan = GoodsReceipt::create([
                'purchase_order_id' => $po->id,
                'tanggal_terima' => $tanggal,
                'diterima_oleh' => $oleh->id,
                'catatan' => $catatan,
            ]);
            $nominal = 0;
            foreach ($jumlahPerItem as $itemId => $jumlah) {
                $item = $items[$itemId] ?? throw new DomainException('Item PO tidak valid.');

                // B.2.7: tidak boleh melebihi jumlah dipesan
                if (round((float) $jumlah - $item->sisa(), 3) > 0) {
                    throw new DomainException(sprintf(
                        'Jumlah diterima %s (%s) melebihi sisa pesanan (%s %s).',
                        $item->rawMaterial->nama_bahan, Format::angka($jumlah),
                        Format::angka($item->sisa()), $item->rawMaterial->satuan->value,
                    ));
                }

                $penerimaan->items()->create(['purchase_order_item_id' => $item->id, 'jumlah_diterima' => $jumlah]);
                $item->increment('jumlah_diterima', $jumlah);
                $nominal += round((float) $jumlah * (float) $item->harga_per_satuan, 2);

                // Stok bahan baku bertambah (log referensi ke PO)
                $this->stok->catat(
                    $item->rawMaterial, TipePergerakan::Masuk, $jumlah,
                    ReferensiStok::Purchasing, $po->id, "Penerimaan {$po->nomor_po}", $oleh,
                );

                // Harga beli terakhir diperbarui otomatis (B.2.7)
                $item->rawMaterial->update(['harga_per_satuan_terakhir' => $item->harga_per_satuan]);
            }

            $lengkap = $po->items()->whereColumn('jumlah_diterima', '<', 'jumlah_dipesan')->doesntExist();
            $po->update(['status' => $lengkap ? StatusPO::DiterimaPenuh : StatusPO::SebagianDiterima]);

            // B.6.4-B: kas keluar otomatis sebesar nilai barang yang diterima pada transaksi ini
            $this->keuangan->catatKasKeluarPembelian($po, $penerimaan, $nominal, $oleh);
            return $penerimaan;
        });
    }
}