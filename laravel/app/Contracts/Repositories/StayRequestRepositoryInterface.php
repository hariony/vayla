<?php

namespace App\Contracts\Repositories;

use App\DTOs\StayRequests\SubmitStayRequestDto;
use App\Enums\StayRequestStatus;
use App\Models\StayRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Les demandes de séjour « dans l'autre sens ». */
interface StayRequestRepositoryInterface
{
    public function creer(SubmitStayRequestDto $demande, ?int $destinationId): StayRequest;

    /**
     * Les nouvelles et les en cours la plus ancienne en tête — celle qui attend
     * depuis le plus longtemps ; les closes la plus récente en tête.
     *
     * @return LengthAwarePaginator<int, StayRequest>
     */
    public function paginer(StayRequestStatus $statut, string $recherche, int $parPage): LengthAwarePaginator;

    public function compter(StayRequestStatus $statut): int;

    public function marquerPrise(StayRequest $demande, int $adminId): void;

    public function clore(StayRequest $demande, int $adminId, string $note): void;
}
