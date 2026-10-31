<?php

namespace App\Services;

use App\Enums\StatusPayroll;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\PayrollRun;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(private FinanceService $keuangan)
    {
    }

    /** B.7.5: total_diterima = pokok + tunjangan + bonus + lembur + THR − potongan */
    public static function hitungTotal(array $k): float
    {
        return round(
            (float) $k['gaji_pokok'] + (float) $k['tunjangan'] + (float) $k['bonus_produksi']
            + (float) $k['lembur'] + (float) $k['thr'] - (float) $k['potongan'], 2);
    }

    private function pastikanOwner(User $user): void
    {
        if (! $user->isOwner()) {
            throw new DomainException('Hanya Owner yang dapat mengelola data gaji.');
        }
    }

    // ───────────── Komponen gaji tetap ─────────────

    public function tambahKomponen(Employee $karyawan, array $data, User $oleh): EmployeeSalaryComponent
    {
        $this->pastikanOwner($oleh);

        if ($data['berlaku_sejak'] < $karyawan->tanggal_mulai_kerja->toDateString()) {
            throw new DomainException('Tanggal berlaku tidak boleh sebelum tanggal mulai kerja.');
        }

        return $karyawan->salaryComponents()->create($data);
    }

    public function hapusKomponen(EmployeeSalaryComponent $komponen, User $oleh): void
    {
        $this->pastikanOwner($oleh);

        $sudahDipakai = PayrollRun::where('status', StatusPayroll::Final->value)
            ->where('periode_bulan', '>=', $komponen->berlaku_sejak->copy()->startOfMonth()->toDateString())
            ->whereHas('details', fn ($q) => $q->where('employee_id', $komponen->employee_id))
            ->exists();

        if ($sudahDipakai) {
            throw new DomainException('Komponen ini sudah dipakai payroll final. Untuk perubahan gaji, tambahkan komponen baru.');
        }

        $komponen->delete();
    }

    // ───────────── Payroll run ─────────────

    /**
     * Buat draft payroll, atau muat ulang draft yang ada (B.7.4 langkah 2).
     * Input manual (bonus, lembur, THR, potongan) yang sudah diisi tetap dipertahankan.
     *
     * @return array{0: PayrollRun, 1: array<int, string>} [payroll run, nama karyawan tanpa komponen gaji]
     */
    public function siapkanDraft(Carbon $bulan, User $oleh): array
    {
        $this->pastikanOwner($oleh);
        $awal = $bulan->copy()->startOfMonth();
        $akhir = $bulan->copy()->endOfMonth();

        return DB::transaction(function () use ($awal, $akhir, $oleh) {
            $run = PayrollRun::where('periode_bulan', $awal->toDateString())->lockForUpdate()->first();

            if ($run?->isFinal()) {
                throw new DomainException('Payroll periode ini sudah final. Koreksi dilakukan di payroll bulan berikutnya.');
            }

            $run ??= PayrollRun::create([
                'periode_bulan' => $awal->toDateString(),
                'status' => StatusPayroll::Draft,
                'total_gaji_agregat' => 0,
                'diproses_oleh' => $oleh->id,
                'diproses_pada' => now(),
            ]);

            $lama = $run->details()->get()->keyBy('employee_id');
            $ikut = [];
            $tanpaKomponen = [];

            foreach (Employee::ikutPayroll($awal, $akhir)->orderBy('kode_karyawan')->get() as $karyawan) {
                $komponen = $karyawan->komponenBerlaku($akhir);
                if (! $komponen) {
                    $tanpaKomponen[] = $karyawan->nama_lengkap;
                    continue;
                }

                $d = $lama->get($karyawan->id);
                $data = [
                    'gaji_pokok' => $komponen->gaji_pokok, // snapshot
                    'tunjangan' => $komponen->tunjangan,
                    'bonus_produksi' => $d?->bonus_produksi ?? 0,
                    'lembur' => $d?->lembur ?? 0,
                    'thr' => $d?->thr ?? 0,
                    'potongan' => $d?->potongan ?? 0,
                ];
                if (self::hitungTotal($data) < 0) {
                    $data['potongan'] = 0; // gaji turun -> potongan lama tidak lagi valid
                }
                $data['total_diterima'] = self::hitungTotal($data);

                $run->details()->updateOrCreate(['employee_id' => $karyawan->id], $data);
                $ikut[] = $karyawan->id;
            }

            // Karyawan yang tidak lagi memenuhi syarat dikeluarkan dari draft
            $run->details()->whereNotIn('employee_id', $ikut ?: [0])->delete();

            $run->update([
                'total_gaji_agregat' => $run->details()->sum('total_diterima'),
                'diproses_oleh' => $oleh->id,
                'diproses_pada' => now(),
            ]);

            return [$run, $tanpaKomponen];
        });
    }

    /**
     * Simpan bonus produksi, lembur, THR, potongan (diisi manual oleh Owner — B.7.7).
     *
     * @param  array<int|string, array{bonus_produksi: mixed, lembur: mixed, thr: mixed, potongan: mixed}>  $input
     */
    public function simpanPenyesuaian(PayrollRun $run, array $input, User $oleh): void
    {
        $this->pastikanOwner($oleh);

        DB::transaction(function () use ($run, $input) {
            $run = PayrollRun::lockForUpdate()->findOrFail($run->id);
            if ($run->isFinal()) {
                throw new DomainException('Payroll yang sudah final tidak dapat diubah.');
            }

            foreach ($run->details()->with('employee')->get() as $detail) {
                if (! isset($input[$detail->id])) {
                    continue;
                }

                $data = [
                    'gaji_pokok' => $detail->gaji_pokok,
                    'tunjangan' => $detail->tunjangan,
                    'bonus_produksi' => (float) ($input[$detail->id]['bonus_produksi'] ?: 0),
                    'lembur' => (float) ($input[$detail->id]['lembur'] ?: 0),
                    'thr' => (float) ($input[$detail->id]['thr'] ?: 0),
                    'potongan' => (float) ($input[$detail->id]['potongan'] ?: 0),
                ];
                $data['total_diterima'] = self::hitungTotal($data);

                if ($data['total_diterima'] < 0) {
                    throw new DomainException("Potongan {$detail->employee->nama_lengkap} melebihi total gaji.");
                }

                $detail->update($data);
            }

            $run->update(['total_gaji_agregat' => $run->details()->sum('total_diterima')]);
        });
    }

    /** draft -> final, lalu kirim SATU angka agregat ke Keuangan (B.7.4 langkah 3–5) */
    public function finalisasi(PayrollRun $run, User $oleh): void
    {
        $this->pastikanOwner($oleh);

        DB::transaction(function () use ($run, $oleh) {
            $run = PayrollRun::lockForUpdate()->findOrFail($run->id);

            if ($run->isFinal()) {
                throw new DomainException('Payroll ini sudah final.');
            }

            $jumlah = $run->details()->count();
            $total = (float) $run->details()->sum('total_diterima');

            if ($jumlah === 0 || $total <= 0) {
                throw new DomainException('Payroll belum berisi karyawan dengan gaji.');
            }

            $run->update([
                'status' => StatusPayroll::Final,
                'total_gaji_agregat' => $total,
                'diproses_oleh' => $oleh->id,
                'diproses_pada' => now(),
            ]);

            // Hanya total_gaji_agregat yang menyeberang ke Keuangan — tanpa data per individu (B.7.7)
            $this->keuangan->catatBebanGajiAgregat($run->periode_bulan, $total, $jumlah, $oleh);
        });
    }

    public function hapusDraft(PayrollRun $run, User $oleh): void
    {
        $this->pastikanOwner($oleh);

        if ($run->isFinal()) {
            throw new DomainException('Payroll yang sudah final tidak dapat dihapus.');
        }

        $run->delete(); // rincian ikut terhapus (cascade)
    }
}