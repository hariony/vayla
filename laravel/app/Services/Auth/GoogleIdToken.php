<?php

namespace App\Services\Auth;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\User as SocialiteUser;
use RuntimeException;
use Throwable;

/**
 * Vérifier le jeton d'identité rendu par Google One Tap.
 *
 * **C'est ici que se joue toute la sécurité de One Tap.** Le navigateur nous
 * envoie un jeton ; sans vérification, n'importe qui pourrait en forger un et
 * entrer dans le compte de son choix — c'est le cas d'école du « ne jamais
 * faire confiance à une identité venue du front ». Quatre contrôles, et aucun
 * n'est facultatif :
 *
 * 1. **La signature**, contre les clés publiques de Google. C'est elle qui
 *    prouve que Google a émis ce jeton et que personne ne l'a retouché.
 * 2. **`aud`**, qui doit être *notre* identifiant client. Sans ce contrôle, un
 *    jeton parfaitement signé mais émis pour **une autre application** serait
 *    accepté : il suffirait de faire connecter la victime chez soi pour
 *    récupérer un jeton et le rejouer ici.
 * 3. **`iss`**, qui doit être Google.
 * 4. **L'expiration**, vérifiée par la bibliothèque à la décision.
 *
 * **Vérification locale, pas d'appel à `tokeninfo`.** L'endpoint de débogage
 * de Google ajouterait un aller-retour réseau à chaque connexion et une panne
 * de plus dans la chaîne. Les clés publiques, elles, se mettent en cache.
 *
 * `firebase/php-jwt` est déjà présent — tiré par le fournisseur Apple — donc
 * aucune dépendance n'est ajoutée pour ça.
 */
class GoogleIdToken
{
    private const CERTS = 'https://www.googleapis.com/oauth2/v3/certs';

    private const EMETTEURS = ['accounts.google.com', 'https://accounts.google.com'];

    /**
     * @throws RuntimeException si le jeton n'est pas authentiquement Google, ou pas pour nous
     */
    public function verifier(string $jeton): SocialiteUser
    {
        $client = (string) config('services.google.client_id');

        if ($client === '') {
            throw new RuntimeException('Google n’est pas configuré.');
        }

        try {
            $charge = (array) JWT::decode($jeton, JWK::parseKeySet($this->cles()));
        } catch (Throwable $e) {
            // Le message d'origine ne remonte jamais à l'utilisateur : il
            // renseigne un attaquant sur l'état du dispositif.
            throw new RuntimeException('Jeton Google invalide.', previous: $e);
        }

        if (($charge['aud'] ?? null) !== $client) {
            throw new RuntimeException('Ce jeton n’a pas été émis pour Vayla.');
        }

        if (! in_array($charge['iss'] ?? '', self::EMETTEURS, true)) {
            throw new RuntimeException('Émetteur inattendu.');
        }

        if (empty($charge['sub'])) {
            throw new RuntimeException('Jeton sans identité.');
        }

        $identite = new SocialiteUser;
        $identite->id = (string) $charge['sub'];
        $identite->email = $charge['email'] ?? null;
        $identite->name = $charge['name'] ?? null;
        $identite->avatar = $charge['picture'] ?? null;
        // `SocialProvider::garantitLAdresse()` lit ce tableau : c'est la même
        // source que pour le flux par redirection, donc la même règle.
        $identite->user = ['email_verified' => $charge['email_verified'] ?? false];

        return $identite;
    }

    /**
     * Les clés publiques de Google, en cache une heure.
     *
     * Google les fait tourner régulièrement ; une heure est bien en deçà de
     * leur durée de vie, et évite un appel réseau à chaque connexion. Une
     * rotation entre deux caches se traduit par un refus, pas par une faille :
     * l'utilisateur recommence, et le cache suivant a les bonnes clés.
     */
    private function cles(): array
    {
        return Cache::remember('google.jwks', now()->addHour(), function () {
            $reponse = Http::timeout(5)->get(self::CERTS);

            if (! $reponse->successful()) {
                throw new RuntimeException('Clés publiques Google indisponibles.');
            }

            return $reponse->json();
        });
    }
}
