<?php

namespace App\Http\Middleware;

use App\Contracts\Currency\ExchangeRateProvider;
use App\Data\DeviseData;
use App\Data\Shared\AuthOwnerData;
use App\Data\Shared\AuthUserData;
use App\Data\Shared\SocialOptionData;
use App\Enums\SocialProvider;
use App\Models\Owner;
use App\Models\User;
use App\Services\Content\Pages\SitePages;
use App\Services\Content\Texts\SiteTexts;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function __construct(
        private SiteTexts $textes,
        private SitePages $pages,
        private ExchangeRateProvider $taux,
        private ConversationService $conversations,
    ) {}

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
                ->map(fn (SocialProvider $p) => new SocialOptionData($p->value, $p->label()))
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
                /*
                 * **Une projection, jamais le modèle entier.**
                 * `$request->user()` sérialisait tout ce que porte la ligne —
                 * et pour le compte propriétaire, dont `$hidden` ne masque que
                 * la clé d'accès et le mot de passe, cela signifiait publier
                 * l'adresse exacte, la date de dernière connexion et l'état de
                 * vérification dans le `data-page` de **chaque** écran. Un
                 * test sur l'adresse exacte l'a révélé.
                 *
                 * **Et la garde est nommée.** Sans elle, `user()` interroge la
                 * garde par défaut : `actingAs($owner, 'proprietaire')` la
                 * change, et un propriétaire se retrouvait publié dans
                 * `auth.user`. La nommer rend la prop indépendante de qui a
                 * appelé `shouldUse`.
                 */
                'user' => fn () => ($u = $request->user('web')) instanceof User ? AuthUserData::fromModel($u) : null,
                // Le propriétaire connecté, sur sa propre garde : l'espace
                // propriétaire et un éventuel back-office n'ont jamais la
                // même session, et ne doivent jamais se confondre.
                'owner' => fn () => ($o = $request->user('proprietaire')) instanceof Owner ? AuthOwnerData::fromModel($o) : null,
            ],
            // Les textes du site et les liens du pied de page, tenus depuis le
            // back-office. En cache jusqu'à la prochaine modification : ils
            // sont lus sur chaque page et changent une fois par mois.
            'textes' => fn () => $this->textes->tous(),
            'pied' => fn () => $this->pages->pied(),
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
            'devise' => fn () => DeviseData::depuis($this->taux),
            // Le compte de conversations en attente, porté par l'onglet
            // « Réservations ». Il est calculé paresseusement : les pages
            // publiques n'ont pas de propriétaire connecté et ne paient donc
            // jamais cette requête.
            'ownerUnread' => fn () => ($o = $request->user('proprietaire')) instanceof Owner
                ? $this->conversations->nonLus($o)
                : 0,
            // Le même compte côté voyageur, porté par sa rubrique
            // « Messages ». Paresseux pour la même raison : une page publique
            // n'a personne de connecté et ne paie donc jamais la requête.
            'travellerUnread' => fn () => ($u = $request->user('web'))
                ? $this->conversations->nonLusVoyageur($u->email)
                : 0,
        ]);
    }
}
