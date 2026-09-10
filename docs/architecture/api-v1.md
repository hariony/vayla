# API v1 — la surface consommée par l'application mobile

Base : `/api/v1` · lecture publique · `throttle:60,1` · réponses JSON.

Versionnée dès la première ligne : une application installée ne se met pas à
jour à la demande. Le jour où le contrat change, v1 doit continuer de répondre.

## Points d'entrée

| Méthode | Chemin | Réponse |
|---|---|---|
| GET | `/listings` | `{ data: Listing[], meta }` |
| GET | `/listings/{slug}` | `{ data: ListingDetail }` — 404 si inconnu |
| GET | `/destinations` | `{ data: Destination[] }` |
| GET | `/destinations/{slug}` | `{ data: Destination }` — 404 si inconnu |
| | | *(la saison et l'accès ne sont servis que par le site pour l'instant)* |
| GET | `/categories` | `{ data: Category[] }` |
| GET | `/amenities` | `{ data: AmenityGroup[] }` — le vocabulaire complet |
| GET | `/amenities/filters` | `{ data: AmenityGroup[] }` — le sous-ensemble filtrable |
| GET | `/trust-levels` | `{ data: TrustLevel[] }` |

### `GET /listings` — paramètres

| Paramètre | Type | Contrainte |
|---|---|---|
| `destination` | slug | — |
| `category` | clé de catégorie | `verifie` filtre sur le niveau 4 |
| `guests` | entier | 1–50, capacité minimale |
| `min_trust` | entier | 1–4 |
| `kind` | type de logement | `villa`, `maison`, `appartement`, `studio`, `bungalow`, `lodge`, `chambre` |
| `max_price` | entier | ariary par nuit, borne haute |
| `amenities[]` | clés d'équipement | 20 maximum |
| `sort` | tri | `confiance` (défaut), `prix-asc`, `prix-desc`, `capacite` |
| `page` | entier | ≥ 1 |
| `per_page` | entier | 1–50 |

`confiance` est le défaut, et ce n'est pas neutre : sur un site dont la
promesse est la vérification, l'ordre naturel des résultats est le niveau de
confiance et non le prix. Les autres tris gardent `trust_level` en départage.

La validation est **partagée avec le site** (`Http\Requests\ListingIndexRequest`) :
`/logements` et `/api/v1/listings` acceptent et refusent exactement la même
chose. Deux requêtes jumelles auraient fini par diverger sur une borne.

Hors bornes → `422` avec les erreurs de validation. Un `per_page` non borné
est une porte de déni de service, et une liste d'équipements non bornée
deviendrait deux cents jointures.

**`amenities[]` est conjonctif.** `?amenities[]=groupe-electrogene&amenities[]=piscine-privee`
ramène les logements qui ont **les deux**, pas l'un ou l'autre. Cocher une case
de plus ne peut que réduire le résultat — c'est le seul comportement défendable
quand un voyageur pose deux conditions.

## Formes

```jsonc
// Listing
{
  "slug": "villa-ambatoloaka",
  "title": "Villa vue lagon, Ambatoloaka",
  "destination": "nosy-be",     // slug
  "place": "Nosy Be",           // lu sur la destination
  "region": "Diana",
  "kind": "villa",
  "kindLabel": "Villa",
  "summary": "Trois chambres au-dessus du lagon, piscine et groupe électrogène.",
  "scene": "lagoon",            // variante d'illustration de repli
  "photo": "an-nosy-komba-pirogue",  // couverture = 1re photo de la galerie
  "photoCount": 5,
  "guests": 6, "bedrooms": 3, "beds": 4, "bathrooms": 3,
  "surface": 180,               // m², nullable
  "price": 185000,              // ariary par nuit, entier, sans conversion
  "minNights": 2,
  "trust": 4,                   // 1..4 — l'échelle de confiance
  "tags": ["mer", "famille", "verifie"],
  "perks": ["Wifi fibre", "Vue mer", "Groupe électrogène"],  // dérivé, voir plus bas
  "amenityCount": 62,
  "featured": true
}

// ListingDetail — servi par /listings/{slug}
{
  "listing": { /* Listing, à l'identique */ },
  "description": "La villa domine la baie d'Ambatoloaka…",
  "amenities": [ /* AmenityGroup[] — rubriques vides exclues */ ],
  "gallery": [ /* Photo[] — dans l'ordre, la 1re est la couverture */ ],

  // Les avis. Aucune note, aucune moyenne : des faits cochés.
  "confirmed": {
    "stays": 3, "nights": 16, "demo": true,
    "points": [
      { "key": "amenities", "label": "Équipements présents",
        "long": "Les équipements annoncés sont là", "icon": "kit",
        "confirmed": 2, "flagged": 1, "answered": 3 }
    ]
  },
  "confirmations": [
    { "traveller": "Claire", "from": "Lyon", "nights": 7, "month": "juin 2026",
      "points": ["photos", "address", "price", "owner", "cleanliness"],
      "flagged": ["amenities"],
      "comment": "Rien à redire sur la maison ni sur l'accueil…",
      "mismatch": "La climatisation de la troisième chambre ne fonctionnait pas.",
      "demo": true }
  ],
  "rules": {
    "checkInFrom": "15:00", "checkOutBefore": "11:00",
    "minNights": 2, "maxNights": null, "guests": 6,
    "pets": false, "smoking": false, "events": true
  },
  "calendar": {
    // Des intervalles, pas une liste de jours : douze mois de dates
    // individuelles font 365 chaînes par réponse pour la même information.
    "blocked": [{ "from": "2026-09-06", "to": "2026-09-11" }],
    "from": "2026-09-02",          // aujourd'hui
    "to": "2027-09-02",            // horizon : 12 mois
    "minNights": 2, "maxNights": null, "price": 185000,

    // La saison : ce que le calendrier de Vayla dit et qu'aucun autre ne dit.
    "season": {
      "zone": "Nord et Nosy Be",
      "best": ["mai", "juin", "juillet", "août", "septembre", "octobre"],
      "year": [                        // 12 entrées, janvier → décembre
        { "n": 2, "month": "février", "initial": "F",
          "kind": "cyclones", "label": "Risque cyclonique",
          "note": "Le cœur de la saison cyclonique",
          "warning": true, "best": false }
      ],
      "months": {                      // 13 mois glissants, indexés AAAA-MM
        "2026-09": { "month": "septembre", "kind": "ideale", "label": "…",
                     "note": "…", "warning": false, "best": true }
      },
      "caveat": "Tendances de saison observées sur cette façade — pas une prévision météo."
    }
  }
}

// Photo
{
  "key": "an-nosy-komba-pirogue",   // public/images/lieux/<key>.webp, 1600 × 1200
  "caption": "Plage et pirogue à Nosy Komba, archipel de Nosy Be",
  "author": "Steve",
  "licence": "CC BY-SA 2.0",
  "licence_url": "https://creativecommons.org/licenses/by-sa/2.0",
  "source": "https://commons.wikimedia.org/wiki/File:…"
}

// AmenityGroup
{
  "key": "energie",
  "label": "Énergie et eau",
  "note": "Le délestage et l'eau courante ne vont pas de soi…",  // ou null
  "amenities": [
    {
      "key": "groupe-electrogene",
      "label": "Groupe électrogène",
      "group": "energie",
      "icon": "bolt",
      "filterable": true,
      "highlight": true,                 // mis en avant sur la carte
      "note": "Démarrage automatique"    // précision du propriétaire, ou null
    }
  ]
}

// meta
{ "page": 1, "per_page": 24, "total": 8, "pages": 1, "demo": true }
```

`perks` n'est pas une donnée saisie : ce sont les libellés des équipements
marqués `highlight`, triés par rubrique. Une carte ne peut donc pas annoncer
un équipement absent de la fiche. L'application n'a **rien à recomposer** —
elle affiche `perks` en liste et `amenities` en rubriques.

## Sept choses que le client mobile doit respecter

1. **`meta.demo`.** À `true`, les annonces servies sont **fictives** : elles
   n'existent que pour dessiner la mise en page. L'application doit le dire à
   l'écran, comme le bandeau « Aperçu » du site. Afficher des annonces fictives
   sans le signaler serait exactement ce que Vayla reproche aux annonces volées.

2. **Ne jamais recopier les libellés de `trust`.** Ils sont publiés par
   `/trust-levels` pour que le site et l'application affichent les mêmes mots.
   Une échelle de confiance qui ne dit pas la même chose sur deux écrans ne
   vaut rien.

3. **Ni ceux des équipements.** Même règle, même raison : `/amenities` sert le
   vocabulaire et ses rubriques dans l'ordre d'affichage. Une application qui
   écrirait « Groupe électrogène » en dur divergerait du site au premier
   ajustement de formulation. `icon` est une clé courte (`bolt`, `pool`,
   `net`…) : le client la fait correspondre à son propre jeu de pictogrammes,
   et retombe sur un point neutre pour une clé qu'il ne connaît pas — le
   vocabulaire s'allonge sans casser les versions installées.

4. **`calendar.blocked` porte la dernière nuit occupée, pas le départ.** Une
   nuit appartient à sa date d'arrivée : un séjour du 12 au 15 occupe les
   nuits 12, 13, 14 et **libère le 15**. Un client qui grise la date de fin
   retire une nuit réservable à chaque période du calendrier.

5. **Afficher `season.caveat`, jamais présenter la saison comme une
   prévision.** Ce sont des normales climatiques par façade. Un client qui
   afficherait « beau temps le 14 » ferait dire au produit une chose qu'il ne
   sait pas — et sur un site dont l'argument unique est la vérification, ça
   suffit à tout casser. Afficher aussi les mauvais mois : c'est
   « risque cyclonique » en février qui rend « meilleure période » crédible
   en août.

6. **Ne jamais calculer de note à partir des confirmations.** Le contrat ne
   publie ni `rating`, ni moyenne, ni pourcentage global, et ce n'est pas un
   oubli : un chiffre unique remplace toujours les faits qu'il résume. Un
   client qui afficherait « 92 % » ou « 4,6 ★ » réintroduirait exactement ce
   que le produit refuse.

   **Et `flagged` s'affiche à côté de `confirmed`, à la même taille.** Un
   client qui ne lirait que `points` produirait une fiche complaisante ;
   `flagged` et `mismatch` ne sont jamais absents, seulement vides.

7. **Créditer chaque photo là où elle est affichée.** CC BY et CC BY-SA
   l'exigent *partout*, pas seulement dans un écran « à propos » : une
   visionneuse plein écran doit porter l'auteur et la licence, qui voyagent
   dans chaque objet `Photo` précisément pour ça.

## Écriture

Aucune pour l'instant. La demande de séjour et l'espace propriétaire viendront
derrière `auth:sanctum` — Sanctum est installé, les jetons personnels sont
migrés, aucune route protégée n'est encore ouverte.
