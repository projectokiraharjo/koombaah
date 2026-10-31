<?php

namespace App\Livewire\Inventory;

use App\Livewire\Concerns\PesanValidasi;
use App\Models\FinishedProduct;
use App\Models\RawMaterial;
use App\Services\BomService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.panel')]
class BomDetail extends Component
{
    use PesanValidasi;

    public FinishedProduct $product;

    public bool $showForm = false;
    public array $rows = [];
    public string $catatan_perubahan = '';

    public function mount(FinishedProduct $product): void
    {
        $this->authorize('inventory');
        $this->product = $product;
    }

    public function bukaEditor(): void
    {
        $this->authorize('bom.ubah'); // PRD B.1.7: hanya Owner
        $this->resetValidation();
        $this->catatan_perubahan = '';

        // Editor diisi dari resep aktif -> Owner tinggal mengubah yang perlu
        $aktif = $this->product->activeBom()->with('details')->first();

        $this->rows = $aktif
            ? $aktif->details->map(fn ($d) => [
                'raw_material_id' => (string) $d->raw_material_id,
                'takaran' => (string) $d->takaran,
            ])->all()
            : [['raw_material_id' => '', 'takaran' => '']];

        $this->showForm = true;
    }

    public function tambahBaris(): void
    {
        $this->rows[] = ['raw_material_id' => '', 'takaran' => ''];
    }

    public function hapusBaris(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function simpan(BomService $service): void
    {
        $this->authorize('bom.ubah');

        $adaVersiAktif = $this->product->activeBom()->exists();

        $this->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.raw_material_id' => ['required', 'distinct', Rule::exists('raw_materials', 'id')->where('status', 'aktif')],
            'rows.*.takaran' => ['required', 'numeric', 'gt:0'],
            // Versi 2 dst wajib ada alasan (user story 5)
            'catatan_perubahan' => [$adaVersiAktif ? 'required' : 'nullable', 'string', 'max:500'],
        ]);

        $header = $service->simpanVersiBaru(
            $this->product,
            $this->rows,
            $this->catatan_perubahan ?: null,
            auth()->user(),
        );

        $this->showForm = false;
        $this->dispatch('toast', message: "Resep versi {$header->versi} disimpan dan sekarang aktif.");
    }

    protected function validationAttributes(): array
    {
        return [
            'rows' => 'Daftar bahan',
            'rows.*.raw_material_id' => 'Bahan baku',
            'rows.*.takaran' => 'Takaran',
            'catatan_perubahan' => 'Alasan perubahan',
        ];
    }

    public function render()
    {
        $versiList = $this->product->boms()
            ->with(['details.rawMaterial', 'creator:id,name'])
            ->orderByDesc('versi')
            ->get();

        return view('livewire.inventory.bom-detail', [
            'versiList' => $versiList,
            'aktif' => $versiList->first(fn ($v) => $v->status->value === 'aktif'),
            'bahanList' => RawMaterial::where('status', 'aktif')->orderBy('nama_bahan')
                ->get(['id', 'kode_bahan', 'nama_bahan', 'satuan']),
        ])->title("Resep {$this->product->nama_produk} · Koombaah");
    }
}