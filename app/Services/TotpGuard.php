<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Gerbang dua langkah untuk aksi yang memindahkan uang.
 *
 * Yang dijaga di sini bukan "siapa yang login", melainkan "siapa yang menekan
 * tombol saat ini juga". Sesi yang sudah login bisa dibajak — lewat kata sandi
 * yang bocor, komputer yang ditinggal terbuka, atau cookie yang dicuri — dan
 * pada titik itu satu klik sudah cukup untuk melepas dana. Kode TOTP hanya ada
 * di ponsel pemiliknya dan berganti tiap 30 detik.
 *
 * Tiga hal yang membuat ini bukan sekadar formalitas:
 *
 * 1. Kode hanya sekali pakai (lihat totp_last_timestep). Kode yang terbaca dari
 *    balik bahu tidak bisa dipakai ulang di sisa 30 detiknya.
 * 2. Percobaannya dibatasi. Enam digit hanya sejuta kemungkinan — tanpa batas
 *    laju, menebaknya cuma soal waktu.
 * 3. Berhasil maupun gagal, keduanya masuk jejak audit ber-rantai.
 */
class TotpGuard
{
    /** Percobaan gagal sebelum gerbang dikunci sementara. */
    public const MAX_ATTEMPTS = 5;

    /** Lama kunci setelah percobaan habis, dalam detik. */
    public const LOCKOUT_SECONDS = 300;

    /**
     * Lama "jendela sudo": setelah satu kode benar, aksi uang berikutnya lewat
     * tanpa ditanya lagi selama menit-menit ini.
     *
     * Alasannya praktis. Admin yang memproses antrean pencairan pagi hari
     * harus mengetik kode untuk tiap baris — dan gerbang yang terasa menyiksa
     * akan dicari jalan pintasnya (2FA dimatikan, atau kunci ditempel di meja),
     * yang justru lebih berbahaya daripada jendela 15 menit ini.
     *
     * Yang ditukar: kalau sesi dibajak DI DALAM jendela — mis. laptop admin
     * ditinggal terbuka — aksi uang bisa lewat tanpa kode. Jauh lebih sempit
     * daripada "selamanya selama masih login", yang merupakan kondisi sebelum
     * gerbang ini ada.
     */
    public const SUDO_WINDOW_MINUTES = 15;

    /** Kunci sesi tempat jendela sudo dicatat. */
    private const WINDOW_KEY = 'totp.sudo_window';

    public function __construct(
        private readonly Totp $totp,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Pastikan $code benar-benar berasal dari aplikasi authenticator milik
     * $user. Melempar ValidationException bila tidak — pemanggil TIDAK BOLEH
     * melanjutkan aksinya bila pengecualian ini terlempar.
     *
     * Mengembalikan CARA aksi itu diotorisasi ("kode", "kode_pemulihan", atau
     * "jendela_15_menit") supaya pemanggil bisa mencatatnya di jejak audit —
     * pemeriksa perlu bisa membedakan aksi yang diketik kodenya langsung dari
     * aksi yang menumpang jendela yang masih terbuka.
     *
     * $action hanya dipakai untuk jejak audit, mis. "disbursement.release".
     *
     * $allowWindow sengaja default FALSE: kalau suatu pemanggil lupa menyebutkan
     * niatnya, yang terjadi adalah perilaku yang lebih ketat, bukan lebih longgar.
     */
    public function assertValid(User $user, ?string $code, string $action, bool $allowWindow = false): string
    {
        if (! $user->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages([
                'totp_code' => 'Aksi ini butuh verifikasi dua langkah. Aktifkan dulu di halaman '
                    .'Keamanan akun, lalu ulangi.',
            ]);
        }

        if ($allowWindow && $this->windowOpenFor($user)) {
            return 'jendela_15_menit';
        }

        $key = 'totp:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $detik = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'totp_code' => 'Terlalu banyak kode salah. Coba lagi dalam '
                    .ceil($detik / 60).' menit.',
            ]);
        }

        $code = trim((string) $code);

        if ($code === '') {
            // Tidak dihitung sebagai percobaan gagal: form yang lupa diisi
            // bukan tebakan, dan tidak boleh ikut menghabiskan jatah pengguna.
            throw ValidationException::withMessages([
                'totp_code' => 'Masukkan kode enam digit dari aplikasi authenticator Anda.',
            ]);
        }

        $pakaiKodePemulihan = $this->looksLikeRecoveryCode($code);

        $berhasil = $pakaiKodePemulihan
            ? $this->consumeRecoveryCode($user, $code)
            : $this->consumeTotpCode($user, $code);

        if (! $berhasil) {
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);

            // Kodenya sendiri TIDAK ikut dicatat. Yang berguna bagi pemeriksa
            // adalah adanya percobaan gagal, bukan angka yang ditebak.
            $this->audit->record('totp.failed', $user, [
                'untuk' => $action,
                'sisa_percobaan' => RateLimiter::remaining($key, self::MAX_ATTEMPTS),
            ], $user);

            throw ValidationException::withMessages([
                'totp_code' => 'Kode salah atau sudah kedaluwarsa. Lihat kode terbaru di aplikasi '
                    .'authenticator Anda.',
            ]);
        }

        RateLimiter::clear($key);

        // Jendela hanya dibuka untuk aksi yang memang mengizinkannya. Kode yang
        // diketik untuk mematikan 2FA, misalnya, tidak boleh sekalian membuka
        // pintu bagi aksi-aksi uang setelahnya.
        if ($allowWindow) {
            $this->openWindow($user);
        }

        return $pakaiKodePemulihan ? 'kode_pemulihan' : 'kode';
    }

    /**
     * Masih ada jendela sudo yang terbuka untuk $user?
     *
     * Jendelanya melekat pada SESI, bukan akun: logout menutupnya, dan sesi di
     * perangkat lain tidak ikut terbuka. Id pemiliknya ikut diperiksa supaya
     * jendela tidak terbawa bila sesi yang sama berganti pengguna.
     */
    public function windowOpenFor(User $user): bool
    {
        $window = session(self::WINDOW_KEY);

        if (! is_array($window) || ($window['user_id'] ?? null) !== $user->id) {
            return false;
        }

        return isset($window['expires_at'])
            && Carbon::parse($window['expires_at'])->isFuture();
    }

    /** Sisa umur jendela dalam menit, untuk ditampilkan ke pengguna. */
    public function windowMinutesLeft(User $user): int
    {
        if (! $this->windowOpenFor($user)) {
            return 0;
        }

        return max(1, (int) ceil(
            Carbon::now()->diffInSeconds(Carbon::parse(session(self::WINDOW_KEY)['expires_at'])) / 60
        ));
    }

    private function openWindow(User $user): void
    {
        session([self::WINDOW_KEY => [
            'user_id' => $user->id,
            'expires_at' => Carbon::now()->addMinutes(self::SUDO_WINDOW_MINUTES)->toIso8601String(),
        ]]);
    }

    /** Tutup paksa — dipakai saat dua langkah dimatikan. */
    public function closeWindow(): void
    {
        session()->forget(self::WINDOW_KEY);
    }

    /**
     * Kode TOTP biasa. Mengembalikan false bila tidak cocok ATAU bila selang
     * waktunya sudah pernah terpakai.
     */
    private function consumeTotpCode(User $user, string $code): bool
    {
        $step = $this->totp->verify((string) $user->totp_secret, $code);

        if ($step === null) {
            return false;
        }

        // Sekali pakai. Sama nilainya dengan kode salah, dan sengaja dijawab
        // dengan pesan yang sama supaya tidak membocorkan bahwa kode yang
        // dicoba penyerang sebenarnya sempat benar.
        if ($user->totp_last_timestep !== null && $step <= $user->totp_last_timestep) {
            return false;
        }

        $user->forceFill(['totp_last_timestep' => $step])->save();

        return true;
    }

    /** Kode pemulihan: sekali pakai, langsung dicoret dari daftar. */
    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $code = strtoupper($code);
        $tersisa = $user->totp_recovery_codes ?? [];

        // array_filter dengan hash_equals, bukan in_array/array_search: waktu
        // bandingnya tidak boleh bergantung pada seberapa mirip tebakannya.
        $cocok = false;
        $sisa = [];

        foreach ($tersisa as $tersimpan) {
            if (! $cocok && hash_equals((string) $tersimpan, $code)) {
                $cocok = true;

                continue;
            }

            $sisa[] = $tersimpan;
        }

        if (! $cocok) {
            return false;
        }

        $user->forceFill(['totp_recovery_codes' => $sisa])->save();

        $this->audit->record('totp.recovery_used', $user, [
            'sisa_kode' => count($sisa),
        ], $user);

        return true;
    }

    private function looksLikeRecoveryCode(string $code): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/', $code);
    }

    /**
     * Kode pemulihan baru. Ditampilkan satu kali saat dibuat — setelah halaman
     * itu ditutup, tidak ada lagi tempat untuk melihatnya.
     *
     * @return array<int, string>
     */
    public function newRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => strtoupper(Str::random(4).'-'.Str::random(4)))
            ->all();
    }
}
