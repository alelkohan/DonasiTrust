<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignItem extends Model
{
    protected $fillable = [
        'campaign_id', 'name', 'quantity', 'unit', 'unit_price', 'subtotal', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'subtotal' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function expenseReports(): HasMany
    {
        return $this->hasMany(ExpenseReport::class);
    }

    /** Total yang sudah dilaporkan terpakai untuk item RAB ini. */
    public function realizedAmount(): int
    {
        return (int) $this->expenseReports()
            ->where('status', ExpenseReport::STATUS_VERIFIED)
            ->sum('amount');
    }
}
