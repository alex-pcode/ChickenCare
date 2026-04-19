<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'sale_date',
        'dozen_count',
        'individual_count',
        'total_amount',
        'paid',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'dozen_count' => 'integer',
        'individual_count' => 'integer',
        'total_amount' => 'decimal:2',
        'paid' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withDefault(['name' => 'Walk-in / No Customer']);
    }

    public function scopeForDateRange(Builder $query, ?Carbon $from, ?Carbon $to): Builder
    {
        if ($from !== null) {
            $query->where('sale_date', '>=', $from);
        }

        if ($to !== null) {
            $query->where('sale_date', '<=', $to);
        }

        return $query;
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('paid', true);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('paid', false);
    }

    public function totalEggs(): int
    {
        return $this->dozen_count * 12 + $this->individual_count;
    }
}
