@use('App\Support\Format')
@use('App\Enums\KategoriBiaya')
<!DOCTYPE html>
<html><head><meta charset="utf-8">@include('ekspor._gaya')</head>
<body>
    <h1>Laporan Laba-Rugi</h1>
    <div class="meta">
        Koombaah — CV Khia Nawasena · Periode <b>{{ $label }}</b> ·
        {{ $snapshot ? 'Snapshot periode ditutup' : 'Real-time (periode belum ditutup)' }}
    </div>

    <table>
        <tr class="tebal"><td>Pendapatan Penjualan</td><td class="kanan">{{ Format::rupiah($lr['pendapatan']) }}</td></tr>
        <tr><td colspan="2" class="tebal">Harga Pokok Penjualan (HPP)</td></tr>
        @forelse ($lr['rincian_hpp'] as $h)
            <tr>
                <td class="indent">{{ $h['nama'] }} — {{ Format::angka($h['jumlah']) }} {{ $h['satuan'] }} × {{ $h['hpp_satuan'] !== null ? Format::rupiah($h['hpp_satuan']) : 'HPP belum ada' }}</td>
                <td class="kanan">({{ Format::rupiah($h['total_hpp']) }})</td>
            </tr>
        @empty
            <tr><td colspan="2" class="indent">Tidak ada penjualan.</td></tr>
        @endforelse
        <tr class="tebal"><td>Laba Kotor</td><td class="kanan">{{ Format::rupiah($lr['laba_kotor']) }}</td></tr>
        <tr><td colspan="2" class="tebal">Biaya Operasional</td></tr>
        @forelse ($lr['biaya'] as $kategori => $nominal)
            <tr><td class="indent">{{ KategoriBiaya::from($kategori)->label() }}</td><td class="kanan">({{ Format::rupiah($nominal) }})</td></tr>
        @empty
            <tr><td colspan="2" class="indent">Belum ada biaya operasional.</td></tr>
        @endforelse
        <tr class="tebal"><td>Total Biaya Operasional</td><td class="kanan">({{ Format::rupiah($lr['total_biaya']) }})</td></tr>
        <tr class="total">
            <td>{{ $lr['laba_rugi'] >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' }}</td>
            <td class="kanan {{ $lr['laba_rugi'] < 0 ? 'merah' : '' }}">{{ Format::rupiah($lr['laba_rugi']) }}</td>
        </tr>
    </table>

    @if ($lr['tanpa_hpp']->isNotEmpty())
        <p class="merah">Catatan: produk tanpa HPP (stok bukan dari batch produksi) dihitung Rp 0 — {{ $lr['tanpa_hpp']->implode(', ') }}.</p>
    @endif

    <div class="kaki">Dicetak {{ $dicetakPada }} oleh {{ $dicetakOleh }} · Sistem Internal Koombaah · Cash-basis accounting</div>
</body></html>