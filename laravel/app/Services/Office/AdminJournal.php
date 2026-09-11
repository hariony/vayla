<?php

namespace App\Services\Office;

use App\Contracts\Office\ActionJournal;
use App\Contracts\Office\JournalReader;
use App\Contracts\Repositories\AdminActionRepositoryInterface;
use App\Data\Office\JournalLineData;
use App\Enums\AdminActionKind;
use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Listing;
use App\Models\OutboundMessage;
use App\Models\Owner;
use App\Models\Page;
use App\Models\Photo;
use App\Models\StayRequest;
use Illuminate\Database\Eloquent\Model;

/**
 * Le journal des gestes de l'équipe.
 *
 * **Écrit par les services, jamais par les contrôleurs.** C'est le service qui
 * sait qu'une annonce a vraiment été mise en ligne ; un contrôleur qui
 * écrirait la ligne après coup la laisserait le jour où le geste échoue.
 */
class AdminJournal implements ActionJournal, JournalReader
{
    /** Le type de sujet, en un mot stable : le journal survit aux renommages de classes. */
    private const TYPES = [
        Listing::class => 'listing',
        Booking::class => 'booking',
        Owner::class => 'owner',
        Admin::class => 'admin',
        OutboundMessage::class => 'whatsapp',
        Destination::class => 'destination',
        Category::class => 'category',
        Amenity::class => 'amenity',
        Page::class => 'page',
        Photo::class => 'photo',
        StayRequest::class => 'stay_request',
    ];

    public function __construct(private AdminActionRepositoryInterface $lignes) {}

    public function consigner(Admin $admin, AdminActionKind $kind, ?Model $sujet, string $resume, ?string $note = null): AdminAction
    {
        return $this->lignes->creer($admin, $kind, $this->type($sujet), $sujet?->getKey(), $resume, $note);
    }

    public function pour(Model $sujet, int $limite = 20): array
    {
        $type = $this->type($sujet);

        if ($type === null) {
            return [];
        }

        return $this->lignes->pour($type, (int) $sujet->getKey(), $limite)
            ->map(fn (AdminAction $a) => JournalLineData::fromModel($a))
            ->all();
    }

    public function dernieres(int $limite, ?AdminActionKind $kind = null): array
    {
        return $this->lignes->dernieres($limite, $kind)
            ->map(fn (AdminAction $a) => JournalLineData::fromModel($a))
            ->all();
    }

    private function type(?Model $sujet): ?string
    {
        return $sujet ? (self::TYPES[$sujet::class] ?? null) : null;
    }
}
