<?php

namespace App\Data\Office\Owners;

use App\Data\Office\OwnerCardData;
use App\Data\Office\PhoneData;
use App\Models\Owner;
use Spatie\LaravelData\Data;

/**
 * Un propriétaire, pour sa fiche. Le back-office voit plus que le site —
 * adresse exacte, mobile money — et c'est pourquoi chaque champ est nommé ;
 * **la clé d'accès, elle, n'en sort jamais**.
 */
final class OwnerDetailData extends Data
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
        public readonly ?string $address,
        public readonly ?string $mobileMoney,
        public readonly ?string $operator,
        public readonly bool $emailVerified,
        public readonly ?string $createdAt,
        public readonly ?string $lastLoginAt,
        public readonly ?string $accessKeySetAt,
    ) {}

    public static function fromModel(Owner $o): self
    {
        return new self(...[
            ...OwnerCardData::fromModel($o)->champs(),
            'address' => $o->address,
            'mobileMoney' => $o->mobile_money,
            'operator' => $o->mobile_money_operator,
            'emailVerified' => $o->email_verified_at !== null,
            'createdAt' => $o->created_at?->toIso8601String(),
            'lastLoginAt' => $o->last_login_at?->toIso8601String(),
            'accessKeySetAt' => $o->access_key_set_at?->toIso8601String(),
        ]);
    }
}
