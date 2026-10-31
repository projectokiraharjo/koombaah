<?php

namespace App\Livewire;

use App\Livewire\Concerns\PesanValidasi;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Profil Saya · Koombaah')]
class Profil extends Component
{
    use PesanValidasi { messages as pesanDasar; }

    public string $name = '';
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->name = auth()->user()->name;
    }

    public function simpanProfil(): void
    {
        $this->validate(['name' => ['required', 'string', 'max:100']]);
        auth()->user()->update(['name' => trim($this->name)]);
        $this->dispatch('toast', message: 'Nama berhasil diperbarui.');
    }

    public function gantiPassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        auth()->user()->update(['password' => $this->password]);
        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->dispatch('toast', message: 'Password berhasil diganti.');
    }

    protected function messages(): array
    {
        return $this->pesanDasar() + [
            'current_password.current_password' => 'Password lama salah.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.different' => 'Password baru harus berbeda dari password lama.',
        ];
    }

    protected function validationAttributes(): array
    {
        return ['name' => 'Nama', 'current_password' => 'Password lama', 'password' => 'Password baru'];
    }

    public function render()
    {
        return view('livewire.profil', ['user' => auth()->user()]);
    }
}