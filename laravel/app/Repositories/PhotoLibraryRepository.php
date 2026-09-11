<?php

namespace App\Repositories;

use App\Contracts\Repositories\PhotoLibraryRepositoryInterface;
use App\Data\Photos\PhotoUsageData;
use App\DTOs\Photos\PhotoCreditDto;
use App\DTOs\Photos\PhotoCreditPatch;
use App\Enums\PhotoLibraryTab;
use App\Models\Photo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PhotoLibraryRepository implements PhotoLibraryRepositoryInterface
{
    public function paginer(PhotoLibraryTab $onglet, string $recherche, int $parPage): LengthAwarePaginator
    {
        return $this->onglet($onglet)
            ->when($recherche !== '', fn (Builder $b) => $this->chercher($b, $recherche))
            ->orderByRaw("case when folder = 'destinations' then 0 else 1 end")
            ->orderByDesc('id')
            ->paginate($parPage)
            ->withQueryString();
    }

    public function compter(PhotoLibraryTab $onglet): int
    {
        return $this->onglet($onglet)->count();
    }

    public function trouver(int $id): ?Photo
    {
        return Photo::query()->find($id);
    }

    public function usages(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $usages = [];

        foreach ($this->galeries('destination_photo', 'destinations', 'destination_id', 'name', $ids) as $l) {
            $usages[$l->photo_id][] = PhotoUsageData::destination((int) $l->id, $l->nom, (int) $l->position === 0);
        }

        foreach ($this->galeries('listing_photo', 'listings', 'listing_id', 'title', $ids) as $l) {
            $usages[$l->photo_id][] = PhotoUsageData::annonce((int) $l->id, $l->nom, (int) $l->position === 0);
        }

        return $usages;
    }

    public function creerPhotoEquipe(string $cle, int $largeur, PhotoCreditDto $credit): Photo
    {
        return Photo::create([
            'key' => $cle,
            'folder' => 'destinations',
            'width' => $largeur,
            'is_ai' => false,
            'caption' => $credit->caption,
            'author' => $credit->author,
            'licence' => $credit->licence->label(),
            'licence_url' => $credit->licence->url(),
            'source_url' => $credit->sourceUrl,
        ]);
    }

    public function appliquerCredit(Photo $photo, PhotoCreditPatch $patch): array
    {
        $photo->caption = $patch->caption;

        if ($patch->author !== null) {
            $photo->author = $patch->author;
        }

        if ($patch->licence !== null) {
            $photo->licence = $patch->licence->label();
            $photo->licence_url = $patch->licence->url();
        }

        if ($patch->toucherSource) {
            $photo->source_url = $patch->sourceUrl;
        }

        $changes = array_keys($photo->getDirty());
        $photo->save();

        return $changes;
    }

    public function supprimer(Photo $photo): void
    {
        $photo->delete();
    }

    private function onglet(PhotoLibraryTab $onglet): Builder
    {
        $lieux = Photo::query()->whereIn('folder', ['lieux', 'destinations']);

        return match ($onglet) {
            PhotoLibraryTab::Lieux => $this->vraies($lieux),
            PhotoLibraryTab::Televersees => Photo::query()->where('folder', 'destinations'),
            PhotoLibraryTab::Inutilisees => $this->vraies($lieux)
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('destination_photo')->whereColumn('destination_photo.photo_id', 'photos.id'))
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('listing_photo')->whereColumn('listing_photo.photo_id', 'photos.id')),
            PhotoLibraryTab::Demonstration => Photo::query()->where('folder', 'lieux')->where(fn (Builder $w) => $w
                ->where('is_ai', true)->orWhere('key', 'like', 'an-%')->orWhere('key', 'like', 'ia-%')),
            PhotoLibraryTab::Proprietaires => Photo::query()->where('folder', 'annonces'),
        };
    }

    /** Ni image générée, ni photo des annonces de démonstration. */
    private function vraies(Builder $requete): Builder
    {
        return $requete->where('is_ai', false)->where('key', 'not like', 'an-%')->where('key', 'not like', 'ia-%');
    }

    private function chercher(Builder $requete, string $recherche): Builder
    {
        $motif = '%'.mb_strtolower($recherche).'%';

        return $requete->where(fn (Builder $w) => $w
            ->whereRaw('lower(caption) like ?', [$motif])
            ->orWhereRaw('lower(author) like ?', [$motif])
            ->orWhereRaw('lower(key) like ?', [$motif]));
    }

    /**
     * Les galeries qui montrent ces photos, par nom.
     *
     * @param  array<int, int>  $ids
     * @return iterable<object{photo_id: int, position: int, id: int, nom: string}>
     */
    private function galeries(string $pivot, string $table, string $cle, string $nom, array $ids): iterable
    {
        return DB::table($pivot)
            ->join($table, "{$table}.id", '=', "{$pivot}.{$cle}")
            ->whereIn("{$pivot}.photo_id", $ids)
            ->orderBy("{$table}.{$nom}")
            ->get(["{$pivot}.photo_id", "{$pivot}.position", "{$table}.id", "{$table}.{$nom} as nom"]);
    }
}
