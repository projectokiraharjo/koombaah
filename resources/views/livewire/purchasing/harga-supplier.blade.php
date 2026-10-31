@use('App\Support\Format')
@use('Carbon\Carbon')

<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Harga Supplier</h1>
        <p class="text-sm text-muted-foreground">Bandingkan harga beli satu bahan baku dari beberapa supplier, berdasarkan PO yang sudah dipesan.</p>
    </div>

    <div class="max-w-md">
        <x-ui.label for="f-bahan">Bahan baku</x-ui.label>
        <x-ui.select id="f-bahan" wire:model.live="bahan" class="mt-1.5">
            @foreach ($bahanList as $b)
                <option value="{{ $b->id }}">{{ $b->kode_bahan }} — {{ $b->nama_bahan }}</option>
            @endforeach
        </x-ui.select>
    </div>

    {{-- Perbandingan per supplier --}}
    <x-ui.card :title="'Perbandingan Harga' . ($terpilih ? ' — ' . $terpilih->nama_bahan : '')"
               :description="$terpilih ? 'Harga per ' . $terpilih->satuan->value . ', diurutkan dari harga terakhir termurah.' : null">
        @if ($perSupplier->isEmpty())
            <p class="text-sm text-muted-foreground">Belum ada transaksi pembelian untuk bahan ini.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b text-left text-muted-foreground">
                        <tr>
                            <th class="py-2 pr-4 font-medium">Supplier</th>
                            <th class="py-2 pr-4 text-right font-medium">Harga Terakhir</th>
                            <th class="py-2 pr-4 text-right font-medium">Terendah</th>
                            <th class="py-2 pr-4 text-right font-medium">Tertinggi</th>
                            <th class="py-2 pr-4 text-right font-medium">Rata-rata</th>
                            <th class="py-2 text-right font-medium">Transaksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($perSupplier as $s)
                            <tr>
                                <td class="py-2 pr-4">
                                    <span class="font-medium">{{ $s['nama'] }}</span>
                                    @if ($loop->first && $perSupplier->count() > 1)
                                        <x-ui.badge class="ml-1">Termurah</x-ui.badge>
                                    @endif
                                    <div class="text-xs text-muted-foreground">terakhir {{ $s['tanggal_terakhir']->format('d/m/Y') }}</div>
                                </td>
                                <td class="py-2 pr-4 text-right font-semibold tabular-nums">{{ Format::rupiah($s['terakhir']) }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($s['terendah']) }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($s['tertinggi']) }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($s['rata']) }}</td>
                                <td class="py-2 text-right tabular-nums">{{ $s['jumlah'] }}×</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    {{-- Riwayat transaksi --}}
    @if ($transaksi->isNotEmpty())
        <x-ui.card title="Riwayat Pembelian" description="20 transaksi terbaru untuk bahan ini.">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b text-left text-muted-foreground">
                        <tr>
                            <th class="py-2 pr-4 font-medium">Tanggal</th>
                            <th class="py-2 pr-4 font-medium">Nomor PO</th>
                            <th class="py-2 pr-4 font-medium">Supplier</th>
                            <th class="py-2 pr-4 text-right font-medium">Jumlah</th>
                            <th class="py-2 text-right font-medium">Harga</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($transaksi as $t)
                            <tr>
                                <td class="py-2 pr-4 whitespace-nowrap">{{ Carbon::parse($t->tanggal_po)->format('d/m/Y') }}</td>
                                <td class="py-2 pr-4">
                                    <a href="{{ route('po.detail', $t->po_id) }}" wire:navigate class="font-mono text-xs hover:underline">{{ $t->nomor_po }}</a>
                                </td>
                                <td class="py-2 pr-4">{{ $t->nama_supplier }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ Format::angka($t->jumlah_dipesan) }} {{ $terpilih?->satuan->value }}</td>
                                <td class="py-2 text-right tabular-nums">{{ Format::rupiah($t->harga_per_satuan) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    {{-- Total pembelian per supplier (US 8) --}}
    <x-ui.card :title="'Total Pembelian per Supplier — ' . $labelBulan" description="Akumulasi nilai PO yang sudah dipesan dalam bulan terpilih.">
        <div class="mb-4 max-w-xs">
            <x-ui.input type="month" wire:model.live="bulan" />
        </div>
        @if ($totalPerSupplier->isEmpty())
            <p class="text-sm text-muted-foreground">Tidak ada pembelian di bulan ini.</p>
        @else
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Supplier</th>
                        <th class="py-2 pr-4 text-right font-medium">Jumlah PO</th>
                        <th class="py-2 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($totalPerSupplier as $r)
                        <tr>
                            <td class="py-2 pr-4 font-medium">{{ $r->nama_supplier }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ $r->jumlah_po }}</td>
                            <td class="py-2 text-right tabular-nums">{{ Format::rupiah($r->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-ui.card>
</div>