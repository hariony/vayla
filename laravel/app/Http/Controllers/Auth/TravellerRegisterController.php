<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterTravellerRequest;
use App\Models\User;
use App\Services\Auth\PendingRegistration;
use App\Services\Verification\CodeSendingFailed;
use App\Services\Verification\CodeThrottled;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    /**
     * Le même formulaire, sous l'autre porte.
     *
     * `/connexion/client` et `/inscription` posent la même question et
     * traversent le même code : seule l'accroche change, pour que celui qui
     * revient et celui qui découvre se reconnaissent chacun. Deux contrôleurs
     * auraient divergé sur le renvoi ou sur l'expiration.
     */
    public function connexion(): Response
    {
        return Inertia::render('Access/Client');
    }

    public function store(RegisterTravellerRequest $request): RedirectResponse
    {
        try {
            $this->inscription->ouvrir(self::CLE, $request->validated());
        } catch (CodeThrottled|CodeSendingFailed $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()->route('register.code');
    }

    public function codeForm(): Response|RedirectResponse
    {
        $attente = $this->inscription->enAttente(self::CLE);

        if (! $attente) {
            return redirect()->route('register');
        }

        return Inertia::render('Auth/Code', [
            'email' => $attente['email'],
            'action' => route('register.confirm'),
            'renvoi' => route('register.resend'),
            'retour' => route('register'),
            'attente' => $this->inscription->attenteAvantRenvoi(self::CLE),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $donnees = $this->inscription->confirmer(self::CLE, (string) $request->input('code'));

        if (! $donnees) {
            return back()->withErrors(['code' => 'Code incorrect ou expiré. Vérifiez-le, ou demandez-en un nouveau.']);
        }

        // **Trouver ou créer**, et c'est toute la mécanique de la porte unique.
        // Le code vient de prouver que celui qui le saisit relève cette boîte :
        // que le compte existe déjà ou non ne change rien à ce qu'il a le droit
        // d'ouvrir.
        $user = User::query()->where('email', $donnees['email'])->first();
        $nouveau = $user === null;

        if ($nouveau) {
            // Ni nom ni mot de passe : le nom est demandé à la demande de
            // séjour, où il sert ; le mot de passe n'existe plus.
            $user = User::create(['email' => $donnees['email']]);
        }

        // Le code **est** la vérification de l'adresse : la redemander par un
        // second message serait la même preuve, une deuxième fois.
        $user->forceFill(['email_verified_at' => Carbon::now()])->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route('traveller.bookings')->with(
            'succes',
            $nouveau ? 'Votre compte est ouvert.' : 'Vous êtes connecté.'
        );
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
