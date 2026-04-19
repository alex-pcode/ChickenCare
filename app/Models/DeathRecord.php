<?php

namespace App\Models;

use Database\Factories\DeathRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeathRecord extends Model
{
    /** @use HasFactory<DeathRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'batch_id',
        'date',
        'count',
        'cause',
        'description',
        'notes',
    ];

    protected $casts = [
        'date'  => 'date',
        'count' => 'integer',
        'cause' => \App\Enums\DeathCause::class,
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
