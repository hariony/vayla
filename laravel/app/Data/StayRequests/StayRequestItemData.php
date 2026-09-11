<?php

namespace App\Data\StayRequests;

use App\Models\StayRequest;
use App\Support\Telephone;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

/**
 * Une demande de séjour dans la file du back-office. **Elle porte de quoi
 * agir sans rien ouvrir d'autre** : WhatsApp prêt à écrire, et le catalogue
 * public déjà filtré sur la demande.
 */
final class StayRequestItemData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $statut,
        public readonly ?string $recue,
        public readonly string $nom,
        public readonly ?string $email,
        public readonly ?string $telephone,
        public readonly ?string $whatsapp,
        public readonly ?string $tel,
        public readonly ?string $destination,
        public readonly ?string $lieu,
        public readonly ?string $arrivee,
        public readonly ?string $depart,
        public readonly ?int $nuits,
        public readonly int $voyageurs,
        public readonly ?int $budget,
        public readonly ?string $message,
        public readonly ?string $parQui,
        public readonly ?string $priseLe,
        public readonly ?string $closeLe,
        public readonly ?string $note,
        public readonly string $catalogue,
    ) {}

    public static function fromModel(StayRequest $d): self
    {
        $telephone = Telephone::depuis($d->phone);

        return new self(
            id: $d->id,
            statut: $d->status->value,
            recue: $d->created_at?->toIso8601String(),
            nom: $d->name,
            email: $d->email,
            telephone: $telephone?->lisible() ?? $d->phone,
            whatsapp: self::whatsapp($telephone, $d->name),
            tel: $telephone ? 'tel:'.$telephone->e164() : null,
            destination: $d->destination?->name,
            lieu: $d->place,
            arrivee: $d->arrival,
            depart: $d->departure,
            nuits: $d->arrival && $d->departure ? (int) Carbon::parse($d->arrival)->diffInDays(Carbon::parse($d->departure)) : null,
            voyageurs: $d->guests,
            budget: $d->budget,
            message: $d->message,
            parQui: $d->admin?->name,
            priseLe: $d->taken_at?->toIso8601String(),
            closeLe: $d->closed_at?->toIso8601String(),
            note: $d->closing_note,
            catalogue: self::catalogue($d),
        );
    }

    /** WhatsApp ne joint qu'un mobile ; `wa.me` veut le numéro **sans le `+`**. */
    private static function whatsapp(?Telephone $telephone, string $nom): ?string
    {
        if (! $telephone || ! $telephone->estMobile()) {
            return null;
        }

        return 'https://wa.me/'.ltrim($telephone->e164(), '+')
            .'?text='.rawurlencode("Bonjour {$nom}, c’est Vayla : nous avons bien reçu votre demande de séjour.");
    }

    /** Le catalogue public déjà filtré sur la demande — sur `APP_URL`, pas sur l'hôte du back-office. */
    private static function catalogue(StayRequest $d): string
    {
        $criteres = array_filter([
            'destination' => $d->destination?->slug,
            'arrival' => $d->arrival,
            'departure' => $d->departure,
            'guests' => $d->guests,
        ]);

        return rtrim((string) config('app.url'), '/').'/logements'.($criteres ? '?'.http_build_query($criteres) : '');
    }
}
