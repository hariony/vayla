<?php

namespace App\Services\Office;

use App\Enums\AdminActionKind;
use App\Models\Admin;
use App\Models\Destination;
use App\Models\Photo;
use App\Services\PhotoUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * La photothèque : toutes les photographies du site, leurs crédits, et où
 * chacune apparaît.
 *
 * **Le crédit est ce qui se corrige ici**, parce que c'est lui qui s'affiche au
 * pied de chaque page — une légende fausse ou un auteur mal orthographié y
 * reste tant que personne ne peut les toucher. Jusqu'ici, une photo de
 * Commons ne se corrigeait que dans `PhotoSeeder`.
 *
 * Cinq provenances, qui n'ont pas les mêmes droits :
 *
 * | Provenance     | Légende | Auteur, source | Licence | Supprimer            |
 * |----------------|---------|----------------|---------|----------------------|
 * | Commons        | oui     | oui            | non     | jamais (versionnée)  |
 * | Équipe         | oui     | oui            | oui     | si elle ne sert pas  |
 * | Propriétaire   | oui     | —              | —       | depuis son annonce   |
 * | Démonstration  | non     | non            | non     | disparaît avec elle  |
 * | Image générée  | non     | non            | non     | disparaît avec elle  |
 *
 * **La licence d'une photo de Commons ne se change pas** : elle est celle que
 * l'auteur a choisie (souvent une version 2.0 ou 3.0 que la liste de l'équipe
 * ne porte pas), et la remplacer par un choix de liste la rendrait fausse.
 * **La légende d'une photo de propriétaire se corrige** : c'est le texte lu aux
 * malvoyants, et elle vaut souvent le titre de l'annonce, faute de mieux.
 */
class OfficePhotoLibraryService
{
    public const ONGLETS = [
        'lieux' => 'Photos de lieux',
        'televersees' => 'Téléversées',
        'inutilisees' => 'Inutilisées',
        'demonstration' => 'Démonstration',
        'proprietaires' => 'Propriétaires',
    ];

    private const PAR_PAGE = 48;

    public function __construct(
        private AdminJournal $journal,
        private PhotoUploadService $photos,
    ) {}

    // ── Lecture ─────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function liste(string $onglet, string $q, ?int $choisie = null): array
    {
        $onglet = array_key_exists($onglet, self::ONGLETS) ? $onglet : 'lieux';
        $motif = $q !== '' ? '%'.mb_strtolower($q).'%' : null;

        $page = $this->requete($onglet)
            ->when($motif, fn (Builder $b) => $b->where(fn (Builder $w) => $w
                ->whereRaw('lower(caption) like ?', [$motif])
                ->orWhereRaw('lower(author) like ?', [$motif])
                ->orWhereRaw('lower(key) like ?', [$motif])))
            // Les téléversées d'abord, les plus récentes en tête : ce sont
            // celles qu'on vient de poser et qu'on veut relire.
            ->orderByRaw("case when folder = 'destinations' then 0 else 1 end")
            ->orderByDesc('id')
            ->paginate(self::PAR_PAGE)
            ->withQueryString();

        $items = collect($page->items());
        $usages = $this->usages($items->pluck('id')->all());

        // Une photo demandée par son adresse (depuis le journal, depuis une
        // destination) s'ouvre même si elle n'est pas sur la page affichée.
        $ouverte = $choisie ? Photo::query()->find($choisie) : null;

        return [
            'photos' => $items->map(fn (Photo $p) => $this->fiche($p, $usages[$p->id] ?? []))->values()->all(),
            'ouverte' => $ouverte ? $this->fiche($ouverte, $this->usages([$ouverte->id])[$ouverte->id] ?? []) : null,
            'meta' => [
                'page' => $page->currentPage(),
                'pages' => $page->lastPage(),
                'total' => $page->total(),
                'precedente' => $page->previousPageUrl(),
                'suivante' => $page->nextPageUrl(),
            ],
            'onglets' => collect(self::ONGLETS)
                ->map(fn (string $label, string $cle) => ['cle' => $cle, 'label' => $label, 'nombre' => $this->requete($cle)->count()])
                ->values()->all(),
            'filtre' => ['onglet' => $onglet, 'q' => $q],
            'disque' => $this->disque(),
            'licences' => collect(OfficeContentService::LICENCES)
                ->map(fn (array $l, string $cle) => ['value' => $cle, 'label' => $l['label']])->values()->all(),
            'destinations' => Destination::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Destination $d) => ['value' => $d->id, 'label' => $d->name])->all(),
        ];
    }

    /**
     * Une photo, mise à plat pour l'écran : son image, son crédit, où elle
     * apparaît, ce qu'elle pèse, et ce qu'on a le droit d'en faire.
     *
     * @param  array<int, array<string, string>>  $usages
     * @return array<string, mixed>
     */
    public function fiche(Photo $p, array $usages): array
    {
        $provenance = $this->provenance($p);
        [$poids, $paliers] = $this->fichiers($p);

        return [
            'id' => $p->id,
            'key' => $p->key,
            'folder' => $p->folder,
            'width' => $p->width,
            'caption' => $p->caption,
            'author' => $p->author,
            'licence' => $p->licence,
            'licence_url' => $p->licence_url,
            'licenceCle' => collect(OfficeContentService::LICENCES)->search(fn (array $l) => $l['label'] === $p->licence) ?: null,
            'source_url' => $p->source_url,
            'provenance' => $provenance,
            'provenanceLabel' => [
                'commons' => 'Wikimedia Commons', 'equipe' => 'Téléversée par l’équipe', 'proprietaire' => 'Photo de propriétaire',
                'demonstration' => 'Annonce de démonstration', 'generee' => 'Image générée',
            ][$provenance],
            'usages' => $usages,
            'poids' => $poids,
            'paliers' => $paliers,
            'ajoutee' => $p->created_at?->toIso8601String(),
            'peut' => [
                'legende' => in_array($provenance, ['commons', 'equipe', 'proprietaire'], true),
                'credit' => in_array($provenance, ['commons', 'equipe'], true),
                'licence' => $provenance === 'equipe',
                'supprimer' => $provenance === 'equipe' && $usages === [],
            ],
            'pourquoiPasSupprimer' => $this->pourquoiPasSupprimer($provenance, $usages),
        ];
    }

    // ── Écriture ────────────────────────────────────────────────────────

    /**
     * Téléverse une photo de lieu. **Dans `images/destinations/`, jamais dans
     * `lieux/`** : ce dernier appartient à `PhotoSeeder` et au catalogue
     * Commons, que `PhotoFilesTest` compare au disque fichier par fichier.
     *
     * Une photo qui n'illustre encore rien n'est **pas créditée au pied de
     * page** (`PhotoRepository::credited`) : on ne crédite pas une image qu'on
     * ne publie pas.
     *
     * @param  array{caption: string, author: string, licence: string, source_url?: ?string}  $credit
     */
    public function televerser(Admin $admin, UploadedFile $fichier, array $credit, ?string $prefixe = null, bool $consigner = true): Photo
    {
        $prefixe = Str::slug($prefixe ?: Str::limit($credit['caption'], 32, '')) ?: 'photo';
        $cle = $prefixe.'-'.Str::lower(Str::random(8));

        try {
            $largeur = $this->photos->produire($fichier, 'destinations', $cle);
        } catch (RuntimeException $e) {
            throw new OfficeRefusal($e->getMessage());
        }

        $licence = OfficeContentService::LICENCES[$credit['licence']];

        $photo = Photo::create([
            'key' => $cle,
            'folder' => 'destinations',
            'width' => $largeur,
            'is_ai' => false,
            'caption' => trim($credit['caption']),
            'author' => trim($credit['author']),
            'licence' => $licence['label'],
            'licence_url' => $licence['url'],
            'source_url' => ($credit['source_url'] ?? null) ?: null,
        ]);

        if ($consigner) {
            $this->journal->consigner($admin, AdminActionKind::PhotoUploaded, $photo,
                "Photo « {$photo->caption} » ajoutée à la photothèque, de {$photo->author} ({$photo->licence}).");
        }

        return $photo;
    }

    /**
     * Corrige la légende et le crédit — **seulement ce que la provenance
     * permet** (voir le tableau en tête de classe). Un champ qu'on n'a pas le
     * droit de toucher est ignoré, pas refusé : le formulaire ne le montre
     * même pas.
     *
     * @param  array<string, ?string>  $donnees
     */
    public function modifier(Admin $admin, Photo $photo, array $donnees): void
    {
        $peut = $this->fiche($photo, [])['peut'];

        if (! $peut['legende']) {
            throw new OfficeRefusal('Cette photo illustre les annonces de démonstration : elle disparaîtra avec elles, son crédit ne se corrige pas.');
        }

        $champs = ['caption' => trim((string) ($donnees['caption'] ?? $photo->caption))];

        if ($peut['credit']) {
            if (array_key_exists('author', $donnees)) {
                $auteur = trim((string) $donnees['author']);
                if ($auteur === '') {
                    throw new OfficeRefusal('Le nom de l’auteur ne peut pas être vide : il est crédité au pied de chaque page.');
                }
                $champs['author'] = $auteur;
            }
            if (array_key_exists('source_url', $donnees)) {
                $champs['source_url'] = trim((string) $donnees['source_url']) ?: null;
            }
        }

        if ($peut['licence'] && ! empty($donnees['licence'])) {
            $licence = OfficeContentService::LICENCES[$donnees['licence']];
            $champs['licence'] = $licence['label'];
            $champs['licence_url'] = $licence['url'];
        }

        $photo->fill($champs);
        $changes = array_keys($photo->getDirty());

        if ($changes === []) {
            return;
        }

        $photo->save();

        $mots = ['caption' => 'légende', 'author' => 'auteur', 'source_url' => 'page d’origine', 'licence' => 'licence'];
        $liste = collect($changes)->map(fn (string $c) => $mots[$c] ?? null)->filter()->unique()->implode(', ');

        $this->journal->consigner($admin, AdminActionKind::PhotoEdited, $photo, "Photo « {$photo->caption} » : {$liste} corrigé".(count($changes) > 1 ? 's' : '').'.');
    }

    /**
     * Supprime une photo téléversée **qui n'illustre plus rien**, fichiers
     * compris. Tout le reste a une raison de rester, et le refus la dit.
     */
    public function supprimer(Admin $admin, Photo $photo): void
    {
        $usages = $this->usages([$photo->id])[$photo->id] ?? [];

        if ($raison = $this->pourquoiPasSupprimer($this->provenance($photo), $usages)) {
            throw new OfficeRefusal($raison);
        }

        $this->photos->effacerFichiers($photo);
        $this->journal->consigner($admin, AdminActionKind::PhotoDeleted, null, "Photo « {$photo->caption} » supprimée de la photothèque, fichiers compris.");
        $photo->delete();
    }

    // ── Rouages ─────────────────────────────────────────────────────────

    private function requete(string $onglet): Builder
    {
        $demo = fn (Builder $b) => $b->where(fn (Builder $w) => $w
            ->where('is_ai', true)->orWhere('key', 'like', 'an-%')->orWhere('key', 'like', 'ia-%'));
        $vraie = fn (Builder $b) => $b->where('is_ai', false)
            ->where('key', 'not like', 'an-%')->where('key', 'not like', 'ia-%');

        return match ($onglet) {
            'televersees' => Photo::query()->where('folder', 'destinations'),
            'inutilisees' => $vraie(Photo::query()->whereIn('folder', ['lieux', 'destinations']))
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('destination_photo')->whereColumn('destination_photo.photo_id', 'photos.id'))
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('listing_photo')->whereColumn('listing_photo.photo_id', 'photos.id')),
            'demonstration' => $demo(Photo::query()->where('folder', 'lieux')),
            'proprietaires' => Photo::query()->where('folder', 'annonces'),
            default => $vraie(Photo::query()->whereIn('folder', ['lieux', 'destinations'])),
        };
    }

    /**
     * Où chaque photo apparaît : galeries de destinations et d'annonces.
     *
     * @param  array<int, int>  $ids
     * @return array<int, array<int, array<string, string>>>
     */
    private function usages(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $usages = [];

        DB::table('destination_photo')
            ->join('destinations', 'destinations.id', '=', 'destination_photo.destination_id')
            ->whereIn('destination_photo.photo_id', $ids)
            ->orderBy('destinations.name')
            ->get(['destination_photo.photo_id', 'destination_photo.position', 'destinations.id', 'destinations.name'])
            ->each(function ($l) use (&$usages) {
                $usages[$l->photo_id][] = [
                    'type' => 'destination',
                    'label' => $l->name.((int) $l->position === 0 ? ' — couverture' : ''),
                    'href' => "/destinations/{$l->id}",
                ];
            });

        DB::table('listing_photo')
            ->join('listings', 'listings.id', '=', 'listing_photo.listing_id')
            ->whereIn('listing_photo.photo_id', $ids)
            ->orderBy('listings.title')
            ->get(['listing_photo.photo_id', 'listing_photo.position', 'listings.id', 'listings.title'])
            ->each(function ($l) use (&$usages) {
                $usages[$l->photo_id][] = [
                    'type' => 'annonce',
                    'label' => $l->title.((int) $l->position === 0 ? ' — couverture' : ''),
                    'href' => "/annonces/{$l->id}",
                ];
            });

        return $usages;
    }

    private function provenance(Photo $p): string
    {
        return match (true) {
            $p->folder === 'annonces' => 'proprietaire',
            $p->folder === 'destinations' => 'equipe',
            (bool) $p->is_ai || str_starts_with($p->key, 'ia-') => 'generee',
            str_starts_with($p->key, 'an-') => 'demonstration',
            default => 'commons',
        };
    }

    /** @param  array<int, array<string, string>>  $usages */
    private function pourquoiPasSupprimer(string $provenance, array $usages): ?string
    {
        return match (true) {
            $provenance === 'commons' => 'Les photographies de Wikimedia Commons ne se suppriment pas d’ici : elles sont versionnées avec leur crédit, dans le catalogue du site.',
            $provenance === 'proprietaire' => 'Cette photo appartient à l’annonce d’un propriétaire : elle se retire depuis l’annonce.',
            in_array($provenance, ['demonstration', 'generee'], true) => 'Elle illustre les annonces de démonstration, et disparaîtra avec elles.',
            $usages !== [] => 'Elle illustre encore '.collect($usages)->pluck('label')->map(fn ($l) => '« '.Str::before($l, ' — ').' »')->unique()->implode(', ').' : retirez-la de la galerie d’abord.',
            default => null,
        };
    }

    /**
     * Ce que la photo pèse sur le disque, tous paliers, et lesquels existent.
     *
     * @return array{0: int, 1: array<int, int>}
     */
    private function fichiers(Photo $p): array
    {
        $poids = 0;
        $paliers = [];

        foreach ([800, 1600, 3200] as $largeur) {
            $chemin = $this->photos->dossier($p->folder)."/{$p->key}-{$largeur}.webp";

            if (is_file($chemin)) {
                $poids += (int) filesize($chemin);
                $paliers[] = $largeur;
            }
        }

        return [$poids, $paliers];
    }

    /**
     * Le poids de toute la photothèque, par provenance : de quoi voir ce que
     * coûte le stockage, et d'où il vient.
     *
     * @return array<string, int>
     */
    private function disque(): array
    {
        $total = 0;
        $parDossier = [];

        foreach (['lieux', 'destinations', 'annonces'] as $dossier) {
            $octets = 0;
            $racine = $this->photos->dossier($dossier);

            if (is_dir($racine)) {
                $fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine, \FilesystemIterator::SKIP_DOTS));
                foreach ($fichiers as $f) {
                    if ($f->isFile() && $f->getExtension() === 'webp') {
                        $octets += $f->getSize();
                    }
                }
            }

            $parDossier[$dossier] = $octets;
            $total += $octets;
        }

        return ['total' => $total, ...$parDossier];
    }
}
