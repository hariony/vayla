<?php

namespace App\Services\Office;

use DomainException;

/**
 * Un geste du back-office que les règles du produit refusent.
 *
 * Le message est **écrit pour l'administrateur** et dit quoi faire : « Vérifiez
 * d'abord le numéro du propriétaire », pas « transition invalide ». Il
 * remonte tel quel dans le bandeau de l'écran.
 */
class OfficeRefusal extends DomainException {}
