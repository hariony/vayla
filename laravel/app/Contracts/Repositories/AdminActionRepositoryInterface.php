<?php

namespace App\Contracts\Repositories;

use App\Enums\AdminActionKind;
use App\Models\Admin;
use App\Models\AdminAction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/** Le journal de l'équipe (`admin_actions`) : écrit une fois, jamais modifié. */
interface AdminActionRepositoryInterface
{
    public function creer(Admin $admin, AdminActionKind $kind, ?string $sujetType, ?int $sujetId, string $resume, ?string $note): AdminAction;

    /** @return Collection<int, AdminAction> les lignes d'un sujet, les plus récentes d'abord */
    public function pour(string $sujetType, int $sujetId, int $limite): Collection;

    /** @return Collection<int, AdminAction> */
    public function dernieres(int $limite, ?AdminActionKind $kind = null): Collection;

    /** @return LengthAwarePaginator<int, AdminAction> */
    public function paginer(?string $famille, int $parPage): LengthAwarePaginator;
}
