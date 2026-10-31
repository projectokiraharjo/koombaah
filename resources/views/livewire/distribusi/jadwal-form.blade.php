@use('App\Support\Format')
@use('App\Enums\WilayahRing')

<div class="flex flex-col gap-6">
    <div>
        <a href="{{ $jadwal ? route('distribusi.detail', $jadwal) : route('distribusi') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">← Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ $jadwal ? 'Edit ' . $jadwal->kode_jadwal : 'Jadwal Pengiriman Baru' }}</h1>
        <p class="text-sm text-muted-foreground">Satu jadwal = satu kurir. Penjadwalan dilakukan manual, tidak berulang otomatis.</p>
    </div>

    <form wire:submit="simpan" class="flex flex-col gap-6">
        <x-ui.card title="Informasi Jadwal">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="grid gap-2">
                    <x-ui.label for="wilayah_ring">Wilayah (Ring)</x-ui.label>
                    <x-ui.select id="wilayah_ring" wire:model.live="wilayah_ring">
                        <option value="">Pilih wilayah</option>
                        @foreach (WilayahRing::cases() as $r)
                            <option value="{{ $r->value }}">{{ $r->label() }} — {{ $r->area() }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.error for="wilayah_ring" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="nama_kurir">Nama Kurir</x-ui.label>
                    <x-ui.input id="nama_kurir" wire:model="nama_kurir" placeholder="mis. Pak Udin" />
                    <x-ui.error for="nama_kurir" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="tanggal_rencana_kirim">Tanggal Rencana Kirim</x-ui.label>
                    <x-ui.input id="tanggal_rencana_kirim" type="date" wire:model="tanggal_rencana_kirim" />
                    <x-ui.error for="tanggal_rencana_kirim" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="biaya">Estimasi Biaya (Rp)</x-ui.label>
                    <x-ui.input id="biaya" type="number" step="500" min="0" wire:model="estimasi_biaya_distribusi" placeholder="bensin + ongkos kurir" />
                    <x-ui.error for="estimasi_biaya_distribusi" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Order yang Dikirim" description="Order berstatus Siap Kirim yang belum masuk jadwal lain.">
            <label class="mb-3 flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="semuaRing" class="size-4 accent-primary">
                Tampilkan juga order dari Ring lain
            </label>

            @if ($tersedia->isEmpty())
                <p class="text-sm text-muted-foreground">
                    Tidak ada order siap kirim {{ $wilayah_ring && ! $semuaRing ? 'di wilayah ini' : '' }}.
                    Ubah status order menjadi <span class="font-medium">Siap Kirim</span> di menu Sales Order.
                </p>
            @else
                <div class="flex flex-col divide-y rounded-lg border">
                    @foreach ($tersedia as $o)
                        <label wire:key="pilih-{{ $o->id }}" class="flex cursor-pointer items-start gap-3 p-3 hover:bg-muted/30">
                            <input type="checkbox" wire:model.live="orderIds" value="{{ $o->id }}" class="mt-1 size-4 accent-primary">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium">{{ $o->partner->nama_usaha }}</span>
                                    <x-ui.badge variant="outline">{{ $o->partner->wilayah_ring->label() }}</x-ui.badge>
                                    <span class="font-mono text-xs text-muted-foreground">{{ $o->nomor_order }}</span>
                                </div>
                                <p class="truncate text-xs text-muted-foreground">{{ $o->partner->alamat }}</p>
                            </div>
                            <span class="shrink-0 text-sm tabular-nums">{{ Format::rupiah($o->total_nilai_order) }}</span>
                        </label>
                    @endforeach
                </div>
            @endif

            @if ($berbedaRing > 0)
                <p class="mt-3 rounded-lg bg-muted/60 px-3 py-2 text-sm">
                    ⚠ {{ $berbedaRing }} order berasal dari Ring yang berbeda dengan wilayah jadwal. Tetap boleh disimpan (PRD B.5.7).
                </p>
            @endif
            <x-ui.error for="orderIds" class="mt-3" />

            <x-slot:footer>
                <p class="ml-auto text-sm">
                    <span class="font-semibold">{{ $jumlahTerpilih }}</span> order dipilih ·
                    nilai <span class="font-semibold tabular-nums">{{ Format::rupiah($nilaiTerpilih) }}</span>
                </p>
            </x-slot:footer>
        </x-ui.card>

        <div class="flex justify-end">
            <x-ui.button type="submit" wire:loading.attr="disabled">Simpan Jadwal</x-ui.button>
        </div>
    </form>
</div>