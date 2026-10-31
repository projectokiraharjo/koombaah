<?php

namespace App\Livewire\Sdm;

use App\Livewire\Concerns\PesanValidasi;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Services\PayrollService;
use DomainException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.panel')]
class KaryawanDetail extends Component
{
    use PesanValidasi;

    public Employee $employee;

    public bool $showForm = false;
    public string $gaji_pokok = '';
    public string $tunjangan = '0';
    public string $berlaku_sejak = '';

    public function mount(Employee $employee): void
    {
        $this->authorize('sdm');
        $this->employee = $employee;
    }

    public function bukaForm(): void
    {
        $this->authorize('sdm');
        $this->resetValidation();
        $kini = $this->employee->komponenBerlaku(now());
        $this->gaji_pokok = $kini ? (string) (float) $kini->gaji_pokok : '';
        $this->tunjangan = $kini ? (string) (float) $kini->tunjangan : '0';
        $this->berlaku_sejak = now()->startOfMonth()->format('Y-m-d');
        $this->showForm = true;
    }

    public function simpan(): void
    {
        $this->authorize('sdm');

        $this->validate([
            'gaji_pokok' => ['required', 'numeric', 'min:0'],
            'tunjangan' => ['required', 'numeric', 'min:0'],
            'berlaku_sejak' => ['required', 'date',
                Rule::unique('employee_salary_components', 'berlaku_sejak')->where('employee_id', $this->employee->id)],
        ], array_merge($this->messages(), [
            'berlaku_sejak.unique' => 'Sudah ada komponen gaji yang berlaku di tanggal ini.',
        ]), ['gaji_pokok' => 'Gaji pokok', 'tunjangan' => 'Tunjangan', 'berlaku_sejak' => 'Berlaku sejak']);

        try {
            app(PayrollService::class)->tambahKomponen($this->employee, [
                'gaji_pokok' => $this->gaji_pokok,
                'tunjangan' => $this->tunjangan,
                'berlaku_sejak' => $this->berlaku_sejak,
            ], auth()->user());
        } catch (DomainException $e) {
            $this->addError('berlaku_sejak', $e->getMessage());
            return;
        }

        $this->showForm = false;
        $this->dispatch('toast', message: 'Komponen gaji ditambahkan.');
    }

    public function hapus(int $id): void
    {
        $this->authorize('sdm');

        try {
            app(PayrollService::class)->hapusKomponen(
                EmployeeSalaryComponent::where('employee_id', $this->employee->id)->findOrFail($id), auth()->user(),
            );
        } catch (DomainException $e) {
            $this->dispatch('toast', message: $e->getMessage());
            return;
        }

        $this->dispatch('toast', message: 'Komponen gaji dihapus.');
    }

    public function render()
    {
        return view('livewire.sdm.karyawan-detail', [
            'komponen' => $this->employee->salaryComponents()->get(),
            'komponenKini' => $this->employee->komponenBerlaku(now()),
            // US 3: riwayat penggajian dari bulan ke bulan
            'riwayat' => $this->employee->payrollDetails()->with('run')->get()
                ->sortByDesc(fn ($d) => $d->run->periode_bulan)->values(),
        ])->title("{$this->employee->nama_lengkap} · Koombaah");
    }
}