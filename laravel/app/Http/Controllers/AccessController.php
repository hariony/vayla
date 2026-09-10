<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * L'aiguillage, puis l'espace client.
 *
 * **Les deux ne fonctionnent pas de la même façon, et l'écran le dit.** Un
 * propriétaire a un compte — numéro et mot de passe — parce qu'il revient
 * toutes les semaines et gère des logements. Un voyageur, **non, et c'est
 * délibéré** : lui faire créer un compte pour demander un séjour ferait
 * abandonner ceux qui hésitaient déjà. Sa référence de réservation tient lieu
 * de porte.
 *
 * L'asymétrie est donc assumée : une carte porte un formulaire de référence,
 * l'autre un bouton de connexion. La masquer derrière deux boutons identiques
 * aurait laissé croire au voyageur qu'il a un compte quelque part, et il
 * aurait cherché un mot de passe qui n'existe pas.
 *
 * **Une référence introuvable dit pourquoi**, et ne renvoie pas sur une page
 * 404 : « votre demande a peut-être expiré » se comprend, une page d'erreur
 * se subit — et notre public ne réessaie pas.
 *
 * **L'entrée par référence a été retirée de l'écran de connexion.** Elle y
 * ouvrait une réservation, pas un compte : deux portes sur un écran qui n'en
 * demande qu'une. `GET /reservations/{reference}` n'a pas bougé — c'est le
 * lien que porte le message de confirmation, et le supprimer aurait cassé
 * toutes les demandes déjà envoyées. Ce qui disparaît, c'est le formulaire qui
 * demandait de retaper la référence.
 *
 * **Choisir d'abord, se connecter ensuite.** `/connexion` ne porte plus que
 * deux cartes : arriver sur quatre champs et deux boutons sans avoir décidé où
 * l'on va, c'est commencer à taper dans le mauvais formulaire. L'espace client
 * a donc son écran (`/connexion/client`), et le propriétaire le sien.
 */
class AccessController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Access/Index');
    }

    /** L'espace client : la connexion au compte, et rien d'autre. */
    public function client(): Response
    {
        return Inertia::render('Access/Client');
    }
}
