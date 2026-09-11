<?php

namespace App\Support;

/**
 * Une adresse du site public, **depuis l'hôte du site public**. Générée par
 * `route()` depuis le back-office, elle porterait l'hôte `office.…` et
 * tomberait sur la page introuvable du back-office.
 */
final class PublicUrl
{
    public static function de(string $chemin): string
    {
        return rtrim((string) config('app.url'), '/').$chemin;
    }
}
