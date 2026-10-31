<?php

namespace App\Livewire\Keuangan;

use App\Enums\StatusPeriode;
use App\Models\FinancialPeriod;
use App\Services\LaporanKeuanganService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Laba-Rugi · Koombaah')]
class LabaRugi extends Component
{
    #[Url] public string $bulan = '';

    public function mount(): void
    {
        $this->authorize('keuangan');
        if (! preg_match('/^\d{4}-\d{2}$/', $this->bulan)) {
            $this->bulan = now()->format('Y-m');
        }
    }

    public function render()
    {
        $laporan = app(LaporanKeuanganService::class);
        $bulan = Carbon::parse($this->bulan . '-01');
        $periode = FinancialPeriod::where('periode_bulan', $bulan->toDateString())->first();

        // US 8: perbandingan 6 bulan (snapshot untuk periode yang sudah ditutup)
        $perbandingan = collect(range(5, 0))->map(function ($i) use ($bulan, $laporan) {
            $b = $bulan->copy()->subMonthsNoOverflow($i);
            return ['label' => $b->translatedFormat('M Y'), 'data' => $laporan->ringkasan($b)];
        });

        return view('livewire.keuangan.laba-rugi', [
            'lr' => $laporan->hitungLabaRugi($bulan),
            'ringkasan' => $laporan->ringkasan($bulan),
            'ditutup' => $periode?->status === StatusPeriode::Ditutup,
            'labelBulan' => $bulan->translatedFormat('F Y'),
            'perbandingan' => $perbandingan,
        ]);
    }
}