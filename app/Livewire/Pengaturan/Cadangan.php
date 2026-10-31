<?php

namespace App\Livewire\Pengaturan;

use App\Services\BackupService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Layout('components.layouts.panel')]
#[Title('Cadangan Data · Koombaah')]
class Cadangan extends Component
{
    public function mount(): void
    {
        $this->authorize('pengaturan');
    }

    public function buatSekarang(): void
    {
        $this->authorize('pengaturan');

        try {
            $nama = app(BackupService::class)->buat();
        } catch (RuntimeException $e) {
            $this->addError('backup', $e->getMessage());

            return;
        }

        $this->dispatch('toast', message: "Cadangan {$nama} berhasil dibuat.");
    }

    public function hapus(string $nama): void
    {
        $this->authorize('pengaturan');
        app(BackupService::class)->hapus($nama);
        $this->dispatch('toast', message: 'Cadangan dihapus.');
    }

    public function render()
    {
        return view('livewire.pengaturan.cadangan', ['file' => app(BackupService::class)->daftar()]);
    }
}