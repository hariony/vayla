<?php

namespace App\Services\Support;

/**
 * Le drapeau `vayla.demo` (env `VAYLA_DEMO`), lu à un seul endroit.
 *
 * Il tient deux choses ensemble : les annonces fictives servies, **et** le
 * bandeau « Aperçu » qui le dit à l'écran. Les séparer rendrait possible
 * d'afficher des annonces fictives sans le signaler — exactement ce que Vayla
 * reproche aux annonces volées. Les services le reçoivent par injection ; les
 * contrôleurs ne le lisent plus.
 */
final class DemoMode
{
    public function actif(): bool
    {
        return (bool) config('vayla.demo');
    }
}
