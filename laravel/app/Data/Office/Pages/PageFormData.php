<?php

namespace App\Data\Office\Pages;

use App\Models\Page;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

/**
 * Une page, pour son formulaire. `adresseFigee` : publiée une fois, ou
 * attendue par le site — son adresse a pu être partagée, elle ne bouge plus.
 */
final class PageFormData extends Data
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
        public readonly ?string $lede,
        public readonly ?string $body,
        #[MapOutputName('seo_description')]
        public readonly ?string $seoDescription,
        #[MapOutputName('internal_note')]
        public readonly ?string $internalNote,
        public readonly bool $adresseFigee,
    ) {}

    public static function fromModel(Page $p): self
    {
        return new self(...[
            ...PageRowData::fromModel($p)->champs(),
            'lede' => $p->lede,
            'body' => $p->body,
            'seoDescription' => $p->seo_description,
            'internalNote' => $p->internal_note,
            'adresseFigee' => $p->published_at !== null || $p->is_system,
        ]);
    }
}
