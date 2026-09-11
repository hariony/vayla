<?php

namespace App\Services\Office;

use App\Enums\AdminActionKind;
use App\Enums\ListingStatus;
use App\Enums\TrustLevel;
use App\Models\Admin;
use App\Models\Listing;

/**
 * Ce que Vayla fait d'une annonce : la vérifier, la mettre en ligne, la
 * renvoyer, l'archiver.
 *
 * **C'est le seul endroit du produit où `trust_level` s'écrit**, et c'est ce
 * qui donne un sens au mot « vérifié ». Chaque règle ci-dessous empêche
 * l'échelle de dire une chose fausse :
 *
 * - **Le niveau 2 exige un numéro vérifié.** Il se lit « numéro et identité
 *   vérifiés » sur la fiche : l'attribuer sans l'appel ferait mentir la jauge
 *   au premier voyageur qui appelle.
 * - **Le niveau 4 ne s'attribue pas, il s'atteint.** Il veut dire « des
 *   voyageurs y ont dormi et ont confirmé » : sans confirmation en base, il n'y
 *   a rien à attribuer — et avec, l'annonce ne peut plus redescendre, sinon des
 *   confirmations se retrouveraient sous un niveau qui les contredit.
 * - **Une annonce en ligne est au moins au niveau 2.** C'est l'appel de
 *   vérification qui met en ligne ; une annonce publiée au niveau 1 serait
 *   exactement l'annonce non vérifiée que Vayla promet de ne pas montrer.
 *
 * **Renvoyer exige un motif**, et il est montré au propriétaire dans son
 * espace : un renvoi muet le laisserait deviner ce qui manque.
 */
class ModerationService
{
    public function __construct(
        private AdminJournal $journal,
    ) {}

    public function publier(Admin $admin, Listing $listing): void
    {
        if ($raison = $this->pourquoiPasPublier($listing)) {
            throw new OfficeRefusal($raison);
        }

        $listing->update(['status' => ListingStatus::Published, 'review_note' => null]);

        $this->journal->consigner($admin, AdminActionKind::ListingPublished, $listing,
            "« {$listing->title} » mise en ligne au niveau {$listing->trust_level->value} — {$listing->trust_level->label()}.");
    }

    public function renvoyer(Admin $admin, Listing $listing, string $motif): void
    {
        if ($listing->status !== ListingStatus::Submitted) {
            throw new OfficeRefusal('Seule une fiche en attente de vérification se renvoie au propriétaire.');
        }

        $listing->update(['status' => ListingStatus::Draft, 'review_note' => trim($motif)]);

        $this->journal->consigner($admin, AdminActionKind::ListingReturned, $listing,
            "« {$listing->title} » renvoyée à {$listing->owner?->name}.", trim($motif));
    }

    public function archiver(Admin $admin, Listing $listing, ?string $motif = null): void
    {
        if ($listing->status === ListingStatus::Archived) {
            throw new OfficeRefusal('Cette annonce est déjà archivée.');
        }

        $listing->update(['status' => ListingStatus::Archived]);

        $this->journal->consigner($admin, AdminActionKind::ListingArchived, $listing,
            "« {$listing->title} » archivée — elle n'est plus proposée aux voyageurs.", $motif ? trim($motif) : null);
    }

    public function niveau(Admin $admin, Listing $listing, TrustLevel $niveau, ?string $note = null): void
    {
        $avant = $listing->trust_level;

        if ($raison = $this->pourquoiPasNiveau($listing, $niveau)) {
            throw new OfficeRefusal($raison);
        }

        $listing->update(['trust_level' => $niveau]);

        $this->journal->consigner($admin, AdminActionKind::TrustLevelChanged, $listing,
            "« {$listing->title} » : niveau {$avant->value} → {$niveau->value} ({$niveau->label()}).", $note ? trim($note) : null);
    }

    /**
     * Pourquoi ce niveau ne peut pas être attribué, ou `null` s'il le peut.
     *
     * **Les mêmes phrases servent au refus et à l'écran** : chaque barreau
     * inaccessible porte sa raison sous le bouton, avant même qu'on clique.
     * Deux rédactions de la même règle — l'une dans le service, l'autre dans le
     * gabarit — finiraient par ne plus dire la même chose.
     */
    public function pourquoiPasNiveau(Listing $listing, TrustLevel $niveau): ?string
    {
        if ($niveau === $listing->trust_level) {
            return "L'annonce est déjà au niveau {$niveau->value}.";
        }

        $confirmations = $listing->confirmations_count ?? $listing->confirmations()->count();

        if ($confirmations > 0 && $niveau !== TrustLevel::Proven) {
            return "Des voyageurs ont confirmé leur séjour ici : l'annonce reste au niveau 4, qu'elle a atteint par eux.";
        }

        if ($niveau === TrustLevel::Proven && $confirmations === 0) {
            return "Le niveau 4 ne s'attribue pas : il s'atteint quand un voyageur confirme son séjour.";
        }

        if ($niveau->isVerified() && ! $listing->owner?->telephoneVerifie()) {
            return "Vérifiez d'abord le numéro du propriétaire : le niveau 2 se lit « numéro et identité vérifiés » sur la fiche.";
        }

        if ($listing->status === ListingStatus::Published && ! $niveau->isVerified()) {
            return "Une annonce en ligne est au moins au niveau 2. Archivez-la d'abord si la vérification ne tient plus.";
        }

        return null;
    }

    public function pourquoiPasPublier(Listing $listing): ?string
    {
        if ($listing->status === ListingStatus::Published) {
            return 'Cette annonce est déjà en ligne.';
        }

        if ($listing->status === ListingStatus::Draft) {
            return "Le propriétaire n'a pas encore envoyé sa fiche : elle est incomplète.";
        }

        if (! $listing->trust_level->isVerified()) {
            return "Attribuez d'abord le niveau 2 au moins, après l'appel de vérification : on ne met pas en ligne une annonce seulement déclarée.";
        }

        return null;
    }
}
