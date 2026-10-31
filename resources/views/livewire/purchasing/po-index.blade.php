@use('App\Support\Format')
@use('App\Enums\StatusPO')

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Purchase Order</h1>
            <p class="text-sm text-muted-foreground">Pemesanan bahan baku ke supplier.</p>
        </div>
        <x-ui.button href="{{ route('po.buat') }}" wire:navigate>+ Buat PO</x-ui.button>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row">
        <x-ui.input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nomor PO…" class="sm:max-w-xs" />
        <x-ui.select wire:model.live="status" class="sm:w-48">
            <option value="">Semua status</option>
            @foreach (StatusPO::cases() as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select wire:model.live="supplier" class="sm:w-56">
            <option value="">Semua supplier</option>
            @foreach ($suppliers as $sup)
                <option value="{{ $sup->id }}">{{ $sup->nama_supplier }}</option>
            @endforeach
        </x-ui.select>
    </div>

    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Nomor PO</th>
                    <th class="px-4 py-3 font-medium">Tanggal</th>
                    <th class="px-4 py-3 font-medium">Supplier</th>
                    <th class="px-4 py-3 text-right font-medium">Total</th>
                    <th class="px-4 py-3 font-medium">Penerimaan</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($daftar as $po)
                    <tr wire:key="po-{{ $po->id }}" class="hover:bg-muted/30">
                        <td class="px-4 py-3">
                            <a href="{{ route('po.detail', $po) }}" wire:navigate class="font-mono text-xs font-medium hover:underline">{{ $po->nomor_po }}</a>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $po->tanggal_po->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $po->supplier->nama_supplier }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ Format::rupiah($po->total_nilai_po) }}</td>
                        <td class="px-4 py-3 text-muted-foreground tabular-nums">
                            @if (in_array($po->status, [StatusPO::Draft, StatusPO::Dibatalkan]))
                                –
                            @else
                                {{ $po->items_lengkap_count }}/{{ $po->items_count }} item lengkap
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$po->status->badge()">{{ $po->status->label() }}</x-ui.badge>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">Belum ada purchase order.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $daftar->links() }}
</div>