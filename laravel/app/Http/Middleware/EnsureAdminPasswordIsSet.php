<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tant que le mot de passe est provisoire, le back-office n'ouvre qu'un écran :
 * celui qui demande d'en choisir un.
 *
 * Un mot de passe posé par un collègue ou par la commande a été vu par
 * quelqu'un d'autre. S'en servir pour publier des annonces, c'est publier sous
 * un secret partagé — et le journal ne saurait plus qui a vraiment agi.
 */
class EnsureAdminPasswordIsSet
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');

        if ($admin && ! $admin->motDePasseChoisi() && ! $request->routeIs('office.account', 'office.account.update', 'office.logout')) {
            return redirect()->route('office.account');
        }

        return $next($request);
    }
}
