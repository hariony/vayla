<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un séjour confirmé par un voyageur. Pas de note : des faits cochés, et ce
 * qui a été signalé.
 */
class StayConfirmation extends Model
{
    protected $fillable = [
        'listing_id', 'booking_id', 'traveller', 'traveller_from', 'nights', 'stayed_on',
        'points', 'flagged', 'comment', 'mismatch', 'is_demo', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'array',
            'flagged' => 'array',
            'stayed_on' => 'date',
            'confirmed_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
