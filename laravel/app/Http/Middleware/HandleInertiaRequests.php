<?php

namespace App\Http\Middleware;

use App\Data\DeviseData;
use App\Enums\SocialProvider;
use App\Models\Owner;
use App\Services\ConversationService;
use App\Services\Currency\ExchangeRateProvider;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            /*
             * Les fournisseurs d'identité **réellement configurés**.
             *
             * Un bouton « Continuer avec Apple » sur une installation sans
             * identifiants Apple mène à une page d'erreur : il vaut mieux ne
             * pas l'afficher. La liste se déduit donc de la configuration, ce
             * qui rend aussi le déploiement progressif possible — Google
             * d'abord, les autres quand leurs comptes existent.
             */
            'social' => fn () => collect(SocialProvider::cases())
                ->filter(fn (SocialProvider $p) => (bool) config("services.{$p->value}.client_id"))
                ->map(fn (SocialProvider $p) => ['cle' => $p->value, 'label' => $p->label()])
                ->values()
                ->all(),
            /*
             * L'identifiant client Google, pour One Tap.
             *
             * **Ce n'est pas un secret** : One Tap l'exige côté navigateur, et
             * il est publié dans le JavaScript de toutes les applications qui
             * l'utilisent. Le secret, lui, ne sort jamais du serveur.
             */
            'google_client_id' => fn () => config('services.google.client_id'),
            'auth' => [
                'user' => $request->user(),
                // Le propriétaire connecté, sur sa propre garde : l'espace
                // propriétaire et un éventuel back-office n'ont jamais la
                // même session, et ne doivent jamais se confondre.
                'owner' => fn () => $request->user('proprietaire')?->only(['name', 'phone', 'city']),
            ],
            // Sans ça, accepter une demande renvoie sur la même page sans
            // rien dire : l'utilisateur reclique, et se demande si ça a marché.
            'flash' => [
                'succes' => fn () => $request->session()->get('succes'),
                'erreur' => fn () => $request->session()->get('erreur'),
            ],
            // Le taux, partagé une fois pour toutes : les prix voyagent en
            // ariary entiers dans les props, et chaque écran convertit ce
            // qu'il affiche. Un total qui n'existe que côté client — les
            // nuits choisies × le tarif — ne peut pas être converti ailleurs.
            'devise' => fn () => DeviseData::depuis(app(ExchangeRateProvider::class)),
            // Le compte de conversations en attente, porté par l'onglet
            // « Réservations ». Il est calculé paresseusement : les pages
            // publiques n'ont pas de propriétaire connecté et ne paient donc
            // jamais cette requête.
            'ownerUnread' => fn () => ($o = $request->user('proprietaire')) instanceof Owner
                ? app(ConversationService::class)->nonLus($o)
                : 0,
        ]);
    }
}
