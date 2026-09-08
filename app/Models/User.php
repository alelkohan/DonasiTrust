<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_DONATUR = 'donatur';
    public const ROLE_PENGAJU = 'pengaju';
    public const ROLE_ADMIN = 'admin';

    public const VERIFICATION_UNVERIFIED = 'unverified';
    public const VERIFICATION_PENDING = 'pending';
    public const VERIFICATION_VERIFIED = 'verified';
    public const VERIFICATION_REJECTED = 'rejected';

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'password',
        'role',
        'phone',
        'organization',
        'identity_document_path',
        'identity_number_last4',
        'identity_number_hash',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'pending_bank_name',
        'pending_bank_account_number',
        'pending_bank_account_holder',
        'verification_status',
        'verification_note',
        'verified_at',
        'verified_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'identity_document_path',
        'identity_number_last4',
        'identity_number_hash',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'pending_bank_name',
        'pending_bank_account_number',
        'pending_bank_account_holder',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function emailOtps(): HasMany
    {
        return $this->hasMany(EmailOtp::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isPengaju(): bool
    {
        return $this->role === self::ROLE_PENGAJU;
    }

    public function isDonatur(): bool
    {
        return $this->role === self::ROLE_DONATUR;
    }

    /**
     * HMAC-SHA256 dari NIK, dipakai untuk mendeteksi satu identitas yang
     * dipakai banyak akun TANPA menyimpan NIK-nya di basis data.
     *
     * Awalan 'identity|' memisahkan domain penggunaan dari kode verifikasi
     * kuitansi, supaya dua fitur berbeda tidak menghasilkan nilai yang sama
     * walau memakai kunci rahasia yang sama.
     */
    public static function hashIdentityNumber(string $nik): string
    {
        $secret = (string) config('donasi.receipt_secret');

        if ($secret === '') {
            throw new \RuntimeException('DONASI_RECEIPT_SECRET belum diisi di file .env.');
        }

        return hash_hmac('sha256', 'identity|'.preg_replace('/\D/', '', $nik), $secret);
    }

    /** Rekening tujuan pencairan sudah lengkap? */
    public function hasPayoutAccount(): bool
    {
        return filled($this->bank_name)
            && filled($this->bank_account_number)
            && filled($this->bank_account_holder);
    }

    /** Tampilan aman untuk publik: BCA ****4821 a.n. AHMAD FAUZI */
    public function maskedPayoutAccount(): ?string
    {
        if (! $this->hasPayoutAccount()) {
            return null;
        }

        $nomor = preg_replace('/\s+/', '', (string) $this->bank_account_number);
        $ekor = substr($nomor, -4);

        return sprintf('%s %s%s a.n. %s',
            strtoupper($this->bank_name),
            str_repeat('*', max(0, strlen($nomor) - 4)),
            $ekor,
            $this->bank_account_holder,
        );
    }

    public function hasPendingPayoutAccount(): bool
    {
        return filled($this->pending_bank_name)
            && filled($this->pending_bank_account_number)
            && filled($this->pending_bank_account_holder);
    }

    public function maskedPendingPayoutAccount(): ?string
    {
        if (! $this->hasPendingPayoutAccount()) {
            return null;
        }

        $nomor = preg_replace('/\s+/', '', (string) $this->pending_bank_account_number);
        $ekor = substr($nomor, -4);

        return sprintf('%s %s%s a.n. %s',
            strtoupper($this->pending_bank_name),
            str_repeat('*', max(0, strlen($nomor) - 4)),
            $ekor,
            $this->pending_bank_account_holder,
        );
    }

    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }


    /** Hanya pengaju terverifikasi yang boleh mengajukan kampanye. */
    public function canSubmitCampaign(): bool
    {
        return $this->isPengaju() && $this->isVerified();
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_PENGAJU => 'Pengaju Kampanye',
            default => 'Donatur',
        };
    }

    public function verificationLabel(): string
    {
        return match ($this->verification_status) {
            self::VERIFICATION_PENDING => 'Menunggu verifikasi',
            self::VERIFICATION_VERIFIED => 'Terverifikasi',
            self::VERIFICATION_REJECTED => 'Ditolak',
            default => 'Belum diverifikasi',
        };
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => route('admin.dashboard'),
            self::ROLE_PENGAJU => route('pengaju.dashboard'),
            default => route('donatur.dashboard'),
        };
    }
}
