<?php

namespace App\DTOs\Auth;

use App\Enums\SocialProvider;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Ce qu'un fournisseur (Google, Facebook) dit de quelqu'un. `verifie` : le
 * fournisseur **atteste** l'adresse — sans cette garantie, on ne lui prête pas
 * notre propre preuve, celle du code.
 */
final readonly class SocialIdentityDto
{
    public function __construct(
        public SocialProvider $provider,
        public string $id,
        public ?string $email,
        public ?string $name,
        public ?string $avatar,
        public bool $verifie,
    ) {}

    public static function depuis(SocialProvider $provider, SocialiteUser $identite): self
    {
        $email = $identite->getEmail() ? mb_strtolower(trim($identite->getEmail())) : null;
        $brut = $identite->user ?? [];

        return new self(
            provider: $provider,
            id: (string) $identite->getId(),
            email: $email,
            name: $identite->getName(),
            avatar: $identite->getAvatar(),
            verifie: $email !== null && $provider->garantitLAdresse(is_array($brut) ? $brut : []),
        );
    }

    /**
     * La forme que la session garde — sérialisée en JSON, elle ne rend jamais
     * un objet.
     *
     * @return array<string, mixed>
     */
    public function enSession(): array
    {
        return ['provider' => $this->provider->value, 'id' => $this->id, 'email' => $this->email, 'name' => $this->name, 'avatar' => $this->avatar, 'verifie' => $this->verifie];
    }

    public static function depuisSession(mixed $valeur): ?self
    {
        if (! is_array($valeur) || ! ($provider = SocialProvider::tryFrom((string) ($valeur['provider'] ?? '')))) {
            return null;
        }

        return new self($provider, (string) ($valeur['id'] ?? ''), $valeur['email'] ?? null, $valeur['name'] ?? null, $valeur['avatar'] ?? null, (bool) ($valeur['verifie'] ?? false));
    }
}
