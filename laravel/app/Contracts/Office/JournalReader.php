<?php

namespace App\Contracts\Office;

use App\Data\Office\JournalLineData;
use App\Enums\AdminActionKind;
use Illuminate\Database\Eloquent\Model;

/** Relire le journal : les lignes d'un sujet, ou les dernières de toute l'équipe. */
interface JournalReader
{
    /** @return array<int, JournalLineData> */
    public function pour(Model $sujet, int $limite = 20): array;

    /** @return array<int, JournalLineData> */
    public function dernieres(int $limite, ?AdminActionKind $kind = null): array;
}
