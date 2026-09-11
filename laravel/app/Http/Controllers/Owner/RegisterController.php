<?php

namespace App\Http\Controllers\Owner;

use App\Enums\SocialProvider;
use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerProfileRequest;
use App\Http\Requests\RegisterOwnerRequest;
use App\Models\Owner;
use App\Services\Auth\PendingRegistration;
use App\Services\Auth\SocialAuthService;
use App\Services\Verification\CodeSendingFailed;
use App\Services\Verification\CodeThrottled;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * **La porte du propriétaire** : adresse, code, puis la fiche s'il est neuf.
 *
 * **Une seule porte pour s'inscrire et pour se connecter**, exactement comme
 * côté voyageur. C'est le code qui décide : le compte existe, on entre ; il
 * n'existe pas, on demande la fiche. Faire choisir avant entre « créer » et
 * « se connecter », c'est demander de trancher une question dont beaucoup
 * n'ont pas la réponse.
 *
 * **Plus de mot de passe.** L'identifiant est devenu l'adresse et non plus le
 * téléphone : l'inscription exige déjà une boîte relevable — sans elle, aucun
 * code n'arrive — donc l'argument qui justifiait le numéro (« beaucoup n'ont
 * pas de boîte qu'ils relèvent ») ne tient plus une fois l'adresse vérifiée.
 * Le téléphone reste ce par quoi Vayla **appelle**, ce qu'il a toujours été de
 * plus utile.
 *
 * « Rester connecté » est posé à la connexion : le propriétaire qui revient
 * répondre à une demande qui expire ne repasse pas par sa boîte à chaque fois.
 *
 * L'inscription d'un propriétaire, en trois temps : adresse, code, fiche.
 *
 * **L'adresse d'abord, seule.** Un formulaire de six champs devant quelqu'un
 * qui n'a encore rien reçu de Vayla est un formulaire qu'on quitte ; une
 * adresse et un bouton, non. Le reste — nom, numéro WhatsApp, mot de passe —
 * vient **après** le code, quand la personne a déjà investi un geste et vu que
 * le service répond.
 *
 * **Le compte n'existe qu'à la troisième étape.** L'adresse vérifiée voyage en
 * session : rien en base tant que la fiche n'est pas remplie, donc aucune
 * adresse squattable et aucun compte propriétaire vide à nettoyer.
 *
 * **S'inscrire n'est pas publier.** Le compte s'ouvre tout seul, mais il naît
 * au niveau 1 — « annonce déclarée » — et une annonce de niveau 1 est presque
 * invisible sur le site. Ce que l'inscription donne, c'est la possibilité de
 * **remplir sa fiche** en attendant l'appel de vérification ; c'est cet appel
 * qui met en ligne. Sans ce palier, n'importe qui se publierait et « vérifié »
 * ne voudrait plus rien dire.
 *
 * **L'écran le dit en toutes lettres**, plutôt que de laisser croire qu'on
 * est en ligne : un propriétaire qui découvre trois jours plus tard que son
 * annonce n'était pas publiée ne revient pas.
 *
 * `phone_verified_at` reste nul : c'est l'e-mail qui est prouvé ici. Le
 * numéro le sera à l'appel, qui est de toute façon le passage obligé.
 */
class RegisterController extends Controller
{
    private const CLE = 'inscription.proprietaire';

    /** L'adresse une fois le code passé, en attente de la fiche. */
    private const CLE_VERIFIEE = 'inscription.proprietaire.verifiee';

    public function __construct(
        private PendingRegistration $inscription,
        private SocialAuthService $social,
    ) {}

    public function form(): Response
    {
        return Inertia::render('Owner/Register');
    }

    /** Le même formulaire, sous l'autre porte : « Me connecter ». */
    public function connexion(): Response
    {
        return Inertia::render('Owner/Login');
    }

    public function store(RegisterOwnerRequest $request): RedirectResponse
    {
        try {
            $this->inscription->ouvrir(self::CLE, $request->validated());
        } catch (CodeThrottled|CodeSendingFailed $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()->route('owner.register.code');
    }

    public function codeForm(): Response|RedirectResponse
    {
        $attente = $this->inscription->enAttente(self::CLE);

        if (! $attente) {
            return redirect()->route('owner.register');
        }

        return Inertia::render('Auth/Code', [
            'email' => $attente['email'],
            'action' => route('owner.register.confirm'),
            'renvoi' => route('owner.register.resend'),
            'retour' => route('owner.register'),
            'attente' => $this->inscription->attenteAvantRenvoi(self::CLE),
        ]);
    }

    /**
     * Le code valide l'adresse — il ne crée pas encore le compte.
     *
     * L'adresse prouvée retourne en session, sous une clé distincte de celle
     * de l'attente : la seconde a été consommée, et laisser la première la
     * réutiliser rouvrirait la porte à un code déjà servi.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $donnees = $this->inscription->confirmer(self::CLE, (string) $request->input('code'));

        if (! $donnees) {
            return back()->withErrors(['code' => 'Code incorrect ou expiré. Vérifiez-le, ou demandez-en un nouveau.']);
        }

        // **Trouver ou créer**, et c'est toute la mécanique de la porte unique.
        // Le code vient de prouver que celui qui le saisit relève cette boîte.
        $owner = Owner::query()->where('email', $donnees['email'])->first();

        if ($owner) {
            Auth::guard('proprietaire')->login($owner, remember: true);
            $request->session()->regenerate();

            return redirect()->route('owner.home');
        }

        $request->session()->put(self::CLE_VERIFIEE, $donnees['email']);

        return redirect()->route('owner.register.profile');
    }

    public function profileForm(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get(self::CLE_VERIFIEE);

        if (! $email) {
            return redirect()->route('owner.register');
        }

        return Inertia::render('Owner/Profile', ['email' => $email]);
    }

    /**
     * La fiche, et seulement là le compte.
     *
     * Le numéro arrive déjà en E.164 : `OwnerProfileRequest` le normalise
     * **avant** la validation, sans quoi la règle d'unicité comparerait une
     * saisie brute à une valeur normalisée et laisserait passer un second
     * compte sur le même numéro écrit autrement.
     */
    public function profile(OwnerProfileRequest $request): RedirectResponse
    {
        $email = $request->session()->get(self::CLE_VERIFIEE);

        if (! $email) {
            return redirect()->route('owner.register');
        }

        $donnees = $request->validated();

        $owner = Owner::create([
            'name' => $donnees['name'],
            'email' => $email,
            'phone' => $donnees['phone'],
            // La clé d'accès est posée dès maintenant : c'est le lien WhatsApp
            // qui ouvre l'espace en un geste, sans passer par la boîte mail.
            'access_key' => Owner::nouvelleCle(),
            'access_key_set_at' => Carbon::now(),
            'is_demo' => false,
        ]);

        $owner->forceFill(['email_verified_at' => Carbon::now()])->save();

        // **L'identité sociale se lie ici, pas au retour du fournisseur.** Le
        // compte n'existait pas encore à ce moment-là : `owners.phone` est
        // obligatoire, et le fournisseur ne le donne pas.
        if ($identite = $request->session()->pull(SocialController::IDENTITE)) {
            if ($provider = SocialProvider::tryFrom($identite['provider'] ?? '')) {
                $this->social->lier($owner, $provider, $identite);
            }
        }

        $request->session()->forget(self::CLE_VERIFIEE);

        Auth::guard('proprietaire')->login($owner, remember: true);
        $request->session()->regenerate();

        return redirect()->route('owner.listings.create')->with('succes',
            'Compte créé. Décrivez votre logement : Vayla vous appelle sous 48 h pour le vérifier.');
    }

    public function resend(): RedirectResponse
    {
        try {
            $this->inscription->renvoyer(self::CLE);
        } catch (CodeThrottled|CodeSendingFailed $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('succes', 'Un nouveau code vient de partir.');
    }
}
