@use('App\Support\Format')
@use('App\Enums\ReferensiStok')

<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Riwayat Pergerakan Stok</h1>
        <p class="text-sm text-muted-foreground">Log permanen seluruh perubahan stok — siapa, kapan, berapa, dan alasannya.</p>
    </div>

    {{-- Filter --}}
    <div class="grid gap-3 rounded-xl border bg-card p-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="grid gap-1.5">
            <x-ui.label for="f-jenis">Jenis item</x-ui.label>
            <x-ui.select id="f-jenis" wire:model.live="jenis">
                <option value="">Semua</option>
                <option value="raw_material">Bahan baku</option>
                <option value="finished_product">Produk jadi</option>
            </x-ui.select>
        </div>
        <div class="grid gap-1.5">
            <x-ui.label for="f-item">Item</x-ui.label>
            <x-ui.select id="f-item" wire:model.live="itemId" :disabled="$jenis === ''">
                <option value="">{{ $jenis === '' ? 'Pilih jenis dulu' : 'Semua item' }}</option>
                @foreach ($itemOptions as $o)
                    <option value="{{ $o->id }}">{{ $o->kode }} — {{ $o->nama }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="grid gap-1.5">
            <x-ui.label for="f-tipe">Pergerakan</x-ui.label>
            <x-ui.select id="f-tipe" wire:model.live="tipe">
                <option value="">Masuk & keluar</option>
                <option value="masuk">Masuk</option>
                <option value="keluar">Keluar</option>
            </x-ui.select>
        </div>
        <div class="grid gap-1.5">
            <x-ui.label for="f-ref">Referensi</x-ui.label>
            <x-ui.select id="f-ref" wire:model.live="referensi">
                <option value="">Semua referensi</option>
                @foreach (ReferensiStok::cases() as $r)
                    <option value="{{ $r->value }}">{{ $r->label() }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="grid gap-1.5">
            <x-ui.label for="f-dari">Dari tanggal</x-ui.label>
            <x-ui.input id="f-dari" type="date" wire:model.live="dari" />
        </div>
        <div class="grid gap-1.5">
            <x-ui.label for="f-sampai">Sampai tanggal</x-ui.label>
            <x-ui.input id="f-sampai" type="date" wire:model.live="sampai" />
        </div>
        <div class="grid gap-1.5">
            <x-ui.label for="f-oleh">Dilakukan oleh</x-ui.label>
            <x-ui.select id="f-oleh" wire:model.live="oleh">
                <option value="">Semua pengguna</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="flex items-end">
            <x-ui.button variant="outline" class="w-full" wire:click="resetFilter">Reset filter</x-ui.button>
        </div>
    </div>

    {{-- Audit per item (user story 8) --}}
    @if ($audit)
        <x-ui.card :title="'Audit Stok — ' . $audit['nama']"
                   description="Membandingkan stok yang tercatat di sistem dengan hasil penjumlahan seluruh log pergerakan.">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
                <div>
                    <p class="text-xs text-muted-foreground">Total masuk</p>
                    <p class="font-semibold tabular-nums">+{{ Format::angka($audit['masuk']) }} {{ $audit['satuan'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Total keluar</p>
                    <p class="font-semibold tabular-nums">−{{ Format::angka($audit['keluar']) }} {{ $audit['satuan'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Saldo dari log</p>
                    <p class="font-semibold tabular-nums">{{ Format::angka($audit['saldo_log']) }} {{ $audit['satuan'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Stok di sistem</p>
                    <p class="font-semibold tabular-nums">{{ Format::angka($audit['stok_sistem']) }} {{ $audit['satuan'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Status</p>
                    @if ($audit['cocok'])
                        <x-ui.badge variant="secondary">Cocok ✓</x-ui.badge>
                    @else
                        <x-ui.badge variant="destructive">Ada selisih</x-ui.badge>
                    @endif
                </div>
            </div>
        </x-ui.card>
    @endif

    {{-- Tabel riwayat --}}
    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Waktu</th>
                    <th class="px-4 py-3 font-medium">Item</th>
                    <th class="px-4 py-3 text-right font-medium">Jumlah</th>
                    <th class="px-4 py-3 text-right font-medium">Saldo Setelah</th>
                    <th class="px-4 py-3 font-medium">Referensi</th>
                    <th class="px-4 py-3 font-medium">Catatan</th>
                    <th class="px-4 py-3 font-medium">Oleh</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($riwayat as $m)
                    @php
                        $isBahan = $m->item_type === 'raw_material';
                        $satuan = $isBahan ? $m->item->satuan->value : $m->item->satuan_jual->value;
                    @endphp
                    <tr wire:key="riwayat-{{ $m->id }}" class="hover:bg-muted/30">
                        <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <x-ui.badge variant="outline">{{ $isBahan ? 'Bahan' : 'Produk' }}</x-ui.badge>
                                <span class="font-mono text-xs text-muted-foreground">{{ $isBahan ? $m->item->kode_bahan : $m->item->kode_produk }}</span>
                            </div>
                            <div class="mt-1 font-medium">{{ $isBahan ? $m->item->nama_bahan : $m->item->nama_produk }}</div>
                        </td>
                        <td @class([
                            'px-4 py-3 text-right font-medium tabular-nums whitespace-nowrap',
                            'text-destructive' => $m->tipe_pergerakan->value === 'keluar',
                        ])>
                            {{ $m->tipe_pergerakan->value === 'masuk' ? '+' : '−' }}{{ Format::angka($m->jumlah) }} {{ $satuan }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap">{{ Format::angka($m->saldo_setelah) }} {{ $satuan }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <x-ui.badge variant="outline">{{ $m->referensi_tipe->label() }}</x-ui.badge>
                            @if ($m->referensi_id)
                                <span class="text-xs text-muted-foreground">#{{ $m->referensi_id }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $m->catatan ?: '–' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $m->user->name }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-muted-foreground">Tidak ada pergerakan stok yang cocok dengan filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $riwayat->links() }}
</div>