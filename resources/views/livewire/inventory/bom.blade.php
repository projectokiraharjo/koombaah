<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Resep Produk (BOM)</h1>
            <p class="text-sm text-muted-foreground">Satu produk = satu resep aktif. Setiap perubahan resep disimpan sebagai versi baru.</p>
        </div>
        <x-ui.input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari produk…" class="sm:max-w-xs" />
    </div>

    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Kode</th>
                    <th class="px-4 py-3 font-medium">Produk</th>
                    <th class="px-4 py-3 font-medium">Resep Aktif</th>
                    <th class="px-4 py-3 text-right font-medium">Jumlah Bahan</th>
                    <th class="px-4 py-3 font-medium">Terakhir Diubah</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($produk as $p)
                    <tr wire:key="bom-{{ $p->id }}" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-mono text-xs">{{ $p->kode_produk }}</td>
                        <td class="px-4 py-3 font-medium">
                            {{ $p->nama_produk }}
                            @if ($p->status->value === 'nonaktif')
                                <x-ui.badge variant="outline" class="ml-1">Nonaktif</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($p->activeBom)
                                <x-ui.badge variant="secondary">Versi {{ $p->activeBom->versi }}</x-ui.badge>
                            @else
                                <x-ui.badge variant="destructive">Belum ada resep</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $p->activeBom?->details_count ?? '–' }}</td>
                        <td class="px-4 py-3 text-muted-foreground">
                            @if ($p->activeBom)
                                {{ $p->activeBom->created_at->format('d/m/Y') }} · {{ $p->activeBom->creator->name }}
                            @else
                                –
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.button variant="ghost" size="sm" href="{{ route('bom.detail', $p) }}" wire:navigate>Lihat</x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">Belum ada produk jadi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>