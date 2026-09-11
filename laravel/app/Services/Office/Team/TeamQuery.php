<?php

namespace App\Services\Office\Team;

use App\Contracts\Repositories\AdminRepositoryInterface;
use App\Data\Office\Team\TeamMemberData;
use App\Data\Office\Team\TeamPageData;
use App\Models\Admin;

/** Les membres de l'équipe, et qui porte encore un mot de passe provisoire. */
final class TeamQuery
{
    public function __construct(private AdminRepositoryInterface $admins) {}

    public function page(Admin $moi): TeamPageData
    {
        return new TeamPageData($this->admins->tousParNom()->map(fn (Admin $a) => new TeamMemberData(
            id: $a->id,
            name: $a->name,
            email: $a->email,
            initiales: $a->initiales(),
            moi: $a->is($moi),
            provisoire: ! $a->motDePasseChoisi(),
            lastLoginAt: $a->last_login_at?->toIso8601String(),
            actions: (int) $a->actions_count,
        ))->all());
    }
}
