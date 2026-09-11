<?php

namespace App\Contracts\Repositories;

use App\Enums\VerificationKind;
use App\Models\VerificationCode;
use Illuminate\Support\Carbon;

/** Les codes à usage unique, par destination (adresse ou numéro, normalisés). */
interface VerificationCodeRepositoryInterface
{
    /** Les codes encore vivants de cette destination expirent : deux codes vivants doublent les chances d'un tirage. */
    public function expirerEnCours(string $destination): void;

    public function creer(string $destination, VerificationKind $kind, string $hash, string $canal, Carbon $expireA): VerificationCode;

    /** Un code qui n'est pas parti ne doit pas rester jouable. */
    public function expirer(VerificationCode $code): void;

    /** Le dernier code non encore validé de cette destination. */
    public function dernierEnCours(string $destination): ?VerificationCode;

    /** Le dernier code envoyé à cette destination, validé ou non. */
    public function dernier(string $destination): ?VerificationCode;

    public function compterEssai(VerificationCode $code): void;

    public function marquerVerifie(VerificationCode $code): void;

    public function verifieDepuis(string $destination, Carbon $depuis): bool;

    public function nombreDepuis(string $destination, Carbon $depuis): int;
}
