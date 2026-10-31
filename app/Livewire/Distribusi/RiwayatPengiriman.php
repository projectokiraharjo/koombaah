<?php

namespace App\Livewire\Distribusi;

use App\Models\DeliverySchedule;
use App\Models\DeliveryScheduleItem;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.panel')]
#[Title('Riwayat Pengiriman · Koombaah')]
class RiwayatPengiriman extends Component
{
    use WithPagination;

    #[Url] public string $ring = '';
    #[Url] public string $mitra = '';
    #[Url] public string $bulan = '';
    #[Url] public string $status = '';

    public function mount(): void
    {
        $this->authorize('distribusi');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    private function bulanValid(): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}$/', $this->bulan);
    }

    /** Filter dasar item pengiriman (join jadwal, order, mitra) */
    private function query(): Builder
    {
        return DeliveryScheduleItem::query()
            ->join('delivery_schedules as ds', 'ds.id', '=', 'delivery_schedule_items.delivery_schedule_id')
            ->join('sales_orders as so', 'so.id', '=', 'delivery_schedule_items.sales_order_id')
            ->join('partners as p', 'p.id', '=', 'so.partner_id')
            ->when($this->ring, fn ($q) => $q->where('p.wilayah_ring', $this->ring))
            ->when($this->mitra, fn ($q) => $q->where('p.id', (int) $this->mitra))
            ->when($this->status, fn ($q) => $q->where('delivery_schedule_items.status_pengiriman_item', $this->status))
            ->when($this->bulanValid(), function ($q) {
                $awal = Carbon::parse($this->bulan . '-01');
                $q->whereBetween('ds.tanggal_rencana_kirim', [$awal->toDateString(), $awal->copy()->endOfMonth()->toDateString()]);
            });
    }

    public function render()
    {
        // US 6: efisiensi per Ring — berhasil, gagal, waktu rata-rata siap_kirim -> terkirim
        $perRing = $this->query()
            ->groupBy('p.wilayah_ring')
            ->selectRaw("p.wilayah_ring,
                COUNT(*) AS jumlah,
                SUM(CASE WHEN delivery_schedule_items.status_pengiriman_item = 'terkirim' THEN 1 ELSE 0 END) AS terkirim,
                SUM(CASE WHEN delivery_schedule_items.status_pengiriman_item = 'gagal_kirim' THEN 1 ELSE 0 END) AS gagal,
                AVG(CASE WHEN delivery_schedule_items.status_pengiriman_item = 'terkirim' AND so.siap_kirim_pada IS NOT NULL
                    THEN EXTRACT(EPOCH FROM (delivery_schedule_items.waktu_terkirim - so.siap_kirim_pada)) / 3600 END) AS rata_jam")
            ->orderBy('p.wilayah_ring')
            ->toBase()
            ->get()
            ->keyBy('wilayah_ring');

        // Estimasi biaya distribusi per Ring (dicatat per jadwal — B.5.7)
        $biayaPerRing = DeliverySchedule::query()
            ->when($this->ring, fn ($q) => $q->where('wilayah_ring', $this->ring))
            ->when($this->bulanValid(), function ($q) {
                $awal = Carbon::parse($this->bulan . '-01');
                $q->whereBetween('tanggal_rencana_kirim', [$awal->toDateString(), $awal->copy()->endOfMonth()->toDateString()]);
            })
            ->groupBy('wilayah_ring')
            ->selectRaw('wilayah_ring, COUNT(*) AS jumlah_jadwal, COALESCE(SUM(estimasi_biaya_distribusi), 0) AS total_biaya')
            ->toBase()
            ->get()
            ->keyBy('wilayah_ring');

        // US 7: daftar pengiriman (bisa difilter per mitra)
        $riwayat = $this->query()
            ->select('delivery_schedule_items.*')
            ->with(['schedule', 'salesOrder.partner'])
            ->orderByDesc('ds.tanggal_rencana_kirim')
            ->orderByDesc('delivery_schedule_items.id')
            ->paginate(20);

        return view('livewire.distribusi.riwayat-pengiriman', [
            'perRing' => $perRing,
            'biayaPerRing' => $biayaPerRing,
            'riwayat' => $riwayat,
            'mitraList' => Partner::orderBy('nama_usaha')->get(['id', 'nama_usaha']),
            'labelPeriode' => $this->bulanValid() ? Carbon::parse($this->bulan . '-01')->translatedFormat('F Y') : 'semua periode',
        ]);
    }
}