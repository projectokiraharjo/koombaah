<?php

namespace App\Livewire\Produksi;

use App\Livewire\Concerns\PesanValidasi;
use App\Models\ProductionBatch;
use App\Services\ProductionService;
use DomainException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.panel')]
class BatchDetail extends Component
{
    use PesanValidasi;

    public ProductionBatch $batch;

    // Dialog selesai & QC
    public bool $showSelesai = false;
    public string $lolos = '';
    public string $reject = '0';
    public string $tanggal_selesai = '';
    public string $catatan_qc = '';

    // Dialog batal
    public bool $showBatal = false;
    public string $alasan = '';

    public function mount(ProductionBatch $batch): void
    {
        $this->authorize('produksi');
        $this->batch = $batch;
    }

    public function bukaSelesai(): void
    {
        $this->authorize('produksi');
        $this->resetValidation();
        $this->lolos = (string) (float) $this->batch->jumlah_target;
        $this->reject = '0';
        $this->tanggal_selesai = now()->format('Y-m-d');
        $this->catatan_qc = '';
        $this->showSelesai = true;
    }

    public function simpanSelesai(): void
    {
        $this->authorize('produksi');

        $this->validate([
            'lolos' => ['required', 'numeric', 'min:0'],
            'reject' => ['required', 'numeric', 'min:0'],
            'tanggal_selesai' => ['required', 'date', 'before_or_equal:today'],
            // User story 6: alasan wajib jika ada reject
            'catatan_qc' => [(float) $this->reject > 0 ? 'required' : 'nullable', 'string', 'max:500'],
        ], attributes: [
            'lolos' => 'Jumlah lolos QC',
            'reject' => 'Jumlah reject',
            'tanggal_selesai' => 'Tanggal selesai',
            'catatan_qc' => 'Alasan reject / catatan QC',
        ]);

        try {
            app(ProductionService::class)->selesaikan(
                $this->batch, $this->lolos, $this->reject, $this->tanggal_selesai,
                $this->catatan_qc ?: null, auth()->user(),
            );
        } catch (DomainException $e) {
            $this->addError('lolos', $e->getMessage());
            return;
        }

        $this->showSelesai = false;
        $this->batch->refresh();
        $this->dispatch('stok-berubah');
        $this->dispatch('toast', message: "{$this->batch->nomor_batch} selesai. Stok produk jadi bertambah sesuai jumlah lolos QC.");
    }

    public function bukaBatal(): void
    {
        $this->authorize('produksi');
        $this->resetValidation();
        $this->alasan = '';
        $this->showBatal = true;
    }

    public function simpanBatal(): void
    {
        $this->authorize('produksi');
        $this->validate(['alasan' => ['required', 'string', 'max:500']], attributes: ['alasan' => 'Alasan pembatalan']);

        try {
            app(ProductionService::class)->batalkan($this->batch, $this->alasan);
        } catch (DomainException $e) {
            $this->addError('alasan', $e->getMessage());
            return;
        }

        $this->showBatal = false;
        $this->batch->refresh();
        $this->dispatch('toast', message: "{$this->batch->nomor_batch} dibatalkan.");
    }

    public function render()
    {
        $this->batch->load(['product', 'bom', 'creator:id,name', 'materials.rawMaterial']);

        return view('livewire.produksi.batch-detail')->title("{$this->batch->nomor_batch} · Koombaah");
    }
}