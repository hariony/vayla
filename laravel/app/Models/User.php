<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/*
 * `name` reste **assignable** bien qu'il ne soit plus stocké : c'est le
 * mutateur qui le reçoit et le sépare. La connexion sociale reçoit un nom
 * entier et écrit `['name' => …]` sur l'un ou l'autre des deux modèles —
 * l'y interdire aurait obligé chaque appelant à savoir lequel des deux
 * découpe ses noms.
 */
#[Fillable(['first_name', 'last_name', 'name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
#[Appends(['name'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Les identités sociales rattachées à ce compte.
     *
     * Un même compte peut en porter plusieurs — Google et Apple, par
     * exemple — sans qu'aucun doublon d'utilisateur ne soit créé.
     *
     * @return MorphMany<SocialAccount, $this>
     */
    public function socialAccounts(): MorphMany
    {
        return $this->morphMany(SocialAccount::class, 'compte');
    }

    /**
     * Le nom complet — **dérivé, jamais stocké**.
     *
     * La colonne `name` a été remplacée par `first_name` et `last_name` :
     * « RAKOTOBE Jean » et « Jean Rakotobe » sont deux écritures courantes, et
     * un champ unique ne permet pas de savoir laquelle on a. Le garder à côté
     * des deux parties aurait fait deux endroits pour un même fait, donc deux
     * versions qui finissent par diverger — la même règle que `perks`, dérivé
     * des équipements marqués.
     *
     * **L'accesseur garde tout le code existant vivant** (`$user->name` reste
     * juste), et `#[Appends]` le fait voyager dans les props partagées, où le
     * menu du compte le lit.
     *
     * **Le mutateur sépare un nom entier.** La connexion sociale en reçoit un
     * d'un seul tenant : plutôt que d'apprendre à tous les appelants à le
     * découper, le modèle le fait une fois. La coupe tombe au premier espace —
     * juste pour « Jean Rakotobe », fausse pour « RAKOTOBE Jean », et
     * corrigeable en dix secondes sur l'écran du compte. Aucune heuristique ne
     * tranchera à notre place.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: null,
            set: function (?string $entier) {
                $propre = trim((string) $entier);

                if ($propre === '') {
                    return ['first_name' => null, 'last_name' => null];
                }

                $morceaux = preg_split('/\s+/', $propre, 2) ?: [];

                return [
                    'first_name' => $morceaux[0] ?? null,
                    'last_name' => $morceaux[1] ?? null,
                ];
            },
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
