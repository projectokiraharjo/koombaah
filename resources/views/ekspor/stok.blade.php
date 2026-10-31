@use('App\Support\Format')
<!DOCTYPE html>
<html><head><meta charset="utf-8">@include('ekspor._gaya')</head>
<body>
    <h1>Ringkasan Stok</h1>
    <div class="meta">Koombaah — CV Khia Nawasena · Kondisi per {{ $dicetakPada }}</div>

    <h3>Bahan Baku</h3>
    <table>
        <tr><th>Kode</th><th>Nama Bahan</th><th class="kanan">Stok</th><th class="kanan">Minimum</th><th>Kondisi</th><th class="kanan">Nilai Stok</th></tr>
        @foreach ($bahan as $b)
            <tr>
                <td>{{ $b->kode_bahan }}</td>
                <td>{{ $b->nama_bahan }}</td>
                <td class="kanan">{{ Format::angka($b->stok_saat_ini) }} {{ $b->satuan->value }}</td>
                <td class="kanan">{{ Format::angka($b->stok_minimum) }}</td>
                <td class="{{ $b->isLowStock() ? 'merah tebal' : '' }}">{{ $b->status->value === 'nonaktif' ? 'Nonaktif' : ($b->isLowStock() ? 'Menipis' : 'Aman') }}</td>
                <td class="kanan">{{ Format::rupiah((float) $b->stok_saat_ini * (float) $b->harga_per_satuan_terakhir) }}</td>
            </tr>
        @endforeach
    </table>

    <h3>Produk Jadi</h3>
    <table>
        <tr><th>Kode</th><th>Nama Produk</th><th>Kategori</th><th class="kanan">Stok</th><th class="kanan">Minimum</th><th>Kondisi</th></tr>
        @foreach ($produk as $p)
            <tr>
                <td>{{ $p->kode_produk }}</td>
                <td>{{ $p->nama_produk }}</td>
                <td>{{ $p->kategori }}</td>
                <td class="kanan">{{ Format::angka($p->stok_saat_ini) }} {{ $p->satuan_jual->value }}</td>
                <td class="kanan">{{ Format::angka($p->stok_minimum) }}</td>
                <td class="{{ $p->isLowStock() ? 'merah tebal' : '' }}">{{ $p->status->value === 'nonaktif' ? 'Nonaktif' : ($p->isLowStock() ? 'Menipis' : 'Aman') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="kaki">Dicetak {{ $dicetakPada }} oleh {{ $dicetakOleh }} · Sistem Internal Koombaah</div>
</body></html>