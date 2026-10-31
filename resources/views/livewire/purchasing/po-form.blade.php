@use('App\Support\Format')

@php
    // Bahan dari supplier utama yang dipilih ditampilkan paling atas (PRD B.2.7)
    $namaSupplier = $suppliers->firstWhere('id', (int) $supplier_id)?->nama_supplier;
    $bahanUtama = $supplier_id ? $bahanList->where('supplier_utama_id', (int) $supplier_id) : collect();
    $bahanLain = $bahanList->diff($bahanUtama);
@endphp

<div class="flex flex-col gap-6">
    <div>
        <a href="{{ $po ? route('po.detail', $po) : route('po') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">← Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ $po ? 'Edit ' . $po->nomor_po : 'Buat Purchase Order' }}</h1>
        <p class="text-sm text-muted-foreground">Metode pembayaran: <span class="font-medium text-foreground">Tunai</span> (kebijakan pembelian saat ini).</p>
    </div>

    <form wire:submit="simpan('draft')" class="flex flex-col gap-6">
        <x-ui.card title="Informasi PO">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <x-ui.label for="supplier_id">Supplier</x-ui.label>
                    <x-ui.select id="supplier_id" wire:model.live="supplier_id">
                        <option value="">Pilih supplier</option>
                        @foreach ($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->nama_supplier }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.error for="supplier_id" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="tanggal_po">Tanggal PO</x-ui.label>
                    <x-ui.input id="tanggal_po" type="date" wire:model="tanggal_po" />
                    <x-ui.error for="tanggal_po" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Daftar Bahan" description="Harga terisi otomatis dari harga beli terakhir — ubah sesuai kesepakatan dengan supplier.">
            <div class="flex flex-col gap-3">
                <div class="hidden gap-2 text-xs font-medium text-muted-foreground sm:grid sm:grid-cols-[1fr_8rem_9rem_8rem_2.25rem]">
                    <span>Bahan baku</span><span>Jumlah</span><span>Harga / satuan</span><span class="text-right">Subtotal</span><span></span>
                </div>

                @foreach ($rows as $i => $row)
                    @php
                        $satuan = $bahanList->firstWhere('id', (int) $row['raw_material_id'])?->satuan->value;
                        $subtotal = is_numeric($row['jumlah_dipesan']) && is_numeric($row['harga_per_satuan'])
                            ? $row['jumlah_dipesan'] * $row['harga_per_satuan'] : 0;
                    @endphp
                    <div wire:key="po-baris-{{ $i }}"
                         class="grid gap-2 rounded-lg border p-3 sm:grid-cols-[1fr_8rem_9rem_8rem_2.25rem] sm:items-start sm:border-0 sm:p-0">
                        <div class="grid gap-1">
                            <x-ui.select wire:model.live="rows.{{ $i }}.raw_material_id">
                                <option value="">Pilih bahan baku</option>
                                @if ($bahanUtama->isNotEmpty())
                                    <optgroup label="Supplier utama: {{ $namaSupplier }}">
                                        @foreach ($bahanUtama as $b)
                                            <option value="{{ $b->id }}">{{ $b->kode_bahan }} — {{ $b->nama_bahan }}</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Bahan lainnya">
                                        @foreach ($bahanLain as $b)
                                            <option value="{{ $b->id }}">{{ $b->kode_bahan }} — {{ $b->nama_bahan }}</option>
                                        @endforeach
                                    </optgroup>
                                @else
                                    @foreach ($bahanList as $b)
                                        <option value="{{ $b->id }}">{{ $b->kode_bahan }} — {{ $b->nama_bahan }}</option>
                                    @endforeach
                                @endif
                            </x-ui.select>
                            <x-ui.error for="rows.{{ $i }}.raw_material_id" />
                        </div>
                        <div class="grid gap-1">
                            <div class="flex items-center gap-1">
                                <x-ui.input type="number" step="0.001" min="0" placeholder="Jumlah"
                                            wire:model.live.debounce.400ms="rows.{{ $i }}.jumlah_dipesan" />
                                <span class="w-9 shrink-0 text-xs text-muted-foreground">{{ $satuan }}</span>
                            </div>
                            <x-ui.error for="rows.{{ $i }}.jumlah_dipesan" />
                        </div>
                        <div class="grid gap-1">
                            <x-ui.input type="number" step="0.01" min="0" placeholder="Harga"
                                        wire:model.live.debounce.400ms="rows.{{ $i }}.harga_per_satuan" />
                            <x-ui.error for="rows.{{ $i }}.harga_per_satuan" />
                        </div>
                        <div class="flex h-9 items-center justify-end text-sm font-medium tabular-nums">{{ Format::rupiah($subtotal) }}</div>
                        <x-ui.button variant="ghost" size="icon" wire:click="hapusBaris({{ $i }})"
                                     :disabled="count($rows) === 1" aria-label="Hapus baris">✕</x-ui.button>
                    </div>
                @endforeach

                <x-ui.error for="rows" />
                <div>
                    <x-ui.button variant="outline" size="sm" wire:click="tambahBaris">+ Tambah Bahan</x-ui.button>
                </div>
            </div>
            <x-slot:footer>
                <div class="ml-auto text-right">
                    <p class="text-sm text-muted-foreground">Total nilai PO</p>
                    <p class="text-xl font-semibold tabular-nums">{{ Format::rupiah($total) }}</p>
                </div>
            </x-slot:footer>
        </x-ui.card>

        <div class="flex flex-wrap justify-end gap-2">
            <x-ui.button type="submit" variant="outline" wire:loading.attr="disabled">Simpan sebagai Draft</x-ui.button>
            <x-ui.button wire:click="simpan('pesan')" wire:loading.attr="disabled"
                         wire:confirm="Setelah dipesan, PO tidak dapat diubah lagi. Lanjutkan?">Simpan & Pesan</x-ui.button>
        </div>
    </form>
</div>