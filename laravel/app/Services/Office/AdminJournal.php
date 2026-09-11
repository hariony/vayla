<?php

namespace App\Services\Office;

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
class AdminJournal
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

    public function consigner(Admin $admin, AdminActionKind $kind, ?Model $sujet, string $resume, ?string $note = null): AdminAction
    {
        return AdminAction::create([
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'kind' => $kind,
            'subject_type' => $sujet ? (self::TYPES[$sujet::class] ?? null) : null,
            'subject_id' => $sujet?->getKey(),
            'summary' => mb_substr($resume, 0, 300),
            'note' => $note,
        ]);
    }

    /**
     * Les lignes qui portent sur un sujet, les plus récentes d'abord.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pour(Model $sujet, int $limite = 20): array
    {
        return AdminAction::query()
            ->where('subject_type', self::TYPES[$sujet::class] ?? '')
            ->where('subject_id', $sujet->getKey())
            ->latest('id')
            ->limit($limite)
            ->get()
            ->map(fn (AdminAction $a) => self::ligne($a))
            ->all();
    }

    /** @return array<string, mixed> */
    public static function ligne(AdminAction $a): array
    {
        return [
            'id' => $a->id,
            'kind' => $a->kind->value,
            'label' => $a->kind->label(),
            'famille' => $a->kind->famille(),
            'admin' => $a->admin_name,
            'summary' => $a->summary,
            'note' => $a->note,
            'subject' => $a->subject_type ? ['type' => $a->subject_type, 'id' => $a->subject_id] : null,
            'at' => $a->created_at->toIso8601String(),
        ];
    }
}
