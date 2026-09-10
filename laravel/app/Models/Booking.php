<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $fillable = [
        'reference', 'listing_id', 'traveller', 'traveller_phone', 'traveller_email',
        'guests', 'message', 'arrival', 'departure', 'nights', 'price_per_night',
        'total', 'commission_rate', 'status', 'hold_expires_at', 'answered_at',
        'completed_at', 'closed_reason', 'owner_read_at', 'traveller_read_at', 'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'arrival' => 'date',
            'departure' => 'date',
            'hold_expires_at' => 'datetime',
            'answered_at' => 'datetime',
            'completed_at' => 'datetime',
            'owner_read_at' => 'datetime',
            'traveller_read_at' => 'datetime',
            'nights' => 'integer',
            'guests' => 'integer',
            // Des entiers, jamais des flottants sur de l'argent — et SQLite
            // les rend en flottant sans ce cast.
            'price_per_night' => 'integer',
            'total' => 'integer',
            'commission_rate' => 'decimal:4',
            'status' => BookingStatus::class,
            'is_demo' => 'boolean',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function confirmation(): HasOne
    {
        return $this->hasOne(StayConfirmation::class);
    }

    /** Le fil d'échange, du plus ancien au plus récent : on lit une conversation dans l'ordre. */
    public function messages(): HasMany
    {
        return $this->hasMany(BookingMessage::class)->orderBy('created_at');
    }

    /**
     * La commission due, en ariary entiers.
     *
     * Calculée depuis les valeurs FIGÉES à la réservation, jamais depuis le
     * tarif courant de l'annonce : une facture qui bouge après coup est une
     * facture qu'on ne paie pas.
     */
    public function commission(): int
    {
        return (int) round($this->total * (float) $this->commission_rate);
    }

    /**
     * Une nuit ne se facture pas parce qu'elle a été réservée, mais parce
     * qu'elle a été **dormie et confirmée**. C'est la seule règle qui rende
     * la facture défendable devant un propriétaire.
     */
    public function isBillable(): bool
    {
        return $this->status->isBillable();
    }
}
