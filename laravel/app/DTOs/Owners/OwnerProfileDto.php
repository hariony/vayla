<?php

namespace App\DTOs\Owners;

/** La fiche du troisième temps de l'inscription : le nom, et le numéro WhatsApp en E.164. */
final readonly class OwnerProfileDto
{
    public function __construct(
        public string $name,
        public string $phone,
    ) {}
}
