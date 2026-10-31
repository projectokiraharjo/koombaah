@use('App\Support\Format')
@use('App\Enums\StatusJadwal')
@use('App\Enums\WilayahRing')

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Jadwal Pengiriman</h1>
            <p class="text-sm text-muted-foreground">Kelompokkan order siap kirim per wilayah, lalu tugaskan ke satu kurir.</p>
        </div>
        <x-ui.button href="{{ route('distribusi.buat') }}" wire:navigate>+ Jadwal Baru</x-ui.button>
    </div>

    {{-- Order siap kirim per Ring --}}
    <div class="grid gap-4 md:grid-cols-3">
        @foreach (WilayahRing::cases() as $r)
            @php $daftar = $siapKirim->get($r->value, collect()); @endphp
            <x-ui.card :title="$r->label() . ' — ' . $daftar->count() . ' order siap kirim'" :description="$r->area()">
                @if ($daftar->isEmpty())
                    <p class="text-sm text-muted-foreground">Tidak ada order menunggu jadwal.</p>
                @else
                    <ul class="grid gap-1 text-sm">
                        @foreach ($daftar->take(4) as $o)
                            <li class="flex justify-between gap-2">
                                <span class="truncate">{{ $o->partner->nama_usaha }}</span>
                                <span class="shrink-0 font-mono text-xs text-muted-foreground">{{ $o->nomor_order }}</span>
                            </li>
                        @endforeach
                        @if ($daftar->count() > 4)
                            <li class="text-xs text-muted-foreground">+{{ $daftar->count() - 4 }} order lainnya</li>
                        @endif
                    </ul>
                @endif
                <x-slot:footer>
                    <x-ui.button variant="outline" size="sm" class="w-full" :disabled="$daftar->isEmpty()"
                                 href="{{ route('distribusi.buat', ['ring' => $r->value]) }}" wire:navigate>
                        Jadwalkan {{ $r->label() }}
                    </x-ui.button>
                </x-slot:footer>
            </x-ui.card>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="flex flex-col gap-2 sm:flex-row">
        <x-ui.select wire:model.live="status" class="sm:w-52">
            <option value="">Semua status</option>
            @foreach (StatusJadwal::cases() as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select wire:model.live="ring" class="sm:w-44">
            <option value="">Semua wilayah</option>
            @foreach (WilayahRing::cases() as $r)
                <option value="{{ $r->value }}">{{ $r->label() }}</option>
            @endforeach
        </x-ui.select>
    </div>

    {{-- Daftar jadwal --}}
    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Kode</th>
                    <th class="px-4 py-3 font-medium">Rencana Kirim</th>
                    <th class="px-4 py-3 font-medium">Wilayah</th>
                    <th class="px-4 py-3 font-medium">Kurir</th>
                    <th class="px-4 py-3 font-medium">Order</th>
                    <th class="px-4 py-3 text-right font-medium">Estimasi Biaya</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($jadwal as $j)
                    <tr wire:key="del-{{ $j->id }}" class="hover:bg-muted/30">
                        <td class="px-4 py-3">
                            <a href="{{ route('distribusi.detail', $j) }}" wire:navigate class="font-mono text-xs font-medium hover:underline">{{ $j->kode_jadwal }}</a>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $j->tanggal_rencana_kirim->format('d/m/Y') }}</td>
                        <td class="px-4 py-3"><x-ui.badge variant="outline">{{ $j->wilayah_ring->label() }}</x-ui.badge></td>
                        <td class="px-4 py-3">{{ $j->nama_kurir }}</td>
                        <td class="px-4 py-3 whitespace-nowrap tabular-nums">
                            {{ $j->items_count }} order
                            @if ($j->terkirim_count || $j->gagal_count)
                                <span class="text-xs text-muted-foreground">({{ $j->terkirim_count }} terkirim{{ $j->gagal_count ? ', ' . $j->gagal_count . ' gagal' : '' }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $j->estimasi_biaya_distribusi !== null ? Format::rupiah($j->estimasi_biaya_distribusi) : '–' }}</td>
                        <td class="px-4 py-3"><x-ui.badge :variant="$j->status->badge()">{{ $j->status->label() }}</x-ui.badge></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-muted-foreground">Belum ada jadwal pengiriman.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $jadwal->links() }}
</div>