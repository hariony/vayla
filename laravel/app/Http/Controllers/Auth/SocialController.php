<?php

namespace App\Http\Controllers\Auth;

use App\Enums\EspaceSocial;
use App\Enums\SocialProvider;
use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleIdToken;
use App\Services\Auth\LiaisonRefusee;
use App\Services\Auth\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * « Continuer avec Google / Facebook / Apple ».
 *
 * **Un seul contrôleur pour les trois.** Le fournisseur arrive par la route et
 * passe par l'enum : trois méthodes identiques auraient divergé, et la copie
 * oubliée aurait été celle qui ne vérifie pas l'adresse.
 *
 * **Le fournisseur inconnu est un 404, pas un 500.** Sans la liste fermée,
 * `/auth/nimportequoi` atteint Socialite, qui lève une exception de
 * configuration — une page d'erreur là où la bonne réponse est « ça n'existe
 * pas ».
 *
 * **Aucun jeton n'est journalisé, jamais.** Les échecs OAuth sont écrits sans
 * le contenu de la requête : un `dump()` de la réponse du fournisseur met un
 * `access_token` dans les logs, et les logs se copient.
 *
 * **Le message rendu à l'utilisateur est générique.** Détailler la cause
 * (« signature invalide », « state expiré ») renseigne un attaquant sur l'état
 * du dispositif sans aider personne d'autre.
 */
class SocialController extends Controller
{
    /** La clé de session qui retient l'espace visé pendant l'aller-retour. */
    private const ESPACE = 'social.espace';

    /** L'identité sociale en attente, le temps que la fiche crée le compte. */
    public const IDENTITE = 'social.identite';

    public function __construct(
        private SocialAuthService $social,
    ) {}

    /**
     * Départ vers le fournisseur, pour l'espace voyageur.
     *
     * `state` est posé par Socialite (session, non `stateless`) : c'est la
     * protection CSRF du flux.
     */
    public function redirect(string $provider, Request $request): RedirectResponse
    {
        return $this->partir($provider, $request, EspaceSocial::Voyageur);
    }

    /** Le même départ, depuis l'écran du propriétaire. */
    public function redirectOwner(string $provider, Request $request): RedirectResponse
    {
        return $this->partir($provider, $request, EspaceSocial::Proprietaire);
    }

    /**
     * **L'espace visé voyage en session, pas dans l'URL de rappel.**
     *
     * Celle-ci est déclarée une fois dans la console du fournisseur et ne peut
     * pas varier selon le bouton cliqué. La session survit au même aller-retour
     * que le `state`, et éviter deux URL de rappel par fournisseur nous épargne
     * de les tenir synchronisées dans trois consoles.
     */
    private function partir(string $provider, Request $request, EspaceSocial $espace): RedirectResponse
    {
        $choisi = $this->provider($provider);

        $request->session()->put(self::ESPACE, $espace->value);

        return Socialite::driver($choisi->value)->redirect();
    }

    /** Retour du fournisseur — commun aux deux espaces. */
    public function callback(string $provider, Request $request): RedirectResponse
    {
        $choisi = $this->provider($provider);

        // Sans marqueur, on retombe sur le voyageur : c'est la porte publique,
        // et une session expirée ne doit pas ouvrir l'espace propriétaire.
        $espace = EspaceSocial::tryFrom((string) $request->session()->pull(self::ESPACE))
            ?? EspaceSocial::Voyageur;

        // L'utilisateur a refusé, ou le fournisseur a renvoyé une erreur : ce
        // n'est pas une panne, et ça ne mérite ni trace ni message alarmant.
        if ($request->has('error')) {
            return redirect()->route($espace->retour())
                ->withErrors(['email' => 'La connexion avec '.$choisi->label().' a été interrompue.']);
        }

        try {
            $identite = Socialite::driver($choisi->value)->user();
        } catch (Throwable $e) {
            // Le message, jamais la requête : elle porte le code d'échange.
            Log::warning('Échec OAuth', ['provider' => $choisi->value, 'raison' => $e->getMessage()]);

            return redirect()->route($espace->retour())
                ->withErrors(['email' => 'La connexion avec '.$choisi->label().' n’a pas abouti. Réessayez, ou entrez votre adresse.']);
        }

        try {
            ['compte' => $compte, 'nouveau' => $nouveau] = $this->social->rattacher($choisi, $identite, $espace);
        } catch (LiaisonRefusee $refus) {
            return redirect()->route($espace->retour())->withErrors(['email' => $refus->getMessage()]);
        }

        // Propriétaire inconnu : rien n'est créé, on passe par la fiche qui
        // demande le numéro. L'adresse vérifiée et l'identité sociale voyagent
        // en session — le lien sera posé quand le compte existera.
        if ($compte === null) {
            $request->session()->put('inscription.proprietaire.verifiee', $identite->getEmail()
                ? mb_strtolower(trim($identite->getEmail()))
                : null);
            $request->session()->put(self::IDENTITE, [
                'provider' => $choisi->value,
                'id' => (string) $identite->getId(),
                'email' => $identite->getEmail(),
                'name' => $identite->getName(),
                'avatar' => $identite->getAvatar(),
                'verifie' => $choisi->garantitLAdresse(is_array($identite->user) ? $identite->user : []),
            ]);

            return redirect()->route('owner.register.profile');
        }

        Auth::guard($espace->garde())->login($compte, remember: true);

        // Contre la fixation de session : l'identifiant qui a servi à arriver
        // ici ne doit pas être celui qui porte la session authentifiée.
        $request->session()->regenerate();

        // **Un propriétaire neuf n'est pas encore joignable.** Vayla le
        // rappelle sur WhatsApp pour la vérification : sans numéro, son annonce
        // ne dépasserait jamais le niveau 1. Il passe donc par la fiche, comme
        // celui qui arrive par un code.
        return redirect()->intended(route($espace->destination()))->with(
            'succes',
            $nouveau ? 'Votre compte est ouvert.' : 'Vous êtes connecté.'
        );
    }

    /**
     * **Google One Tap** — l'invite qui s'affiche en haut à droite.
     *
     * Le navigateur ne nous envoie pas un code à échanger mais un **jeton
     * d'identité déjà signé**. Il n'y a donc pas de `state` : la protection
     * CSRF est celle de Laravel sur ce POST, et l'authenticité vient de la
     * signature du jeton — vérifiée par `GoogleIdToken`, jamais supposée.
     *
     * **Une identité venue du front n'est jamais crue sur parole.** C'est le
     * seul point d'entrée du produit où le navigateur *propose* une identité ;
     * partout ailleurs, la redirection part de chez nous.
     *
     * Le rattachement passe par le **même service** que le flux par
     * redirection : mêmes quatre cas, même refus quand l'adresse n'est pas
     * garantie, donc aucun doublon d'utilisateur.
     */
    public function oneTap(Request $request, GoogleIdToken $jetons): RedirectResponse
    {
        try {
            $identite = $jetons->verifier((string) $request->input('credential'));
        } catch (Throwable $e) {
            Log::warning('One Tap refusé', ['raison' => $e->getMessage()]);

            return back()->withErrors(['email' => 'La connexion Google n’a pas abouti. Entrez votre adresse pour recevoir un code.']);
        }

        try {
            ['compte' => $compte] = $this->social->rattacher(
                SocialProvider::Google,
                $identite,
                EspaceSocial::Voyageur
            );
        } catch (LiaisonRefusee $refus) {
            return back()->withErrors(['email' => $refus->getMessage()]);
        }

        Auth::login($compte, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('traveller.bookings'))->with('succes', 'Vous êtes connecté.');
    }

    /**
     * La liste est fermée, **et un fournisseur non configuré n'existe pas non
     * plus**.
     *
     * Sans le second contrôle, `/auth/apple` sur une installation sans compte
     * Apple redirige vers une page du fournisseur qui refuse un `client_id`
     * vide : l'utilisateur voit une erreur *chez Apple*, où nous ne pouvons
     * rien expliquer. Un 404 chez nous est plus honnête, et c'est la même
     * règle qui retire le bouton de l'écran.
     */
    private function provider(string $nom): SocialProvider
    {
        $choisi = SocialProvider::tryFrom($nom) ?? throw new NotFoundHttpException;

        if (! config("services.{$choisi->value}.client_id")) {
            throw new NotFoundHttpException;
        }

        return $choisi;
    }
}
