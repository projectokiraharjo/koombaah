@use('App\Support\Format')

@php
    $sat = $batch->product->satuan_jual->value;
    $nilaiBahan = $batch->materials->sum(fn ($m) => (float) $m->jumlah_terpakai * (float) $m->harga_per_satuan);
@endphp

<div class="flex flex-col gap-6">
    {{-- Judul & aksi --}}
    <div>
        <a href="{{ route('produksi') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">← Riwayat batch</a>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="font-mono text-2xl font-semibold tracking-tight">{{ $batch->nomor_batch }}</h1>
                    <x-ui.badge :variant="$batch->status->badge()">{{ $batch->status->label() }}</x-ui.badge>
                    @if ($batch->status_qc)
                        <x-ui.badge :variant="$batch->status_qc->badge()">QC: {{ $batch->status_qc->label() }}</x-ui.badge>
                    @endif
                </div>
                <p class="text-sm text-muted-foreground">{{ $batch->product->nama_produk }} · dibuat oleh {{ $batch->creator->name }}</p>
            </div>
            @if ($batch->sedangBerjalan())
                <div class="flex flex-wrap gap-2">
                    <x-ui.button wire:click="bukaSelesai">Selesaikan & Catat QC</x-ui.button>
                    <x-ui.button variant="outline" wire:click="bukaBatal">Batalkan Batch</x-ui.button>
                </div>
            @endif
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Target</p>
            <p class="text-2xl font-semibold tabular-nums">{{ Format::angka($batch->jumlah_target) }} <span class="text-sm font-normal">{{ $sat }}</span></p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Lolos QC (masuk stok)</p>
            <p class="text-2xl font-semibold tabular-nums">{{ $batch->jumlah_lolos_qc !== null ? Format::angka($batch->jumlah_lolos_qc) : '–' }} <span class="text-sm font-normal">{{ $sat }}</span></p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Reject QC</p>
            <p @class(['text-2xl font-semibold tabular-nums', 'text-destructive' => (float) $batch->jumlah_reject_qc > 0])>
                {{ $batch->jumlah_reject_qc !== null ? Format::angka($batch->jumlah_reject_qc) : '–' }} <span class="text-sm font-normal">{{ $sat }}</span>
            </p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs text-muted-foreground">Periode</p>
            <p class="text-sm font-medium">
                {{ $batch->tanggal_mulai->format('d/m/Y') }} → {{ $batch->tanggal_selesai?->format('d/m/Y') ?? 'berjalan' }}
            </p>
            @if ($batch->durasiHari() !== null)
                <p class="text-xs text-muted-foreground">{{ $batch->durasiHari() }} hari</p>
            @endif
        </x-ui.card>
    </div>

    {{-- Hasil & catatan --}}
    @if ($batch->jumlah_aktual !== null)
        @php $selisih = (float) $batch->jumlah_aktual - (float) $batch->jumlah_target; @endphp
        <x-ui.card title="Hasil Produksi">
            <div class="grid gap-1 text-sm">
                <p>Output aktual: <span class="font-medium tabular-nums">{{ Format::angka($batch->jumlah_aktual) }} {{ $sat }}</span>
                    @if ($selisih != 0)
                        <span class="text-muted-foreground">({{ $selisih > 0 ? '+' : '' }}{{ Format::angka($selisih) }} {{ $sat }} dari target)</span>
                    @endif
                </p>
                @if ($batch->catatan_qc)
                    <p>Catatan QC: <span class="text-muted-foreground">{{ $batch->catatan_qc }}</span></p>
                @endif
            </div>
        </x-ui.card>
    @elseif ($batch->catatan_qc)
        <x-ui.card title="Catatan">
            <p class="text-sm text-muted-foreground">{{ $batch->catatan_qc }}</p>
        </x-ui.card>
    @endif

    {{-- Bahan terpakai (snapshot) --}}
    <x-ui.card title="Bahan Baku Terpakai"
               :description="'Snapshot saat batch dimulai, berdasarkan resep versi ' . $batch->bom->versi . '. Tidak berubah meskipun resep atau harga bahan berubah kemudian.'">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Bahan Baku</th>
                        <th class="py-2 pr-4 text-right font-medium">Jumlah Terpakai</th>
                        <th class="py-2 pr-4 text-right font-medium">Harga saat itu</th>
                        <th class="py-2 text-right font-medium">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($batch->materials as $m)
                        <tr>
                            <td class="py-2 pr-4">{{ $m->rawMaterial->nama_bahan }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ Format::angka($m->jumlah_terpakai) }} {{ $m->rawMaterial->satuan->value }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ Format::rupiah($m->harga_per_satuan) }}</td>
                            <td class="py-2 text-right tabular-nums">{{ Format::rupiah((float) $m->jumlah_terpakai * (float) $m->harga_per_satuan) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t">
                        <td colspan="3" class="py-3 pr-4 text-right font-medium">Total nilai bahan</td>
                        <td class="py-3 text-right font-semibold tabular-nums">{{ Format::rupiah($nilaiBahan) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" size="sm" wire:navigate
                         href="{{ route('riwayat-stok', ['ref' => 'produksi']) }}">Lihat pergerakan stok produksi →</x-ui.button>
        </x-slot:footer>
    </x-ui.card>

    {{-- Dialog: selesai & QC --}}
    <x-ui.dialog model="showSelesai" title="Selesaikan Batch & Catat QC"
                 description="Hanya jumlah yang lolos QC yang masuk ke stok produk jadi. Bahan untuk porsi reject tetap dianggap terpakai.">
        @php
            $l = is_numeric($lolos) ? (float) $lolos : 0;
            $r = is_numeric($reject) ? (float) $reject : 0;
            $akt = $l + $r;
            $previewQc = $akt <= 0 ? null : \App\Enums\StatusQc::dariHasil($l, $r);
        @endphp
        <form wire:submit="simpanSelesai" class="flex flex-col gap-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <x-ui.label for="lolos">Lolos QC ({{ $sat }})</x-ui.label>
                    <x-ui.input id="lolos" type="number" step="0.001" min="0" wire:model.live.debounce.300ms="lolos" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="reject">Reject QC ({{ $sat }})</x-ui.label>
                    <x-ui.input id="reject" type="number" step="0.001" min="0" wire:model.live.debounce.300ms="reject" />
                    <x-ui.error for="reject" />
                </div>
            </div>
            <x-ui.error for="lolos" />

            <div class="rounded-lg bg-muted/50 px-4 py-3 text-sm">
                Output aktual <span class="font-medium tabular-nums">{{ Format::angka($akt) }} {{ $sat }}</span>
                (target {{ Format::angka($batch->jumlah_target) }})
                @if ($previewQc)
                    · status QC <x-ui.badge :variant="$previewQc->badge()">{{ $previewQc->label() }}</x-ui.badge>
                @endif
            </div>

            <div class="grid gap-2">
                <x-ui.label for="tanggal_selesai">Tanggal Selesai</x-ui.label>
                <x-ui.input id="tanggal_selesai" type="date" wire:model="tanggal_selesai" />
                <x-ui.error for="tanggal_selesai" />
            </div>

            <div class="grid gap-2">
                <x-ui.label for="catatan_qc">Alasan Reject / Catatan QC {{ $r > 0 ? '' : '(opsional)' }}</x-ui.label>
                <x-ui.textarea id="catatan_qc" wire:model="catatan_qc" rows="2" placeholder="mis. Warna tidak merata, viskositas terlalu encer" />
                <x-ui.error for="catatan_qc" />
            </div>

            <div class="mt-2 flex justify-end gap-2">
                <x-ui.button variant="outline" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="simpanSelesai">Simpan Hasil</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>

    {{-- Dialog: batalkan --}}
    <x-ui.dialog model="showBatal" title="Batalkan Batch"
                 description="Bahan baku yang sudah dikurangi TIDAK dikembalikan (PRD B.3.7). Jika bahan ternyata belum terpakai, catat penyesuaian stok masuk secara manual.">
        <form wire:submit="simpanBatal" class="flex flex-col gap-4">
            <div class="grid gap-2">
                <x-ui.label for="alasan">Alasan Pembatalan</x-ui.label>
                <x-ui.textarea id="alasan" wire:model="alasan" rows="2" placeholder="mis. Mesin mixer rusak, adonan gagal total" />
                <x-ui.error for="alasan" />
            </div>
            <div class="mt-2 flex justify-end gap-2">
                <x-ui.button variant="outline" x-on:click="open = false">Kembali</x-ui.button>
                <x-ui.button type="submit" variant="destructive" wire:loading.attr="disabled" wire:target="simpanBatal">Batalkan Batch</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>
</div>