<?php

namespace App\Services\Photos;

use App\Contracts\Office\ActionJournal;
use App\Contracts\Repositories\PhotoLibraryRepositoryInterface;
use App\DTOs\Photos\PhotoCreditPatch;
use App\DTOs\Photos\UpdatePhotoCreditDto;
use App\Enums\AdminActionKind;
use App\Enums\PhotoProvenance;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;
use App\Models\Photo;

/**
 * Corriger la légende et le crédit d'une photo — **seulement ce que sa
 * provenance permet** (`PhotoProvenance`). Un champ qu'on n'a pas le droit de
 * toucher est ignoré, pas refusé : l'écran ne le montre même pas. Le journal
 * écrit les champs changés, et rien quand rien n'a changé.
 */
final class PhotoCreditEditor
{
    private const MOTS = ['caption' => 'légende', 'author' => 'auteur', 'source_url' => 'page d’origine', 'licence' => 'licence'];

    public function __construct(
        private PhotoLibraryRepositoryInterface $photos,
        private ActionJournal $journal,
    ) {}

    public function corriger(Admin $admin, Photo $photo, UpdatePhotoCreditDto $demande): void
    {
        $changes = $this->photos->appliquerCredit($photo, $this->patch(PhotoProvenance::de($photo), $demande));

        $mots = collect($changes)->map(fn (string $c) => self::MOTS[$c] ?? null)->filter()->unique();

        if ($mots->isNotEmpty()) {
            $this->journal->consigner($admin, AdminActionKind::PhotoEdited, $photo,
                "Photo « {$photo->caption} » : {$mots->implode(', ')} corrigé".($mots->count() > 1 ? 's' : '').'.');
        }
    }

    private function patch(PhotoProvenance $provenance, UpdatePhotoCreditDto $demande): PhotoCreditPatch
    {
        if (! $provenance->legendeModifiable()) {
            throw new OfficeRefusal('Cette photo illustre les annonces de démonstration : elle disparaîtra avec elles, son crédit ne se corrige pas.');
        }

        return new PhotoCreditPatch(
            caption: $demande->caption,
            author: $provenance->creditModifiable() ? $demande->author : null,
            licence: $provenance->licenceModifiable() ? $demande->licence : null,
            toucherSource: $provenance->creditModifiable() && $demande->sourceUrlFournie,
            sourceUrl: $demande->sourceUrl,
        );
    }
}
