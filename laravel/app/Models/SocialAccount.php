<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Une identité chez un fournisseur, rattachée à un compte Vayla.
 *
 * Le couple `(provider, provider_user_id)` est l'identité ; le reste est ce
 * que le fournisseur a bien voulu donner, et peut manquer.
 */
#[Fillable(['provider', 'provider_user_id', 'email', 'name', 'avatar_url', 'email_verified'])]
class SocialAccount extends Model
{
    protected function casts(): array
    {
        return ['email_verified' => 'boolean'];
    }

    /**
     * Le compte Vayla — un `User` ou un `Owner`.
     *
     * Polymorphe parce que le projet a **deux entités authentifiables** : un
     * propriétaire n'est pas une ligne de `users`. Une même personne peut même
     * être les deux, avec le même compte Google.
     *
     * @return MorphTo<Model, $this>
     */
    public function compte(): MorphTo
    {
        return $this->morphTo();
    }
}
