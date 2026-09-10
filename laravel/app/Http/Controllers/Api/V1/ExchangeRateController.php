<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\DeviseData;
use App\Http\Controllers\Controller;
use App\Services\Currency\ExchangeRateProvider;
use Illuminate\Http\JsonResponse;

/**
 * Le taux ariary → euro, publié comme le reste du vocabulaire.
 *
 * L'application mobile affiche les mêmes prix que le site : si elle
 * embarquait son propre taux, deux écrans du même produit donneraient deux
 * ordres de grandeur différents pour le même logement. Elle lit donc celui-ci
 * — et elle lit aussi sa date, pour pouvoir dire d'où sort le chiffre.
 *
 * Les prix des annonces restent en **ariary entiers** partout : rien n'est
 * converti côté serveur, chaque client convertit ce qu'il affiche.
 */
class ExchangeRateController extends Controller
{
    public function __construct(
        private ExchangeRateProvider $rates,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => DeviseData::depuis($this->rates)]);
    }
}
