<?php

namespace App\Services\Office\Invoices;

use App\Contracts\Invoices\InvoiceCalculator;
use App\Contracts\Office\ActionJournal;
use App\Contracts\Repositories\InvoiceSettlementRepositoryInterface;
use App\Contracts\Repositories\OfficeOwnerRepositoryInterface;
use App\DTOs\Office\SettlementDto;
use App\Enums\AdminActionKind;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;
use App\Models\Owner;
use Illuminate\Support\Carbon;

/**
 * Consigner le règlement d'une facture — ou l'annuler. **Le montant est
 * recalculé, jamais saisi** : c'est la facture qui dit ce qui est dû. **Le mois
 * en cours ne se règle pas** : ce n'est pas encore une facture.
 */
final class InvoiceSettlements
{
    public function __construct(
        private InvoiceCalculator $factures,
        private OfficeOwnerRepositoryInterface $proprietaires,
        private InvoiceSettlementRepositoryInterface $reglements,
        private ActionJournal $journal,
    ) {}

    public function regler(Admin $admin, SettlementDto $reglement): Owner
    {
        $owner = $this->proprietaire($reglement->ownerId);
        $debut = $reglement->mois->copy()->startOfMonth();

        if ($debut->gte(Carbon::today()->startOfMonth())) {
            throw new OfficeRefusal("Le mois n'est pas terminé : ce n'est pas encore une facture.");
        }

        $facture = $this->factures->forOwner($owner, $debut);

        if ($facture['due'] <= 0) {
            throw new OfficeRefusal('Aucune commission due ce mois-là : il n’y a rien à régler.');
        }

        $this->reglements->consigner($owner, $debut, $facture['due'], $reglement->reference, $admin->id);

        $this->journal->consigner($admin, AdminActionKind::InvoiceSettled, $owner,
            "Facture de {$facture['period']['label']} réglée par {$owner->name} — ".$this->ariary($facture['due']).'.',
            $reglement->reference ? 'Référence : '.$reglement->reference : null);

        return $owner;
    }

    public function rouvrir(Admin $admin, SettlementDto $reglement): Owner
    {
        $owner = $this->proprietaire($reglement->ownerId);
        $debut = $reglement->mois->copy()->startOfMonth();

        if (! $this->reglements->annuler($owner, $debut)) {
            throw new OfficeRefusal("Cette facture n'était pas marquée comme réglée.");
        }

        $this->journal->consigner($admin, AdminActionKind::InvoiceReopened, $owner,
            "Règlement de {$owner->name} pour ".$debut->translatedFormat('F Y').' annulé.');

        return $owner;
    }

    private function proprietaire(int $id): Owner
    {
        return $this->proprietaires->trouver($id) ?? throw new OfficeRefusal('Ce propriétaire n’existe plus.');
    }

    private function ariary(int $n): string
    {
        return number_format($n, 0, ',', "\u{00A0}")."\u{00A0}Ar";
    }
}
