<?php

namespace App\Services\Office;

use App\Enums\AdminActionKind;
use App\Models\Admin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * La porte du back-office : une adresse et un mot de passe.
 *
 * **Le code par e-mail a été retiré ici, et seulement ici.** Il tient pour les
 * voyageurs et les propriétaires, qui reviennent quelques fois par mois ;
 * l'équipe ouvre le back-office vingt fois par jour, et attendre un e-mail à
 * chaque session n'est pas un outil de travail.
 *
 * Ce qui protège la porte, dans l'ordre où ça joue :
 *
 * - **Un échec ne dit jamais laquelle des deux valeurs est fausse.** « Cette
 *   adresse n'existe pas » ferait de ce formulaire un annuaire de l'équipe —
 *   et chaque adresse y est celle d'un compte qui peut publier des annonces.
 * - **Le temps de réponse ne le dit pas non plus.** Une adresse inconnue passe
 *   quand même par une vérification bcrypt, sur un leurre : sans elle, la
 *   réponse arriverait plus vite pour une adresse inconnue, et le chronomètre
 *   ferait le travail que le message refuse de faire.
 * - **Cinq essais par minute pour une adresse depuis une même machine, vingt
 *   par heure pour une adresse tout court.** Le premier arrête qui devine au
 *   clavier ; le second, qui devine depuis plusieurs machines. Un collègue qui
 *   s'est trompé deux fois n'est gêné par aucun des deux.
 * - **Pas de « rester connecté ».** Une session du back-office oubliée sur un
 *   poste partagé peut publier des annonces et annuler des séjours.
 */
class OfficeAuthService
{
    /** Un hachage bcrypt sans mot de passe connu : il sert à égaliser les temps de réponse. */
    private const LEURRE = '$2y$12$ALXBTokin4n1ryee6V0OnOzoZhqn5dvlh16w0YFhbvyxiKUD6Do/y';

    public const ESSAIS_PAR_MINUTE = 5;

    public const ESSAIS_PAR_HEURE = 20;

    public function __construct(
        private AdminJournal $journal,
    ) {}

    /**
     * L'administrateur si l'adresse et le mot de passe vont ensemble, `null` sinon.
     *
     * @throws OfficeThrottled trop d'essais ; porte le délai en secondes
     */
    public function connecter(string $email, string $motDePasse, string $ip): ?Admin
    {
        $email = mb_strtolower(trim($email));
        $parMachine = 'office-login:'.sha1($email.'|'.$ip);
        $parAdresse = 'office-login-adresse:'.sha1($email);

        foreach ([[$parMachine, self::ESSAIS_PAR_MINUTE], [$parAdresse, self::ESSAIS_PAR_HEURE]] as [$cle, $plafond]) {
            if (RateLimiter::tooManyAttempts($cle, $plafond)) {
                throw new OfficeThrottled(RateLimiter::availableIn($cle));
            }
        }

        $admin = Admin::query()->where('email', $email)->first();

        if ($admin?->password) {
            $bon = Hash::check($motDePasse, $admin->password);
        } else {
            // Le résultat est jeté : seul compte le temps passé à le calculer.
            Hash::check($motDePasse, self::LEURRE);
            $bon = false;
        }

        if (! $bon) {
            RateLimiter::hit($parMachine, 60);
            RateLimiter::hit($parAdresse, 3600);

            return null;
        }

        RateLimiter::clear($parMachine);
        $admin->forceFill(['last_login_at' => Carbon::now()])->save();

        return $admin;
    }

    /**
     * La personne choisit son mot de passe. Il devient **le sien** : la
     * colonne `password_set_at` le dit, et le back-office s'ouvre en entier.
     */
    public function choisir(Admin $admin, string $motDePasse): void
    {
        $admin->forceFill([
            'password' => $motDePasse,
            'password_set_at' => Carbon::now(),
        ])->save();
    }

    /**
     * Un mot de passe provisoire, posé par quelqu'un d'autre : pour un nouveau
     * membre, ou pour celui qui a perdu le sien.
     *
     * **Il n'est montré qu'une fois**, à celui qui l'a demandé, pour qu'il le
     * transmette de vive voix. Il n'est écrit nulle part, ni envoyé par e-mail
     * — un mot de passe dans une boîte de réception y reste. Et il ne vaut
     * qu'une connexion : la suivante exige d'en choisir un.
     */
    public function provisoire(Admin $admin): string
    {
        $motDePasse = self::genererProvisoire();

        $admin->forceFill(['password' => $motDePasse, 'password_set_at' => null])->save();

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
}
