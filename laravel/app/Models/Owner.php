<?php

namespace App\Models;

use App\Support\Telephone;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * Un propriétaire, et **le compte** qui va avec.
 *
 * Il est authentifiable lui-même plutôt que relié à un `User` générique : il
 * porte des logements, des réservations et une facture, c'est-à-dire tout ce
 * que le domaine appelle un propriétaire. Une jointure de plus à chaque écran
 * n'aurait rien exprimé de neuf.
 *
 * **L'identifiant de connexion est le téléphone.** C'est par WhatsApp qu'on
 * joint nos propriétaires ; exiger une adresse e-mail écarterait précisément
 * ceux qu'on veut servir.
 *
 * `mobile_money` reste un numéro de téléphone vers lequel il envoie son
 * règlement — aucun identifiant qui permettrait de le débiter.
 */
class Owner extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    protected $fillable = [
        'access_key', 'access_key_set_at', 'name', 'phone', 'phone_verified_at', 'email', 'email_verified_at', 'password',
        'password_set_at', 'last_login_at', 'city', 'address', 'portrait', 'mobile_money',
        'mobile_money_operator', 'is_demo', 'source',
    ];

    /**
     * La clé n'est jamais sérialisée : elle ouvre l'espace propriétaire, et
     * une donnée qui ouvre une porte n'a rien à faire dans une charge utile
     * qu'un autre écran pourrait renvoyer par mégarde.
     */
    protected $hidden = ['access_key', 'password', 'remember_token'];

    /**
     * Les identités sociales rattachées à ce compte.
     *
     * @return MorphMany<SocialAccount, $this>
     */
    public function socialAccounts(): MorphMany
    {
        return $this->morphMany(SocialAccount::class, 'compte');
    }

    protected function casts(): array
    {
        return [
            'is_demo' => 'boolean',
            'access_key_set_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password_set_at' => 'datetime',
            'last_login_at' => 'datetime',
            // `hashed` chiffre à l'écriture : aucun appel à Hash::make ne
            // traîne dans un contrôleur, donc aucun oubli possible.
            'password' => 'hashed',
        ];
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /** Les réservations de tous ses logements, sans passer par une boucle. */
    public function bookings(): HasManyThrough
    {
        return $this->hasManyThrough(Booking::class, Listing::class);
    }

    /** Une clé longue et non devinable : c'est elle qui porte le droit d'accès. */
    public static function nouvelleCle(): string
    {
        return Str::lower(Str::random(32));
    }

    /** Le numéro, prêt à l'affichage : « +261 34 00 000 01 ». */
    public function telephone(): ?Telephone
    {
        return Telephone::depuis($this->phone);
    }

    /**
     * Le numéro est-il prouvé ?
     *
     * Il l'est dès que le propriétaire a ouvert son compte par le lien envoyé
     * sur son WhatsApp : si le lien est arrivé, le numéro est le bon. C'est
     * exactement ce que prouve un code à usage unique, sans fournisseur de
     * SMS ni coût par message.
     */
    public function telephoneVerifie(): bool
    {
        return $this->phone_verified_at !== null;
    }

    /** Un compte sans mot de passe n'est pas encore ouvert : il attend sa première connexion. */
    public function aUnMotDePasse(): bool
    {
        return $this->password !== null;
    }
}
