@use('App\Support\Format')

<div class="flex flex-col gap-6">
    {{-- Judul --}}
    <div>
        <a href="{{ route('bom') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">← Semua resep</a>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">{{ $product->nama_produk }}</h1>
                <p class="text-sm text-muted-foreground">{{ $product->kode_produk }} · takaran dihitung untuk 1 {{ $product->satuan_jual->value }} produk</p>
            </div>
            @can('bom.ubah')
                <x-ui.button wire:click="bukaEditor">{{ $aktif ? 'Ubah Resep (versi baru)' : 'Buat Resep' }}</x-ui.button>
            @else
                <x-ui.badge variant="outline">Hanya Owner yang dapat mengubah resep</x-ui.badge>
            @endcan
        </div>
    </div>

    {{-- Resep aktif --}}
    <x-ui.card :title="$aktif ? 'Resep Aktif — Versi ' . $aktif->versi : 'Resep Aktif'"
               :description="$aktif
                    ? 'Dibuat ' . $aktif->created_at->format('d/m/Y H:i') . ' oleh ' . $aktif->creator->name
                    : 'Produk ini belum memiliki resep, sehingga belum bisa diproduksi.'">
        @if ($aktif)
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 font-medium">Bahan Baku</th>
                        <th class="py-2 text-right font-medium">Takaran per 1 {{ $product->satuan_jual->value }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($aktif->details as $d)
                        <tr>
                            <td class="py-2">
                                <span class="font-mono text-xs text-muted-foreground">{{ $d->rawMaterial->kode_bahan }}</span>
                                {{ $d->rawMaterial->nama_bahan }}
                            </td>
                            <td class="py-2 text-right tabular-nums">{{ Format::angka($d->takaran) }} {{ $d->satuan }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-ui.card>

    {{-- Riwayat versi (user story 5) --}}
    @if ($versiList->isNotEmpty())
        <x-ui.card title="Riwayat Versi" description="Versi lama tidak pernah diubah atau dihapus — dipakai sebagai acuan riwayat batch produksi.">
            <div class="flex flex-col divide-y">
                @foreach ($versiList as $v)
                    <details wire:key="versi-{{ $v->id }}" class="py-3" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer list-none flex-wrap items-center gap-2 text-sm">
                            <span class="font-medium">Versi {{ $v->versi }}</span>
                            <x-ui.badge :variant="$v->status->value === 'aktif' ? 'default' : 'outline'">{{ $v->status->label() }}</x-ui.badge>
                            <span class="text-muted-foreground">{{ $v->created_at->format('d/m/Y H:i') }} · {{ $v->creator->name }}</span>
                        </summary>
                        @if ($v->catatan_perubahan)
                            <p class="mt-2 text-sm text-muted-foreground">Alasan: {{ $v->catatan_perubahan }}</p>
                        @endif
                        <ul class="mt-2 grid gap-1 text-sm">
                            @foreach ($v->details as $d)
                                <li class="flex justify-between gap-4">
                                    <span>{{ $d->rawMaterial->nama_bahan }}</span>
                                    <span class="tabular-nums">{{ Format::angka($d->takaran) }} {{ $d->satuan }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    {{-- Editor resep (hanya Owner yang bisa membukanya) --}}
    <x-ui.dialog :title="$aktif ? 'Ubah Resep → Versi ' . ($aktif->versi + 1) : 'Buat Resep — Versi 1'"
                 :description="'Isi takaran bahan untuk menghasilkan 1 ' . $product->satuan_jual->value . ' ' . $product->nama_produk . '.'">
        <form wire:submit="simpan" class="flex flex-col gap-4">
            <div class="flex flex-col gap-3">
                @foreach ($rows as $i => $row)
                    @php $satuan = $bahanList->firstWhere('id', (int) $row['raw_material_id'])?->satuan->value; @endphp
                    <div wire:key="baris-{{ $i }}" class="flex items-start gap-2">
                        <div class="grid min-w-0 flex-1 gap-1">
                            <x-ui.select wire:model.live="rows.{{ $i }}.raw_material_id">
                                <option value="">Pilih bahan baku</option>
                                @foreach ($bahanList as $b)
                                    <option value="{{ $b->id }}">{{ $b->kode_bahan }} — {{ $b->nama_bahan }}</option>
                                @endforeach
                            </x-ui.select>
                            <x-ui.error for="rows.{{ $i }}.raw_material_id" />
                        </div>
                        <div class="grid w-36 gap-1">
                            <div class="flex items-center gap-1">
                                <x-ui.input type="number" step="0.001" min="0" wire:model="rows.{{ $i }}.takaran" placeholder="0" />
                                <span class="w-10 shrink-0 text-xs text-muted-foreground">{{ $satuan }}</span>
                            </div>
                            <x-ui.error for="rows.{{ $i }}.takaran" />
                        </div>
                        <x-ui.button variant="ghost" size="icon" wire:click="hapusBaris({{ $i }})"
                                     :disabled="count($rows) === 1" aria-label="Hapus baris">✕</x-ui.button>
                    </div>
                @endforeach

                <x-ui.error for="rows" />
                <div>
                    <x-ui.button variant="outline" size="sm" wire:click="tambahBaris">+ Tambah Bahan</x-ui.button>
                </div>
            </div>

            @if ($aktif)
                <div class="grid gap-2">
                    <x-ui.label for="catatan_perubahan">Alasan Perubahan</x-ui.label>
                    <x-ui.textarea id="catatan_perubahan" wire:model="catatan_perubahan" rows="2"
                                   placeholder="mis. Takaran SLES dinaikkan agar busa lebih banyak" />
                    <x-ui.error for="catatan_perubahan" />
                </div>
            @endif

            <div class="mt-2 flex justify-end gap-2">
                <x-ui.button variant="outline" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="simpan">
                    {{ $aktif ? 'Simpan sebagai Versi ' . ($aktif->versi + 1) : 'Simpan Resep' }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.dialog>
</div>