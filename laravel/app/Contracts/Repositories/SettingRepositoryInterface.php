<?php

namespace App\Contracts\Repositories;

use App\Models\Setting;

/** La table `settings` : une clé, une valeur, qui l'a posée. */
interface SettingRepositoryInterface
{
    /** @return array<string, string> */
    public function valeurs(): array;

    public function ecrire(string $cle, string $valeur, ?int $adminId): void;

    public function trouver(string $cle): ?Setting;
}
