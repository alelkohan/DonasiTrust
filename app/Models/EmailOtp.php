<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class EmailOtp extends Model
{
    public const MAX_ATTEMPTS = 3;
    public const EXPIRATION_MINUTES = 5;
    public const COOLDOWN_SECONDS = 60;

    public const PURPOSE_DISBURSEMENT_REQUEST = 'disbursement_request';
    public const PURPOSE_BANK_CHANGE = 'bank_change';
    public const PURPOSE_DISBURSEMENT_RELEASE = 'disbursement_release';
    public const PURPOSE_PASSWORD_CHANGE = 'password_change';

    protected $fillable = [
        'user_id',
        'email',
        'purpose',
        'code_hash',
        'attempts',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    public function verify(string $code): bool
    {
        if ($this->isExpired() || $this->hasExceededAttempts()) {
            return false;
        }

        if (Hash::check($code, $this->code_hash)) {
            return true;
        }

        $this->increment('attempts');

        return false;
    }

    public static function purposeLabel(string $purpose): string
    {
        return match ($purpose) {
            self::PURPOSE_BANK_CHANGE => 'Penggantian Rekening Pencairan',
            self::PURPOSE_DISBURSEMENT_REQUEST => 'Pengajuan Pencairan Dana',
            self::PURPOSE_DISBURSEMENT_RELEASE => 'Pelepasan Dana Pencairan (Admin)',
            self::PURPOSE_PASSWORD_CHANGE => 'Penggantian Kata Sandi Akun',
            default => 'Verifikasi Transaksi',
        };
    }
}
