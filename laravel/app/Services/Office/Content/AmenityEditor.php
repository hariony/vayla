<?php

namespace App\Services\Office\Content;

use App\Contracts\Office\ActionJournal;
use App\Contracts\Repositories\OfficeAmenityRepositoryInterface;
use App\DTOs\Content\AmenityDto;
use App\Enums\AdminActionKind;
use App\Enums\PositionShift;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;
use App\Models\Amenity;
use App\Services\Support\PositionSwapper;
use App\Services\Support\UniqueSlug;

/**
 * Le vocabulaire des équipements. Il s'allonge au contact des logements réels
 * — ajouter une ligne est une opération de donnée. **Un équipement coché ne se
 * supprime pas** : ce serait retirer en silence la déclaration de
 * propriétaires, et le panneau énergie changerait d'avis sans que personne ait
 * rien touché.
 */
final class AmenityEditor
{
    public function __construct(
        private OfficeAmenityRepositoryInterface $equipements,
        private UniqueSlug $slugs,
        private PositionSwapper $positions,
        private ActionJournal $journal,
    ) {}

    public function creer(Admin $admin, AmenityDto $saisie): Amenity
    {
        $cle = $this->slugs->pour($saisie->label, fn (string $s) => $this->equipements->cleExiste($s));
        $equipement = $this->equipements->creer($cle, $this->equipements->positionSuivante($saisie->group), $saisie);

        $this->journal->consigner($admin, AdminActionKind::AmenitySaved, $equipement,
            "Équipement « {$equipement->label} » ({$equipement->group->label()}) ajouté.");

        return $equipement;
    }

    public function modifier(Admin $admin, Amenity $equipement, AmenityDto $saisie): void
    {
        $this->equipements->modifier($equipement, $saisie);

        $this->journal->consigner($admin, AdminActionKind::AmenitySaved, $equipement,
            "Équipement modifié : « {$equipement->label} » ({$equipement->group->label()}).");
    }

    public function deplacer(Admin $admin, Amenity $equipement, PositionShift $sens): void
    {
        $ordre = $this->positions->deplacer($this->equipements->ordre($equipement->group), $equipement->id, $sens);

        if ($ordre !== null) {
            $this->equipements->ordonner($ordre);
        }

        $this->journal->consigner($admin, AdminActionKind::AmenitySaved, $equipement, "Équipement « {$equipement->label} » déplacé dans sa rubrique.");
    }

    public function supprimer(Admin $admin, Amenity $equipement): void
    {
        $n = $this->equipements->nombreAnnonces($equipement);

        if ($n > 0) {
            throw new OfficeRefusal("« {$equipement->label} » est déclaré par {$n} logement".($n > 1 ? 's' : '').' : le supprimer effacerait leur déclaration. Renommez-le plutôt.');
        }

        $this->journal->consigner($admin, AdminActionKind::AmenityDeleted, $equipement, "Équipement « {$equipement->label} » supprimé.");
        $this->equipements->supprimer($equipement);
    }
}
