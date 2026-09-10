<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
     * Les fournisseurs d'identité (Socialite).
     *
     * **Aucune valeur en dur** : tout vient de `.env`, et `.env.example` ne
     * porte que des clés vides. Un fournisseur sans identifiants est
     * simplement absent des écrans — voir `SocialProvider` et le partage
     * Inertia — plutôt que d'afficher un bouton qui mène à une erreur.
     *
     * `redirect` est laissé configurable : l'URL de rappel doit correspondre
     * **au caractère près** à celle déclarée chez le fournisseur, et elle
     * diffère entre local, recette et production.
     */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', '/auth/facebook/callback'),
    ],

    /*
     * Apple : le « secret » n'est pas une chaîne fixe mais un **JWT signé**
     * avec une clé `.p8`, valable six mois au maximum. Il se régénère ; le
     * poser une fois pour toutes dans `.env` condamne la connexion Apple à
     * s'arrêter sans prévenir. Voir `docs/architecture/social-auth.md`.
     */
    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APPLE_REDIRECT_URI', '/auth/apple/callback'),
    ],

];
