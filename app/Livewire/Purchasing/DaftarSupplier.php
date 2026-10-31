<?php

namespace App\Livewire\Purchasing;

use App\Enums\StatusAktif;
use App\Livewire\Concerns\PesanValidasi;
use App\Models\Supplier;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.panel')]
#[Title('Supplier · Koombaah')]
class DaftarSupplier extends Component
{
    use WithPagination, PesanValidasi;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $filterStatus = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    public string $kode_supplier = '';
    public string $nama_supplier = '';
    public string $kontak_person = '';
    public string $no_telepon = '';
    public string $alamat = '';
    public string $status = 'aktif';

    public function mount(): void
    {
        $this->authorize('purchasing');
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    public function create(): void
    {
        $this->authorize('purchasing');
        $this->resetForm();
        $this->kode_supplier = Supplier::generateKode();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('purchasing');
        $this->resetForm();

        $s = Supplier::findOrFail($id);
        $this->editingId = $s->id;
        $this->kode_supplier = $s->kode_supplier;
        $this->nama_supplier = $s->nama_supplier;
        $this->kontak_person = (string) $s->kontak_person;
        $this->no_telepon = (string) $s->no_telepon;
        $this->alamat = (string) $s->alamat;
        $this->status = $s->status->value;

        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('purchasing');
        $data = $this->validate();
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data); // kolom opsional kosong -> NULL

        if ($this->editingId) {
            Supplier::findOrFail($this->editingId)->update($data);
            $pesan = 'Supplier berhasil diperbarui.';
        } else {
            Supplier::create($data + ['kode_supplier' => Supplier::generateKode()]);
            $pesan = 'Supplier berhasil ditambahkan.';
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', message: $pesan);
    }

    protected function rules(): array
    {
        return [
            'nama_supplier' => ['required', 'string', 'max:150', Rule::unique('suppliers', 'nama_supplier')->ignore($this->editingId)],
            'kontak_person' => ['nullable', 'string', 'max:100'],
            'no_telepon' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(StatusAktif::class)],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nama_supplier' => 'Nama supplier',
            'kontak_person' => 'Kontak person',
            'no_telepon' => 'No. telepon',
            'alamat' => 'Alamat',
            'status' => 'Status',
        ];
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'kode_supplier', 'nama_supplier', 'kontak_person', 'no_telepon', 'alamat', 'status']);
        $this->resetValidation();
    }

    public function render()
    {
        $items = Supplier::query()
            ->withCount(['bahanUtama', 'purchaseOrders'])
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('nama_supplier', 'ilike', "%{$this->search}%")
                ->orWhere('kode_supplier', 'ilike', "%{$this->search}%")
                ->orWhere('kontak_person', 'ilike', "%{$this->search}%")))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('kode_supplier')
            ->paginate(10);

        return view('livewire.purchasing.daftar-supplier', ['items' => $items]);
    }
}