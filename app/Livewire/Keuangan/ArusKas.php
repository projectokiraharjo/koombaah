<?php

namespace App\Livewire\Keuangan;

use App\Services\LaporanKeuanganService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Arus Kas · Koombaah')]
class ArusKas extends Component
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
        $bulan = Carbon::parse($this->bulan . '-01');

        return view('livewire.keuangan.arus-kas', [
            'ak' => app(LaporanKeuanganService::class)->arusKas($bulan),
            'labelBulan' => $bulan->translatedFormat('F Y'),
        ]);
    }
}