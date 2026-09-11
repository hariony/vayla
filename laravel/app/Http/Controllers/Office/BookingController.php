<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficeMessageRequest;
use App\Http\Requests\Office\OfficeReasonRequest;
use App\Models\Booking;
use App\Repositories\OfficeRepository;
use App\Services\Office\OfficeActions;
use App\Services\Office\OfficeReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends OfficeController
{
    public function __construct(
        private OfficeReadService $lecture,
        private OfficeActions $actions,
    ) {}

    public function index(Request $request): Response
    {
        $filtre = (string) $request->query('filtre', 'attente');
        $filtre = array_key_exists($filtre, OfficeRepository::FILTRES_RESERVATIONS) || $filtre === 'toutes' ? $filtre : 'attente';
        $q = trim((string) $request->query('q'));

        return Inertia::render('Office/Bookings/Index', $this->lecture->reservations($filtre, $q === '' ? null : mb_substr($q, 0, 80)));
    }

    public function show(Booking $booking): Response
    {
        return Inertia::render('Office/Bookings/Show', $this->lecture->reservation($booking));
    }

    public function reply(OfficeMessageRequest $request, Booking $booking): RedirectResponse
    {
        $this->actions->ecrire($this->admin($request), $booking, $request->validated('body'));

        return back()->with('succes', 'Message écrit au nom de Vayla : les deux parties le lisent.');
    }

    public function cancel(OfficeReasonRequest $request, Booking $booking): RedirectResponse
    {
        $this->actions->annulerReservation($this->admin($request), $booking, $request->validated('reason'));

        return back()->with('succes', "Réservation {$booking->reference} annulée : les nuits sont rendues au calendrier.");
    }
}
