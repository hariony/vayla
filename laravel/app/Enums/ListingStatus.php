<?php

namespace App\Enums;

/**
 * Cycle de vie d'une annonce.
 *
 * **`Submitted` est l'état qui protège tout le produit.** Un propriétaire
 * remplit son annonce lui-même — c'est lui qui connaît son logement — mais il
 * ne la publie pas : il la **soumet**, et Vayla la contrôle avant qu'elle
 * n'apparaisse. Sans ce palier, n'importe qui se mettrait en ligne, et
 * « vérifié » ne voudrait plus rien dire. Le niveau de confiance
 * (`TrustLevel`) reste attribué par Vayla, jamais déclaré.
 *
 * `Draft` couvre la fiche en cours de remplissage ; `Archived` la retire des
 * listes sans la détruire — une annonce supprimée effacerait l'historique de
 * vérification, et les réservations passées avec elle.
 */
enum ListingStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Submitted => 'En attente de vérification',
            self::Published => 'En ligne',
            self::Archived => 'Archivée',
        };
    }

    /** Ce que le propriétaire doit faire, écrit comme une consigne. */
    public function consigne(): string
    {
        return match ($this) {
            self::Draft => 'Complétez la fiche, puis envoyez-la à Vayla pour vérification.',
            self::Submitted => 'Vayla vérifie votre annonce. Nous vous appelons sous 48 h.',
            self::Published => 'Votre annonce est visible par les voyageurs.',
            self::Archived => "Cette annonce n'est plus proposée. Contactez Vayla pour la remettre en ligne.",
        };
    }

    /** Seule une annonce en ligne se réserve. */
    public function estVisible(): bool
    {
        return $this === self::Published;
    }

    /** Le propriétaire modifie librement tant que Vayla n'a pas encore vérifié. */
    public function estModifiable(): bool
    {
        return $this === self::Draft || $this === self::Submitted;
    }

    /**
     * Le mot court de l'équipe, pour un onglet (au pluriel) ou une ligne. Celui
     * de `label()` — « En attente de vérification » — est écrit pour le
     * propriétaire ; au back-office on trie une file, et « À vérifier » dit le
     * travail.
     */
    public function libelleFile(bool $pluriel = true): string
    {
        return match ($this) {
            self::Submitted => 'À vérifier',
            self::Published => 'En ligne',
            self::Draft => $pluriel ? 'Brouillons' : 'Brouillon',
            self::Archived => $pluriel ? 'Archivées' : 'Archivée',
        };
    }
}
