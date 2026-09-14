<?php

namespace App\Http\Controllers\Owner;

use App\Exceptions\CodeSendingFailed;
use App\Exceptions\CodeThrottled;
use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerProfileRequest;
use App\Http\Requests\RegisterOwnerRequest;
use App\Http\Requests\SignupSourceRequest;
use App\Http\Requests\VerificationCodeRequest;
use App\Services\Auth\PendingRegistration;
use App\Services\Owners\OwnerSignup;
use Illuminate\Http\RedirectResponse;
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

    public function __construct(
        private PendingRegistration $inscription,
        private OwnerSignup $signup,
    ) {}

    public function form(SignupSourceRequest $request): Response
    {
        // Un lien peut mener ici directement, sans passer par `/louer-mon-logement`.
        $this->signup->retenirSource($request->source());

        return Inertia::render('Owner/Register');
    }

    public function connexion(): Response
    {
        return Inertia::render('Owner/Login');
    }

    public function store(RegisterOwnerRequest $request): RedirectResponse
    {
        try {
            $this->inscription->ouvrir(self::CLE, $request->email());
        } catch (CodeThrottled|CodeSendingFailed $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()->route('owner.register.code');
    }

    public function codeForm(): Response|RedirectResponse
    {
        $page = $this->inscription->page(self::CLE, route('owner.register.confirm'), route('owner.register.resend'), route('owner.register'));

        return $page ? Inertia::render('Auth/Code', $page) : redirect()->route('owner.register');
    }

    public function confirm(VerificationCodeRequest $request): RedirectResponse
    {
        $email = $this->inscription->confirmer(self::CLE, $request->code());

        if (! $email) {
            return back()->withErrors(['code' => 'Code incorrect ou expiré. Vérifiez-le, ou demandez-en un nouveau.']);
        }

        $owner = $this->signup->apresCode($email);

        if (! $owner) {
            return redirect()->route('owner.register.profile');
        }

        Auth::guard('proprietaire')->login($owner, remember: true);
        $request->session()->regenerate();

        return redirect()->route('owner.home');
    }

    public function profileForm(): Response|RedirectResponse
    {
        $page = $this->signup->page();

        return $page ? Inertia::render('Owner/Profile', $page) : redirect()->route('owner.register');
    }

    public function profile(OwnerProfileRequest $request): RedirectResponse
    {
        $owner = $this->signup->creer($request->toDto());

        if (! $owner) {
            return redirect()->route('owner.register');
        }

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
