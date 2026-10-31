@use('App\Support\Format')

@php $total = $daftar->sum(fn ($b) => (float) $b->nominal) + $distribusi->sum(fn ($d) => (float) $d->nominal); @endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Biaya Operasional</h1>
            <p class="text-sm text-muted-foreground">Biaya bulanan di luar HPP. Beban gaji diisi sebagai satu angka agregat.</p>
        </div>
        <div class="flex gap-2">
            <x-ui.input type="month" wire:model.live="bulan" class="w-44" />
            <x-ui.button wire:click="create" :disabled="$ditutup">+ Tambah Biaya</x-ui.button>
        </div>
    </div>

    @if ($ditutup)
        <div class="rounded-lg border bg-muted/50 px-4 py-3 text-sm">
            🔒 Periode <span class="font-medium">{{ $labelBulan }}</span> sudah ditutup — data tidak dapat diubah atau dihapus.
        </div>
    @endif

    <x-ui.card :title="'Biaya ' . $labelBulan">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Kategori</th>
                        <th class="py-2 pr-4 font-medium">Catatan</th>
                        <th class="py-2 pr-4 font-medium">Dicatat oleh</th>
                        <th class="py-2 pr-4 text-right font-medium">Nominal</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($daftar as $b)
                        <tr wire:key="oe-{{ $b->id }}">
                            <td class="py-2 pr-4 font-medium">
                                {{ $b->kategori->label() }}
                                @if ($b->kategori === \App\Enums\KategoriBiaya::BebanGaji)
                                    <x-ui.badge variant="outline" class="ml-1">otomatis dari payroll</x-ui.badge>
                                @endif
                            </td>                            
                            <td class="py-2 pr-4 text-muted-foreground">{{ $b->catatan ?: '–' }}</td>
                            <td class="py-2 pr-4">{{ $b->pencatat->name }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($b->nominal) }}</td>
                            <td class="py-2">
                                @unless ($ditutup || $b->kategori === \App\Enums\KategoriBiaya::BebanGaji)                                    <div class="flex justify-end gap-1">
                                        <x-ui.button variant="ghost" size="sm" wire:click="edit({{ $b->id }})">Edit</x-ui.button>
                                        <x-ui.button variant="ghost" size="sm" wire:click="hapus({{ $b->id }})" wire:confirm="Hapus biaya ini?">Hapus</x-ui.button>
                                    </div>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                    @foreach ($distribusi as $d)
                        <tr wire:key="dist-{{ $d->id }}" class="bg-muted/30">
                            <td class="py-2 pr-4 font-medium">Distribusi <x-ui.badge variant="outline" class="ml-1">otomatis</x-ui.badge></td>
                            <td class="py-2 pr-4 text-muted-foreground">{{ $d->catatan }}</td>
                            <td class="py-2 pr-4 text-muted-foreground">Modul Distribusi</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($d->nominal) }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                    @if ($daftar->isEmpty() && $distribusi->isEmpty())
                        <tr><td colspan="5" class="py-6 text-center text-muted-foreground">Belum ada biaya di bulan ini.</td></tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr class="border-t">
                        <td colspan="3" class="py-3 pr-4 text-right font-medium">Total biaya operasional</td>
                        <td class="py-3 pr-4 text-right font-semibold tabular-nums">{{ Format::rupiah($total) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-ui.card>

    <x-ui.dialog :title="$editingId ? 'Edit Biaya Operasional' : 'Tambah Biaya Operasional'"
                 description="Biaya distribusi tidak perlu diisi — tercatat otomatis dari jadwal pengiriman.">
        <form wire:submit="save" class="flex flex-col gap-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <x-ui.label for="periode_bulan">Periode</x-ui.label>
                    <x-ui.input id="periode_bulan" type="month" wire:model="periode_bulan" />
                    <x-ui.error for="periode_bulan" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="kategori">Kategori</x-ui.label>
                    <x-ui.select id="kategori" wire:model="kategori">
                        <option value="">Pilih kategori</option>
                        @foreach ($kategoriList as $k)
                            <option value="{{ $k->value }}">{{ $k->label() }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.error for="kategori" />
                </div>
            </div>
            <div class="grid gap-2">
                <x-ui.label for="nominal">Nominal (Rp)</x-ui.label>
                <x-ui.input id="nominal" type="number" step="1000" min="0" wire:model="nominal" />
                <x-ui.error for="nominal" />
            </div>
            <div class="grid gap-2">
                <x-ui.label for="catatan">Catatan (opsional)</x-ui.label>
                <x-ui.textarea id="catatan" wire:model="catatan" rows="2" placeholder="mis. Tagihan PLN Oktober" />
                <x-ui.error for="catatan" />
            </div>
            <div class="mt-2 flex justify-end gap-2">
                <x-ui.button variant="outline" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>
</div>