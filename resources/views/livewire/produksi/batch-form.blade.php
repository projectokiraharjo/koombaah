@use('App\Support\Format')

<div class="flex flex-col gap-6">
    <div>
        <a href="{{ route('produksi') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">← Riwayat batch</a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">Batch Produksi Baru</h1>
        <p class="text-sm text-muted-foreground">Kebutuhan bahan dihitung otomatis dari resep (BOM) aktif × jumlah target.</p>
    </div>

    <x-ui.card title="Rencana Produksi">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <x-ui.label for="produkId">Produk</x-ui.label>
                <x-ui.select id="produkId" wire:model.live="produkId">
                    <option value="">Pilih produk</option>
                    @foreach ($produkList as $p)
                        <option value="{{ $p->id }}">{{ $p->kode_produk }} — {{ $p->nama_produk }} (resep v{{ $p->activeBom->versi }})</option>
                    @endforeach
                </x-ui.select>
                <x-ui.error for="produkId" />
            </div>
            <div class="grid gap-2">
                <x-ui.label for="jumlah_target">Jumlah Target</x-ui.label>
                <div class="flex items-center gap-2">
                    <x-ui.input id="jumlah_target" type="number" step="0.001" min="0"
                                wire:model.live.debounce.400ms="jumlah_target" placeholder="mis. 100" />
                    <span class="w-10 shrink-0 text-sm text-muted-foreground">{{ $produk?->satuan_jual->value }}</span>
                </div>
            </div>
            <div class="grid gap-2">
                <x-ui.label for="tanggal_mulai">Tanggal Mulai</x-ui.label>
                <x-ui.input id="tanggal_mulai" type="date" wire:model="tanggal_mulai" />
                <x-ui.error for="tanggal_mulai" />
            </div>
        </div>
        <x-ui.error for="jumlah_target" class="mt-3" />
        @if ($tanpaResep > 0)
            <p class="mt-3 text-xs text-muted-foreground">
                {{ $tanpaResep }} produk aktif tidak muncul karena belum punya resep — buat dulu di menu
                <a href="{{ route('bom') }}" wire:navigate class="underline">Resep (BOM)</a>.
            </p>
        @endif
    </x-ui.card>

    @if ($produk)
        <x-ui.card title="Kebutuhan Bahan Baku"
                   :description="'Resep ' . $produk->nama_produk . ' versi ' . $produk->activeBom->versi . ($kebutuhan->isNotEmpty() ? ' × ' . Format::angka($jumlah_target) . ' ' . $produk->satuan_jual->value : '')">
            @if ($kebutuhan->isEmpty())
                <p class="text-sm text-muted-foreground">Isi jumlah target untuk menghitung kebutuhan bahan.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b text-left text-muted-foreground">
                            <tr>
                                <th class="py-2 pr-4 font-medium">Bahan Baku</th>
                                <th class="py-2 pr-4 text-right font-medium">Takaran / 1 {{ $produk->satuan_jual->value }}</th>
                                <th class="py-2 pr-4 text-right font-medium">Kebutuhan</th>
                                <th class="py-2 pr-4 text-right font-medium">Stok Tersedia</th>
                                <th class="py-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($kebutuhan as $k)
                                @php $sat = $k['bahan']->satuan->value; @endphp
                                <tr wire:key="kbt-{{ $k['bahan']->id }}">
                                    <td class="py-2 pr-4">{{ $k['bahan']->nama_bahan }}</td>
                                    <td class="py-2 pr-4 text-right tabular-nums text-muted-foreground">{{ Format::angka($k['takaran']) }} {{ $sat }}</td>
                                    <td class="py-2 pr-4 text-right font-medium tabular-nums">{{ Format::angka($k['kebutuhan']) }} {{ $sat }}</td>
                                    <td class="py-2 pr-4 text-right tabular-nums">{{ Format::angka($k['stok']) }} {{ $sat }}</td>
                                    <td class="py-2">
                                        @if ($k['cukup'])
                                            <x-ui.badge variant="secondary">Cukup</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="destructive">Kurang {{ Format::angka($k['kurang']) }} {{ $sat }}</x-ui.badge>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($adaKurang)
                    <div class="mt-4 rounded-lg border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                        <p class="font-medium">Produksi diblokir: stok bahan baku tidak cukup.</p>
                        <p>Tambah stok lewat pembelian ke supplier, atau kurangi jumlah target.</p>
                    </div>
                    @can('purchasing')
                        <div class="mt-3">
                            <x-ui.button variant="outline" size="sm" href="{{ route('po.buat') }}" wire:navigate>Buat Purchase Order</x-ui.button>
                        </div>
                    @endcan
                @endif
            @endif
        </x-ui.card>
    @endif

    <div class="flex justify-end">
        <x-ui.button wire:click="mulai" wire:loading.attr="disabled"
                     :disabled="! $produk || $kebutuhan->isEmpty() || $adaKurang"
                     wire:confirm="Mulai produksi? Stok bahan baku langsung dikurangi dan tidak dikembalikan meskipun nanti ada reject.">
            Mulai Produksi
        </x-ui.button>
    </div>
</div>