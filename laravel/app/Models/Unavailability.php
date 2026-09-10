<?php

namespace App\Models;

use App\Enums\BlockReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une période où le logement n'est pas libre. `ends_on` est la dernière nuit
 * occupée, pas la date de départ.
 */
class Unavailability extends Model
{
    protected $fillable = ['listing_id', 'starts_on', 'ends_on', 'reason'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            // Vocabulaire fermé : un motif est une note que le propriétaire
            // s'écrit, pas une phrase à composer sur un téléphone.
            'reason' => BlockReason::class,
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
