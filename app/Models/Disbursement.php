<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disbursement extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'reference', 'campaign_id', 'milestone_id', 'requested_by', 'amount',
        'purpose', 'payee_bank_name', 'payee_account_number', 'payee_account_holder',
        'supporting_document_path', 'status', 'review_note',
        'reviewed_by', 'reviewed_at', 'released_at',
    ];

    protected $hidden = ['supporting_document_path', 'payee_account_number'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'reviewed_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Rekening tujuan tersamar untuk tampilan publik. */
    public function maskedPayee(): ?string
    {
        if (! filled($this->payee_account_number)) {
            return null;
        }

        $nomor = (string) $this->payee_account_number;

        return sprintf('%s %s%s a.n. %s',
            strtoupper((string) $this->payee_bank_name),
            str_repeat('*', max(0, strlen($nomor) - 4)),
            substr($nomor, -4),
            $this->payee_account_holder,
        );
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_RELEASED => 'Dana dicairkan',
            default => 'Menunggu persetujuan',
        };
    }

    /** PC-2026-00001 — dipanggil SETELAH baris tersimpan, supaya id-nya pasti unik. */
    public function buildReference(): string
    {
        return sprintf('PC-%s-%05d', ($this->created_at ?? now())->year, $this->id);
    }
}
