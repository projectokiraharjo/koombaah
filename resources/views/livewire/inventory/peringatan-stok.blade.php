@use('App\Support\Format')

<div wire:poll.60s class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Peringatan Stok</h1>
        <p class="text-sm text-muted-foreground">Item aktif dengan stok di bawah stok minimum, diurutkan dari yang paling kritis. Diperbarui otomatis tiap 60 detik.</p>
    </div>

    @if ($bahanBaku->isEmpty() && $produkJadi->isEmpty())
        <x-ui.card title="Semua stok aman ✓" description="Tidak ada bahan baku maupun produk jadi yang berada di bawah stok minimum." />
    @endif

    @foreach ([
        ['judul' => 'Bahan Baku', 'items' => $bahanBaku, 'jenis' => 'raw_material', 'route' => 'stok-bahan-baku',
         'saran' => 'lakukan pembelian ke supplier (Modul Purchasing).'],
        ['judul' => 'Produk Jadi', 'items' => $produkJadi, 'jenis' => 'finished_product', 'route' => 'stok-produk-jadi',
         'saran' => 'jadwalkan batch produksi (Modul Produksi).'],
    ] as $grup)
        @continue($grup['items']->isEmpty())

        <x-ui.card :title="$grup['judul'] . ' Menipis (' . $grup['items']->count() . ')'"
                   :description="'Saran tindakan: ' . $grup['saran']">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b text-left text-muted-foreground">
                        <tr>
                            <th class="py-2 pr-4 font-medium">Item</th>
                            <th class="py-2 pr-4 text-right font-medium">Stok / Minimum</th>
                            <th class="py-2 pr-4 font-medium">Level</th>
                            <th class="py-2 pr-4 text-right font-medium">Kekurangan</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($grup['items'] as $item)
                            @php
                                $isBahan = $grup['jenis'] === 'raw_material';
                                $kode = $isBahan ? $item->kode_bahan : $item->kode_produk;
                                $nama = $isBahan ? $item->nama_bahan : $item->nama_produk;
                                $satuan = $isBahan ? $item->satuan->value : $item->satuan_jual->value;
                                $persen = min(100, (float) $item->stok_saat_ini / (float) $item->stok_minimum * 100);
                                $kurang = (float) $item->stok_minimum - (float) $item->stok_saat_ini;
                            @endphp
                            <tr wire:key="{{ $grup['jenis'] }}-{{ $item->id }}">
                                <td class="py-3 pr-4">
                                    <span class="font-mono text-xs text-muted-foreground">{{ $kode }}</span>
                                    <span class="font-medium">{{ $nama }}</span>
                                </td>
                                <td class="py-3 pr-4 text-right tabular-nums whitespace-nowrap">
                                    {{ Format::angka($item->stok_saat_ini) }} / {{ Format::angka($item->stok_minimum) }} {{ $satuan }}
                                </td>
                                <td class="py-3 pr-4">
                                    <div class="h-2 w-24 overflow-hidden rounded-full bg-muted">
                                        <div class="h-full rounded-full bg-destructive" style="width: {{ $persen }}%"></div>
                                    </div>
                                </td>
                                <td class="py-3 pr-4 text-right font-medium text-destructive tabular-nums whitespace-nowrap">
                                    {{ Format::angka($kurang) }} {{ $satuan }}
                                </td>
                                <td class="py-3">
                                    <div class="flex justify-end gap-2">
                                        <x-ui.button variant="outline" size="sm" wire:navigate
                                                     href="{{ route($grup['route'], ['q' => $kode]) }}">Catat Stok</x-ui.button>
                                        <x-ui.button variant="ghost" size="sm" wire:navigate
                                                     href="{{ route('riwayat-stok', ['jenis' => $grup['jenis'], 'item' => $item->id]) }}">Riwayat</x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endforeach
</div>