@use('App\Support\Format')

<div class="flex flex-col gap-6">
    {{-- Judul & aksi --}}
    <div>
        <a href="{{ route('po') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">← Semua PO</a>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="font-mono text-2xl font-semibold tracking-tight">{{ $po->nomor_po }}</h1>
                    <x-ui.badge :variant="$po->status->badge()">{{ $po->status->label() }}</x-ui.badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ $po->supplier->nama_supplier }} · {{ $po->tanggal_po->format('d/m/Y') }} ·
                    {{ $po->metode_pembayaran->label() }} · dibuat oleh {{ $po->creator->name }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($po->bisaDiedit())
                    <x-ui.button variant="outline" href="{{ route('po.edit', $po) }}" wire:navigate>Edit</x-ui.button>
                    <x-ui.button wire:click="pesan"
                                 wire:confirm="Konfirmasi pesanan ke supplier? PO tidak dapat diubah lagi setelah ini.">Pesan ke Supplier</x-ui.button>
                @endif
                @if ($po->bisaDiterima())
                    <x-ui.button wire:click="bukaPenerimaan">Catat Penerimaan Barang</x-ui.button>
                @endif
                @if ($po->bisaDibatalkan())
                    <x-ui.button variant="destructive" wire:click="batalkan" wire:confirm="Batalkan PO ini?">Batalkan</x-ui.button>
                @endif
            </div>
        </div>
    </div>

    {{-- Item PO --}}
    <x-ui.card title="Item Pesanan">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Bahan Baku</th>
                        <th class="py-2 pr-4 text-right font-medium">Dipesan</th>
                        <th class="py-2 pr-4 text-right font-medium">Diterima</th>
                        <th class="py-2 pr-4 text-right font-medium">Sisa</th>
                        <th class="py-2 pr-4 text-right font-medium">Harga</th>
                        <th class="py-2 text-right font-medium">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($po->items as $item)
                        @php $satuan = $item->rawMaterial->satuan->value; @endphp
                        <tr wire:key="item-{{ $item->id }}">
                            <td class="py-2 pr-4">
                                <span class="font-mono text-xs text-muted-foreground">{{ $item->rawMaterial->kode_bahan }}</span>
                                {{ $item->rawMaterial->nama_bahan }}
                            </td>
                            <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap">{{ Format::angka($item->jumlah_dipesan) }} {{ $satuan }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap">{{ Format::angka($item->jumlah_diterima) }} {{ $satuan }}</td>
                            <td @class(['py-2 pr-4 text-right tabular-nums whitespace-nowrap', 'font-medium' => $item->sisa() > 0])>
                                {{ Format::angka($item->sisa()) }} {{ $satuan }}
                            </td>
                            <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap">{{ Format::rupiah($item->harga_per_satuan) }}</td>
                            <td class="py-2 text-right tabular-nums whitespace-nowrap">{{ Format::rupiah($item->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t">
                        <td colspan="5" class="py-3 pr-4 text-right font-medium">Total</td>
                        <td class="py-3 text-right font-semibold tabular-nums">{{ Format::rupiah($po->total_nilai_po) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-ui.card>

    {{-- Riwayat penerimaan --}}
    @if ($po->goodsReceipts->isNotEmpty())
        <x-ui.card title="Riwayat Penerimaan Barang">
            <div class="flex flex-col divide-y">
                @foreach ($po->goodsReceipts as $gr)
                    <div wire:key="gr-{{ $gr->id }}" class="py-3 first:pt-0">
                        <p class="text-sm font-medium">
                            {{ $gr->tanggal_terima->format('d/m/Y') }}
                            <span class="font-normal text-muted-foreground">· dicatat oleh {{ $gr->receiver->name }}</span>
                        </p>
                        @if ($gr->catatan)
                            <p class="text-sm text-muted-foreground">Catatan: {{ $gr->catatan }}</p>
                        @endif
                        <ul class="mt-1 grid gap-0.5 text-sm">
                            @foreach ($gr->items as $gi)
                                <li class="flex justify-between gap-4">
                                    <span>{{ $gi->poItem->rawMaterial->nama_bahan }}</span>
                                    <span class="tabular-nums">+{{ Format::angka($gi->jumlah_diterima) }} {{ $gi->poItem->rawMaterial->satuan->value }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    {{-- Form penerimaan barang --}}
    <x-ui.dialog title="Catat Penerimaan Barang"
                 description="Isi jumlah yang benar-benar diterima. Jika supplier baru mengirim sebagian, ubah angkanya — sisanya bisa dicatat di penerimaan berikutnya.">
        <form wire:submit="simpanPenerimaan" class="flex flex-col gap-4">
            <div class="grid gap-2">
                <x-ui.label for="tanggal_terima">Tanggal Terima</x-ui.label>
                <x-ui.input id="tanggal_terima" type="date" wire:model="tanggal_terima" />
                <x-ui.error for="tanggal_terima" />
            </div>

            <div class="flex flex-col gap-3">
                @foreach ($po->items->filter(fn ($i) => $i->sisa() > 0) as $item)
                    <div wire:key="terima-{{ $item->id }}" class="grid gap-1">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $item->rawMaterial->nama_bahan }}</p>
                                <p class="text-xs text-muted-foreground">Sisa {{ Format::angka($item->sisa()) }} {{ $item->rawMaterial->satuan->value }}</p>
                            </div>
                            <div class="flex w-40 items-center gap-1">
                                <x-ui.input type="number" step="0.001" min="0" max="{{ $item->sisa() }}" wire:model="terima.{{ $item->id }}" />
                                <span class="w-9 shrink-0 text-xs text-muted-foreground">{{ $item->rawMaterial->satuan->value }}</span>
                            </div>
                        </div>
                        <x-ui.error for="terima.{{ $item->id }}" />
                    </div>
                @endforeach
                <x-ui.error for="terima" />
            </div>

            <div class="grid gap-2">
                <x-ui.label for="catatan">Catatan (opsional)</x-ui.label>
                <x-ui.textarea id="catatan" wire:model="catatan" rows="2" placeholder="mis. Kondisi kemasan baik, 1 karung sedikit sobek" />
                <x-ui.error for="catatan" />
            </div>

            <div class="mt-2 flex justify-end gap-2">
                <x-ui.button variant="outline" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="simpanPenerimaan">Simpan Penerimaan</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>
</div>