<?php

namespace App\Services\Office;

use App\Enums\AdminActionKind;
use App\Enums\MessageAuthor;
use App\Exceptions\BookingRefusedException;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\InvoiceSettlement;
use App\Models\OutboundMessage;
use App\Models\Owner;
use App\Repositories\Contracts\OutboundMessageRepositoryInterface;
use App\Services\BookingService;
use App\Services\ConversationService;
use App\Services\InvoiceService;
use App\Services\Notifications\OwnerNotifier;
use App\Services\OwnerKeyService;
use App\Support\Telephone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Les gestes du back-office qui touchent une réservation, un propriétaire, la
 * file WhatsApp, une facture ou l'équipe.
 *
 * **Aucune règle métier n'est réécrite ici.** Annuler passe par
 * `BookingService`, écrire dans un fil par `ConversationService`, renvoyer un
 * lien par `OwnerNotifier` : le back-office est une troisième porte sur les
 * mêmes services, comme le site et l'API mobile. S'il avait sa propre façon
 * d'annuler, il finirait par annuler ce que le site refuse.
 *
 * Ce que ce service ajoute, c'est **la trace** : chaque geste laisse une ligne
 * au journal, écrite après que le geste a réussi.
 */
class OfficeActions
{
    public function __construct(
        private AdminJournal $journal,
        private BookingService $bookings,
        private ConversationService $conversations,
        private OwnerNotifier $notifier,
        private OwnerKeyService $cles,
        private OutboundMessageRepositoryInterface $file,
        private InvoiceService $factures,
        private OfficeAuthService $acces,
    ) {}

    /**
     * Annuler, c'est aussi **l'écrire dans le fil**.
     *
     * Les deux parties lisent ce fil ; une annulation qui n'y figure pas
     * arriverait au voyageur par un canal dont le propriétaire n'a pas
     * connaissance, et inversement. Le motif y est donc écrit au nom de Vayla.
     */
    public function annulerReservation(Admin $admin, Booking $booking, string $motif): void
    {
        try {
            DB::transaction(function () use ($booking, $motif) {
                $this->bookings->cancel($booking, trim($motif));
                $this->conversations->ecrire($booking, MessageAuthor::Vayla, 'Vayla a annulé cette réservation. '.trim($motif));
            });
        } catch (BookingRefusedException $e) {
            throw new OfficeRefusal($e->getMessage());
        }

        $this->journal->consigner($admin, AdminActionKind::BookingCancelled, $booking,
            "Réservation {$booking->reference} annulée ({$booking->traveller}).", trim($motif));
    }

    public function ecrire(Admin $admin, Booking $booking, string $corps): void
    {
        $this->conversations->ecrire($booking, MessageAuthor::Vayla, $corps);

        $this->journal->consigner($admin, AdminActionKind::MessageWritten, $booking,
            "Message de Vayla dans le fil de {$booking->reference}.", trim($corps));
    }

    /**
     * Le numéro est confirmé **par l'appel de vérification**, et par rien d'autre.
     *
     * C'est le geste qui ouvre le niveau 2. Il se fait après avoir eu la
     * personne au bout du fil — le bouton le dit, et le journal garde qui l'a
     * appuyé.
     */
    public function verifierTelephone(Admin $admin, Owner $owner): void
    {
        if ($owner->telephoneVerifie()) {
            throw new OfficeRefusal('Ce numéro est déjà vérifié.');
        }

        $owner->forceFill(['phone_verified_at' => Carbon::now()])->save();

        $this->journal->consigner($admin, AdminActionKind::PhoneVerified, $owner,
            "Numéro de {$owner->name} vérifié par appel ({$owner->telephone()?->lisible()}).");
    }

    /**
     * Remet le lien d'accès dans la file WhatsApp.
     *
     * La clé ne tourne pas : le propriétaire qui l'a perdue dans ses
     * conversations la retrouve telle quelle. La faire tourner est un geste
     * d'incident (`vayla:rotate-owner-key`), pas de support.
     */
    public function renvoyerLien(Admin $admin, Owner $owner): void
    {
        if (Telephone::depuis($owner->phone)?->estMobile() !== true) {
            throw new OfficeRefusal("Ce numéro n'est pas un mobile : WhatsApp n'y arrive pas. Corrigez-le avec le propriétaire d'abord.");
        }

        if (! $owner->access_key) {
            $this->cles->tourner($owner);
        }

        $this->notifier->lienAcces($owner->refresh());

        $this->journal->consigner($admin, AdminActionKind::AccessLinkSent, $owner,
            "Lien d'accès de {$owner->name} remis dans la file WhatsApp.");
    }

    public function marquerEnvoye(Admin $admin, OutboundMessage $message): void
    {
        if ($message->envoye()) {
            throw new OfficeRefusal('Ce message est déjà marqué comme envoyé.');
        }

        $this->file->marquerEnvoye($message);

        $this->journal->consigner($admin, AdminActionKind::WhatsAppSent, $message,
            "{$message->kind->label()} envoyé à ".($message->owner?->name ?? $message->to).'.');
    }

    /**
     * Consigne le règlement d'une facture.
     *
     * **Le montant est recalculé, jamais saisi** : c'est la facture qui dit ce
     * qui est dû, et une saisie à la main ouvrirait l'écart entre ce que voit
     * le propriétaire et ce que Vayla croit avoir reçu. **Le mois en cours ne
     * se règle pas** — ce n'est pas encore une facture, l'écran du propriétaire
     * le lui dit.
     */
    public function reglerFacture(Admin $admin, Owner $owner, Carbon $mois, ?string $reference): void
    {
        $debut = $mois->copy()->startOfMonth();

        if ($debut->gte(Carbon::today()->startOfMonth())) {
            throw new OfficeRefusal("Le mois n'est pas terminé : ce n'est pas encore une facture.");
        }

        $facture = $this->factures->forOwner($owner, $debut);

        if ($facture['due'] <= 0) {
            throw new OfficeRefusal('Aucune commission due ce mois-là : il n’y a rien à régler.');
        }

        InvoiceSettlement::updateOrCreate(
            ['owner_id' => $owner->id, 'month' => $debut->toDateString()],
            [
                'amount' => $facture['due'],
                'reference' => $reference ? trim($reference) : null,
                'admin_id' => $admin->id,
                'settled_at' => Carbon::now(),
            ],
        );

        $this->journal->consigner($admin, AdminActionKind::InvoiceSettled, $owner,
            "Facture de {$facture['period']['label']} réglée par {$owner->name} — {$this->ariary($facture['due'])}.",
            $reference ? 'Référence : '.trim($reference) : null);
    }

    public function rouvrirFacture(Admin $admin, Owner $owner, Carbon $mois): void
    {
        $debut = $mois->copy()->startOfMonth();

        $supprimes = InvoiceSettlement::query()
            ->where('owner_id', $owner->id)
            ->where('month', $debut->toDateString())
            ->delete();

        if (! $supprimes) {
            throw new OfficeRefusal("Cette facture n'était pas marquée comme réglée.");
        }

        $this->journal->consigner($admin, AdminActionKind::InvoiceReopened, $owner,
            "Règlement de {$owner->name} pour ".$debut->translatedFormat('F Y').' annulé.');
    }

    /**
     * Ajoute un membre, avec un mot de passe **provisoire** à lui transmettre
     * de vive voix. Rien ne part par e-mail : un mot de passe dans une boîte de
     * réception y reste. Il en choisira un à sa première connexion.
     *
     * @return array{0: Admin, 1: string} le membre et son mot de passe provisoire
     */
    public function ajouterMembre(Admin $admin, string $nom, string $email): array
    {
        $membre = Admin::create(['name' => trim($nom), 'email' => mb_strtolower(trim($email))]);
        $motDePasse = $this->acces->provisoire($membre);

        $this->journal->consigner($admin, AdminActionKind::AdminAdded, $membre,
            "{$membre->name} ({$membre->email}) ajouté à l'équipe.");

        return [$membre, $motDePasse];
    }

    /**
     * Retirer un membre. **Jamais soi-même, jamais le dernier** : l'un comme
     * l'autre fermerait le back-office de l'intérieur, et il faudrait une ligne
     * de commande sur le serveur pour le rouvrir.
     */
    public function retirerMembre(Admin $admin, Admin $membre): void
    {
        if ($membre->is($admin)) {
            throw new OfficeRefusal("Vous ne pouvez pas vous retirer vous-même : demandez à un autre membre de l'équipe.");
        }

        if (Admin::query()->count() <= 1) {
            throw new OfficeRefusal("C'est le dernier membre de l'équipe : le retirer fermerait le back-office.");
        }

        $this->journal->consigner($admin, AdminActionKind::AdminRemoved, $membre,
            "{$membre->name} ({$membre->email}) retiré de l'équipe.");

        $membre->delete();
    }

    private function ariary(int $n): string
    {
        return number_format($n, 0, ',', "\u{00A0}")."\u{00A0}Ar";
    }
}
