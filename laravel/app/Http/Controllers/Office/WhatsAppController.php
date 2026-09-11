<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\WhatsAppQueueRequest;
use App\Models\OutboundMessage;
use App\Services\Office\WhatsApp\WhatsAppDispatch;
use App\Services\Office\WhatsApp\WhatsAppQueueQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** La file WhatsApp, envoyée à la main. */
class WhatsAppController extends OfficeController
{
    public function index(WhatsAppQueueRequest $request, WhatsAppQueueQuery $file): Response
    {
        return Inertia::render('Office/WhatsApp/Index', $file->page($request->envoyes()));
    }

    public function sent(Request $request, OutboundMessage $message, WhatsAppDispatch $envoi): RedirectResponse
    {
        $envoi->marquerEnvoye($this->admin($request), $message);

        return back()->with('succes', 'Marqué comme envoyé.');
    }
}
