<?php

namespace App\Data\Office\Pages;

use App\Enums\PageGroup;
use App\Models\Page;
use App\Support\ACompleter;
use App\Support\PublicUrl;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

/**
 * Une page, dans la liste du back-office. `note` : l'équipe s'est laissé une
 * consigne ; `aCompleter` : la page porte encore un « [à compléter] ».
 */
final class PageRowData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $title,
        public readonly ?string $groupe,
        #[MapOutputName('footer_group')]
        public readonly ?string $footerGroup,
        public readonly bool $publiee,
        public readonly bool $systeme,
        public readonly bool $note,
        public readonly bool $aCompleter,
        public readonly ?string $misAJour,
        public readonly string $url,
    ) {}

    public static function fromModel(Page $p): self
    {
        return new self(
            id: $p->id,
            slug: $p->slug,
            title: $p->title,
            groupe: PageGroup::tryFrom((string) $p->footer_group)?->label(),
            footerGroup: $p->footer_group,
            publiee: $p->is_published,
            systeme: $p->is_system,
            note: (bool) $p->internal_note,
            aCompleter: ACompleter::present($p->body, $p->lede),
            misAJour: $p->updated_at?->toIso8601String(),
            url: PublicUrl::de('/'.$p->slug),
        );
    }

    /**
     * Les champs, par nom — pour que le formulaire les reprenne tels quels et
     * n'ajoute que ce qui lui est propre.
     *
     * @return array<string, mixed>
     */
    public function champs(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'groupe' => $this->groupe,
            'footerGroup' => $this->footerGroup,
            'publiee' => $this->publiee,
            'systeme' => $this->systeme,
            'note' => $this->note,
            'aCompleter' => $this->aCompleter,
            'misAJour' => $this->misAJour,
            'url' => $this->url,
        ];
    }
}
