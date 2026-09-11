<?php

namespace App\Services\Notifications;

use App\Contracts\Repositories\OutboundMessageRepositoryInterface;
use App\Enums\NotificationKind;
use App\Models\Booking;
use App\Models\Owner;
use App\Support\Telephone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Ce que Vayla écrit au propriétaire, sur WhatsApp.
 *
 * **C'est le maillon qui rend tout le reste utile.** L'espace propriétaire,
 * le calendrier, le fil d'échange : rien de tout ça ne sert si le propriétaire
 * ne sait pas qu'on l'attend. Une demande a quarante-huit heures pour être
 * répondue ; sans message, il faut qu'il pense à ouvrir Vayla dans cette
 * fenêtre, ce qui n'arrivera pas.
 *
 * **WhatsApp et pas l'e-mail** : c'est là que sont nos propriétaires, et c'est
 * déjà par là qu'on les joint. Beaucoup n'ont pas de boîte qu'ils relèvent.
 *
 * **Le message est écrit en file, pas envoyé.** Vayla n'a pas encore
 * d'entreprise enregistrée, et l'API WhatsApp Business de Meta en exige une :
 * l'envoi se fait à la main, par un lien `wa.me` prêt à cliquer
 * (`vayla:whatsapp`). Le jour où l'API arrive, seul l'expéditeur change — les
 * textes, la file et les règles de non-répétition restent.
 *
 * Trois règles pour les textes, et elles viennent du canal :
 *
 * 1. **Le fait d'abord, le lien en dernier.** On lit un message WhatsApp dans
 *    une notification tronquée : la première ligne doit suffire à savoir s'il
 *    faut ouvrir.
 * 2. **Ce qu'on risque à ne rien faire est écrit.** « Sans réponse, les nuits
 *    repartent dans votre calendrier » est la seule phrase qui fait répondre.
 * 3. **Jamais deux fois le même message.** Un expéditeur qui se répète se met
 *    en sourdine, et le jour où le message compte il n'est plus lu.
 */
class OwnerNotifier
{
    public function __construct(
        private OutboundMessageRepositoryInterface $file,
    ) {}

    /** Une demande vient d'arriver : c'est le message qui déclenche tout. */
    public function nouvelleDemande(Booking $booking): void
    {
        $owner = $booking->listing?->owner;

        if (! $this->joignable($owner) || $this->file->dejaEnfile(NotificationKind::NouvelleDemande, $booking)) {
            return;
        }

        $heures = (int) config('vayla.booking.hold_hours');

        $this->file->enfiler(
            NotificationKind::NouvelleDemande,
            $owner->phone,
            $this->texte([
                "Bonjour {$this->prenom($owner)}, une demande de séjour vient d'arriver sur Vayla.",
                '',
                $booking->listing->title,
                $this->sejour($booking).' · '.$booking->guests.' pers.',
                $this->argent($booking->total).' pour vous',
                '',
                "Vous avez {$heures} h pour répondre. Sans réponse, les nuits repartent dans votre "
                    .'calendrier et le voyageur cherche ailleurs.',
                '',
                'Répondre : '.route('owner.home'),
            ]),
            $owner,
            $booking,
        );
    }

    /**
     * Le rappel, une seule fois, quand le délai se referme.
     *
     * C'est la notification qui rattrape le plus de demandes : celui qui n'a
     * pas répondu au premier message n'a en général pas décidé de refuser, il
     * a oublié.
     */
    public function demandeExpireBientot(Booking $booking): void
    {
        $owner = $booking->listing?->owner;

        if (! $this->joignable($owner) || $this->file->dejaEnfile(NotificationKind::DemandeExpireBientot, $booking)) {
            return;
        }

        $reste = max(0, (int) floor(Carbon::now()->diffInHours($booking->hold_expires_at, false)));

        $this->file->enfiler(
            NotificationKind::DemandeExpireBientot,
            $owner->phone,
            $this->texte([
                "{$this->prenom($owner)}, il reste {$reste} h pour répondre à la demande de "
                    ."{$booking->traveller}.",
                '',
                $booking->listing->title,
                $this->sejour($booking),
                $this->argent($booking->total).' pour vous',
                '',
                'Passé ce délai, les nuits repartent dans votre calendrier.',
                '',
                'Répondre : '.route('owner.home'),
            ]),
            $owner,
            $booking,
        );
    }

    /**
     * Un voyageur a écrit.
     *
     * Pas de contrôle de non-répétition ici, et c'est voulu : deux questions
     * posées à deux jours d'écart sont deux messages à lire. La retenue se
     * fait autrement — seuls les messages **du voyageur** déclenchent, jamais
     * les réponses du propriétaire lui-même.
     */
    public function nouveauMessage(Booking $booking, string $extrait): void
    {
        $owner = $booking->listing?->owner;

        if (! $this->joignable($owner)) {
            return;
        }

        $this->file->enfiler(
            NotificationKind::NouveauMessage,
            $owner->phone,
            $this->texte([
                "Bonjour {$this->prenom($owner)}, {$booking->traveller} vous a écrit à propos de "
                    ."la réservation {$booking->reference}.",
                '',
                '« '.Str::limit($extrait, 180).' »',
                '',
                'Répondre : '.route('owner.bookings.show', $booking->reference),
            ]),
            $owner,
            $booking,
        );
    }

    /**
     * Le lien d'accès au compte — première connexion, ou mot de passe perdu.
     *
     * C'est le seul message qui porte un secret : le texte le dit, parce
     * qu'un lien transféré dans une conversation de groupe ouvre l'espace à
     * tout le monde.
     */
    public function lienAcces(Owner $owner): void
    {
        if (! $this->joignable($owner)) {
            return;
        }

        $this->file->enfiler(
            NotificationKind::LienAcces,
            $owner->phone,
            $this->texte([
                "Bonjour {$this->prenom($owner)}, voici votre lien pour ouvrir votre espace Vayla.",
                '',
                route('owner.access', ['cle' => $owner->access_key]),
                '',
                // Le lien est le chemin court ; l'adresse reste le chemin sûr.
                // Dire les deux évite l'impasse le jour où le lien a tourné.
                'Il ouvre votre espace directement. Vous pouvez aussi entrer avec votre '
                    .'adresse e-mail : nous vous envoyons alors un code à six chiffres.',
                '',
                'Ce lien est personnel : ne le transmettez à personne.',
            ]),
            $owner,
            null,
        );
    }

    /**
     * Un propriétaire sans numéro utilisable ne reçoit rien — et on ne met pas
     * une ligne morte dans la file, qui est une liste de travail humaine.
     */
    private function joignable(?Owner $owner): bool
    {
        return $owner !== null && Telephone::depuis($owner->phone)?->estMobile() === true;
    }

    private function prenom(Owner $owner): string
    {
        return Str::of($owner->name)->trim()->explode(' ')->first() ?: 'bonjour';
    }

    private function sejour(Booking $booking): string
    {
        return $booking->arrival->translatedFormat('j M')
            .' → '.$booking->departure->translatedFormat('j M Y')
            .' ('.$booking->nights.' nuit'.($booking->nights > 1 ? 's' : '').')';
    }

    /**
     * Les milliers séparés par une espace insécable ordinaire : WhatsApp ne
     * dessine pas l'espace fine (U+202F), et « 1110000 Ar » ne se lit pas.
     */
    private function argent(int $ariary): string
    {
        return str_replace(["\u{202f}", "\u{a0}"], ' ', number_format($ariary, 0, ',', "\u{a0}")).' Ar';
    }

    /** @param  array<int, string>  $lignes */
    private function texte(array $lignes): string
    {
        return implode("\n", $lignes);
    }
}
