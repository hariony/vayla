<?php

namespace App\Repositories;

use App\Contracts\Repositories\PageRepositoryInterface;
use App\DTOs\Content\PageDto;
use App\Models\Admin;
use App\Models\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PageRepository implements PageRepositoryInterface
{
    public function pourPied(): Collection
    {
        return Page::query()
            ->where('is_published', true)
            ->whereNotNull('footer_group')
            ->orderBy('footer_position')
            ->orderBy('title')
            ->get(['slug', 'title', 'footer_group']);
    }

    public function publiee(string $slug): ?Page
    {
        return Page::query()->where('slug', $slug)->where('is_published', true)->first();
    }

    public function toutes(): Collection
    {
        return Page::query()
            ->orderByRaw('case when footer_group is null then 1 else 0 end')
            ->orderBy('footer_group')
            ->orderBy('footer_position')
            ->get();
    }

    public function adresseOccupee(string $slug, ?int $sauf = null): bool
    {
        return Page::query()->where('slug', $slug)->when($sauf, fn ($q) => $q->where('id', '!=', $sauf))->exists();
    }

    public function creer(Admin $par, PageDto $contenu, string $slug): Page
    {
        $dernier = (int) Page::query()->where('footer_group', $contenu->groupe?->value)->max('footer_position');

        return Page::create([
            ...$this->champs($contenu),
            'slug' => $slug,
            'footer_position' => $dernier + 1,
            'updated_by' => $par->id,
        ]);
    }

    public function modifier(Admin $par, Page $page, PageDto $contenu, ?string $slug): void
    {
        $page->fill([
            ...$this->champs($contenu),
            'slug' => $slug ?? $page->slug,
            'updated_by' => $par->id,
        ])->save();
    }

    public function publier(Admin $par, Page $page): void
    {
        $page->forceFill([
            'is_published' => true,
            'published_at' => $page->published_at ?? Carbon::now(),
            'updated_by' => $par->id,
        ])->save();
    }

    public function depublier(Admin $par, Page $page): void
    {
        $page->forceFill(['is_published' => false, 'updated_by' => $par->id])->save();
    }

    public function supprimer(Page $page): void
    {
        $page->delete();
    }

    /** @return array<string, mixed> */
    private function champs(PageDto $contenu): array
    {
        return [
            'title' => $contenu->title,
            'lede' => $contenu->lede,
            'body' => $contenu->body,
            'seo_description' => $contenu->seoDescription,
            'footer_group' => $contenu->groupe?->value,
            'internal_note' => $contenu->internalNote,
        ];
    }
}
