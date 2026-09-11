<?php

namespace App\Services\Office;

use App\Enums\AdminActionKind;
use App\Enums\StayRequestStatus;
use App\Models\Admin;
use App\Models\StayRequest;
use App\Support\Telephone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * La file des demandes de séjour « dans l'autre sens ».
 *
 * **Chaque ligne porte ce qu'il faut pour agir sans ouvrir autre chose** : le
 * lien WhatsApp prêt à écrire, le lien vers le catalogue déjà filtré sur la
 * demande (destination, dates, voyageurs), et qui s'en occupe. Prendre une
 * demande l'écrit — deux membres qui écrivent au même voyageur sans le savoir,
 * c'est un voyageur qui reçoit deux fois la même question. La clore demande
 * une note : « trois logements proposés », « rien à Mananara en août ».
 */
class OfficeStayRequestService
{
    public const ONGLETS = ['nouvelles' => 'Nouvelles', 'en_cours' => 'En cours', 'closes' => 'Closes'];

    private const STATUTS = [
        'nouvelles' => StayRequestStatus::New,
        'en_cours' => StayRequestStatus::Taken,
        'closes' => StayRequestStatus::Closed,
    ];

    public function __construct(private AdminJournal $journal) {}

    /** @return array<string, mixed> */
    public function liste(string $onglet, string $q): array
    {
        $onglet = array_key_exists($onglet, self::ONGLETS) ? $onglet : 'nouvelles';
        $motif = $q !== '' ? '%'.mb_strtolower($q).'%' : null;

        $page = StayRequest::query()
            ->with(['destination:id,name,slug', 'admin:id,name'])
            ->where('status', self::STATUTS[$onglet]->value)
            ->when($motif, fn (Builder $b) => $b->where(fn (Builder $w) => $w
                ->whereRaw('lower(name) like ?', [$motif])
                ->orWhereRaw('lower(email) like ?', [$motif])
                ->orWhereRaw('lower(place) like ?', [$motif])
                ->orWhere('phone', 'like', '%'.preg_replace('/\D+/', '', $q).'%')))
            // Les nouvelles, la plus ancienne d'abord : c'est celle qui attend
            // depuis le plus longtemps. Les closes, la plus récente d'abord.
            ->orderBy('created_at', $onglet === 'closes' ? 'desc' : 'asc')
            ->paginate(30)
            ->withQueryString();

        return [
            'demandes' => collect($page->items())->map(fn (StayRequest $d) => $this->ligne($d))->values()->all(),
            'meta' => [
                'page' => $page->currentPage(),
                'pages' => $page->lastPage(),
                'total' => $page->total(),
                'precedente' => $page->previousPageUrl(),
                'suivante' => $page->nextPageUrl(),
            ],
            'onglets' => collect(self::ONGLETS)->map(fn (string $label, string $cle) => [
                'cle' => $cle, 'label' => $label,
                'nombre' => StayRequest::query()->where('status', self::STATUTS[$cle]->value)->count(),
            ])->values()->all(),
            'filtre' => ['onglet' => $onglet, 'q' => $q],
        ];
    }

    public function prendre(Admin $admin, StayRequest $demande): void
    {
        if ($demande->status === StayRequestStatus::Closed) {
            throw new OfficeRefusal('Cette demande est close.');
        }

        $demande->forceFill(['status' => StayRequestStatus::Taken, 'admin_id' => $admin->id, 'taken_at' => Carbon::now()])->save();

        $this->journal->consigner($admin, AdminActionKind::StayRequestTaken, $demande, "Demande de séjour de {$demande->name} prise en charge.");
    }

    public function clore(Admin $admin, StayRequest $demande, string $note): void
    {
        if ($demande->status === StayRequestStatus::Closed) {
            throw new OfficeRefusal('Cette demande est déjà close.');
        }

        $demande->forceFill([
            'status' => StayRequestStatus::Closed,
            'admin_id' => $demande->admin_id ?? $admin->id,
            'closed_at' => Carbon::now(),
            'closing_note' => trim($note),
        ])->save();

        $this->journal->consigner($admin, AdminActionKind::StayRequestClosed, $demande, "Demande de séjour de {$demande->name} close.", trim($note));
    }

    /** @return array<string, mixed> */
    private function ligne(StayRequest $d): array
    {
        $telephone = Telephone::depuis($d->phone);
        $nuits = $d->arrival && $d->departure ? Carbon::parse($d->arrival)->diffInDays(Carbon::parse($d->departure)) : null;

        // Le catalogue déjà filtré sur la demande : ce que l'équipe ouvre
        // pour chercher.
        $criteres = array_filter([
            'destination' => $d->destination?->slug,
            'arrival' => $d->arrival,
            'departure' => $d->departure,
            'guests' => $d->guests,
        ]);

        return [
            'id' => $d->id,
            'statut' => $d->status->value,
            'recue' => $d->created_at?->toIso8601String(),
            'nom' => $d->name,
            'email' => $d->email,
            'telephone' => $telephone?->lisible() ?? $d->phone,
            'whatsapp' => $telephone && $telephone->estMobile()
                ? 'https://wa.me/'.ltrim($telephone->e164(), '+').'?text='.rawurlencode("Bonjour {$d->name}, c’est Vayla : nous avons bien reçu votre demande de séjour.")
                : null,
            'tel' => $telephone ? 'tel:'.$telephone->e164() : null,
            'destination' => $d->destination?->name,
            'lieu' => $d->place,
            'arrivee' => $d->arrival,
            'depart' => $d->departure,
            'nuits' => $nuits,
            'voyageurs' => $d->guests,
            'budget' => $d->budget,
            'message' => $d->message,
            'parQui' => $d->admin?->name,
            'priseLe' => $d->taken_at?->toIso8601String(),
            'closeLe' => $d->closed_at?->toIso8601String(),
            'note' => $d->closing_note,
            'catalogue' => rtrim((string) config('app.url'), '/').'/logements'.($criteres ? '?'.http_build_query($criteres) : ''),
        ];
    }
}
