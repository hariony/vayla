<?php

namespace App\Repositories;

use App\Contracts\Repositories\VerificationCodeRepositoryInterface;
use App\Enums\VerificationKind;
use App\Models\VerificationCode;
use Illuminate\Support\Carbon;

class VerificationCodeRepository implements VerificationCodeRepositoryInterface
{
    public function expirerEnCours(string $destination): void
    {
        VerificationCode::query()->where('destination', $destination)->whereNull('verified_at')->update(['expires_at' => Carbon::now()]);
    }

    public function creer(string $destination, VerificationKind $kind, string $hash, string $canal, Carbon $expireA): VerificationCode
    {
        return VerificationCode::create([
            'destination' => $destination,
            'kind' => $kind,
            'code_hash' => $hash,
            'channel' => $canal,
            'expires_at' => $expireA,
        ]);
    }

    public function expirer(VerificationCode $code): void
    {
        $code->forceFill(['expires_at' => Carbon::now()])->save();
    }

    public function dernierEnCours(string $destination): ?VerificationCode
    {
        return VerificationCode::query()->where('destination', $destination)->whereNull('verified_at')->latest('id')->first();
    }

    public function dernier(string $destination): ?VerificationCode
    {
        return VerificationCode::query()->where('destination', $destination)->latest('id')->first();
    }

    public function compterEssai(VerificationCode $code): void
    {
        $code->increment('attempts');
    }

    public function marquerVerifie(VerificationCode $code): void
    {
        $code->forceFill(['verified_at' => Carbon::now()])->save();
    }

    public function verifieDepuis(string $destination, Carbon $depuis): bool
    {
        return VerificationCode::query()
            ->where('destination', $destination)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', $depuis)
            ->exists();
    }

    public function nombreDepuis(string $destination, Carbon $depuis): int
    {
        return VerificationCode::query()->where('destination', $destination)->where('created_at', '>=', $depuis)->count();
    }
}
