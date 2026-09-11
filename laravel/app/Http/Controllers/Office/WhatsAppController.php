<?php

namespace App\Http\Controllers\Office;

use App\Models\OutboundMessage;
use App\Services\Office\OfficeActions;
use App\Services\Office\OfficeReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La file WhatsApp, à l'écran — ce que `php artisan vayla:whatsapp` faisait en
 * terminal. La commande reste : c'est le même dépôt, et le jour où l'API de
 * Meta arrive, les deux s'effacent derrière un envoi automatique.
 */
class WhatsAppController extends OfficeController
{
    public function index(Request $request, OfficeReadService $lecture): Response
    {
        return Inertia::render('Office/WhatsApp/Index', $lecture->whatsapp($request->query('onglet') === 'envoyes'));
    }

    public function sent(Request $request, OutboundMessage $message, OfficeActions $actions): RedirectResponse
    {
        $actions->marquerEnvoye($this->admin($request), $message);

        return back()->with('succes', 'Marqué comme envoyé.');
    }
}
