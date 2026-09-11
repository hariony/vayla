<?php

namespace App\Services\Office\Journal;

use App\Contracts\Repositories\AdminActionRepositoryInterface;
use App\Data\Office\Journal\JournalPageData;
use App\Data\Office\JournalLineData;
use App\Data\Office\ListFilterData;
use App\Data\Office\PaginatedData;
use App\Data\Office\TabData;
use App\Enums\AdminActionKind;
use App\Models\AdminAction;

/**
 * Le journal, par famille. **Consulter ne s'écrit pas** : seulement ce qui
 * change quelque chose.
 */
final class JournalQuery
{
    private const PAR_PAGE = 40;

    private const LIBELLES = [
        'annonces' => 'Annonces', 'proprietaires' => 'Propriétaires', 'reservations' => 'Réservations',
        'whatsapp' => 'WhatsApp', 'facturation' => 'Facturation', 'contenu' => 'Contenu', 'equipe' => 'Équipe',
    ];

    public function __construct(private AdminActionRepositoryInterface $lignes) {}

    /** @return array<int, string> les familles, dans l'ordre de l'enum */
    public static function familles(): array
    {
        return array_values(array_unique(array_map(fn (AdminActionKind $k) => $k->famille(), AdminActionKind::cases())));
    }

    public function page(?string $famille): JournalPageData
    {
        return new JournalPageData(
            lignes: PaginatedData::fromPaginator($this->lignes->paginer($famille, self::PAR_PAGE), fn (AdminAction $a) => JournalLineData::fromModel($a)),
            onglets: [
                new TabData('tout', 'Tout'),
                ...array_map(fn (string $f) => new TabData($f, self::LIBELLES[$f] ?? $f), self::familles()),
            ],
            filtre: new ListFilterData($famille ?? 'tout', ''),
        );
    }
}
