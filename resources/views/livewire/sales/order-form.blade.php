@use('App\Support\Format')

<div class="flex flex-col gap-6">
    <div>
        <a href="{{ $order ? route('order.detail', $order) : route('order') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">← Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ $order ? 'Edit ' . $order->nomor_order : 'Order Baru' }}</h1>
        <p class="text-sm text-muted-foreground">Pembayaran: <span class="font-medium text-foreground">Tunai</span>. Harga diambil otomatis dari harga jual produk.</p>
    </div>

    @if ($mitraList->isEmpty())
        <x-ui.card title="Belum ada mitra" description="Daftarkan mitra terlebih dahulu sebelum mencatat order.">
            <x-ui.button href="{{ route('mitra') }}" wire:navigate>Ke halaman Mitra</x-ui.button>
        </x-ui.card>
    @else
        <form wire:submit="simpan('baru')" class="flex flex-col gap-6">
            <x-ui.card title="Informasi Order">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <x-ui.label for="partner_id">Mitra</x-ui.label>
                        <x-ui.select id="partner_id" wire:model="partner_id">
                            <option value="">Pilih mitra</option>
                            @foreach ($mitraList as $m)
                                <option value="{{ $m->id }}">{{ $m->nama_usaha }} ({{ $m->wilayah_ring->label() }})</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.error for="partner_id" />
                    </div>
                    <div class="grid gap-2">
                        <x-ui.label for="tanggal_order">Tanggal Order</x-ui.label>
                        <x-ui.input id="tanggal_order" type="date" wire:model="tanggal_order" />
                        <x-ui.error for="tanggal_order" />
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Produk Dipesan" description="Stok hanya ditampilkan sebagai info — validasi blokir dilakukan saat order dikonfirmasi.">
                <div class="flex flex-col gap-3">
                    <div class="hidden gap-2 text-xs font-medium text-muted-foreground sm:grid sm:grid-cols-[1fr_9rem_8rem_8rem_2.25rem]">
                        <span>Produk</span><span>Jumlah</span><span class="text-right">Harga</span><span class="text-right">Subtotal</span><span></span>
                    </div>

                    @foreach ($rows as $i => $row)
                        @php $b = $baris[$i]; $sat = $b['produk']?->satuan_jual->value; @endphp
                        <div wire:key="so-baris-{{ $i }}"
                             class="grid gap-2 rounded-lg border p-3 sm:grid-cols-[1fr_9rem_8rem_8rem_2.25rem] sm:items-start sm:border-0 sm:p-0">
                            <div class="grid gap-1">
                                <x-ui.select wire:model.live="rows.{{ $i }}.finished_product_id">
                                    <option value="">Pilih produk</option>
                                    @foreach ($produkList as $p)
                                        <option value="{{ $p->id }}">{{ $p->kode_produk }} — {{ $p->nama_produk }}</option>
                                    @endforeach
                                </x-ui.select>
                                @if ($b['produk'])
                                    <p @class(['text-xs', 'text-destructive' => $b['stokKurang'], 'text-muted-foreground' => ! $b['stokKurang']])>
                                        Stok tersedia {{ Format::angka($b['produk']->stok_saat_ini) }} {{ $sat }}{{ $b['stokKurang'] ? ' — tidak cukup' : '' }}
                                    </p>
                                @endif
                                <x-ui.error for="rows.{{ $i }}.finished_product_id" />
                            </div>
                            <div class="grid gap-1">
                                <div class="flex items-center gap-1">
                                    <x-ui.input type="number" step="0.001" min="0" placeholder="Jumlah"
                                                wire:model.live.debounce.400ms="rows.{{ $i }}.jumlah_dipesan" />
                                    <span class="w-9 shrink-0 text-xs text-muted-foreground">{{ $sat }}</span>
                                </div>
                                <x-ui.error for="rows.{{ $i }}.jumlah_dipesan" />
                            </div>
                            <div class="flex h-9 items-center justify-end text-sm tabular-nums text-muted-foreground">{{ Format::rupiah($b['harga']) }}</div>
                            <div class="flex h-9 items-center justify-end text-sm font-medium tabular-nums">{{ Format::rupiah($b['subtotal']) }}</div>
                            <x-ui.button variant="ghost" size="icon" wire:click="hapusBaris({{ $i }})"
                                         :disabled="count($rows) === 1" aria-label="Hapus baris">✕</x-ui.button>
                        </div>
                    @endforeach

                    <x-ui.error for="rows" />
                    <div>
                        <x-ui.button variant="outline" size="sm" wire:click="tambahBaris">+ Tambah Produk</x-ui.button>
                    </div>
                </div>
                <x-slot:footer>
                    <div class="ml-auto text-right">
                        <p class="text-sm text-muted-foreground">Total nilai order</p>
                        <p class="text-xl font-semibold tabular-nums">{{ Format::rupiah($total) }}</p>
                    </div>
                </x-slot:footer>
            </x-ui.card>

            <x-ui.card title="Catatan (opsional)">
                <x-ui.textarea wire:model="catatan" rows="2" placeholder="mis. Kirim sebelum jam 10 pagi" />
                <x-ui.error for="catatan" />
            </x-ui.card>

            <div class="flex flex-wrap justify-end gap-2">
                <x-ui.button type="submit" variant="outline" wire:loading.attr="disabled">Simpan (status Baru)</x-ui.button>
                <x-ui.button wire:click="simpan('konfirmasi')" wire:loading.attr="disabled"
                             wire:confirm="Simpan dan konfirmasi order? Stok produk jadi akan langsung dipotong.">Simpan & Konfirmasi</x-ui.button>
            </div>
        </form>
    @endif
</div>