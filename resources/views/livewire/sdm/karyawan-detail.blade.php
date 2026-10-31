@use('App\Support\Format')

<div class="flex flex-col gap-6">
    <div>
        <a href="{{ route('karyawan') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">← Semua karyawan</a>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight">{{ $employee->nama_lengkap }}</h1>
                    <x-ui.badge :variant="$employee->status->value === 'aktif' ? 'secondary' : 'outline'">{{ $employee->status->label() }}</x-ui.badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ $employee->kode_karyawan }} · {{ $employee->posisi }} ·
                    {{ $employee->tanggal_mulai_kerja->format('d/m/Y') }} → {{ $employee->tanggal_berhenti?->format('d/m/Y') ?? 'sekarang' }}
                </p>
            </div>
            @if ($employee->status->value === 'aktif')
                <x-ui.button wire:click="bukaForm">+ Komponen Gaji Baru</x-ui.button>
            @endif
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Gaji pokok saat ini</p>
            <p class="text-xl font-semibold tabular-nums">{{ $komponenKini ? Format::rupiah($komponenKini->gaji_pokok) : '–' }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Tunjangan tetap</p>
            <p class="text-xl font-semibold tabular-nums">{{ $komponenKini ? Format::rupiah($komponenKini->tunjangan) : '–' }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Gaji tetap / bulan</p>
            <p class="text-xl font-semibold tabular-nums">{{ $komponenKini ? Format::rupiah((float) $komponenKini->gaji_pokok + (float) $komponenKini->tunjangan) : '–' }}</p>
        </x-ui.card>
    </div>

    <x-ui.card title="Riwayat Komponen Gaji Tetap" description="Kenaikan gaji dicatat sebagai entri baru (berlaku sejak tanggal tertentu). Bonus produksi, lembur, THR, dan potongan diisi per bulan di Payroll.">
        <table class="w-full text-sm">
            <thead class="border-b text-left text-muted-foreground">
                <tr>
                    <th class="py-2 pr-4 font-medium">Berlaku Sejak</th>
                    <th class="py-2 pr-4 text-right font-medium">Gaji Pokok</th>
                    <th class="py-2 pr-4 text-right font-medium">Tunjangan</th>
                    <th class="py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($komponen as $k)
                    <tr wire:key="esc-{{ $k->id }}">
                        <td class="py-2 pr-4">
                            {{ $k->berlaku_sejak->format('d/m/Y') }}
                            @if ($komponenKini?->id === $k->id) <x-ui.badge class="ml-1">Berlaku</x-ui.badge> @endif
                        </td>
                        <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($k->gaji_pokok) }}</td>
                        <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($k->tunjangan) }}</td>
                        <td class="py-2 text-right">
                            <x-ui.button variant="ghost" size="sm" wire:click="hapus({{ $k->id }})" wire:confirm="Hapus komponen gaji ini?">Hapus</x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-6 text-center text-muted-foreground">Belum ada komponen gaji — karyawan ini tidak akan ikut payroll.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-ui.card>

    <x-ui.card title="Riwayat Penggajian">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Periode</th>
                        <th class="py-2 pr-4 text-right font-medium">Pokok + Tunj.</th>
                        <th class="py-2 pr-4 text-right font-medium">Bonus</th>
                        <th class="py-2 pr-4 text-right font-medium">Lembur</th>
                        <th class="py-2 pr-4 text-right font-medium">THR</th>
                        <th class="py-2 pr-4 text-right font-medium">Potongan</th>
                        <th class="py-2 pr-4 text-right font-medium">Diterima</th>
                        <th class="py-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($riwayat as $d)
                        <tr>
                            <td class="py-2 pr-4">
                                <a href="{{ route('payroll.detail', $d->run) }}" wire:navigate class="hover:underline">{{ $d->run->periode_bulan->translatedFormat('F Y') }}</a>
                            </td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah((float) $d->gaji_pokok + (float) $d->tunjangan) }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($d->bonus_produksi) }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($d->lembur) }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($d->thr) }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($d->potongan) }}</td>
                            <td class="py-2 pr-4 text-right font-medium tabular-nums">{{ Format::rupiah($d->total_diterima) }}</td>
                            <td class="py-2"><x-ui.badge :variant="$d->run->status->badge()">{{ $d->run->status->label() }}</x-ui.badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-6 text-center text-muted-foreground">Belum pernah ikut payroll.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.dialog title="Komponen Gaji Baru" description="Komponen lama tetap tersimpan sebagai riwayat.">
        <form wire:submit="simpan" class="flex flex-col gap-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <x-ui.label for="gaji_pokok">Gaji Pokok / bulan</x-ui.label>
                    <x-ui.input id="gaji_pokok" type="number" step="1000" min="0" wire:model="gaji_pokok" />
                    <x-ui.error for="gaji_pokok" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="tunjangan">Tunjangan Tetap / bulan</x-ui.label>
                    <x-ui.input id="tunjangan" type="number" step="1000" min="0" wire:model="tunjangan" />
                    <x-ui.error for="tunjangan" />
                </div>
            </div>
            <div class="grid gap-2">
                <x-ui.label for="berlaku_sejak">Berlaku Sejak</x-ui.label>
                <x-ui.input id="berlaku_sejak" type="date" wire:model="berlaku_sejak" />
                <x-ui.error for="berlaku_sejak" />
            </div>
            <div class="mt-2 flex justify-end gap-2">
                <x-ui.button variant="outline" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="simpan">Simpan</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>
</div>