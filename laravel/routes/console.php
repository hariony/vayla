<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

/*
 * Toutes les heures : une demande sans réponse rend ses nuits au calendrier.
 * Le blocage immédiat évite la double réservation ; l'expiration évite qu'un
 * propriétaire distrait voie son calendrier se fermer tout seul.
 */
Schedule::command('vayla:release-expired-bookings')->hourly();

/*
 * Le rappel avant expiration, une heure avant la libération dans l'ordre du
 * planificateur : prévenir après avoir rendu les nuits n'aurait plus d'objet.
 * C'est la notification qui rattrape le plus de demandes — celui qui n'a pas
 * répondu n'a en général pas décidé de refuser, il a oublié.
 */
Schedule::command('vayla:remind-pending-bookings')->hourly();
