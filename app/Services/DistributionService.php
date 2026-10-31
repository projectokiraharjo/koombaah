<?php

namespace App\Services;

use App\Enums\StatusJadwal;
use App\Enums\StatusOrder;
use App\Enums\StatusPengirimanItem;
use App\Models\DeliverySchedule;
use App\Models\DeliveryScheduleItem;
use App\Models\SalesOrder;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class DistributionService
{
    public function __construct(private SalesOrderService $sales, private FinanceService $keuangan)
    {
    }

    /**
     * Buat / ubah jadwal (B.5.4 langkah 1–2). Hanya order siap_kirim yang belum ada di jadwal aktif lain.
     *
     * @param  array{wilayah_ring: string, nama_kurir: string, tanggal_rencana_kirim: string, estimasi_biaya_distribusi: ?string}  $data
     */
    public function simpanJadwal(?DeliverySchedule $jadwal, array $data, array $orderIds, User $oleh): DeliverySchedule
    {
        return DB::transaction(function () use ($jadwal, $data, $orderIds, $oleh) {
            $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
            if (empty($orderIds)) {
                throw new DomainException('Pilih minimal satu order untuk dikirim.');
            }

            if ($jadwal) {
                $jadwal = DeliverySchedule::lockForUpdate()->findOrFail($jadwal->id);
                if (! $jadwal->bisaDiubah()) {
                    throw new DomainException('Jadwal yang sudah berangkat tidak dapat diubah.');
                }
            }

            $orders = SalesOrder::whereIn('id', $orderIds)->lockForUpdate()->get();
            if ($orders->count() !== count($orderIds)) {
                throw new DomainException('Sebagian order tidak ditemukan.');
            }

            foreach ($orders as $o) {
                if ($o->status !== StatusOrder::SiapKirim) {
                    throw new DomainException("{$o->nomor_order} tidak berstatus Siap Kirim.");
                }
                $diJadwalLain = DeliveryScheduleItem::where('sales_order_id', $o->id)
                    ->where('status_pengiriman_item', StatusPengirimanItem::Menunggu->value)
                    ->when($jadwal, fn ($q) => $q->where('delivery_schedule_id', '!=', $jadwal->id))
                    ->exists();
                if ($diJadwalLain) {
                    throw new DomainException("{$o->nomor_order} sudah masuk jadwal pengiriman lain.");
                }
            }

            if ($jadwal) {
                $jadwal->update($data);
                $jadwal->items()->whereNotIn('sales_order_id', $orderIds)->delete(); // order yang dikeluarkan
                $sudahAda = $jadwal->items()->pluck('sales_order_id')->all();
            } else {
                $jadwal = DeliverySchedule::create($data + [
                    'kode_jadwal' => DeliverySchedule::generateKode($data['tanggal_rencana_kirim']),
                    'status' => StatusJadwal::Dijadwalkan,
                    'dibuat_oleh' => $oleh->id,
                ]);
                $sudahAda = [];
            }

            foreach (array_diff($orderIds, $sudahAda) as $id) {
                $jadwal->items()->create([
                    'sales_order_id' => $id,
                    'status_pengiriman_item' => StatusPengirimanItem::Menunggu,
                ]);
            }

            return $jadwal;
        });
    }

    /** dijadwalkan -> dalam_perjalanan (B.5.4 langkah 3) */
    public function berangkat(DeliverySchedule $jadwal): void
    {
        DB::transaction(function () use ($jadwal) {
            $jadwal = DeliverySchedule::lockForUpdate()->findOrFail($jadwal->id);

            if (! $jadwal->bisaDiubah()) {
                throw new DomainException('Jadwal ini sudah berangkat.');
            }
            if ($jadwal->items()->doesntExist()) {
                throw new DomainException('Jadwal belum berisi order.');
            }

            $jadwal->update([
                'status' => StatusJadwal::DalamPerjalanan,
                'tanggal_aktual_kirim' => now()->toDateString(),
            ]);

            // B.6.4-C: estimasi biaya distribusi dicatat sebagai kas keluar
            $this->keuangan->catatKasKeluarDistribusi($jadwal->refresh());
        });
    }

    /**
     * Catat hasil pengiriman berdasarkan laporan kurir (B.5.4 langkah 4–6).
     *
     * @param  array<int|string, array{status?: string, catatan?: ?string}>  $hasil  [item_id => ...]
     * @return int jumlah order yang dicatat
     */
    public function catatHasil(DeliverySchedule $jadwal, array $hasil, string $waktuTerkirim): int
    {
        return DB::transaction(function () use ($jadwal, $hasil, $waktuTerkirim) {
            $jadwal = DeliverySchedule::lockForUpdate()->findOrFail($jadwal->id);

            if ($jadwal->status !== StatusJadwal::DalamPerjalanan) {
                throw new DomainException('Hasil pengiriman hanya bisa dicatat saat jadwal berstatus Dalam Perjalanan.');
            }

            $items = $jadwal->items()->with('salesOrder')->lockForUpdate()->get()->keyBy('id');
            $dicatat = 0;

            foreach ($hasil as $itemId => $h) {
                $status = $h['status'] ?? '';
                $catatan = trim((string) ($h['catatan'] ?? ''));

                if (! in_array($status, ['terkirim', 'gagal_kirim'], true)) {
                    continue; // dibiarkan menunggu (kurir belum melapor)
                }

                $item = $items[$itemId] ?? throw new DomainException('Item jadwal tidak valid.');
                if ($item->status_pengiriman_item !== StatusPengirimanItem::Menunggu) {
                    continue;
                }

                if ($status === 'gagal_kirim') {
                    if ($catatan === '') {
                        throw new DomainException("Alasan gagal kirim untuk {$item->salesOrder->nomor_order} wajib diisi.");
                    }
                    // B.5.7: order tetap siap_kirim -> bisa masuk jadwal berikutnya
                    $item->update(['status_pengiriman_item' => StatusPengirimanItem::GagalKirim, 'catatan' => $catatan]);
                } else {
                    $item->update([
                        'status_pengiriman_item' => StatusPengirimanItem::Terkirim,
                        'waktu_terkirim' => $waktuTerkirim,
                        'catatan' => $catatan ?: null,
                    ]);
                    $this->sales->selesaikanPengiriman($item->salesOrder, substr($waktuTerkirim, 0, 10)); // order -> selesai, lunas, kas masuk
                }

                $dicatat++;
            }

            if ($dicatat === 0) {
                throw new DomainException('Pilih hasil (Terkirim / Gagal) untuk minimal satu order.');
            }

            if ($jadwal->items()->where('status_pengiriman_item', StatusPengirimanItem::Menunggu->value)->doesntExist()) {
                $jadwal->update(['status' => StatusJadwal::Selesai]);
            }

            return $dicatat;
        });
    }

    /** Hapus jadwal yang belum berangkat (order kembali ke daftar siap kirim) */
    public function hapus(DeliverySchedule $jadwal): void
    {
        DB::transaction(function () use ($jadwal) {
            $jadwal = DeliverySchedule::lockForUpdate()->findOrFail($jadwal->id);

            if (! $jadwal->bisaDiubah()) {
                throw new DomainException('Jadwal yang sudah berangkat tidak dapat dihapus.');
            }

            $jadwal->delete();
        });
    }
}