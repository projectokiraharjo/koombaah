<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\OperationalExpense;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pengujian Modul SDM dengan data dummy (PRD B.7.8, C.1) */
class SdmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function user(Role $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function karyawan(string $nama, float $pokok, float $tunjangan = 0): Employee
    {
        $e = Employee::create([
            'kode_karyawan' => Employee::generateKode(),
            'nama_lengkap' => $nama,
            'posisi' => 'Staf Produksi',
            'tanggal_mulai_kerja' => '2026-01-01',
            'status' => 'aktif',
        ]);
        EmployeeSalaryComponent::create([
            'employee_id' => $e->id, 'gaji_pokok' => $pokok, 'tunjangan' => $tunjangan, 'berlaku_sejak' => '2026-01-01',
        ]);

        return $e;
    }

    /** A.4: Admin & Admin Keuangan tidak bisa membuka modul SDM, meski lewat URL langsung */
    public function test_selain_owner_ditolak_dari_halaman_sdm(): void
    {
        foreach ([Role::Admin, Role::AdminKeuangan] as $role) {
            $user = $this->user($role);
            $this->actingAs($user)->get('/sdm/karyawan')->assertForbidden();
            $this->actingAs($user)->get('/sdm/payroll')->assertForbidden();
        }

        $this->actingAs($this->user(Role::Owner))->get('/sdm/karyawan')->assertOk();
    }

    /** C.1: proteksi di LEVEL QUERY — data gaji tidak terbaca & tidak bisa ditulis selain oleh Owner */
    public function test_data_gaji_terkunci_di_level_query(): void
    {
        $this->actingAs($this->user(Role::Owner));
        $karyawan = $this->karyawan('Dummy A', 4500000);
        $this->assertSame(1, EmployeeSalaryComponent::count());

        $this->actingAs($this->user(Role::AdminKeuangan));
        $this->assertSame(0, EmployeeSalaryComponent::count()); // baris ada, tapi tidak terlihat

        $this->expectException(AuthorizationException::class);
        EmployeeSalaryComponent::create([
            'employee_id' => $karyawan->id, 'gaji_pokok' => 1, 'tunjangan' => 0, 'berlaku_sejak' => '2026-02-01',
        ]);
    }

    /** B.7.7: hanya total agregat yang menyeberang ke Keuangan; payroll final tidak bisa diubah */
    public function test_payroll_final_mengirim_total_agregat_saja(): void
    {
        $owner = $this->user(Role::Owner);
        $this->actingAs($owner);
        $this->karyawan('Dummy A', 4500000, 300000);
        $this->karyawan('Dummy B', 5000000);

        $service = app(PayrollService::class);
        [$run] = $service->siapkanDraft(Carbon::parse('2026-09-01'), $owner);

        $detailA = $run->details()->whereHas('employee', fn ($q) => $q->where('nama_lengkap', 'Dummy A'))->first();
        $service->simpanPenyesuaian($run, [$detailA->id => [
            'bonus_produksi' => 250000, 'lembur' => 100000, 'thr' => 0, 'potongan' => 50000,
        ]], $owner);
        $service->finalisasi($run, $owner);

        // (4.500.000 + 300.000 + 250.000 + 100.000 − 50.000) + 5.000.000 = 10.100.000
        $beban = OperationalExpense::where('kategori', 'beban_gaji')->get();
        $this->assertCount(1, $beban);
        $this->assertEquals(10100000, (float) $beban->first()->nominal);
        $this->assertStringNotContainsString('Dummy', (string) $beban->first()->catatan); // tanpa nama karyawan

        $this->expectException(DomainException::class);
        $service->siapkanDraft(Carbon::parse('2026-09-01'), $owner); // final tidak bisa dibuka lagi
    }
}