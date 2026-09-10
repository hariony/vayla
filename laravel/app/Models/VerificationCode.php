<?php

namespace App\Models;

use App\Enums\VerificationKind;
use Illuminate\Database\Eloquent\Model;

/**
 * Un code à usage unique et son état.
 *
 * `destination` est une adresse e-mail ou un numéro en E.164 ; `kind` dit
 * laquelle des deux. `channel` dit par quel service c'est parti — les deux ne
 * se confondent pas : un code de téléphone peut passer par WhatsApp ou par
 * SMS sans que ce qu'il prouve change.
 *
 * Casts et prédicats de lecture, pas de logique d'envoi ni de contrôle : ils
 * appartiennent au service.
 */
class VerificationCode extends Model
{
    protected $fillable = ['destination', 'kind', 'code_hash', 'attempts', 'channel', 'expires_at', 'verified_at'];

    /** Le haché n'a rien à faire dans une charge utile, même par mégarde. */
    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'kind' => VerificationKind::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function expire(): bool
    {
        return $this->expires_at->isPast();
    }

    public function utilise(): bool
    {
        return $this->verified_at !== null;
    }

    public function epuise(int $maximum): bool
    {
        return $this->attempts >= $maximum;
    }

    /** Un code encore jouable : ni expiré, ni utilisé, ni épuisé. */
    public function vivant(int $maximum): bool
    {
        return ! $this->expire() && ! $this->utilise() && ! $this->epuise($maximum);
    }
}
