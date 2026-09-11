<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Enums\PropertyType;
use App\Enums\TrustLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    protected $fillable = [
        'slug', 'title', 'destination_id', 'owner_id', 'scene', 'kind',
        'summary', 'description', 'guests', 'bedrooms', 'beds', 'bathrooms',
        'surface', 'price', 'min_nights', 'max_nights', 'check_in_from',
        'check_out_before', 'pets_allowed', 'smoking_allowed', 'events_allowed',
        'trust_level', 'featured', 'is_demo', 'status', 'review_note',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'is_demo' => 'boolean',
            'pets_allowed' => 'boolean',
            'smoking_allowed' => 'boolean',
            'events_allowed' => 'boolean',
            'trust_level' => TrustLevel::class,
            'status' => ListingStatus::class,
            'kind' => PropertyType::class,
        ];
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /** Les réservations. Celles qui bloquent des dates sortent du calendrier. */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->orderBy('arrival');
    }

    /**
     * La galerie, dans l'ordre. La couverture est la position 0 : c'est la
     * seule définition, il n'y a pas de colonne `photo_id` à côté.
     */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class)
            ->withPivot('position')
            ->orderBy('position');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Les équipements. `highlight` désigne ceux que la carte met en avant,
     * `note` porte la précision du propriétaire.
     */
    /**
     * Les périodes occupées. L'absence de ligne vaut disponibilité : c'est
     * le sens de la table, pas un raccourci.
     */
    public function unavailabilities(): HasMany
    {
        return $this->hasMany(Unavailability::class)->orderBy('starts_on');
    }

    /**
     * Les séjours confirmés. C'est cette relation qui fait passer une
     * annonce au niveau 4 de l'échelle, et c'est l'événement facturable.
     */
    public function confirmations(): HasMany
    {
        return $this->hasMany(StayConfirmation::class)->orderByDesc('confirmed_at');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class)->withPivot(['highlight', 'note']);
    }
}
