# Architecture front — Vayla

```
resources/js/
├── Services/                    ← appels API centralisés
│   ├── api.js                      client fetch, préfixe /api/v1
│   ├── ListingService.js
│   ├── DestinationService.js
│   └── AmenityService.js
│
├── Support/                     ← fonctions pures, sans état ni composant
│   ├── photo.js                    src et srcset, bornés par la largeur réelle
│   └── format.js                   nombres en français, espace que la fonte dessine
│
├── Composables/                 ← la logique, hors des composants
│   ├── useSearchQuery.js           le moteur : source unique + décompte API
│   ├── useListingFilters.js        accueil : filtrage en mémoire (lit le moteur)
│   ├── useCatalogueFilters.js      catalogue : filtrage par l'URL
│   ├── useStayDates.js             les dates d'un séjour, partagées
│   ├── usePageMotion.js            chorégraphie GSAP de l'accueil
│   └── useFicheMotion.js           chorégraphie GSAP de la fiche
│
├── Components/                  ← briques réutilisables, transversales
│   ├── SiteHeader.vue  SiteFooter.vue  SearchPanel.vue  CategoryRail.vue
│   ├── ListingCard.vue  TrustGauge.vue  MadagascarMap.vue  VaylaMark.vue
│   ├── AmenityIcon.vue             64 pictogrammes, grille de 24 px
│   ├── StayCalendar.vue            deux mois, sans dépendance de dates
│   ├── SeasonRibbon.vue            l'année en douze segments
│   └── SceneArt.vue  HeroBackdrop.vue  CanopyLayer.vue  GeckoClimb.vue  MakiWalker.vue
│
├── Pages/Home/
│   ├── Index.vue                ← orchestrateur (~95 lignes, aucun style de section)
│   └── Partials/
│       ├── HeroSection.vue         accroche, chiffres, moteur
│       ├── OffersSection.vue       rail + grille + état vide
│       ├── TrustSection.vue        l'échelle dépliée
│       ├── AtlasSection.vue        carte + liste des régions
│       ├── AskSection.vue          demande de séjour
│       └── OwnersSection.vue       la dalle terre
│
├── Pages/Bookings/
│   ├── Create.vue               ← dates, coordonnées, aucun paiement
│   └── Confirmed.vue            ← la référence, et ce qui va se passer
│
├── Pages/Destinations/
│   ├── Index.vue                ← l'atlas, en page adressable
│   ├── Show.vue                 ← où, quand, comment y aller
│   └── Partials/
│       ├── AccessPanel.vue         avion ou route, depuis Tana
│       └── TrustBreakdown.vue      la répartition par barreau
│
└── Pages/Listings/
    ├── Index.vue                ← orchestrateur du catalogue
    ├── Show.vue                 ← orchestrateur de la fiche
    └── Partials/
        ├── FilterPanel.vue         destination, type, prix, confiance, équipements
        ├── ResultsBar.vue          compte, bandeau « Aperçu », tri
        ├── Pagination.vue          numéros, pas de « charger plus »
        ├── Gallery.vue             photo de tête + pellicule + visionneuse
        ├── AvailabilitySection.vue calendrier deux mois, pleine largeur
        ├── BookingBox.vue          total du séjour, dates, demande
        ├── Confirmations.vue       les avis, sans étoiles
        ├── StayRules.vue           « À savoir avant de demander »
        ├── EnergyPanel.vue         électricité, eau, connexion
        ├── TrustPanel.vue          les quatre barreaux, franchis ou non
        └── AmenityGroups.vue       équipements par rubrique, avec les notes
```

## Qui décide quoi

`Index.vue` **n'a pas de balisage de section ni de règle de style.** Il reçoit
les props du `HomeService`, branche `useListingFilters` et `usePageMotion`, et
assemble six partiels. Chaque partiel porte son `<style scoped>`.

L'état suit la même règle que les couches backend — il vit au plus près de qui
en a besoin :

| État | Où | Pourquoi |
|---|---|---|
| destination, voyageurs, dates | `useSearchQuery`, dans `Index` | le hero, l'en-tête compact, l'atlas et la grille regardent le même état |
| `category` | `useListingFilters`, dans `Index` | le rail seul le pilote, et il doit répondre à l'instant |
| destination survolée | `AtlasSection` | la carte et la liste s'éclairent ensemble, personne d'autre n'est concerné |
| ancre du moteur | `HeroSection`, via `defineExpose` | l'en-tête compact appelle `focusSearch()` |

## Filtrage : deux chemins, un contrat

**Accueil — côté client** (`useListingFilters`) : la grille est petite,
entièrement chargée, et le rail doit répondre à l'instant. Passer par le serveur
ferait clignoter la page pour un filtre sur huit lignes.

**Catalogue — côté serveur** (`useCatalogueFilters`) : il pagine, et surtout
l'état de vérité est **l'URL**. Un filtre doit se partager, se mettre en favori,
survivre au retour arrière et être indexable — trois choses qu'un état de
composant ne donne pas. Le composable tient une copie locale pour l'affichage
immédiat des champs, et pousse une **visite Inertia partielle** :

```js
router.get('/logements', params, {
    preserveState: true,
    preserveScroll: true,   // cocher un équipement ne renvoie pas en haut de page
    replace: true,
    only: ['listings', 'meta', 'filtre'],
})
```

`only` compte : le vocabulaire des équipements, les destinations et l'échelle de
confiance ne changent pas entre deux clics. Les recharger à chaque case cochée
serait dix fois la charge utile pour rien.

Le serveur **renvoie les critères qu'il a retenus** (prop `filtre`), et le
composable s'y réaligne. Sans cet écho, un retour arrière laisserait les champs
mentir sur le contenu affiché.

Les deux chemins partagent le même objet de critères côté serveur
(`App\Data\ListingFiltreData`) et la même validation
(`App\Http\Requests\ListingIndexRequest`) que l'API mobile.

`Services/` reste le client de l'API v1, celui que l'application Flutter
reproduira à l'identique : les pages du site passent par Inertia, mais le
contrat est le même.

## Le calendrier : l'argument, pas le composant

Un calendrier de disponibilités est banal, et sur Vayla il est plus faible
qu'ailleurs : rien ne s'y réserve, une case libre ne prouve rien. Ce qui n'a
pas d'équivalent, c'est **la saison** — à Madagascar la date dit si c'est une
bonne idée, pas seulement si c'est libre.

L'ordre de lecture suit cette idée : **le ruban de l'année d'abord**, les
dates ensuite. Mettre la grille en premier aurait fait de la saison une note
de bas de page.

Trois règles de dessin :

- **Pas de cadre autour de la section.** Elle respire sur le blanc comme le
  reste du site ; un panneau bordé aurait mis l'argument dans une boîte.
- **Le ruban est neutre, sauf l'avertissement.** Plus le segment est dense,
  meilleure est la période ; la terre n'apparaît que sur le risque cyclonique.
  Un dégradé de couleurs aurait été plus joli et aurait cassé la seule règle
  chromatique de la maison — le lagon ne dit que « vérifié ».
- **Les titres de mois sont sur la même grille que les mois.** En flex, la
  tête se répartissait entre les flèches et les titres tombaient trente
  pixels à côté de leurs colonnes.

## Les dates : un seul état, trois surfaces

`useStayDates` tient l'arrivée, le départ et le survol. **La section
calendrier et l'encart de prix lisent le même objet** : choisir une date dans
l'un la pose dans l'autre. Deux états séparés finissent toujours par afficher
deux séjours différents, et le bug ne se voit qu'en production.

Trois décisions qui ne se devinent pas :

- **Tout est en chaînes `AAAA-MM-JJ` et en dates UTC.** `new Date('2026-09-12')`
  interprété en heure locale décale d'un jour à Madagascar (UTC+3) : le
  calendrier sélectionnait la veille de ce qu'on cliquait.
- **Le serveur envoie des intervalles, le client les déplie une fois.** Douze
  mois de dates individuelles font 365 chaînes par réponse ; trois intervalles
  portent la même information, et le `Set` construit à la volée rend le test
  d'une case en O(1).
- **Aucune bibliothèque de dates.** Elle pèserait plus que ces cent lignes,
  imposerait sa grille et ses classes, et il faudrait de toute façon la plier
  au design system.

## Le mouvement, et pourquoi il reste discret

`usePageMotion` (accueil) et `useFicheMotion` (fiche) suivent les mêmes règles :

- **Tout dans `gsap.context()`**, nettoyé au démontage. Aucun ScrollTrigger
  ne survit à un changement de page Inertia.
- **`gsap.matchMedia()` coupe sous `prefers-reduced-motion`**, et la coupure
  *rend visible* — jamais une page à moitié transparente.
- **Le template déclare l'intention par attributs** (`data-anim`,
  `data-anim-group`, `data-count`, `data-rung`, `data-sticky`…), le composable
  décide du mouvement. Aucun composant n'importe GSAP.
- **Rien ne dépasse 18 px de course ni 0,8 s.** Sur une page qui promet de la
  vérification, un mouvement démonstratif décrédibilise. La parallaxe de la
  grande photo (5 %) doit se remarquer par son absence, pas par sa présence.
- **Un compteur pose sa valeur exacte à l'arrivée** (`onComplete`) : le ticker
  de GSAP est gelé dans un onglet d'arrière-plan, et un nombre figé à
  mi-course serait un chiffre faux sur une page qui promet des faits.

## Divergences assumées avec le document de référence (`pdp`)

- **`fetch` et pas `axios`** : aucune dépendance ajoutée pour trois appels.
  `pdp` a besoin d'axios pour sa pile DataTables, pas Vayla.
- **Pas de `useXTable` / `useXForm`** : ce couple sert des tableaux triables et
  des formulaires modaux d'administration. L'accueil est une surface publique.
  Le jour où le back-office propriétaires arrive, il reprendra ce couple à
  l'identique.

## Images : trois résolutions, une seule source de vérité

Chaque photographie existe en **800, 1600 et 3200 px** — mais seulement jusqu'à
la résolution que porte vraiment l'original de Commons. `photos.width` dit
laquelle, et `Support/photo.js` arrête le `srcset` là :

```js
photoSrcset(photo)     // '…-800.webp 800w, …-1600.webp 1600w' si width = 1600
photoSrc(photo, 800)   // le repli des navigateurs sans srcset
```

Aucun composant ne construit d'URL d'image lui-même. Un `srcset` qui promettrait
un palier absent ferait télécharger un 404 : sur une connexion malgache, un
aller-retour perdu se paie cher.

**`sizes` se mesure.** C'est la seule information que le navigateur ne peut pas
deviner, et une valeur fausse ne se voit pas en développement — elle se voit sur
l'écran de quelqu'un d'autre. La photo de la page destination annonçait 660 px
pour un cadre de 1 304 : le navigateur choisissait le fichier 800 et l'affichait
mou. Ouvrir la page et lire `getBoundingClientRect().width` est le seul contrôle
qui vaille.

| Surface | Cadre mesuré à 1920 px | `sizes` |
|---|---|---|
| Galerie, photo de tête | 1304 px | `(min-width: 1400px) 1304px, calc(100vw - 3rem)` |
| Galerie, pellicule | 254 px | `(min-width: 760px) 260px, 38vw` |
| Visionneuse | plein écran | `100vw` |
| Photo de destination | 1304 px | `(min-width: 1400px) 1304px, calc(100vw - 3rem)` |
| Carte d'annonce | 310 à 348 px | `(min-width: 1120px) 350px, (min-width: 640px) 45vw, 92vw` |
| Carte de l'atlas | 296 px | `(min-width: 1120px) 300px, …` |
| Vignette de l'atlas d'accueil | 92 px | `96px` |
| Récapitulatif de réservation | 302 px | `(min-width: 1000px) 340px, 92vw` |

## Nombres : `Support/format.js`, jamais `Intl` directement

`Intl.NumberFormat('fr-FR')` sépare les milliers par une espace **fine**
insécable (U+202F). Plus Jakarta Sans ne dessine pas ce caractère : le navigateur
lui donne une chasse nulle et « 185 000 Ar » s'affiche « 185000 Ar ». Sur une
fiche dont le prix est l'information la plus lue, le défaut était partout —
cartes, filtres, encart, facture. `nombre()` repasse en U+00A0, que la fonte
porte. Insécable et non ordinaire : un montant ne doit pas se couper en fin de
ligne.

## Le moteur de recherche : une source, deux destinations

`SearchPanel` **ne détient aucun état**. Tout vient de `useSearchQuery`, tenu par
`Pages/Home/Index.vue` et passé au hero comme à l'en-tête compact. Auparavant
chaque exemplaire recopiait les critères dans ses propres `ref` et les
renvoyait par un événement `search` : deux copies d'une même recherche, donc la
possibilité que le hero et l'en-tête n'affichent pas la même chose.

```
useSearchQuery  ──┬──▶ SearchPanel (hero)
                  ├──▶ SearchPanel (en-tête, compact)
                  ├──▶ useListingFilters ──▶ la grille d'accueil
                  └──▶ ListingService.search() ──▶ le décompte en direct
```

**Chercher emmène au catalogue.** Une recherche par dates ne peut pas se faire
en mémoire : le navigateur n'a ni les périodes déclarées ni les réservations en
cours. Le bouton visite `/logements` avec les critères dans l'URL — un filtre
se partage, se met en favori et survit au retour arrière. Changer de
destination, en revanche, filtre toujours la grille sur place : c'est le geste
le plus fréquent et il ne doit pas faire changer de page.

**Le décompte est le premier usage réel de `ListingService`.** Il doit dire la
vérité sur tout le catalogue, pas sur les huit annonces que la page tient dans
ses props — et surtout pas sur les dates, que le client ne sait pas filtrer.
Même requête que le catalogue avec `per_page=1` : seul `meta.total` est lu.
Débounce de 250 ms, et un jeton de course parce qu'une réponse arrivée après
une frappe plus récente ferait reculer le compteur.

**Les dates ouvrent `StayCalendar`, pas deux `input[type=date]`.** Le sélecteur
natif d'Android change de forme, de langue et de premier jour de semaine d'un
téléphone à l'autre, et il ne sait pas dire « quatre nuits ». C'est le même
composant que la fiche logement, donc le même geste.

**`formatCompact` écrit le mois en lettres — « 14 sept. », jamais « 14/09 ».**
Vayla sert des voyageurs étrangers autant que des Malgaches, et `14/09` se lit
« 14 septembre » ici mais « 9 avril » pour un Américain. Sur des dates de
séjour, l'ambiguïté ne se remarque qu'après la réservation.
