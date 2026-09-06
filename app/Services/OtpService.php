<?php

namespace App\Services;

use App\Mail\SendOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class OtpService
{
    /**
     * Kirim kode OTP 6-digit ke email pengguna.
     * Menerapkan jeda (cooldown) minimal 60 detik.
     */
    public function generateAndSend(User $user, string $purpose): EmailOtp
    {
        $existing = EmailOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->latest()
            ->first();

        if ($existing && $existing->created_at->diffInSeconds(now()) < EmailOtp::COOLDOWN_SECONDS) {
            $remaining = EmailOtp::COOLDOWN_SECONDS - $existing->created_at->diffInSeconds(now());
            throw ValidationException::withMessages([
                'otp' => "Mohon tunggu {$remaining} detik sebelum meminta kode baru.",
            ]);
        }

        // Hapus kode lama untuk tujuan yang sama
        EmailOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->delete();

        // 6 digit angka acak
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = EmailOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(EmailOtp::EXPIRATION_MINUTES),
        ]);

        Mail::to($user->email)->send(new SendOtpMail($code, $purpose, $user->name));

        return $otp;
    }

    /**
     * Validasi kode OTP. Menghapus OTP bila sah, atau melempar ValidationException bila salah/hangus.
     */
    public function assertValid(User $user, string $purpose, ?string $code): void
    {
        if (blank($code)) {
            throw ValidationException::withMessages([
                'otp_code' => 'Kode verifikasi email wajib diisi.',
            ]);
        }

        $otp = EmailOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->latest()
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'otp_code' => 'Belum ada kode verifikasi yang diminta atau kode sudah kadaluwarsa.',
            ]);
        }

        if ($otp->isExpired()) {
            $otp->delete();
            throw ValidationException::withMessages([
                'otp_code' => 'Kode verifikasi telah kadaluwarsa. Silakan minta kode baru.',
            ]);
        }

        if ($otp->hasExceededAttempts()) {
            $otp->delete();
            throw ValidationException::withMessages([
                'otp_code' => 'Batas percobaan salah telah terlampaui (maksimal 3 kali). Silakan minta kode baru.',
            ]);
        }

        if (! $otp->verify($code)) {
            $sisa = max(0, EmailOtp::MAX_ATTEMPTS - $otp->attempts);
            if ($sisa === 0) {
                $otp->delete();
                throw ValidationException::withMessages([
                    'otp_code' => 'Kode salah. Batas percobaan habis. Silakan minta kode baru.',
                ]);
            }

            throw ValidationException::withMessages([
                'otp_code' => "Kode verifikasi salah. Sisa percobaan: {$sisa} kali.",
            ]);
        }

        // Kode valid — langsung hanguskan agar tidak bisa dipakai ulang
        $otp->delete();
    }
}
