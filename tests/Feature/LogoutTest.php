<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_dapat_logout_dengan_sukses(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/keluar');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_navbar_dan_dashboard_menyediakan_tombol_logout(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_DONATUR]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee(route('logout'))
            ->assertSee('Keluar Akun');

        $this->actingAs($user)
            ->get(route('profil.edit'))
            ->assertOk()
            ->assertSee(route('logout'))
            ->assertSee('Keluar Akun');
    }
}
