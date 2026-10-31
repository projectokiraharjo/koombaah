@use('App\Support\Format')
@use('App\Enums\StatusBatch')

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Produksi</h1>
            <p class="text-sm text-muted-foreground">
                Riwayat batch produksi.
                @if ($berjalan > 0)
                    <span class="font-medium text-foreground">{{ $berjalan }} batch sedang berjalan.</span>
                @endif
            </p>
        </div>
        <x-ui.button href="{{ route('produksi.buat') }}" wire:navigate>+ Batch Baru</x-ui.button>
    </div>

    {{-- Filter --}}
    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nomor batch…" />
        <x-ui.select wire:model.live="produk">
            <option value="">Semua produk</option>
            @foreach ($produkList as $p)
                <option value="{{ $p->id }}">{{ $p->nama_produk }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select wire:model.live="status">
            <option value="">Semua status</option>
            @foreach (StatusBatch::cases() as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input type="month" wire:model.live="bulan" title="Kosongkan untuk semua periode" />
    </div>

    {{-- Ringkasan per produk --}}
    @if ($ringkasan->isNotEmpty())
        <x-ui.card :title="'Ringkasan Produksi — ' . $labelPeriode" description="Dari batch berstatus selesai, sesuai filter di atas.">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b text-left text-muted-foreground">
                        <tr>
                            <th class="py-2 pr-4 font-medium">Produk</th>
                            <th class="py-2 pr-4 text-right font-medium">Batch</th>
                            <th class="py-2 pr-4 text-right font-medium">Output Aktual</th>
                            <th class="py-2 pr-4 text-right font-medium">Lolos QC</th>
                            <th class="py-2 pr-4 text-right font-medium">Reject</th>
                            <th class="py-2 text-right font-medium">Tingkat Reject</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($ringkasan as $r)
                            @php $persenReject = (float) $r->total_aktual > 0 ? (float) $r->total_reject / (float) $r->total_aktual * 100 : 0; @endphp
                            <tr>
                                <td class="py-2 pr-4 font-medium">{{ $r->nama_produk }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ $r->jumlah_batch }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ Format::angka($r->total_aktual) }} {{ $r->satuan_jual }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ Format::angka($r->total_lolos) }} {{ $r->satuan_jual }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ Format::angka($r->total_reject) }} {{ $r->satuan_jual }}</td>
                                <td @class(['py-2 text-right tabular-nums', 'font-medium text-destructive' => $persenReject >= 10])>
                                    {{ Format::angka($persenReject, 1) }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    {{-- Daftar batch --}}
    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Nomor Batch</th>
                    <th class="px-4 py-3 font-medium">Produk</th>
                    <th class="px-4 py-3 font-medium">Mulai → Selesai</th>
                    <th class="px-4 py-3 text-right font-medium">Target</th>
                    <th class="px-4 py-3 text-right font-medium">Lolos / Reject</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($daftar as $b)
                    @php $sat = $b->product->satuan_jual->value; @endphp
                    <tr wire:key="batch-{{ $b->id }}" class="hover:bg-muted/30">
                        <td class="px-4 py-3">
                            <a href="{{ route('produksi.detail', $b) }}" wire:navigate class="font-mono text-xs font-medium hover:underline">{{ $b->nomor_batch }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $b->product->nama_produk }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">
                            {{ $b->tanggal_mulai->format('d/m/Y') }} → {{ $b->tanggal_selesai?->format('d/m/Y') ?? '…' }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap">{{ Format::angka($b->jumlah_target) }} {{ $sat }}</td>
                        <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap">
                            @if ($b->jumlah_aktual !== null)
                                {{ Format::angka($b->jumlah_lolos_qc) }} / <span @class(['text-destructive' => (float) $b->jumlah_reject_qc > 0])>{{ Format::angka($b->jumlah_reject_qc) }}</span>
                            @else
                                –
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <x-ui.badge :variant="$b->status->badge()">{{ $b->status->label() }}</x-ui.badge>
                                @if ($b->status_qc)
                                    <x-ui.badge :variant="$b->status_qc->badge()">{{ $b->status_qc->label() }}</x-ui.badge>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">Belum ada batch produksi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $daftar->links() }}
</div>