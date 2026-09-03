<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\Totp;
use App\Services\TotpGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Pendaftaran dan pencabutan verifikasi dua langkah.
 *
 * Kunci yang belum dikonfirmasi disimpan di sesi, BUKAN di basis data. Kalau
 * kunci langsung ditulis ke akun begitu halaman dibuka, pengguna yang gagal
 * memindai QR lalu menutup tab akan punya akun dengan kunci yang tidak dipegang
 * siapa pun — dan pencairan dananya ikut terkunci selamanya.
 */
class TwoFactorController extends Controller
{
    /** Kunci sesi tempat kunci TOTP menunggu dikonfirmasi. */
    private const PENDING_KEY = 'totp.pending_secret';

    public function __construct(
        private readonly Totp $totp,
        private readonly TotpGuard $guard,
        private readonly AuditLogger $audit,
    ) {}

    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->forget(self::PENDING_KEY);

            return view('profile.two-factor', [
                'user' => $user,
                'pendingSecret' => null,
                'provisioningUri' => null,
            ]);
        }

        // Kunci yang sama dipertahankan selama proses pendaftaran belum selesai,
        // supaya me-refresh halaman tidak membatalkan QR yang sudah dipindai.
        $secret = $request->session()->get(self::PENDING_KEY);

        if (! $secret) {
            $secret = $this->totp->generateSecret();
            $request->session()->put(self::PENDING_KEY, $secret);
        }

        return view('profile.two-factor', [
            'user' => $user,
            'pendingSecret' => $secret,
            'provisioningUri' => $this->totp->provisioningUri($secret, $user->email),
        ]);
    }

    /** Konfirmasi: aplikasi authenticator harus membuktikan kuncinya terbaca. */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Verifikasi dua langkah sudah aktif.');
        }

        $request->validate([
            'totp_code' => ['required', 'string'],
        ], [], ['totp_code' => 'kode verifikasi']);

        $secret = $request->session()->get(self::PENDING_KEY);

        if (! $secret) {
            return back()->with('error', 'Sesi pendaftaran sudah kedaluwarsa. Mulai ulang dari awal.');
        }

        $step = $this->totp->verify($secret, $request->string('totp_code')->toString());

        if ($step === null) {
            throw ValidationException::withMessages([
                'totp_code' => 'Kode tidak cocok. Pastikan jam ponsel Anda tepat, lalu masukkan '
                    .'kode terbaru yang tampil di aplikasi.',
            ]);
        }

        $recoveryCodes = $this->guard->newRecoveryCodes();

        $user->forceFill([
            'totp_secret' => $secret,
            'totp_confirmed_at' => now(),
            // Kode yang barusan dipakai untuk konfirmasi langsung dianggap
            // terpakai, supaya tidak bisa dipakai lagi untuk mencairkan dana.
            'totp_last_timestep' => $step,
            'totp_recovery_codes' => $recoveryCodes,
        ])->save();

        $request->session()->forget(self::PENDING_KEY);

        $this->audit->record('totp.enabled', $user, [
            'nama' => $user->name,
            'peran' => $user->roleLabel(),
        ]);

        // Ditampilkan sekali lewat flash session. Tidak disimpan di tempat lain
        // yang bisa dibuka ulang.
        return back()
            ->with('recovery_codes', $recoveryCodes)
            ->with('status', 'Verifikasi dua langkah aktif. Simpan kode pemulihan di bawah ini.');
    }

    /**
     * Mematikan dua langkah butuh kata sandi DAN kode yang masih berlaku.
     * Kalau cukup kata sandi, sesi yang dibajak tinggal mematikannya dulu lalu
     * mencairkan dana — dan seluruh lapisan ini jadi tidak ada gunanya.
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Verifikasi dua langkah belum aktif.');
        }

        $request->validate([
            'current_password' => ['required', 'current_password'],
        ], [], ['current_password' => 'kata sandi']);

        $this->guard->assertValid($user, $request->input('totp_code'), 'totp.disable');

        $user->forceFill([
            'totp_secret' => null,
            'totp_confirmed_at' => null,
            'totp_last_timestep' => null,
            'totp_recovery_codes' => null,
        ])->save();

        // Jendela sudo yang mungkin masih terbuka ikut ditutup. Kalau tidak,
        // sisa menitnya tetap berlaku padahal gerbangnya sudah dicabut.
        $this->guard->closeWindow();

        $this->audit->record('totp.disabled', $user, [
            'nama' => $user->name,
            'peran' => $user->roleLabel(),
        ]);

        return back()->with('status', 'Verifikasi dua langkah dimatikan. '
            .'Pencairan dana tidak bisa diajukan atau dilepas sampai diaktifkan lagi.');
    }

    /** Kode pemulihan baru; yang lama langsung hangus semuanya. */
    public function regenerate(Request $request)
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Verifikasi dua langkah belum aktif.');
        }

        $this->guard->assertValid($user, $request->input('totp_code'), 'totp.recovery_regenerate');

        $recoveryCodes = $this->guard->newRecoveryCodes();

        $user->forceFill(['totp_recovery_codes' => $recoveryCodes])->save();

        $this->audit->record('totp.recovery_regenerated', $user, [
            'jumlah' => count($recoveryCodes),
        ]);

        return back()
            ->with('recovery_codes', $recoveryCodes)
            ->with('status', 'Kode pemulihan baru dibuat. Kode lama sudah tidak berlaku.');
    }
}
