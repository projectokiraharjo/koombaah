<?php

namespace App\Livewire\Sales;

use App\Enums\StatusOrder;
use App\Models\Partner;
use App\Models\SalesOrderItem;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.panel')]
class MitraDetail extends Component
{
    use WithPagination;

    public Partner $partner;

    public function mount(Partner $partner): void
    {
        $this->authorize('sales');
        $this->partner = $partner;
    }

    public function render()
    {
        $terhitung = StatusOrder::terhitung(); // hanya order yang sudah dikonfirmasi & tidak batal

        $ringkasan = $this->partner->salesOrders()
            ->whereIn('status', $terhitung)
            ->selectRaw('COUNT(*) AS jumlah, COALESCE(SUM(total_nilai_order), 0) AS total, MAX(tanggal_order) AS terakhir')
            ->toBase()
            ->first();

        // US 7: pola pembelian — produk yang paling sering dipesan mitra ini
        $produkFavorit = SalesOrderItem::query()
            ->join('sales_orders as so', 'so.id', '=', 'sales_order_items.sales_order_id')
            ->join('finished_products as fp', 'fp.id', '=', 'sales_order_items.finished_product_id')
            ->where('so.partner_id', $this->partner->id)
            ->whereIn('so.status', $terhitung)
            ->groupBy('fp.id', 'fp.nama_produk', 'fp.satuan_jual')
            ->selectRaw('fp.nama_produk, fp.satuan_jual, COUNT(DISTINCT so.id) AS frekuensi,
                SUM(sales_order_items.jumlah_dipesan) AS total_jumlah, SUM(sales_order_items.subtotal) AS total_nilai')
            ->orderByDesc('total_jumlah')
            ->limit(5)
            ->toBase()
            ->get();

        $orders = $this->partner->salesOrders()
            ->withCount('items')
            ->orderByDesc('tanggal_order')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.sales.mitra-detail', [
            'ringkasan' => $ringkasan,
            'produkFavorit' => $produkFavorit,
            'orders' => $orders,
        ])->title("{$this->partner->nama_usaha} · Koombaah");
    }
}