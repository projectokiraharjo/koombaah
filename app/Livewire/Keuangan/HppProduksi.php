<?php

namespace App\Livewire\Keuangan;

use App\Models\FinishedProduct;
use App\Models\ProductionBatch;
use App\Services\LaporanKeuanganService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.panel')]
#[Title('HPP Produksi · Koombaah')]
class HppProduksi extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('keuangan');
    }

    public function render()
    {
        // US 5: HPP per produk & margin terhadap harga jual
        $hppRata = app(LaporanKeuanganService::class)->hppRataRata(now());
        $produk = FinishedProduct::whereIn('id', $hppRata->keys())->orderBy('nama_produk')->get()
            ->map(fn ($p) => [
                'produk' => $p,
                'hpp' => $hppRata[$p->id],
                'margin' => (float) $p->harga_jual_per_satuan - $hppRata[$p->id],
                'margin_persen' => (float) $p->harga_jual_per_satuan > 0
                    ? ((float) $p->harga_jual_per_satuan - $hppRata[$p->id]) / (float) $p->harga_jual_per_satuan * 100 : null,
            ]);

        return view('livewire.keuangan.hpp-produksi', [
            'produk' => $produk,
            'batches' => ProductionBatch::with(['product:id,nama_produk,satuan_jual', 'hpp'])
                ->where('status', 'selesai')
                ->orderByDesc('tanggal_selesai')->orderByDesc('id')
                ->paginate(15),
        ]);
    }
}