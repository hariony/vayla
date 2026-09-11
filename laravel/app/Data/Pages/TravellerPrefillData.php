<?php

namespace App\Data\Pages;

use App\Models\User;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

/**
 * **Ce que le compte évite de retaper.** C'est la seule raison pour laquelle
 * Vayla garde un nom et un numéro : sans ce pré-remplissage, ce seraient trois
 * champs collectés pour un dossier, et Vayla n'en constitue pas. Les noms sont
 * ceux des champs du formulaire.
 */
final class TravellerPrefillData extends Data
{
    public function __construct(
        public readonly ?string $traveller,
        #[MapOutputName('traveller_phone')]
        public readonly ?string $travellerPhone,
        #[MapOutputName('traveller_email')]
        public readonly ?string $travellerEmail,
    ) {}

    public static function fromModel(User $compte): self
    {
        return new self($compte->name, $compte->phone, $compte->email);
    }
}
