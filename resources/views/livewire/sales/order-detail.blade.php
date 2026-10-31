@use('App\Support\Format')
@use('App\Enums\StatusOrder')

@php
    $alur = StatusOrder::alur();
    $posisi = array_search($order->status, $alur, true); // false jika dibatalkan
    $berikut = $order->status->berikutnyaManual();
@endphp

<div class="flex flex-col gap-6">
    {{-- Judul & aksi --}}
    <div>
        <a href="{{ route('order') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">← Semua order</a>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="font-mono text-2xl font-semibold tracking-tight">{{ $order->nomor_order }}</h1>
                    <x-ui.badge :variant="$order->status->badge()">{{ $order->status->label() }}</x-ui.badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    <a href="{{ route('mitra.detail', $order->partner) }}" wire:navigate class="hover:underline">{{ $order->partner->nama_usaha }}</a>
                    · {{ $order->partner->wilayah_ring->label() }} · {{ $order->tanggal_order->format('d/m/Y') }}
                    · {{ $order->metode_pembayaran->label() }} · dicatat oleh {{ $order->recorder->name }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($order->bisaDiedit())
                    <x-ui.button variant="outline" href="{{ route('order.edit', $order) }}" wire:navigate>Edit</x-ui.button>
                    <x-ui.button wire:click="konfirmasi" wire:confirm="Konfirmasi order? Stok produk jadi akan langsung dipotong.">Konfirmasi Order</x-ui.button>
                @endif
                @if ($berikut)
                    <x-ui.button wire:click="lanjutkan">Tandai {{ $berikut->label() }}</x-ui.button>
                @endif
                @if ($order->bisaDibatalkan())
                    <x-ui.button variant="destructive" wire:click="bukaBatal">Batalkan</x-ui.button>
                @endif
            </div>
        </div>
    </div>

    {{-- Stepper status --}}
    @if ($posisi !== false)
        <div class="flex flex-wrap items-center gap-2 text-xs">
            @foreach ($alur as $i => $s)
                <span @class([
                    'rounded-full border px-3 py-1',
                    'border-primary bg-primary text-primary-foreground' => $i === $posisi,
                    'bg-muted text-foreground' => $i < $posisi,
                    'text-muted-foreground' => $i > $posisi,
                ])>{{ $s->label() }}</span>
                @if (! $loop->last) <span class="text-muted-foreground">→</span> @endif
            @endforeach
        </div>
    @endif

    @if ($order->status === StatusOrder::SiapKirim)
        <div class="rounded-lg border bg-muted/40 px-4 py-3 text-sm">
            Order siap dikirim. Status <span class="font-medium">Terkirim</span> dan <span class="font-medium">Selesai</span>
            akan diperbarui otomatis dari <span class="font-medium">Modul Distribusi</span>.
        </div>
    @endif

    {{-- Pesan blokir --}}
    @if ($pesanBlokir)
        <div class="rounded-lg border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive">
            <p class="font-medium">Konfirmasi diblokir.</p>
            <p>{{ $pesanBlokir }}</p>
            @can('produksi')
                <a href="{{ route('produksi.buat') }}" wire:navigate class="mt-1 inline-block font-medium underline">Buat batch produksi →</a>
            @endcan
        </div>
    @endif

    {{-- Item --}}
    <x-ui.card title="Produk Dipesan">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Produk</th>
                        <th class="py-2 pr-4 text-right font-medium">Jumlah</th>
                        @if ($order->bisaDiedit())
                            <th class="py-2 pr-4 text-right font-medium">Stok Saat Ini</th>
                        @endif
                        <th class="py-2 pr-4 text-right font-medium">Harga (snapshot)</th>
                        <th class="py-2 text-right font-medium">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($order->items as $item)
                        @php
                            $sat = $item->product->satuan_jual->value;
                            $kurang = (float) $item->jumlah_dipesan > (float) $item->product->stok_saat_ini;
                        @endphp
                        <tr wire:key="soi-{{ $item->id }}">
                            <td class="py-2 pr-4">
                                <span class="font-mono text-xs text-muted-foreground">{{ $item->product->kode_produk }}</span>
                                {{ $item->product->nama_produk }}
                            </td>
                            <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap">{{ Format::angka($item->jumlah_dipesan) }} {{ $sat }}</td>
                            @if ($order->bisaDiedit())
                                <td @class(['py-2 pr-4 text-right tabular-nums whitespace-nowrap', 'font-medium text-destructive' => $kurang])>
                                    {{ Format::angka($item->product->stok_saat_ini) }} {{ $sat }}
                                </td>
                            @endif
                            <td class="py-2 pr-4 text-right tabular-nums whitespace-nowrap">{{ Format::rupiah($item->harga_per_satuan) }}</td>
                            <td class="py-2 text-right tabular-nums whitespace-nowrap">{{ Format::rupiah($item->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t">
                        <td colspan="{{ $order->bisaDiedit() ? 4 : 3 }}" class="py-3 pr-4 text-right font-medium">Total</td>
                        <td class="py-3 text-right font-semibold tabular-nums">{{ Format::rupiah($order->total_nilai_order) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-ui.card>

    @if ($order->catatan)
        <x-ui.card title="Catatan">
            <p class="whitespace-pre-line text-sm text-muted-foreground">{{ $order->catatan }}</p>
        </x-ui.card>
    @endif

    {{-- Dialog batal --}}
    <x-ui.dialog model="showBatal" title="Batalkan Order"
                 :description="$order->status->stokSudahDipotong()
                    ? 'Stok produk jadi yang sudah dipotong akan DIKEMBALIKAN otomatis dan tercatat di riwayat stok.'
                    : 'Order belum dikonfirmasi, jadi tidak ada stok yang perlu dikembalikan.'">
        <form wire:submit="simpanBatal" class="flex flex-col gap-4">
            <div class="grid gap-2">
                <x-ui.label for="alasan">Alasan Pembatalan</x-ui.label>
                <x-ui.textarea id="alasan" wire:model="alasan" rows="2" placeholder="mis. Mitra membatalkan pesanan via WhatsApp" />
                <x-ui.error for="alasan" />
            </div>
            <div class="mt-2 flex justify-end gap-2">
                <x-ui.button variant="outline" x-on:click="open = false">Kembali</x-ui.button>
                <x-ui.button type="submit" variant="destructive" wire:loading.attr="disabled" wire:target="simpanBatal">Batalkan Order</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>
</div>