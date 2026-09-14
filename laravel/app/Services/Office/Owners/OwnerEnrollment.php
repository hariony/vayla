<?php

namespace App\Services\Office\Owners;

use App\Contracts\Office\ActionJournal;
use App\Contracts\Owners\OwnerAccess;
use App\Contracts\Repositories\OfficeOwnerRepositoryInterface;
use App\DTOs\Office\NewOwnerDto;
use App\Enums\AdminActionKind;
use App\Models\Admin;
use App\Models\Owner;
use Illuminate\Support\Facades\DB;

/**
 * **L'équipe inscrit un propriétaire elle-même**, pendant qu'il est au
 * téléphone, et lui envoie son lien d'accès.
 *
 * C'est l'autre moitié de la collecte : la publicité amène ceux qui savent
 * s'inscrire seuls, l'appel amène les autres. Sans ce geste, le back-office
 * pouvait saisir une annonce pour un propriétaire existant, mais pas faire
 * naître le compte — il fallait lui demander de s'inscrire en ligne, ce qui
 * est précisément ce qu'il ne fait pas.
 *
 * **Créer n'est pas vérifier** : le numéro reste à confirmer, et c'est l'appel
 * de vérification qui le fera. Le lien part **après** la transaction : écrit
 * dedans, il annoncerait un compte qui n'existe pas si elle était annulée.
 */
final class OwnerEnrollment
{
    public const SOURCE_EQUIPE = 'equipe';

    public function __construct(
        private OfficeOwnerRepositoryInterface $proprietaires,
        private OwnerAccess $acces,
        private ActionJournal $journal,
    ) {}

    public function inscrire(Admin $admin, NewOwnerDto $fiche): Owner
    {
        $owner = DB::transaction(function () use ($admin, $fiche) {
            $owner = $this->proprietaires->creer($fiche, self::SOURCE_EQUIPE);

            $this->journal->consigner($admin, AdminActionKind::OwnerCreated, $owner,
                "{$owner->name} inscrit par l'équipe ({$owner->telephone()?->lisible()}).");

            return $owner;
        });

        $this->acces->renvoyer($owner);

        return $owner;
    }
}
