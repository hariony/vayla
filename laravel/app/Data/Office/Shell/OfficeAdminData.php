<?php

namespace App\Data\Office\Shell;

use App\Models\Admin;
use Spatie\LaravelData\Data;

/** L'équipier connecté, tel que la colonne l'affiche. */
final class OfficeAdminData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $initiales,
    ) {}

    public static function fromModel(Admin $admin): self
    {
        return new self($admin->name, $admin->email, $admin->initiales());
    }
}
