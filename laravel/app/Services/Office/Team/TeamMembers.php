<?php

namespace App\Services\Office\Team;

use App\Contracts\Office\ActionJournal;
use App\Contracts\Office\AdminPasswords;
use App\Contracts\Repositories\AdminRepositoryInterface;
use App\DTOs\Office\NewMember;
use App\DTOs\Office\NewMemberDto;
use App\Enums\AdminActionKind;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;

/** Ajouter et retirer des membres de l'équipe. */
final class TeamMembers
{
    public function __construct(
        private AdminRepositoryInterface $admins,
        private AdminPasswords $motsDePasse,
        private ActionJournal $journal,
    ) {}

    /**
     * Un membre ajouté reçoit un mot de passe **provisoire**, à lui transmettre
     * de vive voix. Rien ne part par e-mail. Il en choisira un à sa première
     * connexion.
     */
    public function ajouter(Admin $admin, NewMemberDto $nouveau): NewMember
    {
        $membre = $this->admins->creer($nouveau->nom, $nouveau->email);
        $motDePasse = $this->motsDePasse->provisoire($membre);

        $this->journal->consigner($admin, AdminActionKind::AdminAdded, $membre, "{$membre->name} ({$membre->email}) ajouté à l'équipe.");

        return new NewMember($membre, $motDePasse);
    }

    /**
     * **Jamais soi-même, jamais le dernier** : l'un comme l'autre fermerait le
     * back-office de l'intérieur, et il faudrait une ligne de commande sur le
     * serveur pour le rouvrir.
     */
    public function retirer(Admin $admin, Admin $membre): void
    {
        if ($membre->is($admin)) {
            throw new OfficeRefusal("Vous ne pouvez pas vous retirer vous-même : demandez à un autre membre de l'équipe.");
        }

        if ($this->admins->nombre() <= 1) {
            throw new OfficeRefusal("C'est le dernier membre de l'équipe : le retirer fermerait le back-office.");
        }

        $this->journal->consigner($admin, AdminActionKind::AdminRemoved, $membre, "{$membre->name} ({$membre->email}) retiré de l'équipe.");
        $this->admins->supprimer($membre);
    }
}
