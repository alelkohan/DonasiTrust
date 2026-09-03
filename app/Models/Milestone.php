<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Milestone extends Model
{
    public const STATUS_LOCKED = 'locked';
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DISBURSED = 'disbursed';
    public const STATUS_REPORTED = 'reported';

    protected $fillable = [
        'campaign_id', 'sequence', 'title', 'description', 'amount', 'status',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'amount' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(Disbursement::class);
    }

    public function expenseReports(): HasMany
    {
        return $this->hasMany(ExpenseReport::class);
    }

    /**
     * Kalimat yang menjawab "apa yang harus saya lakukan sekarang?" — bukan
     * sekadar nama status. Pengaju tidak berpikir dalam kosakata sistem
     * ("locked", "available"); dia ingin tahu langkah berikutnya.
     */
    public function guidance(): string
    {
        $campaign = $this->campaign;

        return match ($this->status) {
            self::STATUS_AVAILABLE => 'Dana tahap ini sudah cukup terkumpul. Ajukan pencairan '
                .'dengan menjelaskan keperluannya.',

            self::STATUS_REQUESTED => 'Pengajuan Anda sedang ditinjau admin. Tidak ada yang perlu '
                .'Anda lakukan sampai ada keputusan.',

            self::STATUS_APPROVED => 'Sudah disetujui. Admin sedang memproses transfer ke rekening '
                .'terverifikasi Anda.',

            self::STATUS_DISBURSED => 'Dana sudah cair. Unggah nota pengeluaran sampai totalnya '
                .'mencapai '.rupiah($this->amount).' agar tahap berikutnya terbuka.',

            self::STATUS_REPORTED => 'Tahap ini selesai dan sudah dipertanggungjawabkan.',

            default => $this->lockedGuidance($campaign),
        };
    }

    /** Penjelasan spesifik kenapa tahap ini masih terkunci. */
    private function lockedGuidance(?Campaign $campaign): string
    {
        if (! $campaign) {
            return 'Tahap ini belum terbuka.';
        }

        $tahapSebelumnya = $campaign->milestones()
            ->where('sequence', '<', $this->sequence)
            ->orderByDesc('sequence')
            ->first();

        if ($tahapSebelumnya && $tahapSebelumnya->status !== self::STATUS_REPORTED) {
            return 'Menunggu tahap '.$tahapSebelumnya->sequence.' selesai dilaporkan. '
                .'Tahap dibuka berurutan.';
        }

        $tersedia = max(0, $campaign->collected_amount - $campaign->disbursed_amount);
        $kurang = max(0, $this->amount - $tersedia);

        if ($kurang > 0) {
            return 'Belum bisa dicairkan. Dana tersedia baru '.rupiah($tersedia)
                .' dari '.rupiah($this->amount).' yang dibutuhkan tahap ini — kurang '
                .rupiah($kurang).'.';
        }

        return 'Tahap ini belum terbuka.';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_AVAILABLE => 'Siap diajukan',
            self::STATUS_REQUESTED => 'Menunggu persetujuan',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_DISBURSED => 'Dana cair',
            self::STATUS_REPORTED => 'Sudah dilaporkan',
            default => 'Terkunci',
        };
    }
}
