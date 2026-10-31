<?php

namespace App\Livewire\Keuangan;

use App\Enums\KategoriBiaya;
use App\Livewire\Concerns\PesanValidasi;
use App\Models\CashTransaction;
use App\Models\OperationalExpense;
use App\Services\FinanceService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.panel')]
#[Title('Biaya Operasional · Koombaah')]
class BiayaOperasional extends Component
{
    use PesanValidasi;

    #[Url] public string $bulan = '';

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $periode_bulan = '';
    public string $kategori = '';
    public string $nominal = '';
    public string $catatan = '';

    public function mount(): void
    {
        $this->authorize('keuangan');
        if (! preg_match('/^\d{4}-\d{2}$/', $this->bulan)) {
            $this->bulan = now()->format('Y-m');
        }
    }

    public function create(): void
    {
        $this->authorize('keuangan');
        $this->resetForm();
        $this->periode_bulan = $this->bulan;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('keuangan');
        $this->resetForm();

        $b = OperationalExpense::findOrFail($id);
        $this->editingId = $b->id;
        $this->periode_bulan = $b->periode_bulan->format('Y-m');
        $this->kategori = $b->kategori->value;
        $this->nominal = (string) (float) $b->nominal;
        $this->catatan = (string) $b->catatan;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('keuangan');

        $this->validate([
            'periode_bulan' => ['required', 'date_format:Y-m'],
            'kategori' => ['required', Rule::in(array_map(fn ($k) => $k->value, KategoriBiaya::manual()))],
            'nominal' => ['required', 'numeric', 'gt:0'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], array_merge($this->messages(), [
            'periode_bulan.date_format' => 'Periode tidak valid.',
            'kategori.in' => 'Kategori tidak valid.',
        ]), ['periode_bulan' => 'Periode', 'kategori' => 'Kategori', 'nominal' => 'Nominal', 'catatan' => 'Catatan']);

        try {
            app(FinanceService::class)->simpanBiaya(
                $this->editingId ? OperationalExpense::findOrFail($this->editingId) : null,
                [
                    'periode_bulan' => $this->periode_bulan . '-01',
                    'kategori' => $this->kategori,
                    'nominal' => $this->nominal,
                    'catatan' => $this->catatan ?: null,
                ],
                auth()->user(),
            );
        } catch (DomainException $e) {
            $this->addError('periode_bulan', $e->getMessage());
            return;
        }

        $this->showForm = false;
        $this->bulan = $this->periode_bulan;
        $this->dispatch('toast', message: 'Biaya operasional disimpan.');
    }

    public function hapus(int $id): void
    {
        $this->authorize('keuangan');

        try {
            app(FinanceService::class)->hapusBiaya(OperationalExpense::findOrFail($id));
        } catch (DomainException $e) {
            $this->dispatch('toast', message: $e->getMessage());
            return;
        }

        $this->dispatch('toast', message: 'Biaya operasional dihapus.');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'periode_bulan', 'kategori', 'nominal', 'catatan']);
        $this->resetValidation();
    }

    public function render()
    {
        $awal = Carbon::parse($this->bulan . '-01');

        return view('livewire.keuangan.biaya-operasional', [
            'daftar' => OperationalExpense::with('pencatat:id,name')
                ->where('periode_bulan', $awal->toDateString())->orderBy('kategori')->get(),
            'distribusi' => CashTransaction::where('kategori', 'distribusi')
                ->whereBetween('tanggal_transaksi', [$awal->toDateString(), $awal->copy()->endOfMonth()->toDateString()])
                ->get(),
            'ditutup' => app(FinanceService::class)->periodeDitutup($awal),
            'labelBulan' => $awal->translatedFormat('F Y'),
            'kategoriList' => KategoriBiaya::manual(),
        ]);
    }
}