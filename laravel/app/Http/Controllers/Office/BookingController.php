<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\BookingQueueRequest;
use App\Http\Requests\Office\OfficeMessageRequest;
use App\Http\Requests\Office\OfficeReasonRequest;
use App\Models\Booking;
use App\Services\Office\Bookings\BookingDetailQuery;
use App\Services\Office\Bookings\BookingInterventions;
use App\Services\Office\Bookings\BookingQueueQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Les réservations : la file, la fiche, et les interventions de Vayla dans le fil. */
class BookingController extends OfficeController
{
    public function index(BookingQueueRequest $request, BookingQueueQuery $file): Response
    {
        return Inertia::render('Office/Bookings/Index', $file->page($request->toDto()));
    }

    public function show(Booking $booking, BookingDetailQuery $fiche): Response
    {
        return Inertia::render('Office/Bookings/Show', $fiche->page($booking));
    }

    public function reply(OfficeMessageRequest $request, Booking $booking, BookingInterventions $interventions): RedirectResponse
    {
        $interventions->ecrire($this->admin($request), $booking, $request->body());

        return back()->with('succes', 'Message écrit au nom de Vayla : les deux parties le lisent.');
    }

    public function cancel(OfficeReasonRequest $request, Booking $booking, BookingInterventions $interventions): RedirectResponse
    {
        $interventions->annuler($this->admin($request), $booking, (string) $request->reason());

        return back()->with('succes', "Réservation {$booking->reference} annulée : les nuits sont rendues au calendrier.");
    }
}
