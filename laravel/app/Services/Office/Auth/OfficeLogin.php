<?php

namespace App\Services\Office\Auth;

use App\Contracts\Repositories\AdminRepositoryInterface;
use App\DTOs\Office\LoginDto;
use App\Exceptions\OfficeThrottled;
use App\Models\Admin;
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
final class OfficeLogin
{
    /** Un hachage bcrypt sans mot de passe connu : il sert à égaliser les temps de réponse. */
    private const LEURRE = '$2y$12$ALXBTokin4n1ryee6V0OnOzoZhqn5dvlh16w0YFhbvyxiKUD6Do/y';

    public const ESSAIS_PAR_MINUTE = 5;

    public const ESSAIS_PAR_HEURE = 20;

    public function __construct(
        private AdminRepositoryInterface $admins,
    ) {}

    /**
     * L'administrateur si l'adresse et le mot de passe vont ensemble, `null` sinon.
     *
     * @throws OfficeThrottled trop d'essais ; porte le délai en secondes
     */
    public function connecter(LoginDto $essai): ?Admin
    {
        $parMachine = 'office-login:'.sha1($essai->email.'|'.$essai->ip);
        $parAdresse = 'office-login-adresse:'.sha1($essai->email);

        $this->refuserSiTropDEssais([$parMachine => self::ESSAIS_PAR_MINUTE, $parAdresse => self::ESSAIS_PAR_HEURE]);

        $admin = $this->admins->parEmail($essai->email);

        if (! $this->correspond($admin, $essai->motDePasse)) {
            RateLimiter::hit($parMachine, 60);
            RateLimiter::hit($parAdresse, 3600);

            return null;
        }

        RateLimiter::clear($parMachine);
        $this->admins->marquerConnexion($admin);

        return $admin;
    }

    /** @param  array<string, int>  $plafonds  clé du limiteur → nombre d'essais */
    private function refuserSiTropDEssais(array $plafonds): void
    {
        foreach ($plafonds as $cle => $plafond) {
            if (RateLimiter::tooManyAttempts($cle, $plafond)) {
                throw new OfficeThrottled(RateLimiter::availableIn($cle));
            }
        }
    }

    private function correspond(?Admin $admin, string $motDePasse): bool
    {
        if ($admin?->password) {
            return Hash::check($motDePasse, $admin->password);
        }

        // Le résultat est jeté : seul compte le temps passé à le calculer.
        Hash::check($motDePasse, self::LEURRE);

        return false;
    }
}
