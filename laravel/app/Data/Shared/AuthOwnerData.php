<?php

namespace App\Data\Shared;

use App\Models\Owner;
use Spatie\LaravelData\Data;

/** Le propriétaire connecté, sur sa propre garde (`auth.owner`) — ni adresse exacte, ni clé, ni e-mail. */
final class AuthOwnerData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $phone,
        public readonly ?string $city,
        public readonly ?string $portrait,
    ) {}

    public static function fromModel(Owner $o): self
    {
        return new self($o->name, $o->phone, $o->city, $o->portrait);
    }
}
