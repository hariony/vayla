<?php

namespace App\Services\Office;

use App\Enums\AdminActionKind;
use App\Models\Admin;
use App\Models\Amenity;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Listing;
use App\Models\Owner;
use App\Models\Photo;
use App\Services\OwnerListingService;
use App\Services\PhotoUploadService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * La gestion de contenu du back-office : le texte et les photos des annonces,
 * et les référentiels qui les portent — destinations, catégories,
 * équipements, réglages.
 *
 * **Les règles du produit tiennent ici aussi.** Trois invariants traversent
 * tout le fichier :
 *
 * - **Une clé publique ne bouge jamais** : le slug d'une annonce ou d'une
 *   destination est son adresse publique ; la clé d'une catégorie ou d'un
 *   équipement est un filtre d'URL et un mot de l'API mobile. On renomme le
 *   libellé, jamais la clé — un lien partagé qui casse est un voyageur perdu,
 *   et une version installée de l'application qui ne reconnaît plus un filtre
 *   ne le dit à personne.
 * - **On ne supprime pas ce qui porte une déclaration.** Un équipement coché
 *   par des propriétaires, une destination qui a des logements : les effacer
 *   ferait disparaître en silence ce qu'un propriétaire a déclaré.
 * - **Ce qui se déduit ne se saisit pas.** La catégorie « verifie » vient du
 *   niveau 4 ; `trust_level` et `status` n'entrent par aucun formulaire de
 *   contenu — ils ont leurs gestes à eux, dans `ModerationService`.
 *
 * Chaque geste laisse une ligne au journal, avec la liste de ce qui a changé.
 */
class OfficeContentService
{
    /** La catégorie qui se déduit du niveau 4 : elle ne s'attribue ni ne se supprime. */
    public const CATEGORIE_DEDUITE = 'verifie';

    /**
     * Les catégories qui ne sont pas des étiquettes : « Tout » (le rail sans
     * filtre) et « Séjour confirmé » (le niveau 4). Elles se renomment et se
     * déplacent, mais ne se posent sur aucune annonce et ne se suppriment pas.
     */
    public const CATEGORIES_STRUCTURELLES = ['all', self::CATEGORIE_DEDUITE];

    /**
     * Les licences possibles d'une photo de destination, et la page qui les
     * définit. « Tous droits réservés » n'a pas de page : c'est une photo de
     * l'équipe ou d'un photographe qui l'a cédée.
     */
    public const LICENCES = [
        'vayla' => ['label' => 'Photo Vayla — tous droits réservés', 'url' => null],
        'accord' => ['label' => 'Publiée avec l’accord de l’auteur', 'url' => null],
        'cc-by' => ['label' => 'CC BY 4.0', 'url' => 'https://creativecommons.org/licenses/by/4.0/deed.fr'],
        'cc-by-sa' => ['label' => 'CC BY-SA 4.0', 'url' => 'https://creativecommons.org/licenses/by-sa/4.0/deed.fr'],
        'domaine-public' => ['label' => 'Domaine public', 'url' => null],
    ];

    /** Les libellés des champs d'une annonce, pour dire au journal ce qui a changé. */
    private const CHAMPS = [
        'title' => 'titre', 'destination_id' => 'destination', 'kind' => 'type', 'summary' => 'accroche',
        'description' => 'description', 'guests' => 'capacité', 'bedrooms' => 'chambres', 'beds' => 'couchages',
        'bathrooms' => 'salles d’eau', 'surface' => 'surface', 'price' => 'tarif', 'min_nights' => 'nuits minimum',
        'max_nights' => 'nuits maximum', 'check_in_from' => 'heure d’arrivée', 'check_out_before' => 'heure de départ',
        'pets_allowed' => 'animaux', 'smoking_allowed' => 'fumeurs', 'events_allowed' => 'fêtes', 'featured' => 'mise en avant',
    ];

    public function __construct(
        private AdminJournal $journal,
        private OwnerListingService $annonces,
        private PhotoUploadService $photos,
        private SettingsService $reglages,
        private OfficePhotoLibraryService $phototheque,
    ) {}

    // ── Annonces ────────────────────────────────────────────────────────

    /**
     * Le contenu d'une annonce, **tout le contenu** — y compris ce que le
     * propriétaire ne peut plus toucher après vérification. C'est justement le
     * rôle de Vayla : corriger une capacité mal saisie à l'appel, remplacer une
     * photo floue. Le journal le garde, champ par champ.
     *
     * @return array<int, string> ce qui a changé, en mots
     */
    public function modifierAnnonce(Admin $admin, Listing $listing, array $fiche, array $equipements, array $categories): array
    {
        $changes = DB::transaction(function () use ($listing, $fiche, $equipements, $categories) {
            $listing->fill($fiche)->save();
            $champs = array_keys(array_diff_key($listing->getChanges(), ['updated_at' => true]));

            $avant = $this->empreinteEquipements($listing);
            $this->annonces->poserEquipements($listing, $equipements);
            if ($avant !== $this->empreinteEquipements($listing->refresh())) {
                $champs[] = 'equipements';
            }

            if ($this->poserCategories($listing, $categories)) {
                $champs[] = 'categories';
            }

            return $champs;
        });

        $mots = array_map(fn (string $c) => self::CHAMPS[$c] ?? ($c === 'equipements' ? 'équipements' : 'catégories'), $changes);

        if ($mots !== []) {
            $this->journal->consigner($admin, AdminActionKind::ListingEdited, $listing,
                "« {$listing->title} » : ".implode(', ', $mots).'.');
        }

        return $mots;
    }

    /**
     * Une annonce saisie **pour** un propriétaire — celui qui la dicte au
     * téléphone faute de pouvoir la remplir. Elle naît comme les autres : en
     * brouillon, au niveau 1. La saisir n'est pas la vérifier.
     */
    public function creerAnnonce(Admin $admin, Owner $owner, array $fiche, array $equipements, array $categories): Listing
    {
        $listing = DB::transaction(function () use ($owner, $fiche, $equipements, $categories) {
            $listing = $this->annonces->creer($owner, $fiche);
            $this->annonces->poserEquipements($listing, $equipements);
            $this->poserCategories($listing, $categories);

            return $listing;
        });

        $this->journal->consigner($admin, AdminActionKind::ListingCreated, $listing,
            "« {$listing->title} » saisie pour {$owner->name}, en brouillon.");

        return $listing;
    }

    public function ajouterPhoto(Admin $admin, Listing $listing, UploadedFile $fichier, ?string $legende): void
    {
        try {
            $this->photos->ajouter($listing, $fichier, $legende);
        } catch (RuntimeException $e) {
            throw new OfficeRefusal($e->getMessage());
        }

        $this->journal->consigner($admin, AdminActionKind::ListingEdited, $listing, "« {$listing->title} » : photo ajoutée.");
    }

    public function retirerPhoto(Admin $admin, Listing $listing, int $photoId): void
    {
        // Une photo qui n'est pas dans cette galerie ne se retire pas d'ici :
        // l'identifiant vient du navigateur.
        $photo = $listing->photos()->where('photos.id', $photoId)->first() ?? throw new OfficeRefusal('Cette photo n’est pas dans la galerie de l’annonce.');

        $this->photos->retirer($listing, Photo::findOrFail($photo->id));

        $this->journal->consigner($admin, AdminActionKind::ListingEdited, $listing, "« {$listing->title} » : photo retirée.");
    }

    public function ordonnerPhotos(Admin $admin, Listing $listing, array $ids): void
    {
        $this->photos->reordonner($listing->load('photos'), $ids);

        $this->journal->consigner($admin, AdminActionKind::ListingEdited, $listing,
            "« {$listing->title} » : ordre des photos, nouvelle couverture possible.");
    }

    /** Pose les catégories éditoriales ; « verifie » se déduit et n'est jamais posée. @return bool changé */
    private function poserCategories(Listing $listing, array $ids): bool
    {
        $valides = Category::query()
            ->whereIn('id', array_map('intval', $ids))
            ->whereNotIn('key', self::CATEGORIES_STRUCTURELLES)
            ->pluck('id')
            ->all();

        $resultat = $listing->categories()->sync($valides);

        return $resultat['attached'] !== [] || $resultat['detached'] !== [];
    }

    private function empreinteEquipements(Listing $listing): string
    {
        return $listing->amenities()->get()
            ->map(fn (Amenity $a) => $a->id.':'.(int) $a->pivot->highlight)
            ->sort()->implode(',');
    }

    // ── Destinations ────────────────────────────────────────────────────

    /**
     * Crée ou modifie une destination. **Le slug naît du nom et ne bouge
     * plus** : c'est l'adresse de sa page et le filtre du catalogue.
     */
    public function enregistrerDestination(Admin $admin, ?Destination $destination, array $donnees): Destination
    {
        $creation = $destination === null;
        $destination ??= new Destination(['slug' => $this->slugUnique(Destination::class, 'slug', $donnees['name'])]);

        // La photo ne s'écrit pas ici : elle vient de la galerie, dont
        // `synchroniserCouverture()` est le seul écrivain.
        unset($donnees['slug'], $donnees['photo_id']);
        $destination->fill($donnees)->save();

        $this->journal->consigner($admin, AdminActionKind::DestinationSaved, $destination,
            $creation ? "Destination « {$destination->name} » créée." : "Destination « {$destination->name} » modifiée.");

        return $destination;
    }

    /**
     * Téléverse une photo et l'ajoute **au bout** de la galerie — elle devient
     * la couverture si la galerie était vide.
     *
     * **Dans `images/destinations/`, jamais dans `lieux/`** : ce dernier
     * appartient à `PhotoSeeder` et au catalogue Commons, que `PhotoFilesTest`
     * compare au disque fichier par fichier. Même traitement que les photos
     * d'annonce — 4/3, 800 à 3200 px, jamais d'agrandissement, refus sous
     * 1 200 px : une destination floue sur l'atlas ne donne envie de rien.
     */
    public function ajouterPhotoDestination(Admin $admin, Destination $destination, UploadedFile $fichier, array $credit): Photo
    {
        // Le même téléversement que depuis la photothèque — un seul endroit
        // qui produit, crédite et range la photo —, puis au bout de la galerie.
        $photo = $this->phototheque->televerser($admin, $fichier, $credit, $destination->slug, consigner: false);

        $this->accrocher($destination, $photo);

        $this->journal->consigner($admin, AdminActionKind::DestinationSaved, $destination,
            "« {$destination->name} » : photo ajoutée, de {$photo->author} ({$photo->licence}).");

        return $photo;
    }

    /**
     * Ajoute à la galerie une photo de la photothèque. **Seulement une vraie
     * photographie de lieu** — Commons ou téléversée par l'équipe, jamais une
     * image générée ni une photo d'annonce : une destination est un lieu réel.
     */
    public function ajouterDeLaPhototheque(Admin $admin, Destination $destination, int $photoId): void
    {
        $photo = Photo::query()
            ->whereIn('folder', ['lieux', 'destinations'])
            ->where('is_ai', false)
            ->where('key', 'not like', 'an-%')
            ->find($photoId) ?? throw new OfficeRefusal('Cette photo ne peut pas illustrer une destination : seulement de vraies photographies de lieux, créditées.');

        if ($destination->galerie()->where('photos.id', $photo->id)->exists()) {
            throw new OfficeRefusal('Cette photo est déjà dans la galerie.');
        }

        $this->accrocher($destination, $photo);

        $this->journal->consigner($admin, AdminActionKind::DestinationSaved, $destination,
            "« {$destination->name} » : « {$photo->caption} » ajoutée à la galerie.");
    }

    /**
     * Range la galerie. **La première photo est la couverture** — celle de
     * l'atlas et de l'en-tête — et il n'y a pas d'autre bouton pour la
     * désigner : la même règle que les annonces.
     *
     * @param  array<int, int>  $ids
     */
    public function ordonnerPhotosDestination(Admin $admin, Destination $destination, array $ids): void
    {
        $siennes = $destination->galerie()->pluck('photos.id')->all();
        $avant = $destination->photo_id;

        DB::transaction(function () use ($destination, $ids, $siennes) {
            // Un identifiant venu d'ailleurs ne doit pas entrer dans la
            // galerie par la porte du rangement.
            $ordre = array_values(array_filter(array_map('intval', $ids), fn (int $id) => in_array($id, $siennes, true)));
            $oublies = array_diff($siennes, $ordre);

            foreach ([...$ordre, ...$oublies] as $position => $id) {
                $destination->galerie()->updateExistingPivot($id, ['position' => $position]);
            }

            $this->synchroniserCouverture($destination);
        });

        $couverture = $destination->fresh()->photo_id !== $avant ? ' — nouvelle couverture' : '';
        $this->journal->consigner($admin, AdminActionKind::DestinationSaved, $destination,
            "« {$destination->name} » : galerie rangée{$couverture}.");
    }

    /**
     * Retire une photo de la galerie. Une photo **téléversée** que plus aucune
     * destination ne montre est effacée avec ses fichiers : une image qui
     * traîne serait encore créditée au pied de page. Une photographie de
     * Commons est seulement détachée — elle appartient au catalogue versionné.
     */
    public function retirerDeLaGalerie(Admin $admin, Destination $destination, int $photoId): void
    {
        $photo = $destination->galerie()->where('photos.id', $photoId)->first()
            ?? throw new OfficeRefusal('Cette photo n’est pas dans la galerie de la destination.');

        DB::transaction(function () use ($destination, $photo) {
            $destination->galerie()->detach($photo->id);
            $this->renumeroter($destination);
            $this->synchroniserCouverture($destination);
        });

        $effacee = false;
        if ($photo->folder === 'destinations' && ! DB::table('destination_photo')->where('photo_id', $photo->id)->exists()) {
            $this->photos->effacerFichiers($photo);
            Photo::query()->whereKey($photo->id)->delete();
            $effacee = true;
        }

        $this->journal->consigner($admin, AdminActionKind::DestinationSaved, $destination,
            "« {$destination->name} » : « {$photo->caption} » retirée de la galerie".($effacee ? ', fichiers effacés.' : '.'));
    }

    /**
     * **Le seul écrivain de `destinations.photo_id`** : il la recopie depuis
     * la position 0 de la galerie. L'atlas, l'accueil et l'API lisent cette
     * colonne ; deux écrivains, et la couverture de l'atlas finirait par ne
     * plus être la première photo de la page.
     */
    public function synchroniserCouverture(Destination $destination): void
    {
        $destination->forceFill(['photo_id' => $destination->galerie()->value('photos.id')])->save();
    }

    private function accrocher(Destination $destination, Photo $photo): void
    {
        DB::transaction(function () use ($destination, $photo) {
            $destination->galerie()->attach($photo->id, [
                'position' => (int) DB::table('destination_photo')->where('destination_id', $destination->id)->max('position') + 1,
            ]);
            $this->renumeroter($destination);
            $this->synchroniserCouverture($destination);
        });
    }

    /** Des positions sans trou, de 0 à n − 1. */
    private function renumeroter(Destination $destination): void
    {
        $destination->galerie()->pluck('photos.id')->values()
            ->each(fn (int $id, int $position) => $destination->galerie()->updateExistingPivot($id, ['position' => $position]));
    }

    public function supprimerDestination(Admin $admin, Destination $destination): void
    {
        $n = $destination->listings()->count();

        if ($n > 0) {
            throw new OfficeRefusal("« {$destination->name} » porte {$n} logement".($n > 1 ? 's' : '').' : la supprimer les laisserait sans lieu. Rattachez-les ailleurs d’abord.');
        }

        $this->journal->consigner($admin, AdminActionKind::DestinationDeleted, $destination, "Destination « {$destination->name} » supprimée.");
        $destination->delete();
    }

    // ── Catégories ──────────────────────────────────────────────────────

    public function enregistrerCategorie(Admin $admin, ?Category $categorie, array $donnees): Category
    {
        $creation = $categorie === null;

        if ($categorie?->key === self::CATEGORIE_DEDUITE && ($donnees['sponsored'] ?? false)) {
            throw new OfficeRefusal('« Séjour confirmé » se déduit du niveau 4 : cette place ne se vend pas.');
        }

        $categorie ??= new Category([
            'key' => $this->slugUnique(Category::class, 'key', $donnees['label']),
            'position' => (int) Category::query()->max('position') + 1,
        ]);

        unset($donnees['key']);
        $categorie->fill($donnees)->save();

        $this->journal->consigner($admin, AdminActionKind::CategorySaved, $categorie,
            ($creation ? "Catégorie « {$categorie->label} » créée" : "Catégorie « {$categorie->label} » modifiée")
            .($categorie->sponsored ? ' — mise en avant payée, signalée sur le site.' : '.'));

        return $categorie;
    }

    public function deplacerCategorie(Admin $admin, Category $categorie, string $sens): void
    {
        $this->deplacer(Category::query()->orderBy('position')->orderBy('id')->get(), $categorie, $sens);

        $this->journal->consigner($admin, AdminActionKind::CategorySaved, $categorie, "Catégorie « {$categorie->label} » déplacée dans le rail.");
    }

    public function supprimerCategorie(Admin $admin, Category $categorie): void
    {
        if (in_array($categorie->key, self::CATEGORIES_STRUCTURELLES, true)) {
            throw new OfficeRefusal("« {$categorie->label} » n’est pas une étiquette mais un filtre du rail : elle se renomme, elle ne se supprime pas.");
        }

        $n = $categorie->listings()->count();
        $this->journal->consigner($admin, AdminActionKind::CategoryDeleted, $categorie,
            "Catégorie « {$categorie->label} » supprimée".($n ? " — {$n} logement".($n > 1 ? 's' : '').' ne la portent plus.' : '.'));
        $categorie->delete();
    }

    // ── Équipements ─────────────────────────────────────────────────────

    public function enregistrerEquipement(Admin $admin, ?Amenity $equipement, array $donnees): Amenity
    {
        $creation = $equipement === null;

        $equipement ??= new Amenity([
            'key' => $this->slugUnique(Amenity::class, 'key', $donnees['label']),
            'position' => (int) Amenity::query()->where('group', $donnees['group'])->max('position') + 1,
        ]);

        unset($donnees['key']);
        $equipement->fill($donnees)->save();

        $this->journal->consigner($admin, AdminActionKind::AmenitySaved, $equipement,
            ($creation ? 'Équipement' : 'Équipement modifié :')." « {$equipement->label} » ({$equipement->group->label()})".($creation ? ' ajouté.' : '.'));

        return $equipement;
    }

    public function deplacerEquipement(Admin $admin, Amenity $equipement, string $sens): void
    {
        $this->deplacer(
            Amenity::query()->where('group', $equipement->group->value)->orderBy('position')->orderBy('id')->get(),
            $equipement,
            $sens,
        );

        $this->journal->consigner($admin, AdminActionKind::AmenitySaved, $equipement, "Équipement « {$equipement->label} » déplacé dans sa rubrique.");
    }

    /**
     * **Un équipement coché ne se supprime pas** : ce serait retirer en silence
     * une déclaration de propriétaires — le groupe électrogène disparaîtrait
     * de fiches qui l'annonçaient, et le panneau énergie changerait d'avis sans
     * que personne ait rien touché.
     */
    public function supprimerEquipement(Admin $admin, Amenity $equipement): void
    {
        $n = $equipement->listings()->count();

        if ($n > 0) {
            throw new OfficeRefusal("« {$equipement->label} » est déclaré par {$n} logement".($n > 1 ? 's' : '').' : le supprimer effacerait leur déclaration. Renommez-le plutôt.');
        }

        $this->journal->consigner($admin, AdminActionKind::AmenityDeleted, $equipement, "Équipement « {$equipement->label} » supprimé.");
        $equipement->delete();
    }

    // ── Réglages ────────────────────────────────────────────────────────

    /** Le taux porte sa date, **saisie avec lui** — jamais déduite de `now()`. */
    public function changerTauxEuro(Admin $admin, float $taux, string $releveLe): void
    {
        $avant = $this->reglages->tauxEuro();

        $this->reglages->ecrire(SettingsService::TAUX_EURO, (string) $taux, $admin->id);
        $this->reglages->ecrire(SettingsService::TAUX_EURO_DATE, $releveLe, $admin->id);

        $this->journal->consigner($admin, AdminActionKind::SettingChanged, null,
            'Taux de change : 1 € = '.$this->nombre($avant).' Ar → '.$this->nombre($taux)." Ar, relevé le {$releveLe}.");
    }

    /**
     * **Le nouveau taux ne vaut que pour les nouvelles demandes.** Chaque
     * réservation fige le sien : une facture qui change après coup est une
     * facture qu'on ne paie pas.
     */
    public function changerCommission(Admin $admin, float $pourcent): void
    {
        $avant = $this->reglages->commission() * 100;

        $this->reglages->ecrire(SettingsService::COMMISSION, (string) round($pourcent / 100, 4), $admin->id);

        $this->journal->consigner($admin, AdminActionKind::SettingChanged, null,
            'Commission : '.rtrim(rtrim(number_format($avant, 1, ',', ''), '0'), ',').' % → '
            .rtrim(rtrim(number_format($pourcent, 1, ',', ''), '0'), ',').' % pour les nouvelles demandes.');
    }

    // ── Pièces communes ─────────────────────────────────────────────────

    /** Échange la position d'une ligne avec sa voisine, en renumérotant d'abord. */
    private function deplacer($lignes, $cible, string $sens): void
    {
        $liste = $lignes->values();
        $i = $liste->search(fn ($l) => $l->is($cible));
        $j = $sens === 'haut' ? $i - 1 : $i + 1;

        if ($i === false || $j < 0 || $j >= $liste->count()) {
            return;
        }

        $ordre = $liste->all();
        [$ordre[$i], $ordre[$j]] = [$ordre[$j], $ordre[$i]];

        DB::transaction(function () use ($ordre) {
            foreach ($ordre as $position => $ligne) {
                $ligne->forceFill(['position' => $position])->save();
            }
        });
    }

    private function slugUnique(string $modele, string $colonne, string $texte): string
    {
        $base = Str::slug($texte) ?: 'element';
        $slug = $base;
        $n = 2;

        while ($modele::query()->where($colonne, $slug)->exists()) {
            $slug = "{$base}-{$n}";
            $n++;
        }

        return $slug;
    }

    private function nombre(float $n): string
    {
        return number_format($n, 0, ',', "\u{00A0}");
    }
}
