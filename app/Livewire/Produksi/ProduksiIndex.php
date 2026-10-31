<?php

namespace App\Livewire\Produksi;

use App\Enums\StatusBatch;
use App\Models\FinishedProduct;
use App\Models\ProductionBatch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.panel')]
#[Title('Produksi · Koombaah')]
class ProduksiIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')] public string $search = '';
    #[Url] public string $status = '';
    #[Url] public string $produk = '';
    #[Url] public string $bulan = '';   // format Y-m, kosong = semua periode

    public function mount(): void
    {
        $this->authorize('produksi');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    /** Filter yang sama dipakai untuk daftar & ringkasan (kolom ditulis lengkap agar tidak ambigu saat join) */
    private function query(): Builder
    {
        return ProductionBatch::query()
            ->when($this->search, fn ($q) => $q->where('production_batches.nomor_batch', 'ilike', "%{$this->search}%"))
            ->when($this->status, fn ($q) => $q->where('production_batches.status', $this->status))
            ->when($this->produk, fn ($q) => $q->where('production_batches.finished_product_id', (int) $this->produk))
            ->when($this->bulanValid(), function ($q) {
                $awal = Carbon::parse($this->bulan . '-01');
                $q->whereBetween('production_batches.tanggal_mulai', [$awal->toDateString(), $awal->copy()->endOfMonth()->toDateString()]);
            });
    }

    private function bulanValid(): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}$/', $this->bulan);
    }

    public function render()
    {
        $daftar = $this->query()
            ->with('product:id,kode_produk,nama_produk,satuan_jual')
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->paginate(15);

        // US 7 & 8: ringkasan batch SELESAI per produk sesuai filter
        $ringkasan = $this->query()
            ->where('production_batches.status', StatusBatch::Selesai->value)
            ->join('finished_products as fp', 'fp.id', '=', 'production_batches.finished_product_id')
            ->groupBy('fp.id', 'fp.nama_produk', 'fp.satuan_jual')
            ->selectRaw('fp.nama_produk, fp.satuan_jual, COUNT(*) AS jumlah_batch,
                SUM(production_batches.jumlah_aktual) AS total_aktual,
                SUM(production_batches.jumlah_lolos_qc) AS total_lolos,
                SUM(production_batches.jumlah_reject_qc) AS total_reject')
            ->orderBy('fp.nama_produk')
            ->toBase()
            ->get();

        return view('livewire.produksi.produksi-index', [
            'daftar' => $daftar,
            'ringkasan' => $ringkasan,
            'produkList' => FinishedProduct::orderBy('nama_produk')->get(['id', 'nama_produk']),
            'berjalan' => ProductionBatch::where('status', StatusBatch::SedangBerjalan->value)->count(),
            'labelPeriode' => $this->bulanValid() ? Carbon::parse($this->bulan . '-01')->translatedFormat('F Y') : 'semua periode',
        ]);
    }
}