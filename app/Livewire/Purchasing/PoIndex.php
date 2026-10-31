<?php

namespace App\Livewire\Purchasing;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.panel')]
#[Title('Purchase Order · Koombaah')]
class PoIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')] public string $search = '';
    #[Url] public string $status = '';
    #[Url] public string $supplier = '';

    public function mount(): void
    {
        $this->authorize('purchasing');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $daftar = PurchaseOrder::query()
            ->with('supplier:id,nama_supplier')
            ->withCount([
                'items',
                'items as items_lengkap_count' => fn ($q) => $q->whereColumn('jumlah_diterima', '>=', 'jumlah_dipesan'),
            ])
            ->when($this->search, fn ($q) => $q->where('nomor_po', 'ilike', "%{$this->search}%"))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->supplier, fn ($q) => $q->where('supplier_id', (int) $this->supplier))
            ->orderByDesc('tanggal_po')
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.purchasing.po-index', [
            'daftar' => $daftar,
            'suppliers' => Supplier::orderBy('nama_supplier')->get(['id', 'nama_supplier']),
        ]);
    }
}