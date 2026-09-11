<?php

namespace App\Data\Owners;

use App\Models\Owner;
use Spatie\LaravelData\Data;

/**
 * « Mes informations ». `phoneVerifie` : le numéro est-il prouvé ? Il l'est
 * par l'usage du lien WhatsApp, jamais par sa simple saisie.
 */
final class OwnerAccountData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $city,
        public readonly ?string $address,
        public readonly ?string $portrait,
        public readonly ?string $mobileMoney,
        public readonly ?string $operator,
        public readonly bool $phoneVerifie,
    ) {}

    public static function fromModel(Owner $o): self
    {
        return new self(
            name: $o->name,
            phone: $o->phone,
            email: $o->email,
            city: $o->city,
            address: $o->address,
            portrait: $o->portrait,
            mobileMoney: $o->mobile_money,
            operator: $o->mobile_money_operator,
            phoneVerifie: $o->phone_verified_at !== null,
        );
    }
}
