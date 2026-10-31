<?php

namespace App\Livewire\Sales;

use App\Enums\StatusAktif;
use App\Enums\WilayahRing;
use App\Livewire\Concerns\PesanValidasi;
use App\Models\Partner;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.panel')]
#[Title('Mitra · Koombaah')]
class DaftarMitra extends Component
{
    use WithPagination, PesanValidasi;

    #[Url(as: 'q')] public string $search = '';
    #[Url] public string $ring = '';
    #[Url(as: 'status')] public string $filterStatus = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    public string $kode_mitra = '';
    public string $nama_usaha = '';
    public string $nama_pemilik = '';
    public string $no_telepon = '';
    public string $alamat = '';
    public string $wilayah_ring = '';
    public string $catatan = '';
    public string $status = 'aktif';

    public function mount(): void
    {
        $this->authorize('sales');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'ring', 'filterStatus'], true)) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        $this->authorize('sales');
        $this->resetForm();
        $this->kode_mitra = Partner::generateKode();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('sales');
        $this->resetForm();

        $m = Partner::findOrFail($id);
        $this->editingId = $m->id;
        $this->kode_mitra = $m->kode_mitra;
        $this->nama_usaha = $m->nama_usaha;
        $this->nama_pemilik = (string) $m->nama_pemilik;
        $this->no_telepon = (string) $m->no_telepon;
        $this->alamat = $m->alamat;
        $this->wilayah_ring = $m->wilayah_ring->value;
        $this->catatan = (string) $m->catatan;
        $this->status = $m->status->value;

        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('sales');
        $data = array_map(fn ($v) => $v === '' ? null : $v, $this->validate());

        if ($this->editingId) {
            Partner::findOrFail($this->editingId)->update($data);
            $pesan = 'Data mitra berhasil diperbarui.';
        } else {
            Partner::create($data + ['kode_mitra' => Partner::generateKode()]);
            $pesan = 'Mitra baru berhasil didaftarkan.';
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', message: $pesan);
    }

    protected function rules(): array
    {
        return [
            'nama_usaha' => ['required', 'string', 'max:150'],
            'nama_pemilik' => ['nullable', 'string', 'max:100'],
            'no_telepon' => ['nullable', 'string', 'max:30'],
            'alamat' => ['required', 'string', 'max:500'],
            'wilayah_ring' => ['required', Rule::enum(WilayahRing::class)],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(StatusAktif::class)],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nama_usaha' => 'Nama usaha',
            'nama_pemilik' => 'Nama pemilik/PIC',
            'no_telepon' => 'No. telepon',
            'alamat' => 'Alamat',
            'wilayah_ring' => 'Wilayah (Ring)',
            'catatan' => 'Catatan',
            'status' => 'Status',
        ];
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'kode_mitra', 'nama_usaha', 'nama_pemilik', 'no_telepon', 'alamat', 'wilayah_ring', 'catatan', 'status']);
        $this->resetValidation();
    }

    public function render()
    {
        $items = Partner::query()
            ->withCount('salesOrders')
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('nama_usaha', 'ilike', "%{$this->search}%")
                ->orWhere('kode_mitra', 'ilike', "%{$this->search}%")
                ->orWhere('nama_pemilik', 'ilike', "%{$this->search}%")
                ->orWhere('alamat', 'ilike', "%{$this->search}%")))
            ->when($this->ring, fn ($q) => $q->where('wilayah_ring', $this->ring))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('kode_mitra')
            ->paginate(10);

        return view('livewire.sales.daftar-mitra', ['items' => $items]);
    }
}