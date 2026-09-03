<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Campaign extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    public const CATEGORIES = [
        'pendidikan' => 'Pendidikan',
        'kesehatan' => 'Kesehatan',
        'bencana' => 'Bencana Alam',
        'sosial' => 'Sosial & Kemanusiaan',
        'lingkungan' => 'Lingkungan',
        'infrastruktur' => 'Infrastruktur Publik',
    ];

    protected $fillable = [
        'user_id', 'title', 'slug', 'category', 'cover_path', 'summary', 'description',
        'target_amount', 'collected_amount', 'disbursed_amount', 'deadline',
        'status', 'review_note', 'submitted_at', 'reviewed_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'target_amount' => 'integer',
            'collected_amount' => 'integer',
            'disbursed_amount' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CampaignItem::class)->orderBy('sort_order');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('sequence');
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function paidDonations(): HasMany
    {
        return $this->donations()->where('status', Donation::STATUS_PAID);
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(Disbursement::class);
    }

    public function expenseReports(): HasMany
    {
        return $this->hasMany(ExpenseReport::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_COMPLETED]);
    }

    public function isPublished(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_COMPLETED], true);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function progressPercent(): float
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        return min(100, round($this->collected_amount / $this->target_amount * 100, 1));
    }

    /** Saldo yang terkumpul tapi belum dicairkan. */
    public function remainingBalance(): int
    {
        return max(0, $this->collected_amount - $this->disbursed_amount);
    }

    /** Porsi tahap pertama terhadap total target, dalam persen. */
    public function firstMilestoneShare(): float
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        $pertama = $this->milestones()->orderBy('sequence')->value('amount');

        return $pertama ? round($pertama / $this->target_amount * 100, 1) : 0;
    }

    /**
     * Tahap pertama menyerap lebih dari 40% dana.
     * Bukan pelanggaran — ada proyek yang memang berat di awal (pengeboran
     * sumur, misalnya) — tapi wajib ditandai supaya donatur dan admin sadar
     * bahwa perlindungan bertahapnya lebih tipis di kampanye ini.
     */
    public function isFrontLoaded(): bool
    {
        return $this->firstMilestoneShare() > 40;
    }

    public function daysLeft(): ?int
    {
        if (! $this->deadline) {
            return null;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($this->deadline->startOfDay(), false));
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draf',
            self::STATUS_PENDING => 'Menunggu review',
            self::STATUS_APPROVED => 'Aktif',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_COMPLETED => 'Selesai',
            default => (string) $this->status,
        };
    }

    public static function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'kampanye';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /** Hitung ulang total terkumpul & tercairkan langsung dari tabel transaksi. */
    public function recalculateTotals(): void
    {
        $this->collected_amount = (int) $this->donations()
            ->where('status', Donation::STATUS_PAID)->sum('amount');

        $this->disbursed_amount = (int) $this->disbursements()
            ->where('status', Disbursement::STATUS_RELEASED)->sum('amount');

        $this->save();
    }
}
