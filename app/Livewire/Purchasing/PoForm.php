<?php

namespace App\Livewire\Purchasing;

use App\Livewire\Concerns\PesanValidasi;
use App\Models\PurchaseOrder;
use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use DomainException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.panel')]
class PoForm extends Component
{
    use PesanValidasi;

    public ?PurchaseOrder $po = null;

    public string $supplier_id = '';
    public string $tanggal_po = '';
    public array $rows = [];

    public function mount(?PurchaseOrder $po = null): void
    {
        $this->authorize('purchasing');

        if ($po?->exists) {
            abort_unless($po->bisaDiedit(), 403, 'PO yang sudah dipesan tidak dapat diubah.');

            $this->po = $po;
            $this->supplier_id = (string) $po->supplier_id;
            $this->tanggal_po = $po->tanggal_po->format('Y-m-d');
            $this->rows = $po->items->map(fn ($i) => [
                'raw_material_id' => (string) $i->raw_material_id,
                'jumlah_dipesan' => (string) $i->jumlah_dipesan,
                'harga_per_satuan' => (string) $i->harga_per_satuan,
            ])->all();
        } else {
            $this->tanggal_po = now()->format('Y-m-d');
            $this->rows = [$this->barisKosong()];
        }
    }

    /** Saat bahan dipilih, harga terisi otomatis dari harga beli terakhir */
    public function updatedRows($value, $key): void
    {
        [$index, $field] = explode('.', $key);

        if ($field === 'raw_material_id' && $value && in_array($this->rows[$index]['harga_per_satuan'] ?? '', ['', '0'], true)) {
            $this->rows[$index]['harga_per_satuan'] = (string) RawMaterial::whereKey($value)->value('harga_per_satuan_terakhir');
        }
    }

    public function tambahBaris(): void
    {
        $this->rows[] = $this->barisKosong();
    }

    public function hapusBaris(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function simpan(string $aksi = 'draft')
    {
        $this->authorize('purchasing');

        $this->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('status', 'aktif')],
            'tanggal_po' => ['required', 'date'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.raw_material_id' => ['required', 'distinct', Rule::exists('raw_materials', 'id')->where('status', 'aktif')],
            'rows.*.jumlah_dipesan' => ['required', 'numeric', 'gt:0'],
            'rows.*.harga_per_satuan' => ['required', 'numeric', 'min:0'],
        ]);

        $service = app(PurchaseOrderService::class);

        try {
            $po = $service->simpanDraft($this->po, (int) $this->supplier_id, $this->tanggal_po, $this->rows, auth()->user());
            if ($aksi === 'pesan') {
                $service->pesan($po);
            }
        } catch (DomainException $e) {
            $this->addError('rows', $e->getMessage());
            return null;
        }

        session()->flash('toast', $aksi === 'pesan'
            ? "{$po->nomor_po} disimpan dan dipesan ke supplier."
            : "{$po->nomor_po} disimpan sebagai draft.");

        return $this->redirectRoute('po.detail', ['po' => $po], navigate: true);
    }

    protected function validationAttributes(): array
    {
        return [
            'supplier_id' => 'Supplier',
            'tanggal_po' => 'Tanggal PO',
            'rows' => 'Daftar bahan',
            'rows.*.raw_material_id' => 'Bahan baku',
            'rows.*.jumlah_dipesan' => 'Jumlah',
            'rows.*.harga_per_satuan' => 'Harga',
        ];
    }

    private function barisKosong(): array
    {
        return ['raw_material_id' => '', 'jumlah_dipesan' => '', 'harga_per_satuan' => ''];
    }

    public function render()
    {
        $bahanList = RawMaterial::where('status', 'aktif')->orderBy('nama_bahan')
            ->get(['id', 'kode_bahan', 'nama_bahan', 'satuan', 'supplier_utama_id']);

        $total = collect($this->rows)->sum(fn ($r) => is_numeric($r['jumlah_dipesan'] ?? null) && is_numeric($r['harga_per_satuan'] ?? null)
            ? $r['jumlah_dipesan'] * $r['harga_per_satuan'] : 0);

        return view('livewire.purchasing.po-form', [
            'suppliers' => Supplier::where('status', 'aktif')->orderBy('nama_supplier')->get(['id', 'nama_supplier']),
            'bahanList' => $bahanList,
            'total' => $total,
        ])->title(($this->po ? "Edit {$this->po->nomor_po}" : 'Buat PO') . ' · Koombaah');
    }
}