<?php

namespace App\Data;

use App\Models\Photo;
use Spatie\LaravelData\Data;

/**
 * Une photographie et son crédit.
 *
 * `source` garde le nom court attendu par le front et l'API (la colonne
 * s'appelle `source_url`) : c'est exactement le rôle d'un DTO — absorber
 * l'écart entre le schéma et le contrat public.
 */
class PhotoData extends Data
{
    public function __construct(
        public readonly string $key,
        /** `lieux` (Commons, crédité) ou `annonces` (téléversée par le propriétaire). */
        public readonly string $folder,
        /** Largeur du plus grand fichier réellement disponible. */
        public readonly int $width,
        /** Image générée et non photographiée : le crédit doit le dire. */
        public readonly bool $generated,
        public readonly string $caption,
        /** Nuls sur une photo de propriétaire : elle est à lui, il n'y a rien à créditer. */
        public readonly ?string $author,
        public readonly ?string $licence,
        public readonly ?string $licence_url,
        public readonly ?string $source,
    ) {}

    public static function fromModel(Photo $photo): self
    {
        return new self(
            key: $photo->key,
            folder: $photo->folder,
            width: $photo->width,
            generated: $photo->is_ai,
            caption: $photo->caption,
            author: $photo->author,
            licence: $photo->licence,
            licence_url: $photo->licence_url,
            source: $photo->source_url,
        );
    }
}
