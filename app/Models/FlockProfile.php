<?php

namespace App\Models;

use Database\Factories\FlockProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The aggregate count attributes (`hens`, `roosters`, `chicks`, `brooding`) are retained
 * in the schema for backwards compatibility but are no longer the source of truth for
 * `/flock` overview stats. `App\Services\FlockBatchStatsService::overview()` derives those
 * numbers from `FlockBatch` rows directly — see Story 6 and Resolved Decision #5.
 *
 * @property-read int $flock_size
 *
 * @property-read int $hens      @deprecated use FlockBatchStatsService::overview()
 * @property-read int $roosters  @deprecated use FlockBatchStatsService::overview()
 * @property-read int $chicks    @deprecated use FlockBatchStatsService::overview()
 * @property-read int $brooding  @deprecated use FlockBatchStatsService::overview()
 */
class FlockProfile extends Model
{
    /** @use HasFactory<FlockProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'farm_name',
        'location',
        'flock_size',
        'breed',
        'start_date',
        'hens',
        'roosters',
        'chicks',
        'brooding',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'flock_size' => 'integer',
        'hens' => 'integer',
        'roosters' => 'integer',
        'chicks' => 'integer',
        'brooding' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function flockEvents(): HasMany
    {
        return $this->hasMany(FlockEvent::class);
    }
}
