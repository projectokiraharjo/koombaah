<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Supplier</h1>
            <p class="text-sm text-muted-foreground">Data master pemasok bahan baku.</p>
        </div>
        <x-ui.button wire:click="create">+ Tambah Supplier</x-ui.button>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row">
        <x-ui.input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari kode, nama, atau kontak…" class="sm:max-w-xs" />
        <x-ui.select wire:model.live="filterStatus" class="sm:w-44">
            <option value="">Semua status</option>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
        </x-ui.select>
    </div>

    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Kode</th>
                    <th class="px-4 py-3 font-medium">Nama Supplier</th>
                    <th class="px-4 py-3 font-medium">Kontak</th>
                    <th class="px-4 py-3 text-right font-medium">Bahan Utama</th>
                    <th class="px-4 py-3 text-right font-medium">Jumlah PO</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($items as $s)
                    <tr wire:key="sup-{{ $s->id }}" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-mono text-xs">{{ $s->kode_supplier }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $s->nama_supplier }}</div>
                            @if ($s->alamat)
                                <div class="text-xs text-muted-foreground">{{ $s->alamat }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ $s->kontak_person ?: '–' }}</div>
                            <div class="text-xs text-muted-foreground">{{ $s->no_telepon }}</div>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $s->bahan_utama_count }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $s->purchase_orders_count }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$s->status->value === 'aktif' ? 'secondary' : 'outline'">{{ $s->status->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.button variant="ghost" size="sm" wire:click="edit({{ $s->id }})">Edit</x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-muted-foreground">Belum ada data supplier.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $items->links() }}

    <x-ui.dialog :title="$editingId ? 'Edit Supplier' : 'Tambah Supplier'">
        <form wire:submit="save" class="flex flex-col gap-4">
            <div class="grid gap-2">
                <x-ui.label>Kode</x-ui.label>
                <x-ui.input :value="$kode_supplier" disabled />
            </div>
            <div class="grid gap-2">
                <x-ui.label for="nama_supplier">Nama Supplier</x-ui.label>
                <x-ui.input id="nama_supplier" wire:model="nama_supplier" placeholder="mis. CV Kimia Jaya" />
                <x-ui.error for="nama_supplier" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <x-ui.label for="kontak_person">Kontak Person</x-ui.label>
                    <x-ui.input id="kontak_person" wire:model="kontak_person" />
                    <x-ui.error for="kontak_person" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="no_telepon">No. Telepon / WA</x-ui.label>
                    <x-ui.input id="no_telepon" wire:model="no_telepon" />
                    <x-ui.error for="no_telepon" />
                </div>
            </div>
            <div class="grid gap-2">
                <x-ui.label for="alamat">Alamat</x-ui.label>
                <x-ui.textarea id="alamat" wire:model="alamat" rows="2" />
                <x-ui.error for="alamat" />
            </div>
            <div class="grid gap-2">
                <x-ui.label for="status">Status</x-ui.label>
                <x-ui.select id="status" wire:model="status">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </x-ui.select>
            </div>
            <div class="mt-2 flex justify-end gap-2">
                <x-ui.button variant="outline" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>
</div>