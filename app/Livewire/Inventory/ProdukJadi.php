<?php

namespace App\Livewire\Inventory;

use App\Enums\Satuan;
use App\Enums\StatusAktif;
use App\Livewire\Concerns\PesanValidasi;
use App\Models\FinishedProduct;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.panel')]
#[Title('Produk Jadi · Koombaah')]
class ProdukJadi extends Component
{
    use WithPagination, PesanValidasi;

    private const KATEGORI_DEFAULT = ['deterjen', 'pewangi', 'pelembut', 'pemutih', 'penghilang noda', 'pelicin'];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $filterStatus = '';

    public bool $showForm = false;
    public ?int $editingId = null;
    public bool $satuanTerkunci = false;

    // Field form
    public string $kode_produk = '';
    public string $nama_produk = '';
    public string $kategori = '';
    public string $satuan_jual = '';
    public string $harga_jual_per_satuan = '0';
    public string $stok_minimum = '0';
    public string $status = 'aktif';

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
        $this->kode_produk = FinishedProduct::generateKode();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('inventory');
        $this->resetForm();

        $item = FinishedProduct::findOrFail($id);
        $this->editingId = $item->id;
        $this->satuanTerkunci = $item->sudahDipakai();
        $this->kode_produk = $item->kode_produk;
        $this->nama_produk = $item->nama_produk;
        $this->kategori = $item->kategori;
        $this->satuan_jual = $item->satuan_jual->value;
        $this->harga_jual_per_satuan = (string) $item->harga_jual_per_satuan;
        $this->stok_minimum = (string) $item->stok_minimum;
        $this->status = $item->status->value;

        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('inventory');
        $data = $this->validate();
        $data['kategori'] = strtolower(trim($data['kategori']));

        if ($this->editingId) {
            $item = FinishedProduct::findOrFail($this->editingId);
            if ($item->sudahDipakai()) {
                unset($data['satuan_jual']);
            }
            $item->update($data);            $pesan = 'Produk jadi berhasil diperbarui.';
        } else {
            FinishedProduct::create($data + ['kode_produk' => FinishedProduct::generateKode()]);
            $pesan = 'Produk jadi berhasil ditambahkan.';
        }

        $this->showForm = false;
        $this->dispatch('stok-berubah'); // stok minimum / status bisa berubah
        $this->resetForm();
        $this->dispatch('toast', message: $pesan);
    }

    protected function rules(): array
    {
        return [
            'nama_produk' => ['required', 'string', 'max:100', Rule::unique('finished_products', 'nama_produk')->ignore($this->editingId)],
            'kategori' => ['required', 'string', 'max:50'],
            'satuan_jual' => ['required', Rule::enum(Satuan::class)],
            'harga_jual_per_satuan' => ['required', 'numeric', 'min:0'],
            'stok_minimum' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(StatusAktif::class)],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nama_produk' => 'Nama produk',
            'kategori' => 'Kategori',
            'satuan_jual' => 'Satuan jual',
            'harga_jual_per_satuan' => 'Harga jual',
            'stok_minimum' => 'Stok minimum',
            'status' => 'Status',
        ];
    }

    
    private function resetForm(): void
    {
    $this->reset([
        'editingId',
        'satuanTerkunci',
        'kode_produk',
        'nama_produk',
        'kategori',
        'satuan_jual',
        'harga_jual_per_satuan',
        'stok_minimum',
        'status',
    ]);

    $this->resetValidation();
    }


    public function render()
    {
        $items = FinishedProduct::query()
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('nama_produk', 'ilike', "%{$this->search}%")
                ->orWhere('kode_produk', 'ilike', "%{$this->search}%")
                ->orWhere('kategori', 'ilike', "%{$this->search}%")))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('kode_produk')
            ->paginate(10);

        $kategoriList = FinishedProduct::query()->distinct()->pluck('kategori')
            ->merge(self::KATEGORI_DEFAULT)->unique()->sort()->values();

        return view('livewire.inventory.produk-jadi', [
            'items' => $items,
            'satuanList' => Satuan::cases(),
            'kategoriList' => $kategoriList,
        ]);
    }
}