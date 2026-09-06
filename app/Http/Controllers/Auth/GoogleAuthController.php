<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request)
    {
        // Simpan role pilihan jika pengguna mendaftar dari halaman register
        if ($request->filled('role')) {
            session(['oauth_intended_role' => $request->string('role')->toString()]);
        }

        $clientId = config('services.google.client_id');

        // Jika GOOGLE_CLIENT_ID belum diisi di .env pada lingkungan lokal, tampilkan simulasi
        if (blank($clientId) && app()->environment('local', 'testing')) {
            return view('auth.google-mock', [
                'role' => session('oauth_intended_role', User::ROLE_DONATUR),
            ]);
        }

        return Socialite::driver('google')->redirect();
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
        ]);

        Auth::login($newUser, remember: true);

        return redirect()->intended($newUser->homeRoute());
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', 'Gagal masuk dengan Google. Silakan coba lagi.');
        }

        // Cari berdasarkan google_id atau email
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // Tautkan google_id jika sebelumnya daftar manual
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }

            Auth::login($user, remember: true);

            return redirect()->intended($user->homeRoute());
        }

        // Akun baru via Google
        $role = session()->pull('oauth_intended_role', User::ROLE_DONATUR);
        if (! in_array($role, [User::ROLE_DONATUR, User::ROLE_PENGAJU], true)) {
            $role = User::ROLE_DONATUR;
        }

        $newUser = User::create([
            'name' => $googleUser->getName() ?: 'Pengguna Google',
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'password' => null,
            'role' => $role,
            // Status verifikasi pengaju tetap WAJIB UNVERIFIED dan diperiksa manual oleh Admin
            'verification_status' => User::VERIFICATION_UNVERIFIED,
        ]);

        Auth::login($newUser, remember: true);

        return redirect()->intended($newUser->homeRoute());
    }
}
