<?php

namespace App\Repositories;

use App\Contracts\Repositories\DestinationGalleryRepositoryInterface;
use App\Models\Destination;
use App\Models\Photo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DestinationGalleryRepository implements DestinationGalleryRepositoryInterface
{
    public function photos(Destination $destination): array
    {
        return $destination->galerie()->pluck('photos.id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function accrocherAuBout(Destination $destination, int $photoId): void
    {
        $destination->galerie()->attach($photoId, [
            'position' => (int) $destination->galerie()->max('destination_photo.position') + 1,
        ]);
    }

    public function ordonner(Destination $destination, array $ids): void
    {
        foreach (array_values($ids) as $position => $id) {
            $destination->galerie()->updateExistingPivot($id, ['position' => $position]);
        }
    }

    public function poserCouverture(Destination $destination, ?int $photoId): void
    {
        $destination->forceFill(['photo_id' => $photoId])->save();
    }

    public function galerie(Destination $destination): Collection
    {
        return $destination->galerie()->get();
    }

    public function contient(Destination $destination, int $photoId): bool
    {
        return $destination->galerie()->where('photos.id', $photoId)->exists();
    }

    public function detacher(Destination $destination, int $photoId): void
    {
        $destination->galerie()->detach($photoId);
    }

    public function estMontree(int $photoId): bool
    {
        return DB::table('destination_photo')->where('photo_id', $photoId)->exists();
    }

    public function photoDeLieu(int $photoId): ?Photo
    {
        return $this->lieux()->find($photoId);
    }

    public function photothequeDeLieux(array $exclues): Collection
    {
        return $this->lieux()
            ->whereNotIn('id', $exclues)
            ->orderByRaw("case when folder = 'destinations' then 0 else 1 end")
            ->orderByDesc('id')
            ->get();
    }

    public function destinationsParPhoto(): array
    {
        return DB::table('destination_photo')
            ->join('destinations', 'destinations.id', '=', 'destination_photo.destination_id')
            ->pluck('destinations.name', 'destination_photo.photo_id')
            ->all();
    }

    /** Commons ou l'équipe ; ni image générée, ni photo des annonces de démonstration. */
    private function lieux(): Builder
    {
        return Photo::query()
            ->whereIn('folder', ['lieux', 'destinations'])
            ->where('is_ai', false)
            ->where('key', 'not like', 'an-%');
    }
}
