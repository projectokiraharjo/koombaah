<?php

namespace App\Livewire\Keuangan;

use App\Models\FinancialPeriod;
use App\Services\FinanceService;
use Carbon\Carbon;
use DomainException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Periode Keuangan · Koombaah')]
class PeriodeKeuangan extends Component
{
    public bool $showBuka = false;
    public ?int $periodeId = null;
    public string $alasan = '';

    public function mount(): void
    {
        $this->authorize('keuangan');
    }

    public function tutup(string $bulan): void
    {
        $this->authorize('periode.kelola'); // hanya Owner

        try {
            app(FinanceService::class)->tutupPeriode(Carbon::parse($bulan . '-01'), auth()->user());
        } catch (DomainException $e) {
            $this->dispatch('toast', message: $e->getMessage());
            return;
        }

        $this->dispatch('toast', message: 'Periode ' . Carbon::parse($bulan . '-01')->translatedFormat('F Y') . ' ditutup.');
    }

    public function bukaDialog(int $id): void
    {
        $this->authorize('periode.kelola');
        $this->resetValidation();
        $this->periodeId = $id;
        $this->alasan = '';
        $this->showBuka = true;
    }

    public function bukaKembali(): void
    {
        $this->authorize('periode.kelola');
        $this->validate(['alasan' => ['required', 'string', 'max:500']], ['alasan.required' => 'Alasan membuka kembali periode wajib diisi.']);

        try {
            app(FinanceService::class)->bukaKembaliPeriode(FinancialPeriod::findOrFail($this->periodeId), $this->alasan, auth()->user());
        } catch (DomainException $e) {
            $this->addError('alasan', $e->getMessage());
            return;
        }

        $this->showBuka = false;
        $this->dispatch('toast', message: 'Periode dibuka kembali. Tutup lagi setelah koreksi selesai.');
    }

    public function render()
    {
        $tersimpan = FinancialPeriod::with(['penutup:id,name', 'pembuka:id,name'])->get()
            ->keyBy(fn ($p) => $p->periode_bulan->format('Y-m'));

        // 12 bulan terakhir, termasuk bulan berjalan
        $bulanList = collect(range(0, 11))->map(function ($i) use ($tersimpan) {
            $b = now()->startOfMonth()->subMonthsNoOverflow($i);
            return [
                'kunci' => $b->format('Y-m'),
                'label' => $b->translatedFormat('F Y'),
                'berjalan' => $i === 0,
                'periode' => $tersimpan->get($b->format('Y-m')),
            ];
        });

        return view('livewire.keuangan.periode-keuangan', ['bulanList' => $bulanList]);
    }
}