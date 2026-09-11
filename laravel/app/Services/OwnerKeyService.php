<?php

namespace App\Services;

use App\Models\Owner;
use App\Contracts\Repositories\OwnerRepositoryInterface;

/**
 * La rotation des clés d'accès des propriétaires.
 *
 * L'espace propriétaire s'ouvre par une clé et non par un mot de passe : le
 * lien **est** le droit d'accès. C'est le bon choix pour des gens qu'on joint
 * par WhatsApp et dont beaucoup n'ont jamais créé de compte, mais il n'a de
 * sens que si une clé compromise peut mourir — un téléphone perdu, un lien
 * transféré à la mauvaise personne, un gérant qui s'en va.
 *
 * Trois décisions, et elles se tiennent :
 *
 * - **Aucun délai de grâce : l'ancienne clé meurt à l'instant.** On ne tourne
 *   pas une clé par hygiène, on la tourne parce qu'elle est entre de
 *   mauvaises mains. Laisser l'ancienne vivre « encore un jour, le temps que
 *   le propriétaire clique » y laisserait aussi celui qui l'a récupérée.
 * - **La rotation ne s'ouvre pas depuis l'espace propriétaire.** Un bouton
 *   « mon lien a fuité » sur une page qu'on atteint avec le lien serait
 *   offert à celui qui l'a volé — il changerait la clé et enfermerait dehors
 *   le propriétaire légitime. Elle se fait donc **hors bande** : le
 *   propriétaire appelle ou écrit, Vayla l'identifie par son numéro, puis
 *   renvoie le nouveau lien sur ce même numéro.
 * - **On retient le jour où la clé a été posée**, pas qui s'en est servi. La
 *   question qu'on se pose en situation est « depuis combien de temps ce lien
 *   circule ? » ; un journal de connexions serait une autre fonctionnalité,
 *   et il collecterait des données dont on n'a pas l'usage.
 */
class OwnerKeyService
{
    public function __construct(
        private OwnerRepositoryInterface $owners,
    ) {}

    public function trouver(string $identifiant): ?Owner
    {
        return $this->owners->trouver($identifiant);
    }

    /** Pose une clé neuve et renvoie le lien à transmettre. L'ancienne ne vaut plus rien. */
    public function tourner(Owner $owner): string
    {
        $this->owners->poserCle($owner, Owner::nouvelleCle());

        return $this->lien($owner->refresh());
    }

    /**
     * Tourne toutes les clés. Réservé à l'incident — une base copiée, un
     * fichier de sauvegarde égaré : tous les liens envoyés cessent alors de
     * fonctionner, et il faut les renvoyer un par un.
     *
     * @return array<int, array{owner: Owner, lien: string}>
     */
    public function tournerToutes(): array
    {
        return $this->owners->tous()
            ->map(fn (Owner $o) => ['owner' => $o, 'lien' => $this->tourner($o)])
            ->all();
    }

    /**
     * Le lien complet, prêt à coller dans WhatsApp.
     *
     * Il n'ouvre plus l'espace : il ouvre **le compte**. Le propriétaire y
     * arrive connecté, et pose son mot de passe — à la première connexion
     * comme le jour où il l'a perdu.
     */
    public function lien(Owner $owner): string
    {
        return route('owner.access', ['cle' => $owner->access_key]);
    }
}
