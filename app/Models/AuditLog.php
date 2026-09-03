<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'actor_label', 'action', 'auditable_type', 'auditable_id',
        'metadata', 'ip_address', 'previous_hash', 'current_hash',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Representasi kanonik dari isi entri — inilah yang di-hash.
     * Urutan kunci dikunci supaya hash bisa direproduksi persis.
     */
    public function canonicalPayload(): string
    {
        return json_encode([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'actor_label' => $this->actor_label,
            'action' => $this->action,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'metadata' => $this->metadata,
            'ip_address' => $this->ip_address,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function computeHash(): string
    {
        return hash('sha256', ($this->previous_hash ?? str_repeat('0', 64)).'|'.$this->canonicalPayload());
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'user.registered' => 'Pendaftaran pengguna',
            'user.verification_submitted' => 'Pengajuan verifikasi identitas',
            'user.verified' => 'Identitas diverifikasi admin',
            'user.rejected' => 'Verifikasi identitas ditolak',
            'campaign.created' => 'Kampanye dibuat',
            'campaign.updated' => 'Kampanye diperbarui',
            'campaign.submitted' => 'Kampanye diajukan untuk review',
            'campaign.approved' => 'Kampanye disetujui admin',
            'campaign.rejected' => 'Kampanye ditolak admin',
            'donation.created' => 'Donasi dibuat',
            'donation.paid' => 'Pembayaran donasi diterima',
            'disbursement.requested' => 'Pencairan diajukan',
            'disbursement.approved' => 'Pencairan disetujui',
            'disbursement.rejected' => 'Pencairan ditolak',
            'disbursement.released' => 'Dana dicairkan',
            'expense.reported' => 'Laporan pengeluaran diunggah',
            'expense.verified' => 'Laporan pengeluaran diverifikasi',
            default => str_replace(['.', '_'], [' ', ' '], $this->action),
        };
    }
}
