<?php

namespace App\Data\Office\Account;

use App\Models\Admin;
use Spatie\LaravelData\Data;

/** Le compte de celui qui est connecté. `provisoire` : son mot de passe a été vu par quelqu'un d'autre. */
final class AccountData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly bool $provisoire,
        public readonly ?string $depuis,
    ) {}

    public static function fromModel(Admin $admin): self
    {
        return new self(
            name: $admin->name,
            email: $admin->email,
            provisoire: ! $admin->motDePasseChoisi(),
            depuis: $admin->password_set_at?->toIso8601String(),
        );
    }
}
