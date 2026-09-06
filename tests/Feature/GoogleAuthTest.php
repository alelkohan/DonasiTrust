<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_returns_redirect_response(): void
    {
        config(['services.google.client_id' => 'dummy-client-id']);

        $response = $this->get(route('auth.google'));

        $this->assertTrue($response->isRedirection());
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location') ?: '');
    }

    public function test_google_redirect_shows_mock_view_when_client_id_empty_in_local(): void
    {
        config(['services.google.client_id' => '']);

        $response = $this->get(route('auth.google'));

        $response->assertOk();
        $response->assertSee('Simulasi Akun Google');
    }

    public function test_mock_login_creates_or_logs_in_user(): void
    {
        $response = $this->post(route('auth.google.mock'), [
            'name' => 'Demo User',
            'email' => 'demo.google@example.com',
            'google_id' => 'mock-google-999',
        ]);

        $this->assertAuthenticated();
        $user = User::where('email', 'demo.google@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('mock-google-999', $user->google_id);
    }

    public function test_google_callback_creates_new_user_with_unverified_status(): void
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-unique-id-123');
        $googleUser->shouldReceive('getName')->andReturn('Budi Santoso');
        $googleUser->shouldReceive('getEmail')->andReturn('budi@example.com');
        $googleUser->shouldReceive('getNickname')->andReturn('budi');

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);

        $response = $this->withSession(['oauth_intended_role' => User::ROLE_PENGAJU])
            ->get(route('auth.google.callback'));

        $this->assertAuthenticated();

        $user = User::where('email', 'budi@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('google-unique-id-123', $user->google_id);
        $this->assertSame('Budi Santoso', $user->name);
        $this->assertSame(User::ROLE_PENGAJU, $user->role);
        // Penting: Status verifikasi KYC admin tetap unverified!
        $this->assertSame(User::VERIFICATION_UNVERIFIED, $user->verification_status);
        $this->assertNull($user->password);
    }

    public function test_google_callback_logs_in_existing_user_and_links_google_id(): void
    {
        $existing = User::factory()->create([
            'email' => 'ahmad@example.com',
            'google_id' => null,
            'role' => User::ROLE_PENGAJU,
            'verification_status' => User::VERIFICATION_VERIFIED,
        ]);

        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-id-456');
        $googleUser->shouldReceive('getName')->andReturn('Ahmad Fauzi');
        $googleUser->shouldReceive('getEmail')->andReturn('ahmad@example.com');

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($existing);
        $this->assertSame('google-id-456', $existing->fresh()->google_id);
        // Status verifikasi yang sudah ada tidak terganggu
        $this->assertSame(User::VERIFICATION_VERIFIED, $existing->fresh()->verification_status);
    }
}
