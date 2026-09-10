<?php

use App\Http\Controllers\Api\V1\AmenityController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DestinationController;
use App\Http\Controllers\Api\V1\ExchangeRateController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\TrustLevelController;
use Illuminate\Support\Facades\Route;

/*
 * API v1 — la surface que consommera l'application Flutter.
 *
 * Versionnée dès la première ligne : une application mobile installée ne se
 * met pas à jour à la demande, l'ancienne version doit continuer de répondre
 * le jour où le contrat change.
 *
 * Lecture publique et limitée en débit. L'écriture (demande de séjour,
 * espace propriétaire) viendra derrière `auth:sanctum`.
 */
Route::prefix('v1')->name('api.v1.')->middleware('throttle:60,1')->group(function () {
    Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
    Route::get('/listings/{slug}', [ListingController::class, 'show'])->name('listings.show');

    Route::get('/destinations', [DestinationController::class, 'index'])->name('destinations.index');
    Route::get('/destinations/{slug}', [DestinationController::class, 'show'])->name('destinations.show');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

    // Le vocabulaire des équipements. L'application le lit au lieu de le
    // recopier, sans quoi « Groupe électrogène » finit écrit de deux façons.
    Route::get('/amenities', [AmenityController::class, 'index'])->name('amenities.index');
    Route::get('/amenities/filters', [AmenityController::class, 'filters'])->name('amenities.filters');

    Route::get('/trust-levels', [TrustLevelController::class, 'index'])->name('trust-levels.index');

    // Le taux ariary → euro. L'application affiche les mêmes ordres de
    // grandeur que le site : elle le lit au lieu d'embarquer le sien.
    Route::get('/exchange-rate', [ExchangeRateController::class, 'index'])->name('exchange-rate.index');
});
