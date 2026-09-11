<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\CodeSendingFailed;
use App\Exceptions\CodeThrottled;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterTravellerRequest;
use App\Http\Requests\VerificationCodeRequest;
use App\Services\Auth\PendingRegistration;
use App\Services\Auth\TravellerSignIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La porte du voyageur : une adresse, puis un code. Rien d'autre.
 *
 * **Une seule porte pour s'inscrire et pour se connecter.** Les deux gestes
 * posent la même question, et c'est le code qui décide de la suite : le compte
 * existe, on entre ; il n'existe pas, il s'ouvre. Faire choisir *avant* entre
 * « créer un compte » et « se connecter », c'est demander de trancher une
 * question dont beaucoup n'ont pas la réponse — on ne sait plus si on s'est
 * inscrit un jour, et on se trompe de porte.
 *
 * **Plus de mot de passe.** Le code reçu prouve l'adresse à chaque connexion,
 * ce qu'un mot de passe ne fait jamais : il prouve seulement qu'on connaît une
 * chaîne. Et sur un premier compte en ligne, c'est le mot de passe oublié qui
 * fait perdre les gens, pas la connexion.
 *
 * **Le compte n'existe qu'après le code.** L'adresse vit en session jusque-là :
 * créer une ligne « non vérifiée » à la première étape permettrait de squatter
 * l'adresse de quelqu'un d'autre.
 *
 * **Le compte ne conditionne toujours rien** : la demande de séjour reste
 * ouverte sans lui, et la référence continue d'ouvrir une réservation. Il
 * **ajoute** le confort de retrouver ses séjours, il n'est pas un péage.
 */
class TravellerRegisterController extends Controller
{
    private const CLE = 'inscription.voyageur';

    public function __construct(
        private PendingRegistration $inscription,
    ) {}

    public function form(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function connexion(): Response
    {
        return Inertia::render('Access/Client');
    }

    public function store(RegisterTravellerRequest $request): RedirectResponse
    {
        try {
            $this->inscription->ouvrir(self::CLE, $request->email());
        } catch (CodeThrottled|CodeSendingFailed $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()->route('register.code');
    }

    public function codeForm(): Response|RedirectResponse
    {
        $page = $this->inscription->page(self::CLE, route('register.confirm'), route('register.resend'), route('register'));

        return $page ? Inertia::render('Auth/Code', $page) : redirect()->route('register');
    }

    public function confirm(VerificationCodeRequest $request, TravellerSignIn $porte): RedirectResponse
    {
        $email = $this->inscription->confirmer(self::CLE, $request->code());

        if (! $email) {
            return back()->withErrors(['code' => 'Code incorrect ou expiré. Vérifiez-le, ou demandez-en un nouveau.']);
        }

        $entree = $porte->entrer($email);

        Auth::guard('web')->login($entree->user, remember: true);
        $request->session()->regenerate();

        /*
         * **On n'annonce que ce qui n'est pas déjà visible.** « Vous êtes
         * connecté » répétait ce que l'écran montre tout seul — l'espace, le
         * nom dans le menu du compte — en travers d'un bandeau noir qu'il faut
         * lire avant d'atteindre ce qu'on venait faire. L'ouverture d'un
         * compte, elle, est un fait neuf : elle se dit une fois.
         */
        $vers = redirect()->route('traveller.bookings');

        return $entree->nouveau ? $vers->with('succes', 'Votre compte est ouvert.') : $vers;
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
