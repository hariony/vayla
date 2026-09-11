<?php

namespace App\DTOs\Content;

use App\Enums\ClimateZone;
use App\Enums\DestinationScene;

/**
 * Une destination, telle que l'équipe la saisit. **Ni slug ni photo** : le
 * slug naît du nom et ne bouge plus, la couverture vient de la galerie.
 */
final readonly class DestinationDto
{
    public function __construct(
        public string $name,
        public string $region,
        public string $tagline,
        public ClimateZone $climateZone,
        public DestinationScene $scene,
        public bool $featured,
        public ?string $airportCode = null,
        public ?string $airportName = null,
        public ?string $flightFromTana = null,
        public ?string $roadRoute = null,
        public ?int $roadKm = null,
        public ?string $roadHours = null,
        public ?string $roadNote = null,
    ) {}
}
