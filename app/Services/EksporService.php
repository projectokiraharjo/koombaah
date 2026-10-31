<?php

namespace App\Services;

use App\Enums\KategoriBiaya;
use App\Models\FinishedProduct;
use App\Models\RawMaterial;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** PRD B.8.7: ekspor minimal Laba-Rugi & Ringkasan Stok ke PDF/Excel */
class EksporService
{
    private const FORMAT_RUPIAH = '"Rp" #,##0;[Red]-"Rp" #,##0';

    public function __construct(private LaporanKeuanganService $laporan)
    {
    }

    // ───────── Laba-Rugi ─────────

    public function labaRugiPdf(Carbon $bulan): Response
    {
        return $this->pdf('ekspor.laba-rugi', $this->dataLabaRugi($bulan), 'laba-rugi-' . $bulan->format('Y-m'));
    }

    public function labaRugiExcel(Carbon $bulan): StreamedResponse
    {
        $d = $this->dataLabaRugi($bulan);
        $lr = $d['lr'];

        $baris = [
            ['Laporan Laba-Rugi — Koombaah (CV Khia Nawasena)'],
            ['Periode', $d['label']],
            ['Sumber angka', $d['snapshot'] ? 'Snapshot periode ditutup' : 'Real-time'],
            [],
            ['Pendapatan Penjualan', $lr['pendapatan']],
            [],
            ['Harga Pokok Penjualan (HPP)'],
        ];
        foreach ($lr['rincian_hpp'] as $h) {
            $baris[] = ["   {$h['nama']} — {$h['jumlah']} {$h['satuan']}", -$h['total_hpp']];
        }
        $baris[] = ['Total HPP', -$lr['total_hpp']];
        $baris[] = ['Laba Kotor', $lr['laba_kotor']];
        $baris[] = [];
        $baris[] = ['Biaya Operasional'];
        foreach ($lr['biaya'] as $kategori => $nominal) {
            $baris[] = ['   ' . KategoriBiaya::from($kategori)->label(), -$nominal];
        }
        $baris[] = ['Total Biaya Operasional', -$lr['total_biaya']];
        $baris[] = [];
        $baris[] = [$lr['laba_rugi'] >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH', $lr['laba_rugi']];

        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet()->setTitle('Laba-Rugi');
        $sheet->fromArray($baris, null, 'A1', true);
        $sheet->getStyle('B5:B' . count($baris))->getNumberFormat()->setFormatCode(self::FORMAT_RUPIAH);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A' . count($baris) . ':B' . count($baris))->getFont()->setBold(true);
        $this->rapikan($sheet, 2);

        return $this->xlsx($ss, 'laba-rugi-' . $bulan->format('Y-m'));
    }

    private function dataLabaRugi(Carbon $bulan): array
    {
        return [
            'lr' => $this->laporan->hitungLabaRugi($bulan),
            'snapshot' => $this->laporan->ringkasan($bulan)['snapshot'],
            'label' => $bulan->translatedFormat('F Y'),
        ];
    }

    // ───────── Ringkasan Stok ─────────

    public function stokPdf(): Response
    {
        return $this->pdf('ekspor.stok', $this->dataStok(), 'ringkasan-stok-' . now()->format('Y-m-d'));
    }

    public function stokExcel(): StreamedResponse
    {
        $d = $this->dataStok();
        $ss = new Spreadsheet();

        // Sheet 1: Bahan Baku
        $s1 = $ss->getActiveSheet()->setTitle('Bahan Baku');
        $s1->fromArray([['Kode', 'Nama Bahan', 'Satuan', 'Stok', 'Stok Minimum', 'Kondisi', 'Harga Terakhir', 'Nilai Stok', 'Supplier Utama']]);
        $s1->fromArray($d['bahan']->map(fn ($b) => [
            $b->kode_bahan, $b->nama_bahan, $b->satuan->value, (float) $b->stok_saat_ini, (float) $b->stok_minimum,
            $this->kondisi($b), (float) $b->harga_per_satuan_terakhir,
            (float) $b->stok_saat_ini * (float) $b->harga_per_satuan_terakhir, $b->supplierUtama?->nama_supplier ?? '-',
        ])->all(), null, 'A2', true);
        $n = $d['bahan']->count() + 1;
        $s1->getStyle("G2:H{$n}")->getNumberFormat()->setFormatCode(self::FORMAT_RUPIAH);
        $s1->getStyle('A1:I1')->getFont()->setBold(true);
        $this->rapikan($s1, 9);

        // Sheet 2: Produk Jadi
        $s2 = $ss->createSheet()->setTitle('Produk Jadi');
        $s2->fromArray([['Kode', 'Nama Produk', 'Kategori', 'Satuan', 'Stok', 'Stok Minimum', 'Kondisi', 'Harga Jual']]);
        $s2->fromArray($d['produk']->map(fn ($p) => [
            $p->kode_produk, $p->nama_produk, $p->kategori, $p->satuan_jual->value, (float) $p->stok_saat_ini,
            (float) $p->stok_minimum, $this->kondisi($p), (float) $p->harga_jual_per_satuan,
        ])->all(), null, 'A2', true);
        $n = $d['produk']->count() + 1;
        $s2->getStyle("H2:H{$n}")->getNumberFormat()->setFormatCode(self::FORMAT_RUPIAH);
        $s2->getStyle('A1:H1')->getFont()->setBold(true);
        $this->rapikan($s2, 8);

        $ss->setActiveSheetIndex(0);

        return $this->xlsx($ss, 'ringkasan-stok-' . now()->format('Y-m-d'));
    }

    private function dataStok(): array
    {
        return [
            'bahan' => RawMaterial::with('supplierUtama:id,nama_supplier')->orderBy('kode_bahan')->get(),
            'produk' => FinishedProduct::orderBy('kode_produk')->get(),
        ];
    }

    private function kondisi(RawMaterial|FinishedProduct $item): string
    {
        return match (true) {
            $item->status->value === 'nonaktif' => 'Nonaktif',
            $item->isLowStock() => 'Menipis',
            default => 'Aman',
        };
    }

    // ───────── Helper ─────────

    private function pdf(string $view, array $data, string $namaFile): Response
    {
        $opsi = new Options();
        $opsi->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($opsi);
        $pdf->loadHtml(view($view, $data + [
            'dicetakOleh' => auth()->user()->name,
            'dicetakPada' => now()->format('d/m/Y H:i'),
        ])->render());
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$namaFile}.pdf\"",
        ]);
    }

    private function xlsx(Spreadsheet $ss, string $namaFile): StreamedResponse
    {
        return response()->streamDownload(
            fn () => (new Xlsx($ss))->save('php://output'),
            "{$namaFile}.xlsx",
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    private function rapikan(Worksheet $sheet, int $jumlahKolom): void
    {
        foreach (range(1, $jumlahKolom) as $i) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
    }
}