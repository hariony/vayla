<?php

namespace App\Repositories;

use App\Contracts\Repositories\SettingRepositoryInterface;
use App\Models\Setting;

class SettingRepository implements SettingRepositoryInterface
{
    public function valeurs(): array
    {
        return Setting::query()->pluck('value', 'key')->all();
    }

    public function ecrire(string $cle, string $valeur, ?int $adminId): void
    {
        Setting::query()->updateOrCreate(['key' => $cle], ['value' => $valeur, 'admin_id' => $adminId]);
    }

    public function trouver(string $cle): ?Setting
    {
        return Setting::query()->find($cle);
    }
}
