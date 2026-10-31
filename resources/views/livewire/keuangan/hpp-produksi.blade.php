@use('App\Support\Format')

<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">HPP Produksi</h1>
        <p class="text-sm text-muted-foreground">HPP dihitung otomatis saat batch selesai, murni dari biaya bahan baku (harga saat batch dimulai), dan tidak berubah setelahnya.</p>
    </div>

    <x-ui.card title="HPP & Margin per Produk" description="HPP = rata-rata tertimbang seluruh batch selesai. Margin = harga jual saat ini − HPP.">
        @if ($produk->isEmpty())
            <p class="text-sm text-muted-foreground">Belum ada batch produksi yang selesai.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b text-left text-muted-foreground">
                        <tr>
                            <th class="py-2 pr-4 font-medium">Produk</th>
                            <th class="py-2 pr-4 text-right font-medium">HPP / satuan</th>
                            <th class="py-2 pr-4 text-right font-medium">Harga Jual</th>
                            <th class="py-2 pr-4 text-right font-medium">Margin / satuan</th>
                            <th class="py-2 text-right font-medium">Margin %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($produk as $r)
                            <tr>
                                <td class="py-2 pr-4 font-medium">{{ $r['produk']->nama_produk }} <span class="text-xs text-muted-foreground">/ {{ $r['produk']->satuan_jual->value }}</span></td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($r['hpp']) }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($r['produk']->harga_jual_per_satuan) }}</td>
                                <td @class(['py-2 pr-4 text-right tabular-nums', 'text-destructive' => $r['margin'] < 0])>{{ Format::rupiah($r['margin']) }}</td>
                                <td @class(['py-2 text-right font-medium tabular-nums', 'text-destructive' => ($r['margin_persen'] ?? 0) < 0])>
                                    {{ $r['margin_persen'] !== null ? Format::angka($r['margin_persen'], 1) . '%' : '–' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="HPP per Batch">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Batch</th>
                        <th class="py-2 pr-4 font-medium">Produk</th>
                        <th class="py-2 pr-4 font-medium">Selesai</th>
                        <th class="py-2 pr-4 text-right font-medium">Biaya Bahan</th>
                        <th class="py-2 pr-4 text-right font-medium">Lolos QC</th>
                        <th class="py-2 text-right font-medium">HPP / satuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($batches as $b)
                        <tr wire:key="hpp-{{ $b->id }}">
                            <td class="py-2 pr-4">
                                <a href="{{ route('produksi.detail', $b) }}" wire:navigate class="font-mono text-xs hover:underline">{{ $b->nomor_batch }}</a>
                            </td>
                            <td class="py-2 pr-4">{{ $b->product->nama_produk }}</td>
                            <td class="py-2 pr-4">{{ $b->tanggal_selesai->format('d/m/Y') }}</td>
                            @if ($b->hpp)
                                <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($b->hpp->total_biaya_bahan_baku) }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ Format::angka($b->hpp->jumlah_lolos_qc) }} {{ $b->product->satuan_jual->value }}</td>
                                <td class="py-2 text-right font-medium tabular-nums">{{ Format::rupiah($b->hpp->hpp_per_satuan) }}</td>
                            @else
                                <td colspan="3" class="py-2 text-right text-muted-foreground">
                                    {{ (float) $b->jumlah_lolos_qc > 0 ? 'Belum dihitung — jalankan keuangan:sinkron' : 'Reject penuh — tidak ada HPP per satuan' }}
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-muted-foreground">Belum ada batch selesai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $batches->links() }}</div>
    </x-ui.card>
</div>