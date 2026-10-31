@use('App\Support\Format')
@use('App\Enums\WilayahRing')
@use('App\Enums\StatusPengirimanItem')

@php
    $durasi = fn ($jam) => $jam === null ? '–' : ($jam < 24 ? Format::angka($jam, 1) . ' jam' : Format::angka($jam / 24, 1) . ' hari');
@endphp

<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Riwayat Pengiriman</h1>
        <p class="text-sm text-muted-foreground">Evaluasi efisiensi distribusi per wilayah dan riwayat pengiriman per mitra.</p>
    </div>

    {{-- Filter --}}
    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.select wire:model.live="ring">
            <option value="">Semua wilayah</option>
            @foreach (WilayahRing::cases() as $r)
                <option value="{{ $r->value }}">{{ $r->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select wire:model.live="mitra">
            <option value="">Semua mitra</option>
            @foreach ($mitraList as $m)
                <option value="{{ $m->id }}">{{ $m->nama_usaha }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select wire:model.live="status">
            <option value="">Semua status kirim</option>
            @foreach (StatusPengirimanItem::cases() as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input type="month" wire:model.live="bulan" />
    </div>

    {{-- Ringkasan per Ring --}}
    <x-ui.card :title="'Efisiensi per Wilayah — ' . $labelPeriode"
               description="Waktu rata-rata dihitung dari order berstatus Siap Kirim hingga terkirim.">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Wilayah</th>
                        <th class="py-2 pr-4 text-right font-medium">Pengiriman</th>
                        <th class="py-2 pr-4 text-right font-medium">Terkirim</th>
                        <th class="py-2 pr-4 text-right font-medium">Gagal</th>
                        <th class="py-2 pr-4 text-right font-medium">Rata-rata Waktu</th>
                        <th class="py-2 pr-4 text-right font-medium">Jadwal</th>
                        <th class="py-2 text-right font-medium">Estimasi Biaya</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach (WilayahRing::cases() as $r)
                        @php $d = $perRing->get($r->value); $b = $biayaPerRing->get($r->value); @endphp
                        @continue(! $d && ! $b)
                        <tr>
                            <td class="py-2 pr-4">
                                <span class="font-medium">{{ $r->label() }}</span>
                                <span class="text-xs text-muted-foreground">{{ $r->area() }}</span>
                            </td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ $d->jumlah ?? 0 }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ $d->terkirim ?? 0 }}</td>
                            <td @class(['py-2 pr-4 text-right tabular-nums', 'text-destructive' => ($d->gagal ?? 0) > 0])>{{ $d->gagal ?? 0 }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ $durasi($d?->rata_jam !== null ? (float) $d->rata_jam : null) }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ $b->jumlah_jadwal ?? 0 }}</td>
                            <td class="py-2 text-right tabular-nums">{{ Format::rupiah($b->total_biaya ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($perRing->isEmpty() && $biayaPerRing->isEmpty())
                <p class="py-4 text-center text-sm text-muted-foreground">Belum ada data pengiriman.</p>
            @endif
        </div>
    </x-ui.card>

    {{-- Daftar pengiriman --}}
    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Tanggal</th>
                    <th class="px-4 py-3 font-medium">Mitra</th>
                    <th class="px-4 py-3 font-medium">Order</th>
                    <th class="px-4 py-3 font-medium">Jadwal / Kurir</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($riwayat as $i)
                    <tr wire:key="rw-{{ $i->id }}" class="hover:bg-muted/30">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $i->schedule->tanggal_rencana_kirim->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            {{ $i->salesOrder->partner->nama_usaha }}
                            <x-ui.badge variant="outline" class="ml-1">{{ $i->salesOrder->partner->wilayah_ring->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('order.detail', $i->salesOrder) }}" wire:navigate class="font-mono text-xs hover:underline">{{ $i->salesOrder->nomor_order }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('distribusi.detail', $i->schedule) }}" wire:navigate class="font-mono text-xs hover:underline">{{ $i->schedule->kode_jadwal }}</a>
                            <div class="text-xs text-muted-foreground">{{ $i->schedule->nama_kurir }}</div>
                        </td>
                        <td class="px-4 py-3"><x-ui.badge :variant="$i->status_pengiriman_item->badge()">{{ $i->status_pengiriman_item->label() }}</x-ui.badge></td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ $i->waktu_terkirim?->format('d/m/Y H:i') }} {{ $i->catatan }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">Tidak ada pengiriman yang cocok dengan filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $riwayat->links() }}
</div>