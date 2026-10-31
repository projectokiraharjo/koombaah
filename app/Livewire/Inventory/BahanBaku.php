<?php

namespace App\Livewire\Inventory;

use App\Enums\Satuan;
use App\Enums\StatusAktif;
use App\Livewire\Concerns\PesanValidasi;
use App\Models\RawMaterial;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Supplier;

#[Layout('components.layouts.panel')]
#[Title('Bahan Baku · Koombaah')]
class BahanBaku extends Component
{
    use WithPagination, PesanValidasi;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $filterStatus = '';

    public bool $showForm = false;
    public ?int $editingId = null;
    public bool $satuanTerkunci = false;

    // Field form
    public string $kode_bahan = '';
    public string $nama_bahan = '';
    public string $satuan = '';
    public string $harga_per_satuan_terakhir = '0';
    public string $stok_minimum = '0';
    public string $status = 'aktif';
    public string $supplier_utama_id = '';

    public function mount(): void
    {
        $this->authorize('inventory');
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    public function create(): void
    {
        $this->authorize('inventory');
        $this->resetForm();
        $this->kode_bahan = RawMaterial::generateKode();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('inventory');
        $this->resetForm();

        $item = RawMaterial::findOrFail($id);
        $this->editingId = $item->id;
        $this->satuanTerkunci = $item->sudahDipakai();
        $this->kode_bahan = $item->kode_bahan;
        $this->nama_bahan = $item->nama_bahan;
        $this->satuan = $item->satuan->value;
        $this->harga_per_satuan_terakhir = (string) $item->harga_per_satuan_terakhir;
        $this->stok_minimum = (string) $item->stok_minimum;
        $this->status = $item->status->value;
        $this->supplier_utama_id = (string) ($item->supplier_utama_id ?? '');

        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('inventory');
        $data = $this->validate(); // hanya field yang ada di rules() yang tersimpan
        $data['supplier_utama_id'] = $data['supplier_utama_id'] ?: null;

        if ($this->editingId) {
            $item = RawMaterial::findOrFail($this->editingId);
            if ($item->sudahDipakai()) {
                unset($data['satuan']); // dicek ulang di server, tidak percaya data dari browser
            }
            $item->update($data);            $pesan = 'Bahan baku berhasil diperbarui.';
        } else {
            RawMaterial::create($data + ['kode_bahan' => RawMaterial::generateKode()]);
            $pesan = 'Bahan baku berhasil ditambahkan.';
        }

        $this->showForm = false;
        $this->dispatch('stok-berubah'); // stok minimum / status bisa berubah
        $this->resetForm();
        $this->dispatch('toast', message: $pesan);
    }

    protected function rules(): array
    {
        return [
            'nama_bahan' => ['required', 'string', 'max:100', Rule::unique('raw_materials', 'nama_bahan')->ignore($this->editingId)],
            'satuan' => ['required', Rule::enum(Satuan::class)],
            'harga_per_satuan_terakhir' => ['required', 'numeric', 'min:0'],
            'stok_minimum' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(StatusAktif::class)],
            'supplier_utama_id' => ['nullable', Rule::exists('suppliers', 'id')],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nama_bahan' => 'Nama bahan',
            'satuan' => 'Satuan',
            'harga_per_satuan_terakhir' => 'Harga per satuan',
            'stok_minimum' => 'Stok minimum',
            'status' => 'Status',
            'supplier_utama_id' => 'Supplier utama',        ];
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'satuanTerkunci', 'kode_bahan', 'nama_bahan', 'satuan', 'harga_per_satuan_terakhir', 'stok_minimum', 'status','supplier_utama_id']);        
        $this->resetValidation();
    }

    public function render()
    {
        $items = RawMaterial::query()
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('nama_bahan', 'ilike', "%{$this->search}%")
                ->orWhere('kode_bahan', 'ilike', "%{$this->search}%")))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('kode_bahan')
            ->paginate(10);

        return view('livewire.inventory.bahan-baku', [
            'items' => $items,
            'satuanList' => Satuan::cases(),
            'supplierList' => Supplier::where('status', 'aktif')
                ->orWhere('id', (int) $this->supplier_utama_id)
                ->orderBy('nama_supplier')->get(['id', 'nama_supplier']),
        ]);
    }
}