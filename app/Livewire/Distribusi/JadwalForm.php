<?php

namespace App\Livewire\Distribusi;

use App\Enums\WilayahRing;
use App\Livewire\Concerns\PesanValidasi;
use App\Models\DeliverySchedule;
use App\Models\SalesOrder;
use App\Services\DistributionService;
use DomainException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.panel')]
class JadwalForm extends Component
{
    use PesanValidasi;

    public ?DeliverySchedule $jadwal = null;

    #[Url(as: 'ring')]
    public string $wilayah_ring = '';

    public string $nama_kurir = '';
    public string $tanggal_rencana_kirim = '';
    public string $estimasi_biaya_distribusi = '';
    public array $orderIds = [];
    public bool $semuaRing = false;

    public function mount(?DeliverySchedule $jadwal = null): void
    {
        $this->authorize('distribusi');

        if ($jadwal?->exists) {
            abort_unless($jadwal->bisaDiubah(), 403, 'Jadwal yang sudah berangkat tidak dapat diubah.');

            $this->jadwal = $jadwal;
            $this->wilayah_ring = $jadwal->wilayah_ring->value;
            $this->nama_kurir = $jadwal->nama_kurir;
            $this->tanggal_rencana_kirim = $jadwal->tanggal_rencana_kirim->format('Y-m-d');
            $this->estimasi_biaya_distribusi = $jadwal->estimasi_biaya_distribusi !== null ? (string) (float) $jadwal->estimasi_biaya_distribusi : '';
            $this->orderIds = $jadwal->items()->pluck('sales_order_id')->map(fn ($id) => (string) $id)->all();
        } else {
            $this->tanggal_rencana_kirim = now()->format('Y-m-d');
        }
    }

    public function simpan()
    {
        $this->authorize('distribusi');

        $this->validate([
            'wilayah_ring' => ['required', Rule::enum(WilayahRing::class)],
            'nama_kurir' => ['required', 'string', 'max:100'],
            'tanggal_rencana_kirim' => ['required', 'date'],
            'estimasi_biaya_distribusi' => ['nullable', 'numeric', 'min:0'],
            'orderIds' => ['required', 'array', 'min:1'],
            'orderIds.*' => ['integer', Rule::exists('sales_orders', 'id')],
        ], attributes: [
            'wilayah_ring' => 'Wilayah (Ring)',
            'nama_kurir' => 'Nama kurir',
            'tanggal_rencana_kirim' => 'Tanggal rencana kirim',
            'estimasi_biaya_distribusi' => 'Estimasi biaya distribusi',
            'orderIds' => 'Order yang dikirim',
        ]);

        try {
            $jadwal = app(DistributionService::class)->simpanJadwal($this->jadwal, [
                'wilayah_ring' => $this->wilayah_ring,
                'nama_kurir' => $this->nama_kurir,
                'tanggal_rencana_kirim' => $this->tanggal_rencana_kirim,
                'estimasi_biaya_distribusi' => $this->estimasi_biaya_distribusi === '' ? null : $this->estimasi_biaya_distribusi,
            ], $this->orderIds, auth()->user());
        } catch (DomainException $e) {
            $this->addError('orderIds', $e->getMessage());
            return null;
        }

        session()->flash('toast', "{$jadwal->kode_jadwal} disimpan.");

        return $this->redirectRoute('distribusi.detail', ['jadwal' => $jadwal], navigate: true);
    }

    public function render()
    {
        $idTerpilih = array_map('intval', $this->orderIds);
        $idJadwalIni = $this->jadwal ? $this->jadwal->items()->pluck('sales_order_id')->all() : [];

        // Order yang bisa dipilih: siap kirim & belum dijadwalkan, ditambah order yang sudah ada di jadwal ini
        $tersedia = SalesOrder::query()
            ->with('partner:id,nama_usaha,alamat,wilayah_ring')
            ->where(fn ($q) => $q->bisaDijadwalkan()->orWhereIn('id', $idJadwalIni ?: [0]))
            ->when(! $this->semuaRing && $this->wilayah_ring, fn ($q) => $q->where(fn ($q2) => $q2
                ->whereHas('partner', fn ($p) => $p->where('wilayah_ring', $this->wilayah_ring))
                ->orWhereIn('id', $idTerpilih ?: [0]))) // yang sudah dicentang tidak ikut hilang
            ->orderBy('siap_kirim_pada')
            ->get();

        $terpilih = $tersedia->whereIn('id', $idTerpilih);

        return view('livewire.distribusi.jadwal-form', [
            'tersedia' => $tersedia,
            'jumlahTerpilih' => $terpilih->count(),
            'nilaiTerpilih' => $terpilih->sum(fn ($o) => (float) $o->total_nilai_order),
            // B.5.7: campur Ring diizinkan, hanya diberi peringatan
            'berbedaRing' => $this->wilayah_ring
                ? $terpilih->filter(fn ($o) => $o->partner->wilayah_ring->value !== $this->wilayah_ring)->count()
                : 0,
        ])->title(($this->jadwal ? "Edit {$this->jadwal->kode_jadwal}" : 'Jadwal Baru') . ' · Koombaah');
    }
}