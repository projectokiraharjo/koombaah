<?php

namespace App\Livewire\Inventory;

use App\Enums\ReferensiStok;
use App\Enums\TipePergerakan;
use App\Exceptions\StokTidakCukupException;
use App\Livewire\Concerns\PesanValidasi;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.panel')]
#[Title('Stok Bahan Baku · Koombaah')]
class StokBahanBaku extends Component
{
    use WithPagination, PesanValidasi;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'menipis')]
    public bool $hanyaMenipis = false;

    public bool $showForm = false;
    public ?int $itemId = null;
    public string $tipe = 'masuk';
    public string $jumlah = '';
    public string $catatan = '';

    public function mount(): void
    {
        $this->authorize('inventory');
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedHanyaMenipis(): void { $this->resetPage(); }

    public function bukaForm(int $id, string $tipe = 'masuk'): void
    {
        $this->authorize('inventory');
        $this->reset(['jumlah', 'catatan']);
        $this->resetValidation();

        $this->itemId = RawMaterial::findOrFail($id)->id;
        $this->tipe = $tipe === 'keluar' ? 'keluar' : 'masuk';
        $this->showForm = true;
    }

    public function simpan(StockService $stok): void
    {
        $this->authorize('inventory');

        $this->validate([
            'tipe' => ['required', Rule::enum(TipePergerakan::class)],
            'jumlah' => ['required', 'numeric', 'gt:0'],
            'catatan' => ['required', 'string', 'max:500'], // PRD B.1.7: wajib ada alasan
        ]);

        $item = RawMaterial::findOrFail($this->itemId);

        try {
            $stok->catat(
                $item,
                TipePergerakan::from($this->tipe),
                $this->jumlah,
                ReferensiStok::PenyesuaianManual,
                catatan: $this->catatan,
            );
        } catch (StokTidakCukupException $e) {
            $this->addError('jumlah', $e->getMessage()); // stok tidak boleh minus -> ditolak
            return;
        }

        $this->showForm = false;
        $this->dispatch('stok-berubah');
        $this->dispatch('toast', message: "Stok {$item->nama_bahan} berhasil dicatat.");
    }

    protected function validationAttributes(): array
    {
        return ['tipe' => 'Jenis pergerakan', 'jumlah' => 'Jumlah', 'catatan' => 'Catatan alasan'];
    }

    public function render()
    {
        $items = RawMaterial::query()
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('nama_bahan', 'ilike', "%{$this->search}%")
                ->orWhere('kode_bahan', 'ilike', "%{$this->search}%")))
            ->when($this->hanyaMenipis, fn ($q) => $q->menipis())
            ->orderBy('kode_bahan')
            ->paginate(10);

        return view('livewire.inventory.stok-bahan-baku', [
            'items' => $items,
            'terpilih' => $this->itemId ? RawMaterial::find($this->itemId) : null,
            'riwayat' => StockMovement::with(['item', 'user:id,name'])
                ->where('item_type', 'raw_material')
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
        ]);
    }
}