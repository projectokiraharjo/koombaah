<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * DATA DUMMY KARYAWAN (PRD B.7.8) — tim Koombaah saat ini masih solo.
 * Skenario: 2 staf produksi (1 naik gaji Oktober), 1 admin bertautan akun sistem, 1 resign akhir September.
 */
class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // [nama, posisi, mulai kerja, berhenti, email akun, [[pokok, tunjangan, berlaku sejak], ...]]
            ['Budi Santoso (dummy)', 'Staf Produksi', '2026-07-01', null, null,
                [[4500000, 300000, '2026-07-01'], [4800000, 300000, '2026-10-01']]],
            ['Siti Rahmawati (dummy)', 'Staf Produksi', '2026-08-01', null, null,
                [[4500000, 300000, '2026-08-01']]],
            ['Andi Pratama (dummy)', 'Admin Operasional', '2026-07-15', null, 'admin@koombaah.test',
                [[5000000, 500000, '2026-07-15']]],
            ['Rina Wulandari (dummy)', 'Staf Gudang', '2026-07-01', '2026-09-30', null,
                [[4300000, 200000, '2026-07-01']]],
        ];

        foreach ($data as [$nama, $posisi, $mulai, $berhenti, $email, $komponen]) {
            $karyawan = Employee::firstOrCreate(['nama_lengkap' => $nama], [
                'kode_karyawan' => Employee::generateKode(),
                'posisi' => $posisi,
                'tanggal_mulai_kerja' => $mulai,
                'tanggal_berhenti' => $berhenti,
                'status' => $berhenti ? 'nonaktif' : 'aktif',
                'user_id' => $email ? User::where('email', $email)->value('id') : null,
            ]);

            foreach ($komponen as [$pokok, $tunjangan, $sejak]) {
                EmployeeSalaryComponent::firstOrCreate(
                    ['employee_id' => $karyawan->id, 'berlaku_sejak' => $sejak],
                    ['gaji_pokok' => $pokok, 'tunjangan' => $tunjangan],
                );
            }
        }
    }
}