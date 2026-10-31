@use('App\Enums\WilayahRing')

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Mitra</h1>
            <p class="text-sm text-muted-foreground">Data master usaha laundry mitra Koombaah.</p>
        </div>
        <x-ui.button wire:click="create">+ Daftarkan Mitra</x-ui.button>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row">
        <x-ui.input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama usaha, pemilik, alamat…" class="sm:max-w-xs" />
        <x-ui.select wire:model.live="ring" class="sm:w-56">
            <option value="">Semua wilayah</option>
            @foreach (WilayahRing::cases() as $r)
                <option value="{{ $r->value }}">{{ $r->label() }} — {{ $r->area() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select wire:model.live="filterStatus" class="sm:w-40">
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
                    <th class="px-4 py-3 font-medium">Nama Usaha</th>
                    <th class="px-4 py-3 font-medium">Pemilik / Kontak</th>
                    <th class="px-4 py-3 font-medium">Wilayah</th>
                    <th class="px-4 py-3 text-right font-medium">Order</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($items as $m)
                    <tr wire:key="mitra-{{ $m->id }}" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-mono text-xs">{{ $m->kode_mitra }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $m->nama_usaha }}</div>
                            <div class="text-xs text-muted-foreground">{{ $m->alamat }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ $m->nama_pemilik ?: '–' }}</div>
                            <div class="text-xs text-muted-foreground">{{ $m->no_telepon }}</div>
                        </td>
                        <td class="px-4 py-3"><x-ui.badge variant="outline">{{ $m->wilayah_ring->label() }}</x-ui.badge></td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $m->sales_orders_count }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$m->status->value === 'aktif' ? 'secondary' : 'outline'">{{ $m->status->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <x-ui.button variant="ghost" size="sm" href="{{ route('mitra.detail', $m) }}" wire:navigate>Riwayat</x-ui.button>
                                <x-ui.button variant="ghost" size="sm" wire:click="edit({{ $m->id }})">Edit</x-ui.button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-muted-foreground">
                            Belum ada mitra terdaftar. Klik <span class="font-medium">+ Daftarkan Mitra</span> untuk memulai.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $items->links() }}

    <x-ui.dialog :title="$editingId ? 'Edit Mitra' : 'Daftarkan Mitra Baru'">
        <form wire:submit="save" class="flex flex-col gap-4">
            <div class="grid gap-2">
                <x-ui.label>Kode</x-ui.label>
                <x-ui.input :value="$kode_mitra" disabled />
            </div>
            <div class="grid gap-2">
                <x-ui.label for="nama_usaha">Nama Usaha Laundry</x-ui.label>
                <x-ui.input id="nama_usaha" wire:model="nama_usaha" placeholder="mis. Laundry Bersih Kilat" />
                <x-ui.error for="nama_usaha" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <x-ui.label for="nama_pemilik">Nama Pemilik / PIC</x-ui.label>
                    <x-ui.input id="nama_pemilik" wire:model="nama_pemilik" />
                    <x-ui.error for="nama_pemilik" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="no_telepon">No. Telepon / WA</x-ui.label>
                    <x-ui.input id="no_telepon" wire:model="no_telepon" />
                    <x-ui.error for="no_telepon" />
                </div>
            </div>
            <div class="grid gap-2">
                <x-ui.label for="alamat">Alamat Lengkap</x-ui.label>
                <x-ui.textarea id="alamat" wire:model="alamat" rows="2" />
                <x-ui.error for="alamat" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <x-ui.label for="wilayah_ring">Wilayah (Ring)</x-ui.label>
                    <x-ui.select id="wilayah_ring" wire:model="wilayah_ring">
                        <option value="">Pilih wilayah</option>
                        @foreach (WilayahRing::cases() as $r)
                            <option value="{{ $r->value }}">{{ $r->label() }} — {{ $r->area() }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.error for="wilayah_ring" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="status">Status</x-ui.label>
                    <x-ui.select id="status" wire:model="status">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </x-ui.select>
                </div>
            </div>
            <div class="grid gap-2">
                <x-ui.label for="catatan">Catatan (opsional)</x-ui.label>
                <x-ui.textarea id="catatan" wire:model="catatan" rows="2" placeholder="Catatan pendampingan, preferensi jam kirim, dll." />
                <x-ui.error for="catatan" />
            </div>
            <div class="mt-2 flex justify-end gap-2">
                <x-ui.button variant="outline" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>
</div>