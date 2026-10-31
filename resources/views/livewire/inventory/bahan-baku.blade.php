@use('App\Support\Format')

<div class="flex flex-col gap-6">
    {{-- Judul --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Bahan Baku</h1>
            <p class="text-sm text-muted-foreground">Data master bahan mentah untuk produksi.</p>
        </div>
        <x-ui.button wire:click="create">+ Tambah Bahan Baku</x-ui.button>
    </div>

    {{-- Filter --}}
    <div class="flex flex-col gap-2 sm:flex-row">
        <x-ui.input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari kode atau nama…" class="sm:max-w-xs" />
        <x-ui.select wire:model.live="filterStatus" class="sm:w-44">
            <option value="">Semua status</option>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
        </x-ui.select>
    </div>

    {{-- Tabel --}}
    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Kode</th>
                    <th class="px-4 py-3 font-medium">Nama Bahan</th>
                    <th class="px-4 py-3 font-medium">Satuan</th>
                    <th class="px-4 py-3 text-right font-medium">Harga Terakhir</th>
                    <th class="px-4 py-3 text-right font-medium">Stok</th>
                    <th class="px-4 py-3 text-right font-medium">Stok Min.</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($items as $item)
                    <tr wire:key="bb-{{ $item->id }}" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-mono text-xs">{{ $item->kode_bahan }}</td>
                        <td class="px-4 py-3 font-medium">{{ $item->nama_bahan }}</td>
                        <td class="px-4 py-3">{{ $item->satuan->value }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ Format::rupiah($item->harga_per_satuan_terakhir) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ Format::angka($item->stok_saat_ini) }}
                            @if ($item->isLowStock())
                                <x-ui.badge variant="destructive" class="ml-1">Menipis</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ Format::angka($item->stok_minimum) }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$item->status->value === 'aktif' ? 'secondary' : 'outline'">{{ $item->status->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.button variant="ghost" size="sm" wire:click="edit({{ $item->id }})">Edit</x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-muted-foreground">Belum ada data bahan baku.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $items->links() }}

    {{-- Form tambah/edit --}}
    <x-ui.dialog :title="$editingId ? 'Edit Bahan Baku' : 'Tambah Bahan Baku'"
                 description="Stok tidak diisi di sini — stok berubah lewat pencatatan stok masuk/keluar.">
        <form wire:submit="save" class="flex flex-col gap-4">
            <div class="grid gap-2">
                <x-ui.label>Kode</x-ui.label>
                <x-ui.input :value="$kode_bahan" disabled />
            </div>

            <div class="grid gap-2">
                <x-ui.label for="nama_bahan">Nama Bahan</x-ui.label>
                <x-ui.input id="nama_bahan" wire:model="nama_bahan" placeholder="mis. SLES" />
                <x-ui.error for="nama_bahan" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <x-ui.label for="satuan">Satuan</x-ui.label>
                    <x-ui.select id="satuan" wire:model="satuan" :disabled="$satuanTerkunci">                        <option value="">Pilih satuan</option>
                        @foreach ($satuanList as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.error for="satuan" />
                    @if ($satuanTerkunci)
                        <p class="text-xs text-muted-foreground">Dikunci: bahan ini sudah dipakai di resep atau riwayat stok.</p>
                    @endif
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="harga">Harga per Satuan (Rp)</x-ui.label>
                    <x-ui.input id="harga" type="number" step="0.01" min="0" wire:model="harga_per_satuan_terakhir" />
                    <x-ui.error for="harga_per_satuan_terakhir" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <x-ui.label for="stok_minimum">Stok Minimum</x-ui.label>
                    <x-ui.input id="stok_minimum" type="number" step="0.001" min="0" wire:model="stok_minimum" />
                    <x-ui.error for="stok_minimum" />
                </div>
                            <div class="grid gap-2">
                <x-ui.label for="supplier_utama_id">Supplier Utama (opsional)</x-ui.label>
                <x-ui.select id="supplier_utama_id" wire:model="supplier_utama_id">
                    <option value="">— Belum ditentukan —</option>
                    @foreach ($supplierList as $sup)
                        <option value="{{ $sup->id }}">{{ $sup->nama_supplier }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.error for="supplier_utama_id" />
                <p class="text-xs text-muted-foreground">Hanya rujukan untuk mempercepat pembuatan PO — bahan tetap bisa dibeli dari supplier lain.</p>
            </div>
                <div class="grid gap-2">
                    <x-ui.label for="status">Status</x-ui.label>
                    <x-ui.select id="status" wire:model="status">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </x-ui.select>
                    <x-ui.error for="status" />
                </div>
            </div>

            <div class="mt-2 flex justify-end gap-2">
                <x-ui.button variant="outline" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>
</div>