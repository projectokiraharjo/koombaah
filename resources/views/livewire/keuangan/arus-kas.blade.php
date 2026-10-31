@use('App\Support\Format')

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Arus Kas</h1>
            <p class="text-sm text-muted-foreground">Kas masuk vs kas keluar — {{ $labelBulan }}.</p>
        </div>
        <x-ui.input type="month" wire:model.live="bulan" class="w-44" />
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Kas Masuk</p>
            <p class="text-xl font-semibold tabular-nums">{{ Format::rupiah($ak['total_masuk']) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Kas Keluar</p>
            <p class="text-xl font-semibold tabular-nums">{{ Format::rupiah($ak['total_keluar']) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Arus Kas Bersih</p>
            <p @class(['text-xl font-semibold tabular-nums', 'text-destructive' => $ak['bersih'] < 0])>{{ Format::rupiah($ak['bersih']) }}</p>
        </x-ui.card>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ([['Kas Masuk per Kategori', $ak['masuk']], ['Kas Keluar per Kategori', $ak['keluar']]] as [$judul, $baris])
            <x-ui.card :title="$judul">
                <table class="w-full text-sm">
                    <tbody class="divide-y">
                        @forelse ($baris as $label => $nominal)
                            <tr>
                                <td class="py-2">{{ $label }}</td>
                                <td class="py-2 text-right tabular-nums">{{ Format::rupiah($nominal) }}</td>
                            </tr>
                        @empty
                            <tr><td class="py-2 text-muted-foreground">Tidak ada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.card>
        @endforeach
    </div>

    <x-ui.card title="Transaksi Kas Otomatis" description="Tercatat otomatis dari Sales, Purchasing, dan Distribusi.">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Tanggal</th>
                        <th class="py-2 pr-4 font-medium">Kategori</th>
                        <th class="py-2 pr-4 font-medium">Keterangan</th>
                        <th class="py-2 pr-4 text-right font-medium">Masuk</th>
                        <th class="py-2 text-right font-medium">Keluar</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($ak['transaksi'] as $t)
                        <tr wire:key="ct-{{ $t->id }}">
                            <td class="py-2 pr-4 whitespace-nowrap">{{ $t->tanggal_transaksi->format('d/m/Y') }}</td>
                            <td class="py-2 pr-4"><x-ui.badge variant="outline">{{ $t->kategori->label() }}</x-ui.badge></td>
                            <td class="py-2 pr-4 text-muted-foreground">{{ $t->catatan }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ $t->tipe->value === 'masuk' ? Format::rupiah($t->nominal) : '' }}</td>
                            <td class="py-2 text-right tabular-nums">{{ $t->tipe->value === 'keluar' ? Format::rupiah($t->nominal) : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-muted-foreground">Belum ada transaksi kas di bulan ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    @if ($ak['biaya_manual']->isNotEmpty())
        <x-ui.card title="Biaya Operasional Manual" description="Dicatat per bulan di menu Biaya Operasional, dihitung sebagai kas keluar bulan ini.">
            <table class="w-full text-sm">
                <tbody class="divide-y">
                    @foreach ($ak['biaya_manual'] as $b)
                        <tr>
                            <td class="py-2 pr-4">{{ $b->kategori->label() }}</td>
                            <td class="py-2 pr-4 text-muted-foreground">{{ $b->catatan }}</td>
                            <td class="py-2 text-right tabular-nums">{{ Format::rupiah($b->nominal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-ui.card>
    @endif
</div>