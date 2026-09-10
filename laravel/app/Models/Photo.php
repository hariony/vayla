<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une photographie.
 *
 * Deux provenances qui ne se mélangent pas, et la colonne `folder` les
 * sépare : `lieux` pour les photographies de Wikimedia Commons — auteur,
 * licence et page source obligatoires, c'est ce qu'exigent CC BY et CC BY-SA —
 * et `annonces` pour celles qu'un propriétaire téléverse, qui sont à lui et
 * n'ont aucun crédit à citer. Les fondre ferait apparaître les secondes dans
 * le bloc « Crédits photo » du pied de page, où elles n'ont rien à faire.
 *
 * Relations uniquement : la mise en forme du crédit appartient au service.
 */
class Photo extends Model
{
    protected $fillable = [
        'key', 'folder', 'width', 'is_ai', 'caption', 'author', 'licence', 'licence_url', 'source_url',
    ];

    protected function casts(): array
    {
        return ['width' => 'integer', 'is_ai' => 'boolean'];
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /** Le chemin public, sans l'extension ni la largeur. */
    public function chemin(): string
    {
        return "/images/{$this->folder}/{$this->key}";
    }

    /** Une photo de propriétaire : aucun crédit à afficher, aucun fichier à créditer. */
    public function estDuProprietaire(): bool
    {
        return $this->folder === 'annonces';
    }
}
