<?php

namespace App\Livewire\Sdm;

use App\Livewire\Concerns\PesanValidasi;
use App\Models\PayrollRun;
use App\Services\PayrollService;
use DomainException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.panel')]
class PayrollRunDetail extends Component
{
    use PesanValidasi;

    public PayrollRun $run;
    public array $input = []; // [detail_id => [bonus_produksi, lembur, thr, potongan]]

    public function mount(PayrollRun $run): void
    {
        $this->authorize('sdm');
        $this->run = $run;
        $this->isiInput();
    }

    private function isiInput(): void
    {
        $this->input = $this->run->details()->get()->mapWithKeys(fn ($d) => [$d->id => [
            'bonus_produksi' => (string) (float) $d->bonus_produksi,
            'lembur' => (string) (float) $d->lembur,
            'thr' => (string) (float) $d->thr,
            'potongan' => (string) (float) $d->potongan,
        ]])->all();
    }

    private function validasiInput(): void
    {
        $this->validate([
            'input.*.bonus_produksi' => ['nullable', 'numeric', 'min:0'],
            'input.*.lembur' => ['nullable', 'numeric', 'min:0'],
            'input.*.thr' => ['nullable', 'numeric', 'min:0'],
            'input.*.potongan' => ['nullable', 'numeric', 'min:0'],
        ], attributes: [
            'input.*.bonus_produksi' => 'Bonus produksi', 'input.*.lembur' => 'Lembur',
            'input.*.thr' => 'THR', 'input.*.potongan' => 'Potongan',
        ]);
    }

    public function simpan(): void
    {
        $this->authorize('sdm');
        $this->validasiInput();

        try {
            app(PayrollService::class)->simpanPenyesuaian($this->run, $this->input, auth()->user());
        } catch (DomainException $e) {
            $this->addError('input', $e->getMessage());
            return;
        }

        $this->run->refresh();
        $this->dispatch('toast', message: 'Penyesuaian payroll disimpan.');
    }

    public function muatUlang(): void
    {
        $this->authorize('sdm');

        try {
            [, $tanpaKomponen] = app(PayrollService::class)->siapkanDraft($this->run->periode_bulan, auth()->user());
        } catch (DomainException $e) {
            $this->dispatch('toast', message: $e->getMessage());
            return;
        }

        $this->run->refresh();
        $this->isiInput();
        $this->dispatch('toast', message: $tanpaKomponen
            ? 'Dimuat ulang. Dilewati (tanpa komponen gaji): ' . implode(', ', $tanpaKomponen)
            : 'Data karyawan & gaji dimuat ulang.');
    }

    public function finalisasi(): void
    {
        $this->authorize('sdm');
        $this->validasiInput();

        $service = app(PayrollService::class);

        try {
            $service->simpanPenyesuaian($this->run, $this->input, auth()->user()); // simpan input terakhir dulu
            $service->finalisasi($this->run, auth()->user());
        } catch (DomainException $e) {
            $this->addError('input', $e->getMessage());
            return;
        }

        $this->run->refresh();
        $this->dispatch('toast', message: 'Payroll final. Beban gaji agregat sudah tercatat di Keuangan.');
    }

    public function hapus()
    {
        $this->authorize('sdm');

        try {
            app(PayrollService::class)->hapusDraft($this->run, auth()->user());
        } catch (DomainException $e) {
            $this->dispatch('toast', message: $e->getMessage());
            return null;
        }

        session()->flash('toast', 'Draft payroll dihapus.');

        return $this->redirectRoute('payroll', navigate: true);
    }

    public function render()
    {
        return view('livewire.sdm.payroll-run-detail', [
            'details' => $this->run->details()->with('employee:id,kode_karyawan,nama_lengkap,posisi')->orderBy('employee_id')->get(),
        ])->title('Payroll ' . $this->run->periode_bulan->translatedFormat('F Y') . ' · Koombaah');
    }
}