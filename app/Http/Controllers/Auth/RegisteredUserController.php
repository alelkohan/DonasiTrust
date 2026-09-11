<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'in:donatur,pengaju'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ], [], [
            'name' => 'nama',
            'email' => 'email',
            'password' => 'kata sandi',
            'terms' => 'persetujuan syarat',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            // Di-hash otomatis oleh cast 'password' => 'hashed' pada model User.
            'password' => $data['password'],
            'verification_status' => User::VERIFICATION_UNVERIFIED,
        ]);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        $audit->record('user.registered', $user, ['role' => $user->role], $user);

        $intended = session('url.intended');
        if ($intended && (str_contains($intended, '/login') || str_contains($intended, '/register'))) {
            session()->forget('url.intended');
        }

        if ($donation = app(\App\Services\DonationService::class)->processPendingDonation($user)) {
            return redirect()->route('donasi.checkout', $donation->reference)
                ->with('status', 'Akun berhasil dibuat dan Anda telah otomatis masuk. Silakan selesaikan transaksi donasi Anda.');
        }

        return redirect()->intended($user->homeRoute())
            ->with('status', 'Selamat datang di DonasiTrust, '.$user->name.'. Pendaftaran berhasil dan Anda telah otomatis masuk.');
    }
}
