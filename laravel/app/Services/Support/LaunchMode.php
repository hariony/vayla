<?php

namespace App\Services\Support;

/**
 * Le drapeau `vayla.lancement` (env `VAYLA_LANCEMENT`), lu à un seul endroit.
 *
 * **La collecte des logements avant l'ouverture.** Il décide, à lui seul, de
 * trois choses qui doivent basculer ensemble : le site public fermé, l'espace
 * propriétaire réduit à sa fiche et à ses informations, et la page d'arrivée
 * des propriétaires comme seule porte. Les séparer rendrait possible un espace
 * réduit sur un site ouvert — des demandes qui arrivent sans rubrique pour y
 * répondre.
 *
 * **Ce qui reste ouvert est écrit ici**, par nom de route, et nulle part
 * ailleurs : le middleware le lit, le test le vérifie. Une route ajoutée plus
 * tard est **fermée par défaut** pendant la collecte — c'est le bon sens de
 * l'erreur : on découvre qu'un écran manque, jamais qu'un écran a fui.
 */
final class LaunchMode
{
    /**
     * Les routes qui restent ouvertes pendant la collecte.
     *
     * - la page d'arrivée des propriétaires, et leur porte (connexion,
     *   inscription, code, fiche, lien d'accès, connexion sociale) ;
     * - « Mes logements » en entier : la fiche en cinq étapes, ses photos,
     *   l'envoi à Vayla ;
     * - « Mes informations », portrait compris ;
     * - les deux sorties, et les pages légales publiées.
     *
     * Le calendrier, les demandes, les messages, les réservations et la
     * facturation n'ont rien à montrer tant qu'aucun voyageur ne peut réserver.
     */
    public const ROUTES_OUVERTES = [
        'owners.landing', 'owners.landing.ancienne',
        'owner.login',
        'owner.register', 'owner.register.*',
        'owner.access',
        'owner.logout',
        'owner.listings', 'owner.listings.*',
        'owner.photos.*',
        'owner.account', 'owner.account.*',
        'social.redirect.owner', 'social.callback',
        'traveller.logout',
        'pages.show',
    ];

    public function actif(): bool
    {
        return (bool) config('vayla.lancement');
    }
}
