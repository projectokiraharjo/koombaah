<?php

namespace App\Livewire\Inventory;

use App\Services\LowStockService;
use Livewire\Attributes\On;
use Livewire\Component;

class LowStockBadge extends Component
{
    public string $jenis = 'total'; // bahan_baku | produk_jadi | total

    /** Dipicu setiap kali stok / stok minimum berubah -> badge dihitung ulang */
    #[On('stok-berubah')]
    public function segarkan(): void
    {
    }

    public function render()
    {
        $jumlah = auth()->user()?->can('inventory')
            ? (app(LowStockService::class)->jumlah()[$this->jenis] ?? 0)
            : 0;

        return view('livewire.inventory.low-stock-badge', ['jumlah' => $jumlah]);
    }
}