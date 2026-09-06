<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(AuditLogger $audit)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Gagal melakukan autentikasi dengan Google. Silakan coba lagi.');
        }

        $email = $googleUser->getEmail();

        if (empty($email)) {
            return redirect()->route('login')
                ->with('error', 'Akun Google Anda tidak menyediakan alamat email.');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $name = $googleUser->getName() ?? $googleUser->getNickname() ?? explode('@', $email)[0];

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'role' => User::ROLE_DONATUR,
                'password' => bcrypt(Str::random(24)),
                'verification_status' => User::VERIFICATION_UNVERIFIED,
                'email_verified_at' => now(),
            ]);

            event(new Registered($user));
            $audit->record('user.registered', $user, ['role' => $user->role, 'provider' => 'google'], $user);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        $audit->record('user.login', $user, ['provider' => 'google'], $user);

        if ($donation = app(\App\Services\DonationService::class)->processPendingDonation($user)) {
            return redirect()->route('donasi.checkout', $donation->reference)
                ->with('status', 'Berhasil masuk dengan Google! Silakan selesaikan transaksi donasi Anda.');
        }

        return redirect()->intended($user->homeRoute())
            ->with('status', 'Selamat datang di DonasiTrust, '.$user->name.'.');
    }
}
