<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PRD B.8.7: tampilan dashboard & ekspor disesuaikan per role */
class DashboardTest extends TestCase
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

    public function test_owner_melihat_semua_ringkasan(): void
    {
        $this->actingAs($this->user(Role::Owner))->get('/dashboard')->assertOk()
            ->assertSee('Ringkasan Keuangan')->assertSee('Ringkasan Stok')
            ->assertSee('Ringkasan SDM')->assertSee('Perbandingan Harga Supplier');
    }

    public function test_admin_tidak_melihat_keuangan_dan_sdm(): void
    {
        $this->actingAs($this->user(Role::Admin))->get('/dashboard')->assertOk()
            ->assertSee('Ringkasan Stok')->assertSee('Ringkasan Distribusi')
            ->assertDontSee('Ringkasan Keuangan')->assertDontSee('Ringkasan SDM');
    }

    public function test_admin_keuangan_tidak_melihat_sdm(): void
    {
        $this->actingAs($this->user(Role::AdminKeuangan))->get('/dashboard')->assertOk()
            ->assertSee('Ringkasan Keuangan')->assertSee('Ringkasan Stok')
            ->assertDontSee('Ringkasan SDM');
    }

    public function test_ekspor_laba_rugi_hanya_untuk_owner_dan_admin_keuangan(): void
    {
        $this->actingAs($this->user(Role::Admin))->get('/ekspor/laba-rugi/pdf')->assertForbidden();

        $this->actingAs($this->user(Role::AdminKeuangan))->get('/ekspor/laba-rugi/pdf')
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
}