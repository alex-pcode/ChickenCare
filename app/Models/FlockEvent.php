<?php

namespace App\Models;

use Database\Factories\FlockEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlockEvent extends Model
{
    /** @use HasFactory<FlockEventFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'type',
        'description',
        'affected_birds',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'affected_birds' => 'integer',
    ];

    public function flockProfile(): BelongsTo
    {
        return $this->belongsTo(FlockProfile::class);
    }
}
