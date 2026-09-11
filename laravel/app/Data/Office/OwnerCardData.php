<?php

namespace App\Data\Office;

use App\Models\Owner;
use Spatie\LaravelData\Data;

/** Un propriétaire, en carte : qui, où, et comment le joindre. */
final class OwnerCardData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $city,
        public readonly ?string $portrait,
        public readonly ?PhoneData $telephone,
        public readonly bool $verified,
        public readonly bool $isDemo,
    ) {}

    public static function fromModel(Owner $o): self
    {
        return new self($o->id, $o->name, $o->email, $o->city, $o->portrait, PhoneData::depuis($o->phone), $o->telephoneVerifie(), (bool) $o->is_demo);
    }

    /**
     * Les champs, par nom — pour qu'une fiche détaillée les reprenne tels quels
     * et n'ajoute que ce qui lui est propre.
     *
     * @return array<string, mixed>
     */
    public function champs(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'city' => $this->city,
            'portrait' => $this->portrait,
            'telephone' => $this->telephone,
            'verified' => $this->verified,
            'isDemo' => $this->isDemo,
        ];
    }
}
