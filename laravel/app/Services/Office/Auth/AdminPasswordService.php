<?php

namespace App\Services\Office\Auth;

use App\Contracts\Office\ActionJournal;
use App\Contracts\Office\AdminPasswords;
use App\Contracts\Repositories\AdminRepositoryInterface;
use App\Enums\AdminActionKind;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;

/**
 * Les mots de passe de l'équipe : celui qu'on choisit, et le provisoire qu'un
 * autre pose pour nous.
 *
 * **Un mot de passe posé par quelqu'un d'autre est provisoire** : il a été vu
 * — par le collègue qui l'a dicté, par le terminal qui l'a affiché — et
 * `EnsureAdminPasswordIsSet` n'ouvre alors que « Mon compte ».
 */
final class AdminPasswordService implements AdminPasswords
{
    public function __construct(
        private AdminRepositoryInterface $admins,
        private ActionJournal $journal,
    ) {}

    /**
     * La personne choisit son mot de passe. Il devient **le sien** : la
     * colonne `password_set_at` le dit, et le back-office s'ouvre en entier.
     */
    public function choisir(Admin $admin, string $motDePasse): void
    {
        $this->admins->poserMotDePasse($admin, $motDePasse, choisi: true);
    }

    /**
     * Un mot de passe provisoire, pour un nouveau membre ou pour celui qui a
     * perdu le sien.
     *
     * **Il n'est montré qu'une fois**, à celui qui l'a demandé, pour qu'il le
     * transmette de vive voix. Il n'est écrit nulle part, ni envoyé par e-mail
     * — un mot de passe dans une boîte de réception y reste. Et il ne vaut
     * qu'une connexion : la suivante exige d'en choisir un.
     */
    public function provisoire(Admin $admin): string
    {
        $motDePasse = self::genererProvisoire();

        $this->admins->poserMotDePasse($admin, $motDePasse, choisi: false);

        return $motDePasse;
    }

    /** Remet à zéro le mot de passe d'un collègue, et le consigne. */
    public function reinitialiser(Admin $par, Admin $membre): string
    {
        if ($membre->is($par)) {
            throw new OfficeRefusal('Pour changer le vôtre, passez par « Mon compte » : il faut connaître l’actuel.');
        }

        $motDePasse = $this->provisoire($membre);

        $this->journal->consigner($par, AdminActionKind::AdminPasswordReset, $membre,
            "Mot de passe de {$membre->name} remis à zéro : il en choisira un à sa prochaine connexion.");

        return $motDePasse;
    }

    /**
     * Seize caractères sans O/0 ni I/l/1 : il se dicte au téléphone, et une
     * confusion de caractère enfermerait dehors quelqu'un qui a le bon.
     */
    public static function genererProvisoire(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';

        return collect(range(1, 4))
            ->map(fn () => collect(range(1, 4))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode(''))
            ->implode('-');
    }
}
