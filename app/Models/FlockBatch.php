<?php

namespace App\Models;

use Database\Factories\FlockBatchFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlockBatch extends Model
{
    /** @use HasFactory<FlockBatchFactory> */
    use HasFactory;

    protected $fillable = [
        'batch_name',
        'breed',
        'acquisition_date',
        'initial_count',
        'current_count',
        'hens_count',
        'roosters_count',
        'chicks_count',
        'brooding_count',
        'type',
        'age_at_acquisition',
        'expected_laying_start_date',
        'actual_laying_start_date',
        'source',
        'cost',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'expected_laying_start_date' => 'date',
        'actual_laying_start_date' => 'date',
        'initial_count' => 'integer',
        'current_count' => 'integer',
        'hens_count' => 'integer',
        'roosters_count' => 'integer',
        'chicks_count' => 'integer',
        'brooding_count' => 'integer',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
        'age_at_acquisition' => \App\Enums\BatchAgeAtAcquisition::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function batchEvents(): HasMany
    {
        return $this->hasMany(BatchEvent::class, 'batch_id');
    }

    public function deathRecords(): HasMany
    {
        return $this->hasMany(DeathRecord::class, 'batch_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public static function resolveType(int $hens, int $roosters, int $chicks, int $brooding): string
    {
        $hensAndBrooding = $hens + $brooding;

        if ($hensAndBrooding > 0 && $roosters === 0 && $chicks === 0) {
            return 'hens';
        }

        if ($roosters > 0 && $hensAndBrooding === 0 && $chicks === 0) {
            return 'roosters';
        }

        if ($chicks > 0 && $hensAndBrooding === 0 && $roosters === 0) {
            return 'chicks';
        }

        return 'mixed';
    }
}
