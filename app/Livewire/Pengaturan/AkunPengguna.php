<?php

namespace App\Livewire\Pengaturan;

use App\Enums\Role;
use App\Livewire\Concerns\PesanValidasi;
use App\Models\Employee;
use App\Models\User;
use App\Services\PenggunaService;
use DomainException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Akun Pengguna · Koombaah')]
class AkunPengguna extends Component
{
    use PesanValidasi { messages as pesanDasar; }

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public string $role = 'admin';
    public bool $is_active = true;
    public string $password = '';
    public string $password_confirmation = '';
    public string $employee_id = '';

    public function mount(): void
    {
        $this->authorize('pengaturan');
    }

    public function create(): void
    {
        $this->authorize('pengaturan');
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('pengaturan');
        $this->resetForm();

        $u = User::with('employee:id,user_id')->findOrFail($id);
        $this->editingId = $u->id;
        $this->name = $u->name;
        $this->email = $u->email;
        $this->role = $u->role->value;
        $this->is_active = $u->is_active;
        $this->employee_id = (string) ($u->employee?->id ?? '');

        $this->showForm = true;
    }

    public function save(PenggunaService $service): void
    {
        $this->authorize('pengaturan');
        $data = $this->validate();

        try {
            $service->simpan($this->editingId ? User::findOrFail($this->editingId) : null, $data, auth()->user());
        } catch (DomainException $e) {
            $this->addError('form', $e->getMessage());

            return;
        }

        $pesan = $this->editingId ? 'Akun berhasil diperbarui.' : 'Akun berhasil dibuat.';
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', message: $pesan);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role' => ['required', Rule::enum(Role::class)],
            'is_active' => ['boolean'],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('status', 'aktif')],
        ];
    }

    protected function messages(): array
    {
        return $this->pesanDasar() + [
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'email.email' => 'Format email tidak valid.',
        ];
    }

    protected function validationAttributes(): array
    {
        return ['name' => 'Nama', 'email' => 'Email', 'role' => 'Role', 'password' => 'Password', 'employee_id' => 'Karyawan'];
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'role', 'is_active', 'password', 'password_confirmation', 'employee_id']);
        $this->resetValidation();
    }

    public function render()
    {
        $q = trim($this->search);

        return view('livewire.pengaturan.akun-pengguna', [
            'items' => User::with('employee:id,user_id,nama_lengkap')
                ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'ilike', "%{$q}%")->orWhere('email', 'ilike', "%{$q}%")))
                ->orderByDesc('is_active')->orderBy('name')->get(),
            'karyawan' => Employee::where('status', 'aktif')
                ->where(fn ($w) => $w->whereNull('user_id')->when($this->editingId, fn ($x) => $x->orWhere('user_id', $this->editingId)))
                ->orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'posisi']),
        ]);
    }
}