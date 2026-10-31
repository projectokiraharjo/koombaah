<?php

namespace App\Livewire\Sdm;

use App\Enums\StatusAktif;
use App\Livewire\Concerns\PesanValidasi;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.panel')]
#[Title('Karyawan · Koombaah')]
class DaftarKaryawan extends Component
{
    use WithPagination, PesanValidasi;

    #[Url(as: 'q')] public string $search = '';
    #[Url(as: 'status')] public string $filterStatus = 'aktif';

    public bool $showForm = false;
    public ?int $editingId = null;

    public string $kode_karyawan = '';
    public string $nama_lengkap = '';
    public string $posisi = '';
    public string $no_telepon = '';
    public string $tanggal_mulai_kerja = '';
    public string $status = 'aktif';
    public string $tanggal_berhenti = '';
    public string $user_id = '';
    // Hanya saat tambah baru: komponen gaji awal
    public string $gaji_pokok_awal = '';
    public string $tunjangan_awal = '';

    public function mount(): void
    {
        $this->authorize('sdm');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'filterStatus'], true)) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        $this->authorize('sdm');
        $this->resetForm();
        $this->kode_karyawan = Employee::generateKode();
        $this->tanggal_mulai_kerja = now()->format('Y-m-d');
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('sdm');
        $this->resetForm();

        $e = Employee::findOrFail($id);
        $this->editingId = $e->id;
        $this->kode_karyawan = $e->kode_karyawan;
        $this->nama_lengkap = $e->nama_lengkap;
        $this->posisi = $e->posisi;
        $this->no_telepon = (string) $e->no_telepon;
        $this->tanggal_mulai_kerja = $e->tanggal_mulai_kerja->format('Y-m-d');
        $this->status = $e->status->value;
        $this->tanggal_berhenti = $e->tanggal_berhenti?->format('Y-m-d') ?? '';
        $this->user_id = (string) ($e->user_id ?? '');
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('sdm');

        $this->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'posisi' => ['required', 'string', 'max:100'],
            'no_telepon' => ['nullable', 'string', 'max:30'],
            'tanggal_mulai_kerja' => ['required', 'date'],
            'status' => ['required', Rule::enum(StatusAktif::class)],
            // B.7.7: nonaktif = sudah resign -> wajib ada tanggal berhenti
            'tanggal_berhenti' => [$this->status === 'nonaktif' ? 'required' : 'nullable', 'date', 'after_or_equal:tanggal_mulai_kerja'],
            'user_id' => ['nullable', Rule::exists('users', 'id'), Rule::unique('employees', 'user_id')->ignore($this->editingId)],
            'gaji_pokok_awal' => ['nullable', 'numeric', 'min:0'],
            'tunjangan_awal' => ['nullable', 'numeric', 'min:0'],
        ], array_merge($this->messages(), [
            'tanggal_berhenti.after_or_equal' => 'Tanggal berhenti tidak boleh sebelum tanggal mulai kerja.',
            'tanggal_berhenti.required' => 'Karyawan nonaktif wajib memiliki tanggal berhenti.',
        ]), [
            'nama_lengkap' => 'Nama lengkap', 'posisi' => 'Posisi', 'no_telepon' => 'No. telepon',
            'tanggal_mulai_kerja' => 'Tanggal mulai kerja', 'user_id' => 'Akun sistem',
            'gaji_pokok_awal' => 'Gaji pokok', 'tunjangan_awal' => 'Tunjangan',
        ]);

        $data = [
            'nama_lengkap' => $this->nama_lengkap,
            'posisi' => $this->posisi,
            'no_telepon' => $this->no_telepon ?: null,
            'tanggal_mulai_kerja' => $this->tanggal_mulai_kerja,
            'status' => $this->status,
            'tanggal_berhenti' => $this->status === 'nonaktif' ? $this->tanggal_berhenti : null,
            'user_id' => $this->user_id ?: null,
        ];

        DB::transaction(function () use ($data) {
            if ($this->editingId) {
                Employee::findOrFail($this->editingId)->update($data);
                return;
            }

            $karyawan = Employee::create($data + ['kode_karyawan' => Employee::generateKode()]);

            if ($this->gaji_pokok_awal !== '') {
                EmployeeSalaryComponent::create([
                    'employee_id' => $karyawan->id,
                    'gaji_pokok' => $this->gaji_pokok_awal,
                    'tunjangan' => $this->tunjangan_awal ?: 0,
                    'berlaku_sejak' => $this->tanggal_mulai_kerja,
                ]);
            }
        });

        $this->showForm = false;
        $this->dispatch('toast', message: $this->editingId ? 'Data karyawan diperbarui.' : 'Karyawan baru didaftarkan.');
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'kode_karyawan', 'nama_lengkap', 'posisi', 'no_telepon', 'tanggal_mulai_kerja',
            'status', 'tanggal_berhenti', 'user_id', 'gaji_pokok_awal', 'tunjangan_awal']);
        $this->resetValidation();
    }

    public function render()
    {
        $items = Employee::query()
            ->with('user:id,name,role')
            // Gaji pokok yang berlaku hari ini (subquery ikut terlindungi global scope Owner-only)
            ->addSelect(['gaji_pokok_kini' => EmployeeSalaryComponent::select('gaji_pokok')
                ->whereColumn('employee_id', 'employees.id')
                ->where('berlaku_sejak', '<=', now()->toDateString())
                ->orderByDesc('berlaku_sejak')->limit(1)])
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('nama_lengkap', 'ilike', "%{$this->search}%")
                ->orWhere('kode_karyawan', 'ilike', "%{$this->search}%")
                ->orWhere('posisi', 'ilike', "%{$this->search}%")))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('kode_karyawan')
            ->paginate(10);

        $userTertaut = Employee::whereNotNull('user_id')->where('id', '!=', $this->editingId ?? 0)->pluck('user_id');

        return view('livewire.sdm.daftar-karyawan', [
            'items' => $items,
            'userList' => User::whereNotIn('id', $userTertaut)->orderBy('name')->get(['id', 'name', 'role']),
            'posisiList' => Employee::query()->distinct()->pluck('posisi')
                ->merge(['Staf Produksi', 'Staf Gudang', 'Admin Operasional'])->unique()->sort()->values(),
        ]);
    }
}