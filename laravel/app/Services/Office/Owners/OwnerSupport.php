<?php

namespace App\Services\Office\Owners;

use App\Contracts\Office\ActionJournal;
use App\Contracts\Owners\OwnerAccess;
use App\Contracts\Repositories\OfficeOwnerRepositoryInterface;
use App\Enums\AdminActionKind;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;
use App\Models\Owner;
use App\Support\Telephone;

/** Les gestes de l'équipe envers un propriétaire : confirmer son numéro, lui renvoyer son lien. */
final class OwnerSupport
{
    public function __construct(
        private OfficeOwnerRepositoryInterface $proprietaires,
        private OwnerAccess $acces,
        private ActionJournal $journal,
    ) {}

    /**
     * Le numéro est confirmé **par l'appel de vérification**, et par rien
     * d'autre. C'est le geste qui ouvre le niveau 2 ; le journal garde qui l'a
     * appuyé.
     */
    public function verifierTelephone(Admin $admin, Owner $owner): void
    {
        if ($owner->telephoneVerifie()) {
            throw new OfficeRefusal('Ce numéro est déjà vérifié.');
        }

        $this->proprietaires->marquerNumeroVerifie($owner);

        $this->journal->consigner($admin, AdminActionKind::PhoneVerified, $owner,
            "Numéro de {$owner->name} vérifié par appel ({$owner->telephone()?->lisible()}).");
    }

    public function renvoyerLien(Admin $admin, Owner $owner): void
    {
        if (Telephone::depuis($owner->phone)?->estMobile() !== true) {
            throw new OfficeRefusal("Ce numéro n'est pas un mobile : WhatsApp n'y arrive pas. Corrigez-le avec le propriétaire d'abord.");
        }

        $this->acces->renvoyer($owner);

        $this->journal->consigner($admin, AdminActionKind::AccessLinkSent, $owner, "Lien d'accès de {$owner->name} remis dans la file WhatsApp.");
    }
}
