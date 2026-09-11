<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un membre de l'équipe Vayla, sur sa propre garde (`admin`).
 *
 * **Adresse et mot de passe**, contrairement au reste du produit : l'équipe
 * ouvre le back-office vingt fois par jour, et un code par e-mail à chaque
 * session n'est pas un outil de travail. Le mot de passe est haché à
 * l'écriture (`hashed`), jamais sérialisé.
 *
 * **Un mot de passe posé par quelqu'un d'autre est provisoire** :
 * `password_set_at` reste nul, et le back-office n'ouvre que l'écran qui
 * demande d'en choisir un (`EnsureAdminPasswordIsSet`).
 */
class Admin extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    protected $fillable = ['name', 'email', 'password', 'password_set_at', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'password_set_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Le mot de passe est-il celui que la personne a choisi ? */
    public function motDePasseChoisi(): bool
    {
        return $this->password !== null && $this->password_set_at !== null;
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AdminAction::class);
    }

    /** Deux lettres pour la pastille : « Hanta Rabe » → « HR ». */
    public function initiales(): string
    {
        $mots = preg_split('/\s+/', trim($this->name)) ?: [];

        return mb_strtoupper(collect($mots)->take(2)->map(fn ($m) => mb_substr($m, 0, 1))->implode(''));
    }
}
