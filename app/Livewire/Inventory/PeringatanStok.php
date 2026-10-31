<?php

namespace App\Livewire\Inventory;

use App\Services\LowStockService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Peringatan Stok · Koombaah')]
class PeringatanStok extends Component
{
    public function mount(): void
    {
        $this->authorize('inventory');
    }

    #[On('stok-berubah')]
    public function segarkan(): void
    {
    }

    public function render()
    {
        $service = app(LowStockService::class);

        return view('livewire.inventory.peringatan-stok', [
            'bahanBaku' => $service->bahanBaku(),
            'produkJadi' => $service->produkJadi(),
        ]);
    }
}