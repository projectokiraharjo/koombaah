@use('App\Support\Format')
@use('App\Enums\StatusJadwal')

@php
    $menunggu = $jadwal->items->filter(fn ($i) => $i->status_pengiriman_item->value === 'menunggu');
@endphp

<div class="flex flex-col gap-6">
    {{-- Judul & aksi --}}
    <div>
        <a href="{{ route('distribusi') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">← Semua jadwal</a>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="font-mono text-2xl font-semibold tracking-tight">{{ $jadwal->kode_jadwal }}</h1>
                    <x-ui.badge :variant="$jadwal->status->badge()">{{ $jadwal->status->label() }}</x-ui.badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ $jadwal->wilayah_ring->label() }} ({{ $jadwal->wilayah_ring->area() }}) · kurir
                    <span class="font-medium text-foreground">{{ $jadwal->nama_kurir }}</span> · dibuat oleh {{ $jadwal->creator->name }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($jadwal->bisaDiubah())
                    <x-ui.button variant="outline" href="{{ route('distribusi.edit', $jadwal) }}" wire:navigate>Edit</x-ui.button>
                    <x-ui.button wire:click="berangkat" wire:confirm="Tandai kurir sudah berangkat? Isi jadwal tidak bisa diubah lagi.">Kurir Berangkat</x-ui.button>
                    <x-ui.button variant="destructive" wire:click="hapus" wire:confirm="Hapus jadwal ini? Order akan kembali ke daftar siap kirim.">Hapus</x-ui.button>
                @endif
                @if ($jadwal->status === StatusJadwal::DalamPerjalanan && $menunggu->isNotEmpty())
                    <x-ui.button wire:click="bukaHasil">Catat Hasil Pengiriman</x-ui.button>
                @endif
            </div>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Rencana kirim</p>
            <p class="text-lg font-semibold">{{ $jadwal->tanggal_rencana_kirim->format('d/m/Y') }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Berangkat aktual</p>
            <p class="text-lg font-semibold">{{ $jadwal->tanggal_aktual_kirim?->format('d/m/Y') ?? '–' }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Estimasi biaya distribusi</p>
            <p class="text-lg font-semibold tabular-nums">{{ $jadwal->estimasi_biaya_distribusi !== null ? Format::rupiah($jadwal->estimasi_biaya_distribusi) : '–' }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Nilai order dibawa</p>
            <p class="text-lg font-semibold tabular-nums">{{ Format::rupiah($jadwal->items->sum(fn ($i) => (float) $i->salesOrder->total_nilai_order)) }}</p>
        </x-ui.card>
    </div>

    {{-- Daftar order --}}
    <x-ui.card :title="'Order dalam Jadwal (' . $jadwal->items->count() . ')'">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Mitra & Alamat</th>
                        <th class="py-2 pr-4 font-medium">Order</th>
                        <th class="py-2 pr-4 text-right font-medium">Nilai</th>
                        <th class="py-2 pr-4 font-medium">Status Kirim</th>
                        <th class="py-2 font-medium">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($jadwal->items as $item)
                        @php $o = $item->salesOrder; @endphp
                        <tr wire:key="dsi-{{ $item->id }}">
                            <td class="py-2 pr-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium">{{ $o->partner->nama_usaha }}</span>
                                    @if ($o->partner->wilayah_ring !== $jadwal->wilayah_ring)
                                        <x-ui.badge variant="outline">{{ $o->partner->wilayah_ring->label() }}</x-ui.badge>
                                    @endif
                                </div>
                                <div class="text-xs text-muted-foreground">{{ $o->partner->alamat }} · {{ $o->partner->no_telepon }}</div>
                            </td>
                            <td class="py-2 pr-4">
                                <a href="{{ route('order.detail', $o) }}" wire:navigate class="font-mono text-xs hover:underline">{{ $o->nomor_order }}</a>
                            </td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($o->total_nilai_order) }}</td>
                            <td class="py-2 pr-4"><x-ui.badge :variant="$item->status_pengiriman_item->badge()">{{ $item->status_pengiriman_item->label() }}</x-ui.badge></td>
                            <td class="py-2 text-muted-foreground">
                                {{ $item->waktu_terkirim?->format('d/m/Y H:i') }}
                                {{ $item->catatan }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    {{-- Dialog: catat hasil --}}
    <x-ui.dialog model="showHasil" title="Catat Hasil Pengiriman"
                 description="Isi berdasarkan laporan kurir. Order yang terkirim otomatis selesai & lunas (tunai). Order yang gagal kembali ke daftar siap kirim.">
        <form wire:submit="simpanHasil" class="flex flex-col gap-4">
            <div class="grid gap-2">
                <x-ui.label for="waktu_terkirim">Waktu Terkirim</x-ui.label>
                <x-ui.input id="waktu_terkirim" type="datetime-local" wire:model="waktu_terkirim" />
                <x-ui.error for="waktu_terkirim" />
            </div>

            <div class="flex flex-col divide-y rounded-lg border">
                @foreach ($menunggu as $item)
                    <div wire:key="hasil-{{ $item->id }}" class="grid gap-2 p-3">
                        <p class="text-sm font-medium">
                            {{ $item->salesOrder->partner->nama_usaha }}
                            <span class="font-mono text-xs font-normal text-muted-foreground">{{ $item->salesOrder->nomor_order }}</span>
                        </p>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach (['terkirim' => 'Terkirim', 'gagal_kirim' => 'Gagal', '' => 'Belum ada kabar'] as $val => $label)
                                <label @class([
                                    'flex h-8 cursor-pointer items-center justify-center rounded-md border text-xs font-medium',
                                    'border-primary bg-primary text-primary-foreground' => ($hasil[$item->id]['status'] ?? '') === $val,
                                    'hover:bg-accent' => ($hasil[$item->id]['status'] ?? '') !== $val,
                                ])>
                                    <input type="radio" class="sr-only" value="{{ $val }}" wire:model.live="hasil.{{ $item->id }}.status">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        @if (($hasil[$item->id]['status'] ?? '') === 'gagal_kirim')
                            <x-ui.input wire:model="hasil.{{ $item->id }}.catatan" placeholder="Alasan gagal (wajib), mis. laundry tutup" />
                        @endif
                    </div>
                @endforeach
            </div>
            <x-ui.error for="hasil" />

            <div class="mt-2 flex justify-end gap-2">
                <x-ui.button variant="outline" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="simpanHasil">Simpan Hasil</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>
</div>