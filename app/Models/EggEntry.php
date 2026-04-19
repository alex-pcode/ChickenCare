<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\EggEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EggEntry extends Model
{
    /** @use HasFactory<EggEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'count',
        'size',
        'color',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForWeek(Builder $query, ?Carbon $date = null): Builder
    {
        $date ??= now();
        return $query->whereBetween('date', [
            $date->copy()->startOfWeek(),
            $date->copy()->endOfWeek(),
        ]);
    }

    public function scopeForMonth(Builder $query, ?Carbon $date = null): Builder
    {
        $date ??= now();
        return $query->whereMonth('date', $date->month)
                     ->whereYear('date', $date->year);
    }
}
