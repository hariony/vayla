<?php

namespace App\Contracts\Office;

use App\Enums\AdminActionKind;
use App\Models\Admin;
use App\Models\AdminAction;
use Illuminate\Database\Eloquent\Model;

/**
 * Le journal des gestes de l'équipe : **seuls ceux qui engagent quelqu'un**,
 * écrits par les services après que le geste a réussi, jamais modifiés.
 */
interface ActionJournal
{
    public function consigner(Admin $admin, AdminActionKind $kind, ?Model $sujet, string $resume, ?string $note = null): AdminAction;
}
