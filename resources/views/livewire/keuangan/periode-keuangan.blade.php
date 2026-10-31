@use('App\Support\Format')
@use('App\Enums\StatusPeriode')

<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Periode Keuangan</h1>
        <p class="text-sm text-muted-foreground">
            Periode yang ditutup mengunci seluruh transaksi kas & biaya di bulan itu, dan laporan memakai angka snapshot.
            Hanya Owner yang dapat menutup atau membuka kembali.
        </p>
    </div>

    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Periode</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 text-right font-medium">Pendapatan</th>
                    <th class="px-4 py-3 text-right font-medium">Laba / Rugi</th>
                    <th class="px-4 py-3 font-medium">Riwayat</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($bulanList as $b)
                    @php $p = $b['periode']; $ditutup = $p?->status === StatusPeriode::Ditutup; @endphp
                    <tr wire:key="per-{{ $b['kunci'] }}">
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('keuangan', ['bulan' => $b['kunci']]) }}" wire:navigate class="hover:underline">{{ $b['label'] }}</a>
                        </td>
                        <td class="px-4 py-3">
                            @if ($b['berjalan'])
                                <x-ui.badge variant="outline">Berjalan</x-ui.badge>
                            @else
                                <x-ui.badge :variant="($p?->status ?? StatusPeriode::Terbuka)->badge()">{{ ($p?->status ?? StatusPeriode::Terbuka)->label() }}</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $ditutup ? Format::rupiah($p->total_pendapatan) : '–' }}</td>
                        <td @class(['px-4 py-3 text-right tabular-nums', 'text-destructive' => $ditutup && (float) $p->laba_rugi < 0])>
                            {{ $ditutup ? Format::rupiah($p->laba_rugi) : '–' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-muted-foreground">
                            @if ($p?->ditutup_pada)
                                <div>Ditutup {{ $p->ditutup_pada->format('d/m/Y H:i') }} oleh {{ $p->penutup?->name }}</div>
                            @endif
                            @if ($p?->dibuka_kembali_pada)
                                <div>Dibuka kembali {{ $p->dibuka_kembali_pada->format('d/m/Y H:i') }} oleh {{ $p->pembuka?->name }} — "{{ $p->alasan_buka_kembali }}"</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @can('periode.kelola')
                                @if ($ditutup)
                                    <x-ui.button variant="outline" size="sm" wire:click="bukaDialog({{ $p->id }})">Buka Kembali</x-ui.button>
                                @elseif (! $b['berjalan'])
                                    <x-ui.button size="sm" wire:click="tutup('{{ $b['kunci'] }}')"
                                                 wire:confirm="Tutup periode {{ $b['label'] }}? Seluruh transaksi bulan ini akan dikunci.">Tutup Periode</x-ui.button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <x-ui.dialog model="showBuka" title="Buka Kembali Periode"
                 description="Alasan tercatat permanen sebagai jejak audit. Setelah koreksi, tutup kembali periode agar snapshot dihitung ulang.">
        <form wire:submit="bukaKembali" class="flex flex-col gap-4">
            <div class="grid gap-2">
                <x-ui.label for="alasan">Alasan</x-ui.label>
                <x-ui.textarea id="alasan" wire:model="alasan" rows="2" placeholder="mis. Ada tagihan listrik September yang terlewat dicatat" />
                <x-ui.error for="alasan" />
            </div>
            <div class="mt-2 flex justify-end gap-2">
                <x-ui.button variant="outline" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="bukaKembali">Buka Kembali</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>
</div>