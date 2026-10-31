@use('App\Support\Format')
@use('App\Enums\KategoriBiaya')

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Laporan Laba-Rugi</h1>
            <p class="text-sm text-muted-foreground">Pendapatan − HPP − Biaya Operasional (cash-basis).</p>
        <div class="flex flex-wrap gap-2">
            <x-ui.input type="month" wire:model.live="bulan" class="w-44" />
            <x-ui.button variant="outline" href="{{ route('ekspor.laba-rugi', ['format' => 'pdf', 'bulan' => $bulan]) }}">PDF</x-ui.button>
            <x-ui.button variant="outline" href="{{ route('ekspor.laba-rugi', ['format' => 'xlsx', 'bulan' => $bulan]) }}">Excel</x-ui.button>
        </div>

    @if ($ditutup)
        <div class="rounded-lg border bg-muted/50 px-4 py-3 text-sm">
            🔒 Periode {{ $labelBulan }} sudah ditutup — angka total di bawah memakai <span class="font-medium">snapshot resmi</span> saat penutupan.
        </div>
    @endif

    {{-- Ringkasan --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([['Pendapatan', $ringkasan['pendapatan']], ['HPP', $ringkasan['hpp']], ['Biaya Operasional', $ringkasan['biaya']]] as [$label, $nilai])
            <x-ui.card>
                <p class="text-xs text-muted-foreground">{{ $label }}</p>
                <p class="text-xl font-semibold tabular-nums">{{ Format::rupiah($nilai) }}</p>
            </x-ui.card>
        @endforeach
        <x-ui.card>
            <p class="text-xs text-muted-foreground">{{ $ringkasan['laba_rugi'] >= 0 ? 'Laba Bersih' : 'Rugi Bersih' }}</p>
            <p @class(['text-xl font-semibold tabular-nums', 'text-destructive' => $ringkasan['laba_rugi'] < 0])>{{ Format::rupiah($ringkasan['laba_rugi']) }}</p>
        </x-ui.card>
    </div>

    {{-- Laporan rinci --}}
    <x-ui.card :title="'Laba-Rugi ' . $labelBulan">
        <table class="w-full text-sm">
            <tbody>
                <tr class="border-b">
                    <td class="py-2 font-medium">Pendapatan Penjualan</td>
                    <td class="py-2 text-right font-medium tabular-nums">{{ Format::rupiah($lr['pendapatan']) }}</td>
                </tr>

                <tr><td colspan="2" class="pt-3 pb-1 font-medium">Harga Pokok Penjualan (HPP)</td></tr>
                @forelse ($lr['rincian_hpp'] as $h)
                    <tr>
                        <td class="py-1 pl-4 text-muted-foreground">
                            {{ $h['nama'] }} — {{ Format::angka($h['jumlah']) }} {{ $h['satuan'] }} ×
                            {{ $h['hpp_satuan'] !== null ? Format::rupiah($h['hpp_satuan']) : 'HPP belum ada' }}
                        </td>
                        <td class="py-1 text-right tabular-nums">({{ Format::rupiah($h['total_hpp']) }})</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="py-1 pl-4 text-muted-foreground">Tidak ada penjualan.</td></tr>
                @endforelse
                <tr class="border-t">
                    <td class="py-2 font-medium">Laba Kotor</td>
                    <td class="py-2 text-right font-medium tabular-nums">{{ Format::rupiah($lr['laba_kotor']) }}</td>
                </tr>

                <tr><td colspan="2" class="pt-3 pb-1 font-medium">Biaya Operasional</td></tr>
                @forelse ($lr['biaya'] as $kategori => $nominal)
                    <tr>
                        <td class="py-1 pl-4 text-muted-foreground">{{ KategoriBiaya::from($kategori)->label() }}</td>
                        <td class="py-1 text-right tabular-nums">({{ Format::rupiah($nominal) }})</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="py-1 pl-4 text-muted-foreground">Belum ada biaya operasional.</td></tr>
                @endforelse
                <tr class="border-t">
                    <td class="py-2 font-medium">Total Biaya Operasional</td>
                    <td class="py-2 text-right font-medium tabular-nums">({{ Format::rupiah($lr['total_biaya']) }})</td>
                </tr>

                <tr class="border-t-2 border-foreground/20">
                    <td class="py-3 text-base font-semibold">{{ $lr['laba_rugi'] >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' }}</td>
                    <td @class(['py-3 text-right text-base font-semibold tabular-nums', 'text-destructive' => $lr['laba_rugi'] < 0])>{{ Format::rupiah($lr['laba_rugi']) }}</td>
                </tr>
            </tbody>
        </table>

        @if ($lr['tanpa_hpp']->isNotEmpty())
            <p class="mt-3 text-xs text-destructive">
                ⚠ Produk tanpa HPP (stok berasal dari input manual, bukan batch produksi): {{ $lr['tanpa_hpp']->implode(', ') }}. HPP-nya dihitung Rp 0.
            </p>
        @endif
    </x-ui.card>

    {{-- Perbandingan 6 bulan --}}
    <x-ui.card title="Perbandingan 6 Bulan" description="Periode yang sudah ditutup memakai angka snapshot.">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Bulan</th>
                        <th class="py-2 pr-4 text-right font-medium">Pendapatan</th>
                        <th class="py-2 pr-4 text-right font-medium">HPP</th>
                        <th class="py-2 pr-4 text-right font-medium">Biaya Operasional</th>
                        <th class="py-2 pr-4 text-right font-medium">Laba / Rugi</th>
                        <th class="py-2 font-medium">Sumber</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($perbandingan as $p)
                        <tr>
                            <td class="py-2 pr-4 font-medium">{{ $p['label'] }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($p['data']['pendapatan']) }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($p['data']['hpp']) }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($p['data']['biaya']) }}</td>
                            <td @class(['py-2 pr-4 text-right font-medium tabular-nums', 'text-destructive' => $p['data']['laba_rugi'] < 0])>{{ Format::rupiah($p['data']['laba_rugi']) }}</td>
                            <td class="py-2">
                                <x-ui.badge :variant="$p['data']['snapshot'] ? 'secondary' : 'outline'">{{ $p['data']['snapshot'] ? 'Snapshot' : 'Real-time' }}</x-ui.badge>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>