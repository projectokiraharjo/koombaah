@use('App\Support\Format')
@use('Carbon\Carbon')

<div class="flex flex-col gap-6">
    <div>
        <a href="{{ route('mitra') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">← Semua mitra</a>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight">{{ $partner->nama_usaha }}</h1>
                    <x-ui.badge variant="outline">{{ $partner->wilayah_ring->label() }}</x-ui.badge>
                    @if ($partner->status->value === 'nonaktif')
                        <x-ui.badge variant="outline">Nonaktif</x-ui.badge>
                    @endif
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ $partner->kode_mitra }} · {{ $partner->nama_pemilik ?: 'PIC belum diisi' }}
                    @if ($partner->no_telepon) · {{ $partner->no_telepon }} @endif
                </p>
                <p class="text-sm text-muted-foreground">{{ $partner->alamat }} ({{ $partner->wilayah_ring->area() }})</p>
            </div>
            @can('distribusi')
                <x-ui.button variant="outline" href="{{ route('riwayat-pengiriman', ['mitra' => $partner->id]) }}" wire:navigate>Riwayat Pengiriman</x-ui.button>
            @endcan
            @if ($partner->status->value === 'aktif')
                <x-ui.button href="{{ route('order.buat') }}" wire:navigate>+ Order untuk Mitra Ini</x-ui.button>
            @endif
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Order terkonfirmasi</p>
            <p class="text-2xl font-semibold tabular-nums">{{ $ringkasan->jumlah }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Total nilai pembelian</p>
            <p class="text-2xl font-semibold tabular-nums">{{ Format::rupiah($ringkasan->total) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Order terakhir</p>
            <p class="text-2xl font-semibold">{{ $ringkasan->terakhir ? Carbon::parse($ringkasan->terakhir)->format('d/m/Y') : '–' }}</p>
        </x-ui.card>
    </div>

    @if ($partner->catatan)
        <x-ui.card title="Catatan Mitra">
            <p class="whitespace-pre-line text-sm text-muted-foreground">{{ $partner->catatan }}</p>
        </x-ui.card>
    @endif

    {{-- Produk favorit --}}
    @if ($produkFavorit->isNotEmpty())
        <x-ui.card title="Produk Paling Sering Dipesan" description="Dari order yang sudah dikonfirmasi (tidak termasuk yang dibatalkan).">
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Produk</th>
                        <th class="py-2 pr-4 text-right font-medium">Frekuensi</th>
                        <th class="py-2 pr-4 text-right font-medium">Total Jumlah</th>
                        <th class="py-2 text-right font-medium">Total Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($produkFavorit as $p)
                        <tr>
                            <td class="py-2 pr-4 font-medium">{{ $p->nama_produk }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ $p->frekuensi }}×</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ Format::angka($p->total_jumlah) }} {{ $p->satuan_jual }}</td>
                            <td class="py-2 text-right tabular-nums">{{ Format::rupiah($p->total_nilai) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-ui.card>
    @endif

    {{-- Riwayat order --}}
    <x-ui.card title="Riwayat Order" description="Seluruh order mitra ini, termasuk yang dibatalkan.">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Nomor</th>
                        <th class="py-2 pr-4 font-medium">Tanggal</th>
                        <th class="py-2 pr-4 text-right font-medium">Item</th>
                        <th class="py-2 pr-4 text-right font-medium">Total</th>
                        <th class="py-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($orders as $o)
                        <tr wire:key="mo-{{ $o->id }}">
                            <td class="py-2 pr-4">
                                <a href="{{ route('order.detail', $o) }}" wire:navigate class="font-mono text-xs hover:underline">{{ $o->nomor_order }}</a>
                            </td>
                            <td class="py-2 pr-4">{{ $o->tanggal_order->format('d/m/Y') }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ $o->items_count }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($o->total_nilai_order) }}</td>
                            <td class="py-2"><x-ui.badge :variant="$o->status->badge()">{{ $o->status->label() }}</x-ui.badge></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-muted-foreground">Mitra ini belum pernah memesan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>
    </x-ui.card>
</div>