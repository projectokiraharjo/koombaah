@use('App\Support\Format')

<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Stok Bahan Baku</h1>
        <p class="text-sm text-muted-foreground">Untuk stok awal & penyesuaian manual. Pembelian dari supplier nanti tercatat otomatis lewat Modul Purchasing.</p>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <x-ui.input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari kode atau nama…" class="sm:max-w-xs" />
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model.live="hanyaMenipis" class="size-4 accent-primary">
            Hanya stok menipis
        </label>
    </div>

    {{-- Daftar stok --}}
    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Kode</th>
                    <th class="px-4 py-3 font-medium">Bahan Baku</th>
                    <th class="px-4 py-3 text-right font-medium">Stok</th>
                    <th class="px-4 py-3 text-right font-medium">Stok Min.</th>
                    <th class="px-4 py-3 font-medium">Kondisi</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($items as $item)
                    <tr wire:key="stok-{{ $item->id }}" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-mono text-xs">{{ $item->kode_bahan }}</td>
                        <td class="px-4 py-3 font-medium">{{ $item->nama_bahan }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ Format::angka($item->stok_saat_ini) }} {{ $item->satuan->value }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ Format::angka($item->stok_minimum) }}</td>
                        <td class="px-4 py-3">
                            @if ($item->isLowStock())
                                <x-ui.badge variant="destructive">Menipis</x-ui.badge>
                            @else
                                <x-ui.badge variant="secondary">Aman</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <x-ui.button variant="outline" size="sm" wire:click="bukaForm({{ $item->id }}, 'masuk')">+ Masuk</x-ui.button>
                                <x-ui.button variant="outline" size="sm" wire:click="bukaForm({{ $item->id }}, 'keluar')">− Keluar</x-ui.button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $items->links() }}

    {{-- Pergerakan terakhir --}}
    <x-ui.card title="Pergerakan Terakhir" description="10 pencatatan terbaru. Log ini permanen — koreksi dilakukan dengan penyesuaian baru.">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Waktu</th>
                        <th class="py-2 pr-4 font-medium">Bahan</th>
                        <th class="py-2 pr-4 text-right font-medium">Jumlah</th>
                        <th class="py-2 pr-4 font-medium">Referensi</th>
                        <th class="py-2 pr-4 font-medium">Catatan</th>
                        <th class="py-2 font-medium">Oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($riwayat as $m)
                        <tr wire:key="mv-{{ $m->id }}">
                            <td class="py-2 pr-4 whitespace-nowrap text-muted-foreground">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-2 pr-4">{{ $m->item->nama_bahan }}</td>
                            <td @class([
                                'py-2 pr-4 text-right font-medium tabular-nums whitespace-nowrap',
                                'text-destructive' => $m->tipe_pergerakan->value === 'keluar',
                            ])>
                                {{ $m->tipe_pergerakan->value === 'masuk' ? '+' : '−' }}{{ Format::angka($m->jumlah) }} {{ $m->item->satuan->value }}
                            </td>
                            <td class="py-2 pr-4"><x-ui.badge variant="outline">{{ $m->referensi_tipe->label() }}</x-ui.badge></td>
                            <td class="py-2 pr-4 text-muted-foreground">{{ $m->catatan }}</td>
                            <td class="py-2">{{ $m->user->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-muted-foreground">Belum ada pergerakan stok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    {{-- Form catat stok --}}
    <x-ui.dialog :title="$terpilih ? 'Catat Stok — ' . $terpilih->nama_bahan : 'Catat Stok'"
                 description="Setiap pencatatan tersimpan permanen di riwayat stok.">
        @if ($terpilih)
            <form wire:submit="simpan" class="flex flex-col gap-4">
                <div class="grid grid-cols-2 gap-2">
                    @foreach (['masuk' => '+ Stok Masuk', 'keluar' => '− Stok Keluar'] as $val => $label)
                        <label @class([
                            'flex h-9 cursor-pointer items-center justify-center rounded-md border text-sm font-medium transition-colors',
                            'border-primary bg-primary text-primary-foreground' => $tipe === $val,
                            'hover:bg-accent' => $tipe !== $val,
                        ])>
                            <input type="radio" wire:model.live="tipe" value="{{ $val }}" class="sr-only">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>

                <div class="grid gap-2">
                    <x-ui.label for="jumlah">Jumlah ({{ $terpilih->satuan->value }})</x-ui.label>
                    <x-ui.input id="jumlah" type="number" step="0.001" min="0" wire:model.live.debounce.300ms="jumlah" />
                    <x-ui.error for="jumlah" />
                </div>

                @php
                    $sekarang = (float) $terpilih->stok_saat_ini;
                    $delta = is_numeric($jumlah) ? (float) $jumlah : 0;
                    $setelah = $tipe === 'masuk' ? $sekarang + $delta : $sekarang - $delta;
                @endphp
                <div class="rounded-lg bg-muted/50 px-4 py-3 text-sm">
                    Stok sekarang <span class="font-medium tabular-nums">{{ Format::angka($sekarang) }}</span>
                    → setelah dicatat
                    <span @class(['font-semibold tabular-nums', 'text-destructive' => $setelah < 0])>{{ Format::angka($setelah) }}</span>
                    {{ $terpilih->satuan->value }}
                </div>

                <div class="grid gap-2">
                    <x-ui.label for="catatan">Catatan Alasan</x-ui.label>
                    <x-ui.textarea id="catatan" wire:model="catatan" rows="2"
                                   placeholder="mis. Stok awal (stock opname), bahan rusak, kadaluarsa, selisih stok fisik" />
                    <x-ui.error for="catatan" />
                </div>

                <div class="mt-2 flex justify-end gap-2">
                    <x-ui.button variant="outline" x-on:click="open = false">Batal</x-ui.button>
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="simpan">Simpan</x-ui.button>
                </div>
            </form>
        @endif
    </x-ui.dialog>
</div>