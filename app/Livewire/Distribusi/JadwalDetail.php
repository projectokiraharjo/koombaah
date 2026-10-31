<?php

namespace App\Livewire\Distribusi;

use App\Livewire\Concerns\PesanValidasi;
use App\Models\DeliverySchedule;
use App\Services\DistributionService;
use Carbon\Carbon;
use DomainException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.panel')]
class JadwalDetail extends Component
{
    use PesanValidasi;

    public DeliverySchedule $jadwal;

    public bool $showHasil = false;
    public array $hasil = [];          // [item_id => ['status' => ..., 'catatan' => ...]]
    public string $waktu_terkirim = '';

    public function mount(DeliverySchedule $jadwal): void
    {
        $this->authorize('distribusi');
        $this->jadwal = $jadwal;
    }

    public function berangkat(): void
    {
        $this->authorize('distribusi');

        try {
            app(DistributionService::class)->berangkat($this->jadwal);
        } catch (DomainException $e) {
            $this->dispatch('toast', message: $e->getMessage());
            return;
        }

        $this->jadwal->refresh();
        $this->dispatch('toast', message: "{$this->jadwal->kode_jadwal} berangkat. Catat hasilnya setelah kurir kembali.");
    }

    public function hapus()
    {
        $this->authorize('distribusi');

        try {
            app(DistributionService::class)->hapus($this->jadwal);
        } catch (DomainException $e) {
            $this->dispatch('toast', message: $e->getMessage());
            return null;
        }

        session()->flash('toast', 'Jadwal dihapus. Order kembali ke daftar siap kirim.');

        return $this->redirectRoute('distribusi', navigate: true);
    }

    public function bukaHasil(): void
    {
        $this->authorize('distribusi');
        $this->resetValidation();

        // Default "terkirim" -> Admin cukup mengubah yang gagal
        $this->hasil = $this->jadwal->items()->where('status_pengiriman_item', 'menunggu')->get()
            ->mapWithKeys(fn ($i) => [$i->id => ['status' => 'terkirim', 'catatan' => '']])
            ->all();
        $this->waktu_terkirim = now()->format('Y-m-d\TH:i');
        $this->showHasil = true;
    }

    public function simpanHasil(): void
    {
        $this->authorize('distribusi');

        $this->validate([
            'waktu_terkirim' => ['required', 'date', 'before_or_equal:now'],
            'hasil' => ['required', 'array'],
            'hasil.*.status' => ['nullable', 'in:terkirim,gagal_kirim,'],
            'hasil.*.catatan' => ['nullable', 'string', 'max:500'],
        ], array_merge($this->messages(), [
            'hasil.*.status.in' => 'Status pengiriman tidak valid.',
            'waktu_terkirim.before_or_equal' => 'Waktu terkirim tidak boleh di masa depan.',
        ]), ['waktu_terkirim' => 'Waktu terkirim']);

        try {
            $jumlah = app(DistributionService::class)->catatHasil(
                $this->jadwal, $this->hasil, Carbon::parse($this->waktu_terkirim)->toDateTimeString(),
            );
        } catch (DomainException $e) {
            $this->addError('hasil', $e->getMessage());
            return;
        }

        $this->showHasil = false;
        $this->jadwal->refresh();
        $this->dispatch('toast', message: "Hasil {$jumlah} pengiriman dicatat.");
    }

    public function render()
    {
        $this->jadwal->load(['creator:id,name', 'items.salesOrder.partner']);

        return view('livewire.distribusi.jadwal-detail')->title("{$this->jadwal->kode_jadwal} · Koombaah");
    }
}