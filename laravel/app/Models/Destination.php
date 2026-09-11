<?php

namespace App\Models;

use App\Enums\ClimateZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Destination extends Model
{
    protected $fillable = [
        'slug', 'name', 'region', 'climate_zone', 'tagline', 'scene', 'photo_id',
        'featured', 'opened_at', 'airport_code', 'airport_name', 'flight_from_tana',
        'road_route', 'road_km', 'road_hours', 'road_note',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'opened_at' => 'date',
            'climate_zone' => ClimateZone::class,
        ];
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }

    /**
     * La galerie, dans l'ordre. La position 0 est la couverture, et
     * `photo_id` n'en est que la copie — voir `synchroniserCouverture()`.
     */
    public function galerie(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class, 'destination_photo')
            ->withPivot('position')
            ->orderBy('destination_photo.position');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }
}
