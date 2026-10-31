<?php

namespace App\Livewire\Produksi;

use App\Exceptions\StokBahanKurangException;
use App\Livewire\Concerns\PesanValidasi;
use App\Models\FinishedProduct;
use App\Services\ProductionService;
use DomainException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Batch Produksi Baru · Koombaah')]
class BatchForm extends Component
{
    use PesanValidasi;

    #[Url(as: 'produk')]
    public string $produkId = '';

    public string $jumlah_target = '';
    public string $tanggal_mulai = '';

    public function mount(): void
    {
        $this->authorize('produksi');
        $this->tanggal_mulai = now()->format('Y-m-d');
    }

    public function mulai()
    {
        $this->authorize('produksi');

        $this->validate([
            'produkId' => ['required', Rule::exists('finished_products', 'id')->where('status', 'aktif')],
            'jumlah_target' => ['required', 'numeric', 'gt:0'],
            'tanggal_mulai' => ['required', 'date', 'before_or_equal:today'],
        ], attributes: [
            'produkId' => 'Produk',
            'jumlah_target' => 'Jumlah target',
            'tanggal_mulai' => 'Tanggal mulai',
        ]);

        try {
            $batch = app(ProductionService::class)->mulai(
                FinishedProduct::findOrFail((int) $this->produkId),
                $this->jumlah_target,
                $this->tanggal_mulai,
                auth()->user(),
            );
        } catch (StokBahanKurangException $e) {
            $this->addError('jumlah_target', $e->getMessage()); // blokir total
            return null;
        } catch (DomainException $e) {
            $this->addError('produkId', $e->getMessage());
            return null;
        }

        session()->flash('toast', "{$batch->nomor_batch} dimulai. Stok bahan baku sudah dikurangi.");

        return $this->redirectRoute('produksi.detail', ['batch' => $batch], navigate: true);
    }

    public function render()
    {
        // Hanya produk aktif yang sudah punya resep aktif yang bisa diproduksi
        $produkList = FinishedProduct::where('status', 'aktif')->whereHas('activeBom')
            ->with('activeBom')->orderBy('nama_produk')->get();

        $produk = $produkList->firstWhere('id', (int) $this->produkId);
        $targetValid = is_numeric($this->jumlah_target) && (float) $this->jumlah_target > 0;

        $kebutuhan = $produk && $targetValid
            ? app(ProductionService::class)->hitungKebutuhan($produk, $this->jumlah_target)
            : collect();

        return view('livewire.produksi.batch-form', [
            'produkList' => $produkList,
            'tanpaResep' => FinishedProduct::where('status', 'aktif')->whereDoesntHave('activeBom')->count(),
            'produk' => $produk,
            'kebutuhan' => $kebutuhan,
            'adaKurang' => $kebutuhan->contains(fn ($k) => ! $k['cukup']),
        ]);
    }
}