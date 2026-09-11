<?php

namespace App\DTOs\StayRequests;

/**
 * La recherche en cours, arrivée dans l'adresse de `/demande`. **Une
 * suggestion, jamais un critère** : une valeur illisible est simplement
 * oubliée, elle ne fait pas échouer la page — la règle du séjour pré-rempli
 * sur la fiche.
 */
final readonly class StayRequestPrefillDto
{
    public function __construct(
        public string $destination = '',
        public string $arrival = '',
        public string $departure = '',
        public int $guests = 2,
    ) {}
}
