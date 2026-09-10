# Architecture backend — Vayla

Cinq couches, une direction de dépendance : le haut connaît le bas, jamais l'inverse.

```
app/
│
├── Http/                        ← PRÉSENTATION — reçoit, délègue, retourne
│   ├── Controllers/
│   │   ├── Api/V1/                 ListingController, DestinationController,
│   │   │                           CategoryController, AmenityController,
│   │   │                           TrustLevelController
│   │   ├── HomeController.php      rend Inertia « Home/Index »
│   │   ├── ListingController.php   catalogue et fiche (« Listings/… »)
│   │   ├── DestinationController.php  atlas et page destination
│   │   ├── BookingController.php   réserver, et la référence
│   │   └── AiController.php
│   └── Requests/
│       └── ListingIndexRequest.php     validation partagée site + API
│
├── Data/                        ← TRANSFERT (spatie/laravel-data)
│   ├── ListingData.php             le contrat d'une annonce en liste
│   ├── ListingDetailData.php       la fiche : description + équipements groupés
│   ├── ListingFiltreData.php       les critères de recherche
│   ├── AmenityData.php             un équipement (+ highlight et note du pivot)
│   ├── AmenityGroupData.php        une rubrique et ce qu'elle contient
│   ├── DestinationData.php
│   ├── CategoryData.php
│   ├── PhotoData.php
│   └── TrustLevelData.php
│
├── Services/                    ← MÉTIER — logique et orchestration
│   ├── HomeService.php             assemble les props de l'accueil
│   ├── CatalogueService.php        assemble les props de /logements
│   ├── DestinationPageService.php  assemble les props de /destinations
│   ├── AvailabilityService.php     le calendrier d'un logement
│   ├── SeasonService.php           ce que vaut chaque mois, par façade
│   ├── ConfirmationService.php     les séjours confirmés, sans note
│   ├── BookingService.php          réservations, blocage, expiration
│   ├── BookingPageService.php      les props des écrans de réservation
│   ├── InvoiceService.php          la facture mensuelle du propriétaire
│   ├── ListingService.php
│   ├── DestinationService.php      tri de l'atlas, compteurs
│   ├── CategoryService.php
│   ├── AmenityService.php          vocabulaire groupé, et son sous-ensemble filtrable
│   ├── PhotoService.php            table indexée + liste des crédits
│   └── TrustLadderService.php
│
├── Repositories/                ← DONNÉES — le seul endroit qui parle Eloquent
│   ├── Contracts/                  une interface par dépôt
│   │   ├── ListingRepositoryInterface.php
│   │   ├── DestinationRepositoryInterface.php
│   │   ├── CategoryRepositoryInterface.php
│   │   ├── AmenityRepositoryInterface.php
│   │   └── PhotoRepositoryInterface.php
│   ├── ListingRepository.php
│   ├── DestinationRepository.php
│   ├── CategoryRepository.php
│   ├── AmenityRepository.php
│   └── PhotoRepository.php
│
├── Models/                      ← ELOQUENT — relations et casts, pas de logique
│   ├── Listing.php  Destination.php  Category.php  Amenity.php
│   ├── Unavailability.php  StayConfirmation.php
│   ├── Owner.php  Booking.php
│   ├── Photo.php  User.php
│
├── Enums/                       ← VALEURS MÉTIER
│   ├── TrustLevel.php              l'échelle à quatre niveaux
│   ├── AmenityGroup.php            les onze rubriques d'équipements
│   ├── PropertyType.php            villa, maison, appartement, studio…
│   ├── ListingSort.php             les tris du catalogue
│   ├── ClimateZone.php             les six façades climatiques
│   ├── SeasonKind.php              ce que vaut un mois
│   ├── ConfirmationPoint.php       ce qu'un voyageur confirme
│   ├── BookingStatus.php           le cycle de vie d'une réservation
│   └── ListingStatus.php
│
├── Exceptions/                  ← EXCEPTIONS MÉTIER
│   ├── ListingNotFoundException.php
│   └── DestinationNotFoundException.php
│
└── Providers/
    └── AppServiceProvider.php   ← binding Interface → implémentation
```

## Le flux d'une requête

```
HTTP
 └─ FormRequest        validation, autorisation, bornes
     └─ Controller     délègue, ne décide rien
         └─ Data       typage fort des critères entrants
             └─ Service        règles métier, tri, orchestration
                 └─ Repository (via son interface)
                     └─ Model (Eloquent)
                 ← Data        contrat de sortie
     ← Inertia::render(...)  ou  response()->json(...)
```

## Deux sorties, un seul métier

C'est ce que l'architecture achète, et la raison d'être du découpage :

```
                      ┌── HomeController ──→ Inertia ──→ Vue
ListingService ───────┤
                      └── Api\V1\ListingController ──→ JSON ──→ Flutter
```

Le site et l'application mobile **ne peuvent pas diverger** sur ce qu'est une
annonce ou sur son niveau de confiance : les deux lisent le même `ListingData`.

## Les règles qui tiennent le domaine

- **`TrustLevel` est un enum, pas une table.** Quatre niveaux figés sont une
  règle métier. Les rendre éditables permettrait qu'un niveau change de sens
  sous les annonces déjà vérifiées.
- **« Séjour confirmé » n'est jamais stocké** dans le pivot `category_listing` :
  il se déduit de `trust_level = 4`, dans `ListingData` et dans
  `ListingRepository`. Deux écritures pour un même fait, et l'échelle finit
  par mentir sur l'une des deux.
- **Ni `place` ni `region` sur `listings`** : ils se lisent sur la destination.
  `ListingData` les remet à plat pour le front. Une seule source de vérité.
- **Les compteurs de destination sont calculés**, jamais saisis. Aucun chiffre
  affiché sur la page ne doit pouvoir mentir.
- **`vayla.demo` tient deux choses ensemble** : les annonces fictives servies
  et le bandeau « Aperçu » qui le dit à l'écran. Les séparer rendrait possible
  d'afficher des annonces fictives sans le signaler.
- **Les équipements sont une table, leurs rubriques un enum.** La liste des
  équipements s'allongera au contact des logements réels : ajouter une ligne
  est une opération de donnée. Le sens d'une rubrique ne doit pas glisser sous
  les annonces déjà remplies, donc `AmenityGroup` est figé.
- **`perks` est dérivé du pivot**, plus jamais saisi : ce sont les libellés des
  équipements marqués `highlight`. L'ancien tableau JSON de texte libre ne se
  filtrait pas et permettait à une carte de vanter un équipement que la fiche
  ne détaillait pas.
- **`ListingDetailData` existe pour ne pas alourdir la liste.** Huit annonces
  × quarante équipements dans la grille d'accueil, c'est dix fois la charge
  utile pour des données que la carte n'affiche pas. La liste porte les trois
  équipements mis en avant, la fiche porte tout.
- **Le filtre par équipements est conjonctif** : un `whereHas` par clé. Cocher
  deux cases pose deux conditions, pas une alternative.
- **`vayla.demo` vaut aussi sur la fiche.** `findBySlug()` prend le drapeau :
  sans lui, une URL directe servirait encore une annonce fictive alors que le
  catalogue s'est vidé.
- **Les facettes du panneau de filtres ne sont jamais inventées** : les types
  proposés sont ceux réellement présents, les bornes de prix sont les vraies.
  Proposer « Lodge » sans aucun lodge donnerait un filtre qui ne ramène rien.
- **On facture le séjour effectué, jamais la réservation.** Seul
  `BookingStatus::Completed` est facturable, et il ne s'atteint que par une
  confirmation du voyageur. Facturer l'acceptation reviendrait à facturer les
  no-shows : le propriétaire refuserait de payer, et il aurait raison.
- **Les montants sont figés sur la réservation.** `price_per_night`, `total`
  et `commission_rate` y sont recopiés. Une facture qui change après coup
  parce que le tarif de l'annonce a bougé est une facture qu'on ne paie pas.
- **Le blocage de dates et son expiration vont ensemble.** Une demande retire
  ses nuits dès son dépôt — sinon deux voyageurs réservent la même semaine —
  et les rend au bout de `hold_hours`. L'un sans l'autre chasse les
  propriétaires.
- **Le contrôle de chevauchement est dans la transaction**, avec un verrou
  sur les réservations de l'annonce. Fait avant l'écriture, deux demandes
  simultanées le passeraient toutes les deux.
- **Aucun chiffre affiché n'est saisi.** Compteurs de destination, répartition
  par barreau, bornes de prix, meilleurs mois : tout est calculé depuis les
  annonces ou depuis un enum. C'est la seule garantie qu'une page ne puisse
  pas mentir sur ce qu'elle montre — et un test compare la somme des barreaux
  au compteur de la destination.
- **Le zéro se publie.** Cinq destinations sur onze n'ont aucune annonce : le
  service les sert avec `listings: 0` et des barreaux à zéro plutôt que de les
  masquer. Un atlas qui ne montrerait que les destinations pourvues serait
  flatteur et faux.
- **« Introuvable » sort en 404 des deux côtés** : JSON pour le client mobile,
  HTML pour le site. Une URL d'annonce périmée qui renverrait un 500 serait
  indexée comme une panne.

## Divergences assumées avec le document de référence (`pdp`)

- **Vocabulaire du domaine en anglais** (`Listing`, `Destination`, `TrustLevel`)
  quand `pdp` est en français (`Finop`, `Cible`) : le domaine de `pdp` *est*
  français, celui de Vayla ne l'est pas. Les commentaires, eux, restent en
  français comme partout dans le dépôt.
- **Pas de `list()` façon DataTables** : `pdp` est un back-office de tableaux,
  Vayla est une surface publique. La pagination passe par `LengthAwarePaginator`.

## La recherche par dates : la chaîne complète, couche par couche

Le moteur de la page d'accueil portait deux champs de dates qui étaient
**collectés puis jetés** — aucune couche ne les connaissait. Les brancher a
traversé les cinq couches, et c'est un bon exemple de ce que l'architecture
achète :

| Couche | Ce qu'elle ajoute |
|---|---|
| `Http/Requests/ListingIndexRequest` | valide et **borne** : les deux dates vont par paire (`required_with` croisé), l'arrivée n'est pas dans le passé, le départ est après l'arrivée, le séjour n'excède pas un an |
| `Data/SejourData` | l'objet-valeur qui porte **la règle de la nuit** : `derniereNuit()` = départ − 1 jour, `couvre()` teste le chevauchement |
| `Data/ListingFiltreData` | transporte le séjour, ou `null` |
| `Repositories/ListingRepository` | traduit en SQL : deux `whereDoesntHave` (périodes déclarées, réservations bloquantes non expirées) et les bornes `min_nights` / `max_nights` |
| `Services/CatalogueService` | renvoie l'écho des critères au front, dates comprises |

Le site et l'API traversent exactement le même chemin : `/logements?arrival=…`
et `/api/v1/listings?arrival=…` acceptent et refusent les mêmes valeurs. Un
couple invalide donne un 422 en JSON et une redirection vers `/logements` sur
le site.

**Pourquoi `SejourData` et pas une méthode privée dans le dépôt.** La
soustraction du jour de départ était déjà écrite deux fois —
`AvailabilityService::blocked()` et `BookingService::overlaps()`. Une troisième
écriture dans le dépôt aurait garanti qu'une des trois finisse par diverger, et
une divergence là-dessus ne se voit pas : elle retire ou ajoute une nuit
vendable en silence. `BookingService::overlaps()` a d'ailleurs été réécrit pour
passer par `SejourData::couvre()` plutôt que de parcourir les nuits une à une.

**Pourquoi les dates ne filtrent pas côté client.** Le navigateur n'a ni les
périodes déclarées ni les réservations en cours — et il ne doit pas les avoir :
publier le calendrier complet de chaque annonce dans la charge utile de la page
d'accueil serait à la fois lourd et indiscret. C'est exactement la frontière que
l'architecture trace : ce qui se déduit des props reste au client, ce qui
demande la base passe par le service.
