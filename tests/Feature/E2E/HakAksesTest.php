<?php

namespace Tests\Feature\E2E;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\DataUji;
use Tests\TestCase;

/** Fase 5 — hak akses dicocokkan langsung dengan tabel PRD A.4 */
class HakAksesTest extends TestCase
{
    use RefreshDatabase, DataUji;

    /** route => modul */
    private const HALAMAN = [
        'dashboard' => 'dashboard',
        'bahan-baku' => 'inventory', 'produk-jadi' => 'inventory', 'bom' => 'inventory', 'stok-bahan-baku' => 'inventory',
        'stok-produk-jadi' => 'inventory', 'peringatan-stok' => 'inventory', 'riwayat-stok' => 'inventory',
        'supplier' => 'purchasing', 'po' => 'purchasing', 'po.buat' => 'purchasing', 'harga-supplier' => 'purchasing',
        'produksi' => 'produksi', 'produksi.buat' => 'produksi',
        'mitra' => 'sales', 'order' => 'sales', 'order.buat' => 'sales',
        'distribusi' => 'distribusi', 'distribusi.buat' => 'distribusi', 'riwayat-pengiriman' => 'distribusi',
        'keuangan' => 'keuangan', 'arus-kas' => 'keuangan', 'biaya-operasional' => 'keuangan', 'hpp' => 'keuangan', 'periode' => 'keuangan',
        'karyawan' => 'sdm', 'payroll' => 'sdm',
        'pengaturan' => 'pengaturan', 'pengaturan.backup' => 'pengaturan',   
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** Ditulis ulang dari PRD A.4 — sengaja TIDAK memakai Role::MODULES agar kode benar-benar diuji terhadap PRD */
    private function aksesPrd(Role $role): array
    {
        return match ($role) {
            Role::Owner => ['dashboard', 'inventory', 'purchasing', 'produksi', 'sales', 'distribusi', 'keuangan', 'sdm', 'pengaturan'],
            Role::Admin => ['dashboard', 'inventory', 'purchasing', 'produksi', 'sales', 'distribusi'],
            Role::AdminKeuangan => ['dashboard', 'keuangan'],
        };
    }

    public function test_setiap_halaman_hanya_terbuka_untuk_role_yang_berhak(): void
    {
        $salah = [];

        foreach (Role::cases() as $role) {
            $user = $this->user($role);
            foreach (self::HALAMAN as $route => $modul) {
                $harap = in_array($modul, $this->aksesPrd($role), true) ? 200 : 403;
                $dapat = $this->actingAs($user)->get(route($route))->status();
                if ($dapat !== $harap) {
                    $salah[] = "{$role->label()} → {$route}: diharapkan {$harap}, didapat {$dapat}";
                }
            }
        }

        $this->assertSame([], $salah, "Hak akses tidak sesuai PRD A.4:\n" . implode("\n", $salah));
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        foreach (['dashboard', 'po', 'keuangan', 'payroll'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_akun_nonaktif_tidak_bisa_membuka_apa_pun(): void
    {
        $this->actingAs($this->user(Role::Owner, aktif: false))->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();    
    }

    public function test_izin_khusus_owner(): void
    {
        foreach (Role::cases() as $role) {
            $user = $this->user($role);
            $owner = $role === Role::Owner;
            foreach (['bom.ubah', 'periode.kelola', 'ringkasan.sdm'] as $izin) {
                $this->assertSame($owner, Gate::forUser($user)->allows($izin), "{$role->label()} — {$izin}");
            }
        }
    }
}