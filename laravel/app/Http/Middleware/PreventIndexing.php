<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Les pages ouvertes par une clé ne doivent ni s'indexer, ni fuiter leur URL.
 *
 * Deux écrans de Vayla portent leur droit d'accès **dans leur adresse** :
 * l'espace propriétaire (`/proprietaire/{cle}`) et le suivi de réservation
 * (`/reservations/{reference}`). C'est un choix assumé — nos propriétaires
 * sont joints par WhatsApp et beaucoup n'ont jamais créé de compte — mais il
 * déplace la sécurité sur l'URL elle-même, et une URL circule.
 *
 * D'où deux en-têtes, et ils ne coûtent rien :
 *
 * - `X-Robots-Tag: noindex, nofollow` — plus fiable qu'une balise `<meta>`,
 *   qui suppose que le robot exécute le JavaScript d'Inertia. Il suffit
 *   qu'un lien soit collé une fois dans un espace public pour qu'une clé
 *   devienne trouvable par recherche.
 * - `Referrer-Policy: no-referrer` — sans lui, un clic vers un site tiers
 *   emporte l'URL complète, clé comprise, dans les journaux de ce site.
 *   Aucun lien sortant n'existe aujourd'hui sur ces pages ; l'en-tête est là
 *   pour le jour où quelqu'un en ajoutera un sans y penser.
 */
class PreventIndexing
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
