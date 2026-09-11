<?php

namespace App\Data\Office\Texts;

use App\Models\SiteText;
use Spatie\LaravelData\Data;

/** Un texte du site, pour son champ : sa borne, son original, sa version actuelle. */
final class SiteTextFieldData extends Data
{
    public function __construct(
        public readonly string $cle,
        public readonly string $label,
        public readonly string $type,
        public readonly int $max,
        public readonly ?string $aide,
        public readonly string $defaut,
        public readonly string $valeur,
        public readonly bool $modifie,
        public readonly ?string $le,
    ) {}

    /** @param  array<string, mixed>  $definition  l'entrée de `SiteTextCatalog` */
    public static function depuis(string $cle, array $definition, ?SiteText $reecrit): self
    {
        return new self(
            cle: $cle,
            label: $definition['label'],
            type: $definition['type'],
            max: $definition['max'],
            aide: $definition['aide'] ?? null,
            defaut: $definition['defaut'],
            valeur: $reecrit->value ?? $definition['defaut'],
            modifie: $reecrit !== null,
            le: $reecrit?->updated_at?->toIso8601String(),
        );
    }
}
