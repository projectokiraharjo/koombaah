@use('App\Support\Format')
@use('App\Enums\WilayahRing')
@use('App\Enums\StatusQc')

<div class="flex flex-col gap-8">
    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm text-muted-foreground">Halo, {{ $user->name }} · <x-ui.badge variant="secondary">{{ $user->role->label() }}</x-ui.badge></p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Dashboard — {{ $labelBulan }}</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.input type="month" wire:model.live="bulan" class="w-44" />
            @can('keuangan')
                <x-ui.button variant="outline" size="sm" href="{{ route('ekspor.laba-rugi', ['format' => 'pdf', 'bulan' => $bulan]) }}">Laba-Rugi PDF</x-ui.button>
                <x-ui.button variant="outline" size="sm" href="{{ route('ekspor.laba-rugi', ['format' => 'xlsx', 'bulan' => $bulan]) }}">Laba-Rugi Excel</x-ui.button>
            @endcan
            @can('ringkasan.stok')
                <x-ui.button variant="outline" size="sm" href="{{ route('ekspor.stok', ['format' => 'pdf']) }}">Stok PDF</x-ui.button>
                <x-ui.button variant="outline" size="sm" href="{{ route('ekspor.stok', ['format' => 'xlsx']) }}">Stok Excel</x-ui.button>
            @endcan
        </div>
    </div>

    {{-- Perlu tindakan (US 6) --}}
    @if (isset($d['penjualan']) || isset($d['produksi']))
        @php
            $tindakan = array_filter([
                isset($d['penjualan']) && $d['penjualan']['order_baru'] ? [$d['penjualan']['order_baru'] . ' order baru belum dikonfirmasi', 'order', 'sales'] : null,
                isset($d['penjualan']) && $d['penjualan']['siap_kirim'] ? [$d['penjualan']['siap_kirim'] . ' order siap kirim belum dijadwalkan', 'distribusi', 'distribusi'] : null,
                isset($d['produksi']) && $d['produksi']['berjalan_sekarang'] ? [$d['produksi']['berjalan_sekarang'] . ' batch produksi sedang berjalan', 'produksi', 'produksi'] : null,
                isset($d['stok']) && $d['stok']['menipis']['total'] ? [$d['stok']['menipis']['total'] . ' item stok menipis', 'peringatan-stok', 'inventory'] : null,
            ]);
        @endphp
        @if ($tindakan)
            <div class="flex flex-wrap gap-2">
                @foreach ($tindakan as [$teks, $route, $gate])
                    @can($gate)
                        <a href="{{ route($route) }}" wire:navigate class="rounded-full border bg-card px-3 py-1 text-sm hover:bg-accent">⚑ {{ $teks }}</a>
                    @else
                        <span class="rounded-full border bg-card px-3 py-1 text-sm">⚑ {{ $teks }}</span>
                    @endcan
                @endforeach
            </div>
        @endif
    @endif

    {{-- ═════════ Ringkasan Keuangan ═════════ --}}
    @isset($d['keuangan'])
        @php $k = $d['keuangan']; $r = $k['ringkasan']; @endphp
        <section class="flex flex-col gap-4">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold">Ringkasan Keuangan</h2>
                <x-ui.badge :variant="$r['snapshot'] ? 'secondary' : 'outline'">{{ $r['snapshot'] ? 'Snapshot periode ditutup' : 'Real-time' }}</x-ui.badge>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([['Pendapatan', $r['pendapatan']], ['HPP', $r['hpp']], ['Biaya Operasional', $r['biaya']]] as [$label, $nilai])
                    <x-ui.card>
                        <p class="text-xs text-muted-foreground">{{ $label }}</p>
                        <p class="text-xl font-semibold tabular-nums">{{ Format::rupiah($nilai) }}</p>
                    </x-ui.card>
                @endforeach
                <x-ui.card>
                    <p class="text-xs text-muted-foreground">{{ $r['laba_rugi'] >= 0 ? 'Laba Bersih' : 'Rugi Bersih' }}</p>
                    <p @class(['text-xl font-semibold tabular-nums', 'text-destructive' => $r['laba_rugi'] < 0])>{{ Format::rupiah($r['laba_rugi']) }}</p>
                </x-ui.card>
            </div>
            <div class="grid gap-4 lg:grid-cols-3">
                <x-ui.card title="Arus Kas" class="lg:col-span-1">
                    <dl class="grid gap-2 text-sm">
                        <div class="flex justify-between"><dt>Kas masuk</dt><dd class="tabular-nums">{{ Format::rupiah($k['kas_masuk']) }}</dd></div>
                        <div class="flex justify-between"><dt>Kas keluar</dt><dd class="tabular-nums">{{ Format::rupiah($k['kas_keluar']) }}</dd></div>
                        <div class="flex justify-between border-t pt-2 font-medium">
                            <dt>Arus kas bersih</dt>
                            <dd @class(['tabular-nums', 'text-destructive' => $k['kas_bersih'] < 0])>{{ Format::rupiah($k['kas_bersih']) }}</dd>
                        </div>
                    </dl>
                    <x-slot:footer>
                        <x-ui.button variant="ghost" size="sm" href="{{ route('arus-kas', ['bulan' => $bulan]) }}" wire:navigate>Detail arus kas →</x-ui.button>
                    </x-slot:footer>
                </x-ui.card>

                {{-- Grafik laba-rugi 6 bulan (US 3) --}}
                <x-ui.card title="Laba-Rugi 6 Bulan Terakhir" class="lg:col-span-2">
                    @php $maks = max(1, $k['tren']->max(fn ($t) => abs($t['laba_rugi']))); @endphp
                    <div class="grid h-48 grid-cols-6 gap-3">
                        @foreach ($k['tren'] as $t)
                            <div class="flex h-full flex-col items-center gap-1">
                                <span @class(['text-[11px] tabular-nums', 'text-destructive' => $t['laba_rugi'] < 0, 'text-muted-foreground' => $t['laba_rugi'] >= 0])>{{ Format::ringkas($t['laba_rugi']) }}</span>
                                <div class="flex w-full flex-1 items-end">
                                    <div @class(['w-full rounded-t-md', 'bg-destructive' => $t['laba_rugi'] < 0, 'bg-primary' => $t['laba_rugi'] >= 0])
                                         style="height: {{ max(2, abs($t['laba_rugi']) / $maks * 100) }}%"></div>
                                </div>
                                <span class="text-xs text-muted-foreground">{{ $t['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <x-slot:footer>
                        <x-ui.button variant="ghost" size="sm" href="{{ route('keuangan', ['bulan' => $bulan]) }}" wire:navigate>Laporan laba-rugi lengkap →</x-ui.button>
                    </x-slot:footer>
                </x-ui.card>
            </div>
        </section>
    @endisset

    {{-- ═════════ Ringkasan Stok ═════════ --}}
    @isset($d['stok'])
        @php $s = $d['stok']; @endphp
        <section class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold">Ringkasan Stok <span class="text-sm font-normal text-muted-foreground">(kondisi saat ini)</span></h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.card>
                    <p class="text-xs text-muted-foreground">Bahan baku menipis</p>
                    <p @class(['text-2xl font-semibold tabular-nums', 'text-destructive' => $s['menipis']['bahan_baku'] > 0])>
                        {{ $s['menipis']['bahan_baku'] }} <span class="text-sm font-normal text-muted-foreground">/ {{ $s['total_bahan'] }} item</span>
                    </p>
                </x-ui.card>
                <x-ui.card>
                    <p class="text-xs text-muted-foreground">Produk jadi menipis</p>
                    <p @class(['text-2xl font-semibold tabular-nums', 'text-destructive' => $s['menipis']['produk_jadi'] > 0])>
                        {{ $s['menipis']['produk_jadi'] }} <span class="text-sm font-normal text-muted-foreground">/ {{ $s['total_produk'] }} item</span>
                    </p>
                </x-ui.card>
                <x-ui.card>
                    <p class="text-xs text-muted-foreground">Nilai stok bahan baku</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ Format::rupiah($s['nilai_bahan']) }}</p>
                </x-ui.card>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ([['Bahan Baku Paling Kritis', $s['kritis_bahan'], 'nama_bahan', 'satuan'], ['Produk Jadi Paling Kritis', $s['kritis_produk'], 'nama_produk', 'satuan_jual']] as [$judul, $daftar, $kolNama, $kolSatuan])
                    <x-ui.card :title="$judul">
                        @if ($daftar->isEmpty())
                            <p class="text-sm text-muted-foreground">Semua stok aman ✓</p>
                        @else
                            <ul class="grid gap-2 text-sm">
                                @foreach ($daftar as $item)
                                    <li class="flex items-center justify-between gap-3">
                                        <span class="truncate">{{ $item->$kolNama }}</span>
                                        <span class="shrink-0 tabular-nums text-destructive">{{ Format::angka($item->stok_saat_ini) }} / {{ Format::angka($item->stok_minimum) }} {{ $item->$kolSatuan->value }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @can('inventory')
                            <x-slot:footer>
                                <x-ui.button variant="ghost" size="sm" href="{{ route('peringatan-stok') }}" wire:navigate>Lihat peringatan stok →</x-ui.button>
                            </x-slot:footer>
                        @endcan
                    </x-ui.card>
                @endforeach
            </div>
        </section>
    @endisset

    {{-- ═════════ Ringkasan Produksi & Penjualan ═════════ --}}
    @if (isset($d['produksi']) || isset($d['penjualan']))
        <section class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold">Ringkasan Produksi & Penjualan</h2>
            <div class="grid gap-4 lg:grid-cols-2">
                @isset($d['produksi'])
                    @php $p = $d['produksi']; @endphp
                    <x-ui.card title="Produksi" :description="'Batch yang dimulai di ' . $labelBulan">
                        <div class="mb-4 flex flex-wrap gap-2 text-sm">
                            <x-ui.badge>{{ $p['per_status']['selesai'] ?? 0 }} selesai</x-ui.badge>
                            <x-ui.badge variant="outline">{{ $p['per_status']['sedang_berjalan'] ?? 0 }} berjalan</x-ui.badge>
                            <x-ui.badge variant="outline">{{ $p['per_status']['dibatalkan'] ?? 0 }} dibatalkan</x-ui.badge>
                            <span class="text-muted-foreground">·</span>
                            @foreach (StatusQc::cases() as $qc)
                                <x-ui.badge :variant="$qc->badge()">{{ $p['per_qc'][$qc->value] ?? 0 }} {{ $qc->label() }}</x-ui.badge>
                            @endforeach
                        </div>
                        @if ($p['per_produk']->isEmpty())
                            <p class="text-sm text-muted-foreground">Belum ada batch selesai.</p>
                        @else
                            <table class="w-full text-sm">
                                <thead class="border-b text-left text-muted-foreground">
                                    <tr><th class="py-1 font-medium">Produk</th><th class="py-1 text-right font-medium">Lolos QC</th><th class="py-1 text-right font-medium">Reject</th></tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($p['per_produk'] as $r)
                                        @php $persen = (float) $r->aktual > 0 ? (float) $r->reject / (float) $r->aktual * 100 : 0; @endphp
                                        <tr>
                                            <td class="py-1.5">{{ $r->nama_produk }} <span class="text-xs text-muted-foreground">({{ $r->batch }} batch)</span></td>
                                            <td class="py-1.5 text-right tabular-nums">{{ Format::angka($r->lolos) }} {{ $r->satuan_jual }}</td>
                                            <td @class(['py-1.5 text-right tabular-nums', 'text-destructive' => $persen >= 10])>{{ Format::angka($persen, 1) }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </x-ui.card>
                @endisset

                @isset($d['penjualan'])
                    @php $j = $d['penjualan']; @endphp
                    <x-ui.card title="Penjualan" :description="'Order terkonfirmasi di ' . $labelBulan">
                        <div class="mb-4 grid grid-cols-3 gap-3">
                            <div><p class="text-xs text-muted-foreground">Order</p><p class="text-xl font-semibold tabular-nums">{{ $j['jumlah_order'] }}</p></div>
                            <div><p class="text-xs text-muted-foreground">Nilai</p><p class="text-xl font-semibold tabular-nums">{{ Format::ringkas($j['nilai']) }}</p></div>
                            <div><p class="text-xs text-muted-foreground">Dibatalkan</p><p class="text-xl font-semibold tabular-nums">{{ $j['dibatalkan'] }}</p></div>
                        </div>
                        <p class="mb-1 text-xs font-medium text-muted-foreground">Produk terlaris (nilai)</p>
                        <ul class="mb-4 grid gap-1 text-sm">
                            @forelse ($j['terlaris'] as $t)
                                <li class="flex justify-between gap-3"><span class="truncate">{{ $t->nama_produk }}</span><span class="tabular-nums">{{ Format::rupiah($t->nilai) }}</span></li>
                            @empty
                                <li class="text-muted-foreground">Belum ada penjualan.</li>
                            @endforelse
                        </ul>
                        <p class="mb-1 text-xs font-medium text-muted-foreground">Mitra paling aktif</p>
                        <ul class="grid gap-1 text-sm">
                            @forelse ($j['mitra_aktif'] as $m)
                                <li class="flex justify-between gap-3">
                                    <span class="truncate">{{ $m->nama_usaha }} <span class="text-xs text-muted-foreground">{{ WilayahRing::from($m->wilayah_ring)->label() }}</span></span>
                                    <span class="shrink-0 tabular-nums">{{ $m->jumlah }} order</span>
                                </li>
                            @empty
                                <li class="text-muted-foreground">Belum ada order.</li>
                            @endforelse
                        </ul>
                    </x-ui.card>
                @endisset
            </div>
        </section>
    @endif

    {{-- ═════════ TAHAP 2 ═════════ --}}
    @if (isset($d['distribusi']) || isset($d['sdm']) || isset($d['supplier']))
        <section class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold">Distribusi, SDM & Supplier</h2>
            <div class="grid gap-4 lg:grid-cols-2">
                @isset($d['distribusi'])
                    @php $ds = $d['distribusi']; @endphp
                    <x-ui.card title="Ringkasan Distribusi" :description="$ds['jumlah_jadwal'] . ' jadwal · estimasi biaya ' . Format::rupiah($ds['biaya'])">
                        @if ($ds['per_ring']->isEmpty())
                            <p class="text-sm text-muted-foreground">Belum ada pengiriman di bulan ini.</p>
                        @else
                            <table class="w-full text-sm">
                                <thead class="border-b text-left text-muted-foreground">
                                    <tr>
                                        <th class="py-1 font-medium">Wilayah</th>
                                        <th class="py-1 text-right font-medium">Terkirim</th>
                                        <th class="py-1 text-right font-medium">Gagal</th>
                                        <th class="py-1 text-right font-medium">Rata-rata</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach ($ds['per_ring'] as $r)
                                        @php $jam = $r->rata_jam !== null ? (float) $r->rata_jam : null; @endphp
                                        <tr>
                                            <td class="py-1.5">{{ WilayahRing::from($r->wilayah_ring)->label() }}</td>
                                            <td class="py-1.5 text-right tabular-nums">{{ $r->terkirim }}</td>
                                            <td @class(['py-1.5 text-right tabular-nums', 'text-destructive' => $r->gagal > 0])>{{ $r->gagal }}</td>
                                            <td class="py-1.5 text-right tabular-nums">{{ $jam === null ? '–' : ($jam < 24 ? Format::angka($jam, 1) . ' jam' : Format::angka($jam / 24, 1) . ' hari') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </x-ui.card>
                @endisset

                @isset($d['sdm'])
                    @php $sd = $d['sdm']; @endphp
                    <x-ui.card title="Ringkasan SDM" description="Tanpa rincian gaji per karyawan.">
                        <div class="grid grid-cols-3 gap-3">
                            <div><p class="text-xs text-muted-foreground">Karyawan aktif</p><p class="text-2xl font-semibold tabular-nums">{{ $sd['aktif'] }}</p></div>
                            <div><p class="text-xs text-muted-foreground">Resign bulan ini</p><p class="text-2xl font-semibold tabular-nums">{{ $sd['resign_bulan_ini'] }}</p></div>
                            <div>
                                <p class="text-xs text-muted-foreground">Payroll {{ $labelBulan }}</p>
                                @if ($sd['payroll'])
                                    <x-ui.badge :variant="$sd['payroll']->status->badge()" class="mt-1">{{ $sd['payroll']->status->label() }}</x-ui.badge>
                                @else
                                    <x-ui.badge variant="outline" class="mt-1">Belum diproses</x-ui.badge>
                                @endif
                            </div>
                        </div>
                        <x-slot:footer>
                            <x-ui.button variant="ghost" size="sm" href="{{ route('payroll') }}" wire:navigate>Ke Payroll →</x-ui.button>
                        </x-slot:footer>
                    </x-ui.card>
                @endisset

                @isset($d['supplier'])
                    <x-ui.card title="Perbandingan Harga Supplier" description="Harga beli terakhir dari tiap supplier, untuk bahan yang dibeli dari ≥ 2 supplier." class="lg:col-span-2">
                        @if ($d['supplier']->isEmpty())
                            <p class="text-sm text-muted-foreground">Belum ada bahan baku yang dibeli dari lebih dari satu supplier.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="border-b text-left text-muted-foreground">
                                        <tr>
                                            <th class="py-1 pr-4 font-medium">Bahan Baku</th>
                                            <th class="py-1 pr-4 font-medium">Termurah</th>
                                            <th class="py-1 pr-4 font-medium">Termahal</th>
                                            <th class="py-1 text-right font-medium">Selisih</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach ($d['supplier'] as $r)
                                            <tr>
                                                <td class="py-1.5 pr-4">
                                                    @can('purchasing')
                                                        <a href="{{ route('harga-supplier', ['bahan' => $r['bahan_id']]) }}" wire:navigate class="hover:underline">{{ $r['bahan'] }}</a>
                                                    @else
                                                        {{ $r['bahan'] }}
                                                    @endcan
                                                    <span class="text-xs text-muted-foreground">({{ $r['jumlah_supplier'] }} supplier)</span>
                                                </td>
                                                <td class="py-1.5 pr-4">{{ $r['termurah'] }} <span class="tabular-nums text-muted-foreground">{{ Format::rupiah($r['harga_murah']) }}/{{ $r['satuan'] }}</span></td>
                                                <td class="py-1.5 pr-4">{{ $r['termahal'] }} <span class="tabular-nums text-muted-foreground">{{ Format::rupiah($r['harga_mahal']) }}/{{ $r['satuan'] }}</span></td>
                                                <td class="py-1.5 text-right font-medium tabular-nums">{{ Format::angka($r['selisih_persen'], 1) }}%</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </x-ui.card>
                @endisset
            </div>
        </section>
    @endif
</div>