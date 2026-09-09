<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DonationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle(Request $request)
    {
        // Simpan role pilihan jika pengguna mendaftar dari halaman register
        if ($request->filled('role')) {
            session(['oauth_intended_role' => $request->string('role')->toString()]);
        }

        $clientId = config('services.google.client_id');

        // Jika GOOGLE_CLIENT_ID belum diisi di .env pada lingkungan lokal, tampilkan simulasi
        if (blank($clientId)) {
            return view('auth.google-mock', [
                'role' => session('oauth_intended_role', User::ROLE_DONATUR),
            ]);
        }

        try {
            return Socialite::driver('google')->redirect();
        } catch (\Throwable $e) {
            if (app()->environment('local', 'testing')) {
                return view('auth.google-mock', [
                    'role' => session('oauth_intended_role', User::ROLE_DONATUR),
                ]);
            }

            return redirect()->route('login')
                ->with('error', 'Gagal menghubungkan ke Google OAuth. Pastikan GOOGLE_CLIENT_ID & GOOGLE_CLIENT_SECRET sudah diatur.');
        }
    }

    /** Alias untuk kompatibilitas route redirect */
    public function redirect(Request $request)
    {
        return $this->redirectToGoogle($request);
    }

    public function mockLogin(Request $request)
    {
        abort_unless(app()->environment('local', 'testing'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'google_id' => ['nullable', 'string', 'max:60'],
        ]);

        $googleId = $data['google_id'] ?: 'mock-google-' . substr(md5($data['email']), 0, 10);

        $user = User::where('google_id', $googleId)
            ->orWhere('email', $data['email'])
            ->first();

        if ($user) {
            if (! $user->google_id) {
                $user->update(['google_id' => $googleId]);
            }

            Auth::login($user, remember: true);

            if ($donation = app(DonationService::class)->processPendingDonation($user)) {
                return redirect()->route('donasi.checkout', $donation->reference)
                    ->with('status', 'Berhasil masuk dengan Google! Silakan selesaikan transaksi donasi Anda.');
            }

            return redirect()->intended($user->homeRoute());
        }

        $role = session()->pull('oauth_intended_role', User::ROLE_DONATUR);
        if (! in_array($role, [User::ROLE_DONATUR, User::ROLE_PENGAJU], true)) {
            $role = User::ROLE_DONATUR;
        }

        $newUser = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'google_id' => $googleId,
            'password' => null,
            'role' => $role,
            'verification_status' => User::VERIFICATION_UNVERIFIED,
            'email_verified_at' => now(),
        ]);

        event(new Registered($newUser));

        Auth::login($newUser, remember: true);

        if ($donation = app(DonationService::class)->processPendingDonation($newUser)) {
            return redirect()->route('donasi.checkout', $donation->reference)
                ->with('status', 'Berhasil masuk dengan Google! Silakan selesaikan transaksi donasi Anda.');
        }

        return redirect()->intended($newUser->homeRoute());
    }

    public function handleGoogleCallback(AuditLogger $audit)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->with('error', 'Gagal melakukan autentikasi dengan Google. Silakan coba lagi.');
        }

        $email = $googleUser->getEmail();

        if (empty($email)) {
            return redirect()->route('login')
                ->with('error', 'Akun Google Anda tidak menyediakan alamat email.');
        }

        // Cari berdasarkan google_id atau email
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            // Tautkan google_id jika sebelumnya daftar manual
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }

            Auth::login($user, remember: true);
            request()->session()->regenerate();

            $audit->record('user.login', $user, ['provider' => 'google'], $user);

            if ($donation = app(DonationService::class)->processPendingDonation($user)) {
                return redirect()->route('donasi.checkout', $donation->reference)
                    ->with('status', 'Berhasil masuk dengan Google! Silakan selesaikan transaksi donasi Anda.');
            }

            return redirect()->intended($user->homeRoute())
                ->with('status', 'Selamat datang di DonasiTrust, '.$user->name.'.');
        }

        // Akun baru via Google
        $role = session()->pull('oauth_intended_role', User::ROLE_DONATUR);
        if (! in_array($role, [User::ROLE_DONATUR, User::ROLE_PENGAJU], true)) {
            $role = User::ROLE_DONATUR;
        }

        $name = $googleUser->getName() ?? $googleUser->getNickname() ?? explode('@', $email)[0];

        $newUser = User::create([
            'name' => $name,
            'email' => $email,
            'google_id' => $googleUser->getId(),
            'password' => null,
            'role' => $role,
            // Status verifikasi pengaju tetap WAJIB UNVERIFIED dan diperiksa manual oleh Admin
            'verification_status' => User::VERIFICATION_UNVERIFIED,
            'email_verified_at' => now(),
        ]);

        event(new Registered($newUser));
        $audit->record('user.registered', $newUser, ['role' => $newUser->role, 'provider' => 'google'], $newUser);

        Auth::login($newUser, remember: true);
        request()->session()->regenerate();

        $audit->record('user.login', $newUser, ['provider' => 'google'], $newUser);

        if ($donation = app(DonationService::class)->processPendingDonation($newUser)) {
            return redirect()->route('donasi.checkout', $donation->reference)
                ->with('status', 'Berhasil masuk dengan Google! Silakan selesaikan transaksi donasi Anda.');
        }

        return redirect()->intended($newUser->homeRoute())
            ->with('status', 'Selamat datang di DonasiTrust, '.$newUser->name.'.');
    }

    /** Alias untuk kompatibilitas */
    public function callback(AuditLogger $audit)
    {
        return $this->handleGoogleCallback($audit);
    }
}
