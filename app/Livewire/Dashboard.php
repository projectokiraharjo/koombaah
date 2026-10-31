<?php

namespace App\Livewire;

use App\Services\DashboardService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Dashboard · Koombaah')]
class Dashboard extends Component
{
    #[Url] public string $bulan = '';

    public function mount(): void
    {
        $this->authorize('dashboard');
        if (! preg_match('/^\d{4}-\d{2}$/', $this->bulan)) {
            $this->bulan = now()->format('Y-m');
        }
    }

    public function render()
    {
        $bulan = Carbon::parse($this->bulan . '-01');
        $service = app(DashboardService::class);
        $user = auth()->user();

        // B.8.7: hanya bagian yang diizinkan role ini yang DIHITUNG (bukan sekadar disembunyikan)
        $d = [];
        foreach (['keuangan', 'stok', 'produksi', 'penjualan', 'distribusi', 'sdm', 'supplier'] as $bagian) {
            if ($user->can("ringkasan.{$bagian}")) {
                $d[$bagian] = $service->{$bagian}($bulan);
            }
        }

        return view('livewire.dashboard', [
            'd' => $d,
            'user' => $user,
            'labelBulan' => $bulan->translatedFormat('F Y'),
        ]);
    }
}