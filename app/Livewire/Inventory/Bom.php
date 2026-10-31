<?php

namespace App\Livewire\Inventory;

use App\Models\FinishedProduct;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Resep Produk (BOM) · Koombaah')]
class Bom extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('inventory');
    }

    public function render()
    {
        $produk = FinishedProduct::query()
            ->with(['activeBom' => fn ($q) => $q->withCount('details')->with('creator:id,name')])
            ->when($this->search, fn ($q) => $q->where('nama_produk', 'ilike', "%{$this->search}%"))
            ->orderBy('kode_produk')
            ->get();

        return view('livewire.inventory.bom', ['produk' => $produk]);
    }
}