<?php

namespace App\Livewire\Purchasing;

use App\Enums\StatusPO;
use App\Models\PurchaseOrderItem;
use App\Models\RawMaterial;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Harga Supplier · Koombaah')]
class HargaSupplier extends Component
{
    #[Url] public string $bahan = '';
    #[Url] public string $bulan = '';

    public function mount(): void
    {
        $this->authorize('purchasing');

        if (! preg_match('/^\d{4}-\d{2}$/', $this->bulan)) {
            $this->bulan = now()->format('Y-m');
        }
        if ($this->bahan === '') {
            $this->bahan = (string) (PurchaseOrderItem::orderByDesc('id')->value('raw_material_id')
                ?? RawMaterial::orderBy('nama_bahan')->value('id'));
        }
    }

    public function render()
    {
        $bahanList = RawMaterial::orderBy('nama_bahan')->get(['id', 'kode_bahan', 'nama_bahan', 'satuan']);
        $terpilih = $bahanList->firstWhere('id', (int) $this->bahan);

        // US 6: riwayat harga satu bahan dari berbagai supplier
        $transaksi = PurchaseOrderItem::query()
            ->join('purchase_orders as po', 'po.id', '=', 'purchase_order_items.purchase_order_id')
            ->join('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->where('purchase_order_items.raw_material_id', (int) $this->bahan)
            ->whereIn('po.status', StatusPO::terkonfirmasi())
            ->orderByDesc('po.tanggal_po')
            ->orderByDesc('po.id')
            ->get([
                'po.id as po_id', 'po.nomor_po', 'po.tanggal_po', 's.id as supplier_id', 's.nama_supplier',
                'purchase_order_items.jumlah_dipesan', 'purchase_order_items.harga_per_satuan',
            ]);

        $perSupplier = $transaksi->groupBy('supplier_id')->map(fn ($baris) => [
            'nama' => $baris->first()->nama_supplier,
            'terakhir' => (float) $baris->first()->harga_per_satuan,
            'tanggal_terakhir' => Carbon::parse($baris->first()->tanggal_po),
            'terendah' => $baris->min(fn ($b) => (float) $b->harga_per_satuan),
            'tertinggi' => $baris->max(fn ($b) => (float) $b->harga_per_satuan),
            'rata' => $baris->avg(fn ($b) => (float) $b->harga_per_satuan),
            'jumlah' => $baris->count(),
        ])->sortBy('terakhir')->values();

        // US 8: total pembelian per supplier dalam satu bulan
        $awal = Carbon::parse($this->bulan . '-01')->startOfMonth();
        $totalPerSupplier = DB::table('purchase_orders as po')
            ->join('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->whereIn('po.status', StatusPO::terkonfirmasi())
            ->whereBetween('po.tanggal_po', [$awal->toDateString(), $awal->copy()->endOfMonth()->toDateString()])
            ->groupBy('s.id', 's.nama_supplier')
            ->selectRaw('s.nama_supplier, COUNT(po.id) AS jumlah_po, SUM(po.total_nilai_po) AS total')
            ->orderByDesc('total')
            ->get();

        return view('livewire.purchasing.harga-supplier', [
            'bahanList' => $bahanList,
            'terpilih' => $terpilih,
            'transaksi' => $transaksi->take(20),
            'perSupplier' => $perSupplier,
            'totalPerSupplier' => $totalPerSupplier,
            'labelBulan' => $awal->translatedFormat('F Y'),
        ]);
    }
}