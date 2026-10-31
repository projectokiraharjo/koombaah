<?php

namespace App\Livewire\Sales;

use App\Exceptions\StokProdukKurangException;
use App\Livewire\Concerns\PesanValidasi;
use App\Models\FinishedProduct;
use App\Models\Partner;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use DomainException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.panel')]
class OrderForm extends Component
{
    use PesanValidasi;

    public ?SalesOrder $order = null;

    public string $partner_id = '';
    public string $tanggal_order = '';
    public string $catatan = '';
    public array $rows = [];   // [finished_product_id, jumlah_dipesan, harga_snapshot]

    public function mount(?SalesOrder $order = null): void
    {
        $this->authorize('sales');

        if ($order?->exists) {
            abort_unless($order->bisaDiedit(), 403, 'Order yang sudah dikonfirmasi tidak dapat diubah.');

            $this->order = $order;
            $this->partner_id = (string) $order->partner_id;
            $this->tanggal_order = $order->tanggal_order->format('Y-m-d');
            $this->catatan = (string) $order->catatan;
            $this->rows = $order->items->map(fn ($i) => [
                'finished_product_id' => (string) $i->finished_product_id,
                'jumlah_dipesan' => (string) (float) $i->jumlah_dipesan,
                'harga_snapshot' => (string) $i->harga_per_satuan,
            ])->all();
        } else {
            $this->tanggal_order = now()->format('Y-m-d');
            $this->rows = [$this->barisKosong()];
        }
    }

    /** Ganti produk -> harga ikut harga jual master saat ini */
    public function updatedRows($value, $key): void
    {
        [$index, $field] = explode('.', $key);
        if ($field === 'finished_product_id') {
            $this->rows[$index]['harga_snapshot'] = '';
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

    public function simpan(string $aksi = 'baru')
    {
        $this->authorize('sales');

        $this->validate([
            'partner_id' => ['required', Rule::exists('partners', 'id')->where('status', 'aktif')],
            'tanggal_order' => ['required', 'date', 'before_or_equal:today'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.finished_product_id' => ['required', 'distinct', Rule::exists('finished_products', 'id')->where('status', 'aktif')],
            'rows.*.jumlah_dipesan' => ['required', 'numeric', 'gt:0'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $service = app(SalesOrderService::class);

        try {
            $order = $service->simpan($this->order, (int) $this->partner_id, $this->tanggal_order, $this->rows, $this->catatan ?: null, auth()->user());
        } catch (DomainException $e) {
            $this->addError('rows', $e->getMessage());
            return null;
        }

        $pesan = "{$order->nomor_order} tersimpan dengan status Baru.";

        if ($aksi === 'konfirmasi') {
            try {
                $service->konfirmasi($order, auth()->user());
                $pesan = "{$order->nomor_order} tersimpan dan dikonfirmasi. Stok produk jadi sudah dipotong.";
            } catch (StokProdukKurangException $e) {
                // Order tetap tersimpan (status baru) — konfirmasi diblokir
                $pesan = "{$order->nomor_order} tersimpan, tetapi BELUM dikonfirmasi: stok tidak cukup.";
            }
        }

        session()->flash('toast', $pesan);

        return $this->redirectRoute('order.detail', ['order' => $order], navigate: true);
    }

    protected function validationAttributes(): array
    {
        return [
            'partner_id' => 'Mitra',
            'tanggal_order' => 'Tanggal order',
            'rows' => 'Daftar produk',
            'rows.*.finished_product_id' => 'Produk',
            'rows.*.jumlah_dipesan' => 'Jumlah',
            'catatan' => 'Catatan',
        ];
    }

    private function barisKosong(): array
    {
        return ['finished_product_id' => '', 'jumlah_dipesan' => '', 'harga_snapshot' => ''];
    }

    public function render()
    {
        $produkList = FinishedProduct::where('status', 'aktif')->orderBy('nama_produk')
            ->get(['id', 'kode_produk', 'nama_produk', 'satuan_jual', 'harga_jual_per_satuan', 'stok_saat_ini']);

        // Harga tampilan: snapshot lama (saat edit) atau harga master saat ini
        $baris = collect($this->rows)->map(function ($r) use ($produkList) {
            $p = $produkList->firstWhere('id', (int) $r['finished_product_id']);
            $harga = $r['harga_snapshot'] !== '' ? (float) $r['harga_snapshot'] : (float) ($p?->harga_jual_per_satuan ?? 0);
            $jumlah = is_numeric($r['jumlah_dipesan']) ? (float) $r['jumlah_dipesan'] : 0;

            return [
                'produk' => $p,
                'harga' => $harga,
                'subtotal' => $jumlah * $harga,
                'stokKurang' => $p && $jumlah > (float) $p->stok_saat_ini,
            ];
        });

        return view('livewire.sales.order-form', [
            'mitraList' => Partner::where('status', 'aktif')->orderBy('nama_usaha')->get(['id', 'kode_mitra', 'nama_usaha', 'wilayah_ring']),
            'produkList' => $produkList,
            'baris' => $baris,
            'total' => $baris->sum('subtotal'),
        ])->title(($this->order ? "Edit {$this->order->nomor_order}" : 'Order Baru') . ' · Koombaah');
    }
}