<?php

namespace App\Models;

use App\Enums\FeedType;
use Database\Factories\FeedInventoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class FeedInventory extends Model
{
    /** @use HasFactory<FeedInventoryFactory> */
    use HasFactory;

    protected $table = 'feed_inventory';

    protected $fillable = ['brand', 'feed_type', 'quantity', 'unit', 'opened_date', 'depleted_date', 'batch_number', 'total_cost', 'expense_id'];

    protected $casts = [
        'opened_date' => 'date',
        'depleted_date' => 'date',
        'feed_type' => FeedType::class,
        'quantity' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function isActive(): bool
    {
        return $this->depleted_date === null;
    }

    public function durationInDays(): ?int
    {
        if ($this->opened_date === null || $this->depleted_date === null) {
            return null;
        }

        return $this->opened_date->diffInDays($this->depleted_date);
    }

    public function markDepleted(): void
    {
        $this->depleted_date = now()->toDateString();
        $this->save();
    }
}
