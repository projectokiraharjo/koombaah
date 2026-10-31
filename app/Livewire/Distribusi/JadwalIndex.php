<?php

namespace App\Livewire\Distribusi;

use App\Models\DeliverySchedule;
use App\Models\SalesOrder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.panel')]
#[Title('Jadwal Pengiriman · Koombaah')]
class JadwalIndex extends Component
{
    use WithPagination;

    #[Url] public string $status = '';
    #[Url] public string $ring = '';

    public function mount(): void
    {
        $this->authorize('distribusi');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        // US 1 & 2: order siap kirim yang belum dijadwalkan, dikelompokkan per Ring
        $siapKirim = SalesOrder::bisaDijadwalkan()
            ->with('partner:id,nama_usaha,wilayah_ring')
            ->orderBy('siap_kirim_pada')
            ->get()
            ->groupBy(fn ($o) => $o->partner->wilayah_ring->value);

        $jadwal = DeliverySchedule::query()
            ->withCount([
                'items',
                'items as terkirim_count' => fn ($q) => $q->where('status_pengiriman_item', 'terkirim'),
                'items as gagal_count' => fn ($q) => $q->where('status_pengiriman_item', 'gagal_kirim'),
            ])
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->ring, fn ($q) => $q->where('wilayah_ring', $this->ring))
            ->orderByDesc('tanggal_rencana_kirim')
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.distribusi.jadwal-index', ['siapKirim' => $siapKirim, 'jadwal' => $jadwal]);
    }
}