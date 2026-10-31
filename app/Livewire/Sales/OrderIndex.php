<?php

namespace App\Livewire\Sales;

use App\Models\SalesOrder;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.panel')]
#[Title('Sales Order · Koombaah')]
class OrderIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')] public string $search = '';
    #[Url] public string $status = '';
    #[Url] public string $ring = '';
    #[Url] public string $bulan = '';

    public function mount(): void
    {
        $this->authorize('sales');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $daftar = SalesOrder::query()
            ->with('partner:id,kode_mitra,nama_usaha,wilayah_ring')
            ->withCount('items')
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('nomor_order', 'ilike', "%{$this->search}%")
                ->orWhereHas('partner', fn ($p) => $p->where('nama_usaha', 'ilike', "%{$this->search}%"))))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->ring, fn ($q) => $q->whereHas('partner', fn ($p) => $p->where('wilayah_ring', $this->ring)))
            ->when(preg_match('/^\d{4}-\d{2}$/', $this->bulan), function ($q) {
                $awal = Carbon::parse($this->bulan . '-01');
                $q->whereBetween('tanggal_order', [$awal->toDateString(), $awal->copy()->endOfMonth()->toDateString()]);
            })
            ->orderByDesc('tanggal_order')
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.sales.order-index', ['daftar' => $daftar]);
    }
}