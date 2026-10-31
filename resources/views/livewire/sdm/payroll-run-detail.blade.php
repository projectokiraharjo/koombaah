@use('App\Support\Format')
@use('App\Services\PayrollService')

@php
    $final = $run->isFinal();
    // Total live dari isian (sebelum disimpan)
    $totalLive = $details->sum(fn ($d) => max(0, PayrollService::hitungTotal([
        'gaji_pokok' => $d->gaji_pokok, 'tunjangan' => $d->tunjangan,
        'bonus_produksi' => is_numeric($input[$d->id]['bonus_produksi'] ?? null) ? $input[$d->id]['bonus_produksi'] : 0,
        'lembur' => is_numeric($input[$d->id]['lembur'] ?? null) ? $input[$d->id]['lembur'] : 0,
        'thr' => is_numeric($input[$d->id]['thr'] ?? null) ? $input[$d->id]['thr'] : 0,
        'potongan' => is_numeric($input[$d->id]['potongan'] ?? null) ? $input[$d->id]['potongan'] : 0,
    ])));
@endphp

<div class="flex flex-col gap-6">
    <div>
        <a href="{{ route('payroll') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">← Semua payroll</a>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight">Payroll {{ $run->periode_bulan->translatedFormat('F Y') }}</h1>
                    <x-ui.badge :variant="$run->status->badge()">{{ $run->status->label() }}</x-ui.badge>
                </div>
                <p class="text-sm text-muted-foreground">{{ $details->count() }} karyawan · diproses {{ $run->diproses_pada->format('d/m/Y H:i') }} oleh {{ $run->pemroses->name }}</p>
            </div>
            @unless ($final)
                <div class="flex flex-wrap gap-2">
                    <x-ui.button variant="outline" wire:click="muatUlang">Muat Ulang Karyawan</x-ui.button>
                    <x-ui.button variant="outline" wire:click="simpan">Simpan</x-ui.button>
                    <x-ui.button wire:click="finalisasi"
                                 wire:confirm="Finalisasi payroll? Data tidak dapat diubah lagi dan total agregat akan dicatat ke Keuangan.">Finalisasi</x-ui.button>
                    <x-ui.button variant="destructive" wire:click="hapus" wire:confirm="Hapus draft payroll ini?">Hapus Draft</x-ui.button>
                </div>
            @endunless
        </div>
    </div>

    @if ($final)
        <div class="rounded-lg border bg-muted/50 px-4 py-3 text-sm">
            🔒 Payroll final. Tercatat di Keuangan sebagai <span class="font-medium">Beban Gaji (agregat) {{ Format::rupiah($run->total_gaji_agregat) }}</span>
            — Admin Keuangan hanya melihat angka total ini, tanpa rincian per karyawan. Koreksi dilakukan di payroll bulan berikutnya.
        </div>
    @endif

    <x-ui.error for="input" />

    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Karyawan</th>
                    <th class="px-4 py-3 text-right font-medium">Gaji Pokok</th>
                    <th class="px-4 py-3 text-right font-medium">Tunjangan</th>
                    <th class="px-4 py-3 text-right font-medium">Bonus Produksi</th>
                    <th class="px-4 py-3 text-right font-medium">Lembur</th>
                    <th class="px-4 py-3 text-right font-medium">THR</th>
                    <th class="px-4 py-3 text-right font-medium">Potongan</th>
                    <th class="px-4 py-3 text-right font-medium">Diterima</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($details as $d)
                    @php
                        $i = $input[$d->id] ?? [];
                        $angka = fn ($k) => is_numeric($i[$k] ?? null) ? $i[$k] : 0;
                        $total = PayrollService::hitungTotal([
                            'gaji_pokok' => $d->gaji_pokok, 'tunjangan' => $d->tunjangan,
                            'bonus_produksi' => $angka('bonus_produksi'), 'lembur' => $angka('lembur'),
                            'thr' => $angka('thr'), 'potongan' => $angka('potongan'),
                        ]);
                    @endphp
                    <tr wire:key="pd-{{ $d->id }}">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $d->employee->nama_lengkap }}</div>
                            <div class="text-xs text-muted-foreground">{{ $d->employee->kode_karyawan }} · {{ $d->employee->posisi }}</div>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ Format::rupiah($d->gaji_pokok) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ Format::rupiah($d->tunjangan) }}</td>
                        @foreach (['bonus_produksi', 'lembur', 'thr', 'potongan'] as $kolom)
                            <td class="px-2 py-3 text-right">
                                @if ($final)
                                    <span class="tabular-nums">{{ Format::rupiah($d->$kolom) }}</span>
                                @else
                                    <x-ui.input type="number" step="1000" min="0" class="w-28 text-right"
                                                wire:model.live.debounce.500ms="input.{{ $d->id }}.{{ $kolom }}" />
                                @endif
                            </td>
                        @endforeach
                        <td @class(['px-4 py-3 text-right font-semibold tabular-nums', 'text-destructive' => $total < 0])>
                            {{ Format::rupiah($final ? $d->total_diterima : $total) }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-muted-foreground">Tidak ada karyawan yang memenuhi syarat payroll di periode ini.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="border-t bg-muted/30">
                    <td colspan="7" class="px-4 py-3 text-right font-medium">Total Gaji Agregat (dikirim ke Keuangan)</td>
                    <td class="px-4 py-3 text-right text-base font-semibold tabular-nums">{{ Format::rupiah($final ? $run->total_gaji_agregat : $totalLive) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>