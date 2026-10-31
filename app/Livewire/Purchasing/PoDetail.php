<?php

namespace App\Livewire\Purchasing;

use App\Livewire\Concerns\PesanValidasi;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use DomainException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.panel')]
class PoDetail extends Component
{
    use PesanValidasi;

    public PurchaseOrder $po;

    // Form penerimaan
    public bool $showForm = false;
    public string $tanggal_terima = '';
    public array $terima = [];   // [purchase_order_item_id => jumlah]
    public string $catatan = '';

    public function mount(PurchaseOrder $po): void
    {
        $this->authorize('purchasing');
        $this->po = $po;
    }

    public function pesan(): void
    {
        $this->jalankan(fn (PurchaseOrderService $s) => $s->pesan($this->po), "{$this->po->nomor_po} dipesan ke supplier.");
    }

    public function batalkan(): void
    {
        $this->jalankan(fn (PurchaseOrderService $s) => $s->batalkan($this->po), "{$this->po->nomor_po} dibatalkan.");
    }

    public function bukaPenerimaan(): void
    {
        $this->authorize('purchasing');
        $this->resetValidation();
        $this->tanggal_terima = now()->format('Y-m-d');
        $this->catatan = '';
        // Default: isi sisa penuh -> tinggal ubah jika yang datang hanya sebagian
        $this->terima = $this->po->items()->get()
            ->mapWithKeys(fn ($i) => [$i->id => (string) max(0, $i->sisa())])
            ->all();
        $this->showForm = true;
    }

    public function simpanPenerimaan(): void
    {
        $this->authorize('purchasing');

        $this->validate([
            'tanggal_terima' => ['required', 'date', 'before_or_equal:today'],
            'terima' => ['required', 'array'],
            'terima.*' => ['nullable', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], attributes: [
            'tanggal_terima' => 'Tanggal terima',
            'terima.*' => 'Jumlah diterima',
            'catatan' => 'Catatan',
        ]);

        try {
            app(PurchaseOrderService::class)->terimaBarang(
                $this->po, $this->terima, $this->tanggal_terima, $this->catatan ?: null, auth()->user(),
            );
        } catch (DomainException $e) {
            $this->addError('terima', $e->getMessage());
            return;
        }

        $this->showForm = false;
        $this->po->refresh();
        $this->dispatch('stok-berubah'); // badge low stock ikut ter-update
        $this->dispatch('toast', message: 'Penerimaan barang dicatat. Stok bahan baku bertambah.');
    }

    private function jalankan(callable $aksi, string $pesanSukses): void
    {
        $this->authorize('purchasing');

        try {
            $aksi(app(PurchaseOrderService::class));
        } catch (DomainException $e) {
            $this->dispatch('toast', message: $e->getMessage());
            return;
        }

        $this->po->refresh();
        $this->dispatch('toast', message: $pesanSukses);
    }

    public function render()
    {
        $this->po->load([
            'supplier', 'creator:id,name', 'items.rawMaterial',
            'goodsReceipts' => fn ($q) => $q->orderByDesc('id'),
            'goodsReceipts.receiver:id,name',
            'goodsReceipts.items.poItem.rawMaterial',
        ]);

        return view('livewire.purchasing.po-detail')->title("{$this->po->nomor_po} · Koombaah");
    }
}