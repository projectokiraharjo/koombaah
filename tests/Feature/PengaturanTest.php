<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Pengaturan\AkunPengguna;
use App\Livewire\Profil;
use App\Models\User;
use App\Services\PenggunaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Concerns\DataUji;
use Tests\TestCase;

/** Modul Pengaturan (PRD A.4, B.7.5, C.1) */
class PengaturanTest extends TestCase
{
    use RefreshDatabase, DataUji;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_pengaturan_hanya_owner_dan_profil_untuk_semua_role(): void
    {
        foreach (Role::cases() as $role) {
            $user = $this->user($role);
            $harap = $role === Role::Owner ? 200 : 403;
            $this->actingAs($user)->get(route('pengaturan'))->assertStatus($harap);
            $this->actingAs($user)->get(route('pengaturan.backup'))->assertStatus($harap);
            $this->actingAs($user)->get(route('profil'))->assertOk();
        }
    }

    public function test_owner_membuat_akun_staf_baru(): void
    {
        $this->actingAs($this->user(Role::Owner));

        Livewire::test(AkunPengguna::class)->call('create')
            ->set('name', 'Staf Gudang')->set('email', 'Gudang@Koombaah.test')->set('role', 'admin')
            ->set('password', 'rahasia123')->set('password_confirmation', 'rahasia123')
            ->call('save')->assertHasNoErrors();

        $baru = User::where('email', 'gudang@koombaah.test')->firstOrFail();
        $this->assertSame(Role::Admin, $baru->role);
        $this->assertTrue(Hash::check('rahasia123', $baru->password));
    }

    public function test_aturan_pengaman_akun(): void
    {
        $owner = $this->user(Role::Owner);
        $service = app(PenggunaService::class);
        $data = ['name' => $owner->name, 'email' => $owner->email, 'role' => 'admin', 'is_active' => true];

        $this->assertDitolak(fn () => $service->simpan($owner, $data, $owner), pesan: 'akun Anda sendiri');
        $this->assertDitolak(fn () => $service->simpan($owner, ['role' => 'owner', 'is_active' => false] + $data, $owner), pesan: 'akun Anda sendiri');
        $this->assertDitolak(fn () => $service->simpan(null, ['email' => 'x@koombaah.test'] + $data, $this->user(Role::Admin)), pesan: 'Hanya Owner');

        // Owner lain boleh diturunkan selama masih ada Owner aktif
        $owner2 = $this->user(Role::Owner);
        $service->simpan($owner2, ['name' => $owner2->name, 'email' => $owner2->email, 'role' => 'admin', 'is_active' => true], $owner);
        $this->assertSame(Role::Admin, $owner2->fresh()->role);
    }

    public function test_akun_yang_dinonaktifkan_langsung_dikeluarkan(): void
    {
        $admin = $this->user(Role::Admin);
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();

        $admin->update(['is_active' => false]);
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_ganti_password_wajib_password_lama_yang_benar(): void
    {
        $user = $this->user(Role::AdminKeuangan);
        $this->actingAs($user);

        Livewire::test(Profil::class)
            ->set('current_password', 'salah')->set('password', 'passwordbaru1')->set('password_confirmation', 'passwordbaru1')
            ->call('gantiPassword')->assertHasErrors('current_password');

        Livewire::test(Profil::class)
            ->set('current_password', 'password')->set('password', 'passwordbaru1')->set('password_confirmation', 'passwordbaru1')
            ->call('gantiPassword')->assertHasNoErrors();

        $this->assertTrue(Hash::check('passwordbaru1', $user->fresh()->password));
    }

    public function test_unduh_backup_menolak_nama_file_sembarang(): void
    {
        $this->actingAs($this->user(Role::Owner))
            ->get(route('pengaturan.backup.unduh', 'rahasia.txt'))->assertNotFound();
    }
}