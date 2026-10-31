<?php

namespace App\Http\Controllers;

use App\Services\EksporService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EksporController
{
    public function labaRugi(Request $request, string $format, EksporService $ekspor)
    {
        $bulan = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('bulan'))
            ? Carbon::parse($request->query('bulan') . '-01')
            : now()->startOfMonth();

        return $format === 'pdf' ? $ekspor->labaRugiPdf($bulan) : $ekspor->labaRugiExcel($bulan);
    }

    public function stok(string $format, EksporService $ekspor)
    {
        return $format === 'pdf' ? $ekspor->stokPdf() : $ekspor->stokExcel();
    }
}