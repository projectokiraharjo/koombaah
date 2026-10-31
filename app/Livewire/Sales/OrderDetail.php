<?php

namespace App\Livewire\Sales;

use App\Exceptions\StokProdukKurangException;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use DomainException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.panel')]
class OrderDetail extends Component
{
    public SalesOrder $order;

    public bool $showBatal = false;
    public string $alasan = '';
    public string $pesanBlokir = '';

    public function mount(SalesOrder $order): void
    {
        $this->authorize('sales');
        $this->order = $order;
    }

    public function konfirmasi(): void
    {
        $this->authorize('sales');
        $this->pesanBlokir = '';

        try {
            app(SalesOrderService::class)->konfirmasi($this->order, auth()->user());
        } catch (StokProdukKurangException $e) {
            $this->pesanBlokir = $e->getMessage(); // blokir total (B.4.7)
            return;
        } catch (DomainException $e) {
            $this->dispatch('toast', message: $e->getMessage());
            return;
        }

        $this->order->refresh();
        $this->dispatch('stok-berubah');
        $this->dispatch('toast', message: "{$this->order->nomor_order} dikonfirmasi. Stok produk jadi sudah dipotong.");
    }

    public function lanjutkan(): void
    {
        $this->authorize('sales');

        try {
            $status = app(SalesOrderService::class)->lanjutkan($this->order);
        } catch (DomainException $e) {
            $this->dispatch('toast', message: $e->getMessage());
            return;
        }

        $this->order->refresh();
        $this->dispatch('toast', message: "Status order menjadi {$status->label()}.");
    }

    public function bukaBatal(): void
    {
        $this->authorize('sales');
        $this->resetValidation();
        $this->alasan = '';
        $this->showBatal = true;
    }

    public function simpanBatal(): void
    {
        $this->authorize('sales');
        $this->validate(['alasan' => ['required', 'string', 'max:500']], [
            'alasan.required' => 'Alasan pembatalan wajib diisi.',
        ]);

        $stokKembali = $this->order->status->stokSudahDipotong();

        try {
            app(SalesOrderService::class)->batalkan($this->order, $this->alasan, auth()->user());
        } catch (DomainException $e) {
            $this->addError('alasan', $e->getMessage());
            return;
        }

        $this->showBatal = false;
        $this->order->refresh();
        $this->dispatch('stok-berubah');
        $this->dispatch('toast', message: $stokKembali
            ? "{$this->order->nomor_order} dibatalkan. Stok produk jadi sudah dikembalikan."
            : "{$this->order->nomor_order} dibatalkan.");
    }

    public function render()
    {
        $this->order->load(['partner', 'recorder:id,name', 'items.product']);

        return view('livewire.sales.order-detail')->title("{$this->order->nomor_order} · Koombaah");
    }
}