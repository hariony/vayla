<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La facturation, vue par le propriétaire.
 *
 * **Le tableau de bord n'en montrait qu'une, et seulement la dernière.** Or
 * c'est la rubrique qui décide si un propriétaire reste : il paie une
 * commission sur des séjours qu'il doit pouvoir vérifier ligne à ligne. Sans
 * historique, « pourquoi ce montant » n'a pas de réponse.
 *
 * **Le mois en cours s'affiche avant les factures**, alors qu'il n'en est pas
 * une : c'est ce qui s'accumule. Le cacher jusqu'au premier du mois suivant
 * ferait découvrir un montant qu'on aurait pu voir venir — et c'est exactement
 * ce qui fait qu'une commission se sent comme un piège.
 */
class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoices,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Owner/Invoices', [
            'facturation' => $this->invoices->historique($request->user('proprietaire')),
            'taux' => app(SettingsService::class)->commission(),
        ]);
    }
}
