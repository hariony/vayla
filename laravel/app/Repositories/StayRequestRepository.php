<?php

namespace App\Repositories;

use App\Contracts\Repositories\StayRequestRepositoryInterface;
use App\DTOs\StayRequests\SubmitStayRequestDto;
use App\Enums\StayRequestStatus;
use App\Models\StayRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class StayRequestRepository implements StayRequestRepositoryInterface
{
    public function creer(SubmitStayRequestDto $demande, ?int $destinationId): StayRequest
    {
        return StayRequest::create([
            'destination_id' => $destinationId,
            'place' => $demande->place,
            'arrival' => $demande->arrival,
            'departure' => $demande->departure,
            'guests' => $demande->guests,
            'budget' => $demande->budget,
            'name' => $demande->name,
            'email' => $demande->email,
            'phone' => $demande->phone,
            'message' => $demande->message,
            'user_id' => $demande->userId,
        ]);
    }

    public function paginer(StayRequestStatus $statut, string $recherche, int $parPage): LengthAwarePaginator
    {
        return StayRequest::query()
            ->with(['destination:id,name,slug', 'admin:id,name'])
            ->where('status', $statut->value)
            ->when($recherche !== '', fn (Builder $b) => $this->chercher($b, $recherche))
            ->orderBy('created_at', $statut === StayRequestStatus::Closed ? 'desc' : 'asc')
            ->paginate($parPage)
            ->withQueryString();
    }

    public function compter(StayRequestStatus $statut): int
    {
        return StayRequest::query()->where('status', $statut->value)->count();
    }

    public function marquerPrise(StayRequest $demande, int $adminId): void
    {
        $demande->forceFill(['status' => StayRequestStatus::Taken, 'admin_id' => $adminId, 'taken_at' => Carbon::now()])->save();
    }

    public function clore(StayRequest $demande, int $adminId, string $note): void
    {
        $demande->forceFill([
            'status' => StayRequestStatus::Closed,
            'admin_id' => $demande->admin_id ?? $adminId,
            'closed_at' => Carbon::now(),
            'closing_note' => $note,
        ])->save();
    }

    private function chercher(Builder $requete, string $recherche): Builder
    {
        $motif = '%'.mb_strtolower($recherche).'%';
        $chiffres = preg_replace('/\D+/', '', $recherche);

        return $requete->where(fn (Builder $w) => $w
            ->whereRaw('lower(name) like ?', [$motif])
            ->orWhereRaw('lower(email) like ?', [$motif])
            ->orWhereRaw('lower(place) like ?', [$motif])
            ->when($chiffres !== '', fn (Builder $t) => $t->orWhere('phone', 'like', "%{$chiffres}%")));
    }
}
