<?php

namespace App\Repositories;

use App\Contracts\Repositories\AdminActionRepositoryInterface;
use App\Enums\AdminActionKind;
use App\Models\Admin;
use App\Models\AdminAction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdminActionRepository implements AdminActionRepositoryInterface
{
    public function creer(Admin $admin, AdminActionKind $kind, ?string $sujetType, ?int $sujetId, string $resume, ?string $note): AdminAction
    {
        return AdminAction::create([
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'kind' => $kind,
            'subject_type' => $sujetType,
            'subject_id' => $sujetId,
            'summary' => mb_substr($resume, 0, 300),
            'note' => $note,
        ]);
    }

    public function pour(string $sujetType, int $sujetId, int $limite): Collection
    {
        return AdminAction::query()
            ->where('subject_type', $sujetType)
            ->where('subject_id', $sujetId)
            ->latest('id')
            ->limit($limite)
            ->get();
    }

    public function dernieres(int $limite, ?AdminActionKind $kind = null): Collection
    {
        return AdminAction::query()
            ->when($kind, fn ($q) => $q->where('kind', $kind->value))
            ->latest('id')
            ->limit($limite)
            ->get();
    }

    public function paginer(?string $famille, int $parPage): LengthAwarePaginator
    {
        $kinds = $famille
            ? array_map(fn (AdminActionKind $k) => $k->value, array_filter(AdminActionKind::cases(), fn (AdminActionKind $k) => $k->famille() === $famille))
            : null;

        return AdminAction::query()
            ->when($kinds !== null, fn ($q) => $q->whereIn('kind', $kinds))
            ->latest('id')
            ->paginate($parPage)
            ->withQueryString();
    }
}
