<?php

return [
    /*
     * Les huit annonces de démonstration sont fictives : elles n'existent que
     * pour dessiner et éprouver la grille tant qu'aucun propriétaire n'a
     * publié. À false, la grille se vide et la page bascule sur son état
     * « aucun logement », qui renvoie vers la demande de séjour.
     *
     * Le bandeau « Aperçu » de l'accueil est piloté par ce même drapeau :
     * il doit rester impossible d'afficher des annonces fictives sans le dire.
     */
    'demo' => (bool) env('VAYLA_DEMO', true),

    /*
     * La commission, prélevée sur les séjours EFFECTUÉS — jamais sur les
     * réservations. Facturer une réservation reviendrait à facturer les
     * no-shows : le propriétaire refuserait de payer, et il aurait raison.
     *
     * Le taux est figé sur chaque réservation à sa création : l'augmenter
     * ne doit jamais s'appliquer rétroactivement à des séjours engagés.
     * On démarre bas, le temps d'avoir des preuves de conversion.
     */
    'commission' => [
        'rate' => (float) env('VAYLA_COMMISSION_RATE', 0.05),
    ],

    /*
     * L'euro affiché à côté de l'ariary.
     *
     * Vayla n'encaisse rien : ce taux ne sert qu'à donner un ordre de grandeur
     * au voyageur étranger, qui ne sait pas ce que valent 185 000 Ar. L'ariary
     * reste le prix — l'euro est une aide à la lecture, jamais l'inverse, et
     * jamais seul.
     *
     * La date est saisie **avec** le taux. Un montant converti sans sa date est
     * invérifiable, et sur un site dont l'argument unique est la vérification,
     * un chiffre invérifiable est un chiffre de trop. À mettre à jour à la main
     * jusqu'à ce qu'une API de change prenne le relais : il suffira alors de
     * relier `ExchangeRateProvider` à une autre implémentation.
     */
    'currency' => [
        'eur_rate' => (float) env('VAYLA_EUR_RATE', 5000),
        'eur_rate_date' => env('VAYLA_EUR_RATE_DATE', '2026-09-04'),
    ],

    /*
     * Le code à usage unique qui prouve un numéro de téléphone.
     *
     * **`driver` décide du canal, et rien d'autre ne change.** `log` en
     * développement — le code part dans les journaux et dans la console, aucun
     * message réel, aucun coût. `whatsapp` et `sms` envoient pour de vrai dès
     * que les identifiants sont posés.
     *
     * Les quatre bornes ne sont pas décoratives : un OTP mal borné est pire
     * que pas d'OTP du tout. `max_per_hour` en particulier empêche qu'un
     * inconnu fasse payer à Vayla vingt messages sur le téléphone de
     * quelqu'un d'autre.
     */
    'otp' => [
        'driver' => env('VAYLA_OTP_DRIVER', 'log'),

        // Dix minutes : le temps de basculer sur WhatsApp, lire, revenir. Au
        // -delà on protège surtout contre l'oubli, pas contre l'attaque.
        'ttl_minutes' => (int) env('VAYLA_OTP_TTL', 10),

        // Cinq essais sur un million de combinaisons : le tirage au sort est
        // hors de portée, et personne n'est bloqué pour deux fautes de frappe.
        'max_attempts' => 5,

        // Une minute avant de pouvoir en redemander un : sans ça, le bouton
        // « renvoyer » devient un distributeur de messages payants.
        'resend_seconds' => 60,

        // Cinq codes par heure et par numéro. C'est la seule borne qui protège
        // le portefeuille **et** le téléphone d'un tiers.
        'max_per_hour' => 5,

        'whatsapp' => [
            'token' => env('WHATSAPP_TOKEN'),
            'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
            // Meta impose un modèle d'authentification approuvé : on ne peut
            // pas envoyer un texte libre à quelqu'un qui n'a pas écrit avant.
            'template' => env('WHATSAPP_OTP_TEMPLATE', 'vayla_code'),
            'lang' => env('WHATSAPP_OTP_LANG', 'fr'),
            /*
             * Un modèle d'authentification **peut** être créé sans bouton de
             * copie. Envoyer le composant bouton à un modèle qui n'en a pas
             * fait échouer le message avec une erreur Meta qui ne nomme pas
             * la cause — d'où l'interrupteur plutôt qu'une supposition.
             */
            'copy_button' => (bool) env('WHATSAPP_OTP_COPY_BUTTON', true),
        ],

        'sms' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],
    ],

    'booking' => [
        /*
         * Une demande bloque les dates dès son dépôt — sinon deux voyageurs
         * réservent la même semaine — et les rend au bout de ce délai si le
         * propriétaire n'a pas répondu. Sans cette expiration, un
         * propriétaire distrait verrait son calendrier se fermer tout seul.
         */
        'hold_hours' => (int) env('VAYLA_BOOKING_HOLD_HOURS', 48),
    ],

    /*
     * Le back-office vit sur son propre hôte, pas sous un préfixe du site.
     *
     * Un `/admin` à côté de `/logements` partagerait le cookie de session du
     * site public, se devinerait au premier essai, et chaque lien du site y
     * serait à un clic. Un hôte à part, c'est un cookie à part : la session
     * d'un voyageur n'y voyage pas, celle d'un administrateur n'en sort pas.
     */
    'office' => [
        'domaine' => env('VAYLA_OFFICE_DOMAIN', 'office.localhost'),
    ],
];
