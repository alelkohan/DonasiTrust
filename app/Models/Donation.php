<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'reference', 'campaign_id', 'user_id', 'donor_name', 'donor_email',
        'is_anonymous', 'message', 'amount', 'status', 'payment_channel',
        'gateway', 'gateway_reference', 'gateway_payload', 'paid_at', 'verification_code',
    ];

    protected $hidden = ['donor_email', 'gateway_payload'];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'gateway_payload' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Batas waktu sesi pembayaran, dari data yang disimpan saat sesi dibuat.
     * Tidak dihitung ulang dari now(), supaya nilainya tidak bergeser setiap
     * halaman disegarkan.
     */
    public function expiresAt(): \Illuminate\Support\Carbon
    {
        $tersimpan = $this->gateway_payload['expires_at'] ?? null;

        return $tersimpan
            ? \Illuminate\Support\Carbon::parse($tersimpan)
            : ($this->created_at ?? now())->copy()->addHours(2);
    }

    public function isExpired(): bool
    {
        return ! $this->isPaid() && $this->expiresAt()->isPast();
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /** Nama yang boleh ditampilkan publik. */
    public function displayName(): string
    {
        if ($this->is_anonymous) {
            return 'Hamba Allah';
        }

        return $this->donor_name ?: ($this->user?->name ?: 'Donatur');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Lunas',
            self::STATUS_FAILED => 'Gagal',
            self::STATUS_EXPIRED => 'Kedaluwarsa',
            default => 'Menunggu pembayaran',
        };
    }

    /** DT-2026-000001 — berurutan dari id, mudah dibaca manusia di kuitansi. */
    public function buildReference(): string
    {
        return sprintf('DT-%s-%06d', ($this->created_at ?? now())->year, $this->id);
    }
}
