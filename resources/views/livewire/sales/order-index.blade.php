@use('App\Support\Format')
@use('App\Enums\StatusOrder')
@use('App\Enums\WilayahRing')

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Sales Order</h1>
            <p class="text-sm text-muted-foreground">Order dari mitra, dicatat manual oleh Admin.</p>
        </div>
        <x-ui.button href="{{ route('order.buat') }}" wire:navigate>+ Order Baru</x-ui.button>
    </div>

    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nomor order / nama mitra…" />
        <x-ui.select wire:model.live="status">
            <option value="">Semua status</option>
            @foreach (StatusOrder::cases() as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select wire:model.live="ring">
            <option value="">Semua wilayah</option>
            @foreach (WilayahRing::cases() as $r)
                <option value="{{ $r->value }}">{{ $r->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input type="month" wire:model.live="bulan" />
    </div>

    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Nomor Order</th>
                    <th class="px-4 py-3 font-medium">Tanggal</th>
                    <th class="px-4 py-3 font-medium">Mitra</th>
                    <th class="px-4 py-3 text-right font-medium">Item</th>
                    <th class="px-4 py-3 text-right font-medium">Total</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($daftar as $o)
                    <tr wire:key="so-{{ $o->id }}" class="hover:bg-muted/30">
                        <td class="px-4 py-3">
                            <a href="{{ route('order.detail', $o) }}" wire:navigate class="font-mono text-xs font-medium hover:underline">{{ $o->nomor_order }}</a>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $o->tanggal_order->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            {{ $o->partner->nama_usaha }}
                            <x-ui.badge variant="outline" class="ml-1">{{ $o->partner->wilayah_ring->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $o->items_count }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ Format::rupiah($o->total_nilai_order) }}</td>
                        <td class="px-4 py-3"><x-ui.badge :variant="$o->status->badge()">{{ $o->status->label() }}</x-ui.badge></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">Belum ada order.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $daftar->links() }}
</div>