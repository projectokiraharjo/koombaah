<?php

namespace App\Services;

use App\Enums\MetodePembayaran;
use App\Enums\ReferensiStok;
use App\Enums\StatusOrder;
use App\Enums\StatusPembayaran;
use App\Enums\TipePergerakan;
use App\Exceptions\StokProdukKurangException;
use App\Models\FinishedProduct;
use App\Models\SalesOrder;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    public function __construct(private StockService $stok, private FinanceService $keuangan)
    {
    }

    /** Catat / ubah order berstatus baru. Harga = snapshot harga jual master (B.4.7). */
    public function simpan(?SalesOrder $order, int $partnerId, string $tanggal, array $baris, ?string $catatan, User $oleh): SalesOrder
    {
        return DB::transaction(function () use ($order, $partnerId, $tanggal, $baris, $catatan, $oleh) {
            $hargaLama = [];

            if ($order) {
                $order = SalesOrder::lockForUpdate()->findOrFail($order->id);
                if (! $order->bisaDiedit()) {
                    throw new DomainException('Hanya order berstatus baru yang dapat diubah.');
                }
                // Produk yang sudah ada di order tetap memakai harga snapshot lamanya
                $hargaLama = $order->items()->pluck('harga_per_satuan', 'finished_product_id')->all();
                $order->items()->delete();
            } else {
                $order = new SalesOrder([
                    'nomor_order' => SalesOrder::generateNomor($tanggal),
                    'status' => StatusOrder::Baru,
                    'metode_pembayaran' => MetodePembayaran::Tunai, // B.4.7: seluruh order tunai
                    'status_pembayaran' => StatusPembayaran::BelumDibayar,
                    'dicatat_oleh' => $oleh->id,
                ]);
            }

            $order->fill([
                'partner_id' => $partnerId,
                'tanggal_order' => $tanggal,
                'catatan' => $catatan,
                'total_nilai_order' => 0,
            ])->save();

            $produk = FinishedProduct::whereIn('id', array_column($baris, 'finished_product_id'))->get()->keyBy('id');

            $total = 0;
            foreach ($baris as $b) {
                $id = (int) $b['finished_product_id'];
                $harga = $hargaLama[$id] ?? $produk[$id]->harga_jual_per_satuan;
                $subtotal = round((float) $b['jumlah_dipesan'] * (float) $harga, 2);

                $order->items()->create([
                    'finished_product_id' => $id,
                    'jumlah_dipesan' => $b['jumlah_dipesan'],
                    'harga_per_satuan' => $harga,
                    'subtotal' => $subtotal,
                ]);
                $total += $subtotal;
            }

            $order->update(['total_nilai_order' => $total]);

            return $order;
        });
    }

    /** baru -> dikonfirmasi: validasi stok (blokir total) lalu potong stok (B.4.4 langkah 3–5) */
    public function konfirmasi(SalesOrder $order, User $oleh): void
    {
        DB::transaction(function () use ($order, $oleh) {
            $order = SalesOrder::with('items')->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== StatusOrder::Baru) {
                throw new DomainException('Hanya order berstatus baru yang dapat dikonfirmasi.');
            }
            if ($order->items->isEmpty()) {
                throw new DomainException('Order belum memiliki item.');
            }

            $produk = FinishedProduct::whereIn('id', $order->items->pluck('finished_product_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            // B.4.7 — BLOKIR TOTAL: semua item dicek dulu sebelum ada stok yang dipotong
            $kurang = [];
            foreach ($order->items as $item) {
                $p = $produk[$item->finished_product_id];
                $selisih = round((float) $item->jumlah_dipesan - (float) $p->stok_saat_ini, 3);
                if ($selisih > 0) {
                    $kurang[] = ['nama' => $p->nama_produk, 'kurang' => $selisih, 'satuan' => $p->satuan_jual->value];
                }
            }
            if ($kurang) {
                throw new StokProdukKurangException($kurang);
            }

            foreach ($order->items as $item) {
                $this->stok->catat(
                    $produk[$item->finished_product_id], TipePergerakan::Keluar, $item->jumlah_dipesan,
                    ReferensiStok::Sales, $order->id, "Order {$order->nomor_order}", $oleh,
                );
            }

            $order->update(['status' => StatusOrder::Dikonfirmasi]);
        });
    }

    /** dikonfirmasi -> diproses -> siap_kirim (US 4) */
    public function lanjutkan(SalesOrder $order): StatusOrder
    {
        return DB::transaction(function () use ($order) {
            $order = SalesOrder::lockForUpdate()->findOrFail($order->id);

            $berikut = $order->status->berikutnyaManual()
                ?? throw new DomainException('Status order ini tidak dapat dilanjutkan secara manual.');

            $data = ['status' => $berikut];
            if ($berikut === StatusOrder::SiapKirim && ! $order->siap_kirim_pada) {
                $data['siap_kirim_pada'] = now(); // dasar hitung waktu rata-rata pengiriman (B.5.3 US 6)
            }
            $order->update($data);
            return $berikut;
        });
    }

    /** Batalkan order. Stok yang sudah terpotong dikembalikan otomatis (B.4.7). */
    public function batalkan(SalesOrder $order, string $alasan, User $oleh): void
    {
        DB::transaction(function () use ($order, $alasan, $oleh) {
            $order = SalesOrder::with('items.product')->lockForUpdate()->findOrFail($order->id);

            if (! $order->bisaDibatalkan()) {
                throw new DomainException('Order yang sudah terkirim atau selesai tidak dapat dibatalkan.');
            }
            if ($order->deliveryItems()->where('status_pengiriman_item', 'menunggu')->exists()) {
                throw new DomainException('Order ini sudah masuk jadwal pengiriman aktif. Keluarkan dulu dari jadwal (menu Distribusi) sebelum dibatalkan.');
            }
            if (blank($alasan)) {
                throw new DomainException('Alasan pembatalan wajib diisi.');
            }

            if ($order->status->stokSudahDipotong()) {
                foreach ($order->items as $item) {
                    $this->stok->catat(
                        $item->product, TipePergerakan::Masuk, $item->jumlah_dipesan,
                        ReferensiStok::Sales, $order->id,
                        "Pengembalian stok akibat pembatalan {$order->nomor_order}: {$alasan}", $oleh,
                    );
                }
            }

            $order->update([
                'status' => StatusOrder::Dibatalkan,
                'catatan' => trim(($order->catatan ? $order->catatan . "\n" : '') . 'Dibatalkan: ' . $alasan),
            ]);
        });
    }
        /**
     * Dipanggil Modul Distribusi saat order berhasil diantar (B.5.4 langkah 5).
     * Seluruh order tunai -> uang diterima saat barang sampai -> langsung selesai (B.4.4 langkah 7).
     */
    public function selesaikanPengiriman(SalesOrder $order, ?string $tanggal = null): void    
    {
        $order = SalesOrder::lockForUpdate()->findOrFail($order->id);

        if ($order->status !== StatusOrder::SiapKirim) {
            throw new DomainException("{$order->nomor_order} tidak berstatus Siap Kirim.");
        }

        // terkirim -> selesai (tunai) dalam satu langkah
        $order->update([
            'status' => StatusOrder::Selesai,
            'status_pembayaran' => StatusPembayaran::Lunas,
        ]);

        // B.6.4-A: kas masuk otomatis sebesar total nilai order
        $this->keuangan->catatKasMasukPenjualan($order, $tanggal ?? now()->toDateString());    
    }
}