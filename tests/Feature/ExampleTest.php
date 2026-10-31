<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** Halaman utama tidak dibuka langsung: tamu diarahkan ke login */
    public function test_halaman_utama_mengarahkan_tamu_ke_login(): void
    {
        $this->followingRedirects()
            ->get('/')
            ->assertOk()
            ->assertSee('Masuk');
    }
}