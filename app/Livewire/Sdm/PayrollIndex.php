<?php

namespace App\Livewire\Sdm;

use App\Models\PayrollRun;
use App\Services\PayrollService;
use Carbon\Carbon;
use DomainException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Payroll · Koombaah')]
class PayrollIndex extends Component
{
    public string $bulan = '';

    public function mount(): void
    {
        $this->authorize('sdm');
        $this->bulan = now()->format('Y-m');
    }

    public function buat()
    {
        $this->authorize('sdm');
        $this->validate(['bulan' => ['required', 'date_format:Y-m']], ['bulan.date_format' => 'Periode tidak valid.']);

        try {
            [$run, $tanpaKomponen] = app(PayrollService::class)->siapkanDraft(Carbon::parse($this->bulan . '-01'), auth()->user());
        } catch (DomainException $e) {
            $this->addError('bulan', $e->getMessage());
            return null;
        }

        session()->flash('toast', $tanpaKomponen
            ? 'Draft dibuat. Dilewati (belum punya komponen gaji): ' . implode(', ', $tanpaKomponen)
            : 'Draft payroll dibuat.');

        return $this->redirectRoute('payroll.detail', ['run' => $run], navigate: true);
    }

    public function render()
    {
        return view('livewire.sdm.payroll-index', [
            'daftar' => PayrollRun::with('pemroses:id,name')->withCount('details')->orderByDesc('periode_bulan')->get(),
        ]);
    }
}