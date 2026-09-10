<?php

namespace App\Services;

use App\Data\TrustLevelData;
use App\Enums\TrustLevel;

/**
 * L'échelle de confiance, mise en forme.
 *
 * Elle ne vient pas de la base : quatre niveaux figés sont une règle métier,
 * pas une donnée éditable. Les rendre modifiables en base reviendrait à
 * permettre qu'un niveau change de sens sous les annonces déjà vérifiées.
 */
class TrustLadderService
{
    /** @return array<int, TrustLevelData> */
    public function ladder(): array
    {
        return array_map(
            static fn (TrustLevel $level) => TrustLevelData::fromEnum($level),
            TrustLevel::ladder()
        );
    }
}
