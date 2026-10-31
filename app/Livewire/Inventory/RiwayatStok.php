<?php

namespace App\Livewire\Inventory;

use App\Models\FinishedProduct;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.panel')]
#[Title('Riwayat Pergerakan Stok · Koombaah')]
class RiwayatStok extends Component
{
    use WithPagination;

    #[Url] public string $jenis = '';            // raw_material | finished_product
    #[Url(as: 'item')] public string $itemId = '';
    #[Url] public string $tipe = '';             // masuk | keluar
    #[Url(as: 'ref')] public string $referensi = '';
    #[Url] public string $dari = '';
    #[Url] public string $sampai = '';
    #[Url] public string $oleh = '';

    public function mount(): void
    {
        $this->authorize('inventory');
    }

    public function updated(string $property): void
    {
        if ($property === 'jenis') {
            $this->itemId = ''; // ganti jenis -> pilihan item direset
        }
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->reset(['jenis', 'itemId', 'tipe', 'referensi', 'dari', 'sampai', 'oleh']);
        $this->resetPage();
    }

    public function render()
    {
        // Saldo berjalan dihitung dari SELURUH riwayat tiap item dulu, baru kemudian difilter.
        // Jadi angka "saldo setelah" tetap benar walaupun yang tampil hanya sebagian baris.
        $denganSaldo = StockMovement::query()
            ->select('stock_movements.*')
            ->selectRaw("SUM(CASE WHEN tipe_pergerakan = 'masuk' THEN jumlah ELSE -jumlah END)
                         OVER (PARTITION BY item_type, item_id ORDER BY id) AS saldo_setelah");

        $riwayat = StockMovement::query()
            ->fromSub($denganSaldo, 'stock_movements')
            ->with(['item', 'user:id,name'])
            ->when($this->jenisValid(), fn ($q) => $q->where('item_type', $this->jenis))
            ->when($this->jenisValid() && $this->itemId, fn ($q) => $q->where('item_id', (int) $this->itemId))
            ->when($this->tipe, fn ($q) => $q->where('tipe_pergerakan', $this->tipe))
            ->when($this->referensi, fn ($q) => $q->where('referensi_tipe', $this->referensi))
            ->when($this->tanggalValid($this->dari), fn ($q) => $q->whereDate('created_at', '>=', $this->dari))
            ->when($this->tanggalValid($this->sampai), fn ($q) => $q->whereDate('created_at', '<=', $this->sampai))
            ->when($this->oleh, fn ($q) => $q->where('dilakukan_oleh', (int) $this->oleh))
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.inventory.riwayat-stok', [
            'riwayat' => $riwayat,
            'itemOptions' => $this->itemOptions(),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'audit' => $this->audit(),
        ]);
    }

    private function itemOptions(): Collection
    {
        return match ($this->jenis) {
            'raw_material' => RawMaterial::orderBy('nama_bahan')->get(['id', 'kode_bahan as kode', 'nama_bahan as nama']),
            'finished_product' => FinishedProduct::orderBy('nama_produk')->get(['id', 'kode_produk as kode', 'nama_produk as nama']),
            default => collect(),
        };
    }

    /** User story 8: bandingkan stok di sistem dengan total seluruh log pergerakan */
    private function audit(): ?array
    {
        if (! $this->jenisValid() || ! $this->itemId) {
            return null;
        }

        $item = ($this->jenis === 'raw_material' ? RawMaterial::class : FinishedProduct::class)::find((int) $this->itemId);
        if (! $item) {
            return null;
        }

        $total = StockMovement::where('item_type', $this->jenis)->where('item_id', $item->id)
            ->selectRaw("COALESCE(SUM(CASE WHEN tipe_pergerakan = 'masuk' THEN jumlah END), 0) AS masuk")
            ->selectRaw("COALESCE(SUM(CASE WHEN tipe_pergerakan = 'keluar' THEN jumlah END), 0) AS keluar")
            ->first();

        $isBahan = $item instanceof RawMaterial;
        $saldoLog = (float) $total->masuk - (float) $total->keluar;
        $stokSistem = (float) $item->stok_saat_ini;

        return [
            'nama' => $isBahan ? $item->nama_bahan : $item->nama_produk,
            'satuan' => $isBahan ? $item->satuan->value : $item->satuan_jual->value,
            'masuk' => $total->masuk,
            'keluar' => $total->keluar,
            'saldo_log' => $saldoLog,
            'stok_sistem' => $stokSistem,
            'cocok' => round($saldoLog - $stokSistem, 3) == 0.0,
        ];
    }

    private function jenisValid(): bool
    {
        return in_array($this->jenis, ['raw_material', 'finished_product'], true);
    }

    private function tanggalValid(string $tanggal): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal);
    }
}