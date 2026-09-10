<?php

namespace App\Models;

use App\Enums\MessageAuthor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un message dans le fil d'une réservation. Relations et casts, pas de logique.
 */
class BookingMessage extends Model
{
    protected $fillable = ['booking_id', 'author', 'body'];

    protected function casts(): array
    {
        return ['author' => MessageAuthor::class];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
