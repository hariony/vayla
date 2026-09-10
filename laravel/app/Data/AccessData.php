<?php

namespace App\Data;

use App\Models\Destination;
use Spatie\LaravelData\Data;

/**
 * « Y aller » — comment on rejoint une destination.
 *
 * L'information la plus difficile à trouver quand on prépare un séjour à
 * Madagascar, et celle qu'aucune plateforme de location ne publie. Tuléar
 * est à 1 h 30 d'avion ou à deux jours de route : ce n'est pas le même
 * voyage, ni le même budget.
 *
 * `caveat` part avec la donnée et n'est pas décoratif. Les durées routières
 * malgaches varient du simple au double selon la saison et l'état de la
 * chaussée — annoncer « 9 h » sans le dire serait une fausse précision, et
 * une fausse précision sur un site qui vend la vérification coûte cher.
 */
class AccessData extends Data
{
    public function __construct(
        public readonly ?string $airportCode,
        public readonly ?string $airportName,
        public readonly ?string $flight,
        public readonly ?string $route,
        public readonly ?int $km,
        public readonly ?string $hours,
        public readonly ?string $note,
        public readonly string $caveat,
    ) {}

    public static function fromModel(Destination $destination): self
    {
        return new self(
            airportCode: $destination->airport_code,
            airportName: $destination->airport_name,
            flight: $destination->flight_from_tana,
            route: $destination->road_route,
            km: $destination->road_km,
            hours: $destination->road_hours,
            note: $destination->road_note,
            caveat: 'Durées indicatives depuis Antananarivo, très variables selon la saison et l\'état des routes.',
        );
    }

    /** Une destination sans vol ni route déclarés n'affiche pas le bloc. */
    public function isEmpty(): bool
    {
        return $this->flight === null && $this->route === null && $this->note === null;
    }
}
