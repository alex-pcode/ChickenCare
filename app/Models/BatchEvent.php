<?php

namespace App\Models;

use Database\Factories\BatchEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchEvent extends Model
{
    /** @use HasFactory<BatchEventFactory> */
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'user_id',
        'date',
        'type',
        'description',
        'affected_count',
        'notes',
    ];

    protected $casts = [
        'date'           => 'date',
        'affected_count' => 'integer',
        'type'           => \App\Enums\BatchEventType::class,
    ];

    public function flockBatch(): BelongsTo
    {
        return $this->belongsTo(FlockBatch::class, 'batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
