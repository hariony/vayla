<?php

use App\Exceptions\DestinationNotFoundException;
use App\Exceptions\ListingNotFoundException;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PreventIndexing;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // Les écrans dont l'adresse **est** le droit d'accès : ni indexation,
        // ni fuite de l'URL par l'en-tête Referer.
        $middleware->alias([
            'sans-index' => PreventIndexing::class,
        ]);

        /*
         * **Les deux redirections dépendent de la porte, pas du site.**
         *
         * Elles pointaient toutes les deux sur l'espace propriétaire — écrites
         * à l'époque où il était le seul espace derrière une connexion. Depuis
         * qu'un voyageur a un compte, c'était faux dans les deux sens : un
         * voyageur déjà connecté qui rouvrait `/inscription` atterrissait sur
         * **l'écran de connexion propriétaire** (renvoyé vers `/proprietaire`,
         * puis rejeté par la garde), et un voyageur déconnecté demandant
         * `/mes-reservations` y atterrissait aussi. Dans les deux cas on
         * demandait un numéro de téléphone à quelqu'un qui n'en a pas donné.
         *
         * Le chemin se déduit donc de la **route demandée** : ce qui commence
         * par `/proprietaire` appartient au propriétaire, le reste au voyageur.
         * On ne peut pas se fier au garde authentifié — au moment où ces
         * fermetures s'exécutent, justement, il n'y en a pas.
         */
        $cotePro = fn ($request) => $request->is('proprietaire*');

        $middleware->redirectGuestsTo(
            fn ($request) => $cotePro($request) ? route('owner.login') : route('access.client')
        );

        $middleware->redirectUsersTo(
            fn ($request) => $cotePro($request) ? route('owner.home') : route('traveller.bookings')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // « Introuvable » est un fait métier, pas une panne. Les deux
        // exceptions du domaine sortent donc en 404 des deux côtés : en JSON
        // pour le client mobile, qui doit lire un statut et non une page
        // d'erreur, et en HTML pour le site — sans quoi une URL d'annonce
        // périmée renverrait un 500 et serait indexée comme telle.
        $exceptions->renderable(function (
            DestinationNotFoundException|ListingNotFoundException $e,
            Request $request
        ) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 404);
            }

            return response()->view('errors.404', ['message' => $e->getMessage()], 404);
        });
    })->create();
