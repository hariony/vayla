# Photographies d'intérieur générées — les 41 prompts

Les huit annonces de démonstration montrent aujourd'hui des paysages : village,
plage, rue. On ne peut pas juger une fiche de logement là-dessus — il manque la
chambre, le séjour, la cuisine, la piscine. Ce document contient les prompts à
donner à un générateur d'images pour les produire.

**Je ne peux pas les générer moi-même** : aucun outil de génération d'images
n'est disponible dans cette session. Vous les produisez où vous voulez (ChatGPT,
Gemini, Midjourney, Flux, Leonardo), vous déposez les fichiers dans un dossier,
et `scripts/import-photos-ia.py` fait tout le reste — recadrage 4/3, trois
résolutions, entrées prêtes à coller dans `PhotoSeeder` et `ListingSeeder`.

---

## Les deux règles qui ne se négocient pas

**1. Ces images sont marquées comme générées, partout où elles s'affichent.**
Clé préfixée `ia-`, colonne `is_ai` à `true`, mention « image générée » dans le
crédit de la visionneuse et au pied de page. Vayla vend la vérification : une
photo fabriquée qui passerait pour une photo prise sur place détruirait
l'argument entier du produit, et plus sûrement qu'une page laide. Le bandeau
« Aperçu » du haut de fiche ne suffit pas — il parle de l'annonce, pas de
l'image.

**2. Ces clés disparaissent le jour où de vraies annonces arrivent**, comme les
clés `an-`. Elles servent à juger la mise en page, rien d'autre.

Les photographies de Wikimedia Commons **restent** : ce sont elles qui
illustrent les destinations et l'atlas, et là il s'agit de vrais lieux.

---

## Comment écrire un prompt qui ne ressemble pas à un hôtel générique

Trois pièges, et ils reviennent tous :

- **Le générateur ne connaît pas Madagascar.** Sans ancrage, il produit un
  intérieur d'agence immobilière de Floride. Chaque prompt porte donc des
  marqueurs concrets : bois de palissandre, sol en ciment ciré, moustiquaire
  bordée, varangue, toit en falafa, latérite rouge, bougainvillier, cuve d'eau
  sur le toit, volets en bois plein, brique de terre cuite sur les hautes
  terres.
- **Les six images d'une annonce doivent être la même maison.** D'où la
  **signature** répétée mot pour mot dans chaque prompt d'une annonce : mêmes
  matériaux, même sol, même lumière, mêmes couleurs. Sans elle on obtient six
  belles images de six maisons différentes, et ça se voit immédiatement dans la
  pellicule.
- **Le luxe générique est un contresens.** Un lodge d'Andasibe à 175 000 Ar
  n'est pas un resort. Une case d'Ifaty à 78 000 Ar est une case en bois, et
  c'est ce qu'elle promet. Une image trop belle pour le prix affiché fait
  exactement ce que Vayla reproche aux annonces volées.

**À ajouter à chaque prompt** (ou en paramètres selon l'outil) :

```
Format 4:3, photographie d'intérieur immobilier, objectif 24 mm, lumière
naturelle, pas de grand-angle déformant. Aucune personne, aucun texte, aucun
logo, aucune marque, aucun filigrane.
```

Midjourney : ajouter `--ar 4:3 --style raw`. Les autres outils prennent les
phrases telles quelles.

---

## Nommage des fichiers

```
<slug>-<piece>.png
```

Par exemple `villa-ambatoloaka-piscine.png`. Le script en déduit la clé
`ia-villa-ambatoloaka-piscine`. Format d'entrée : PNG, JPEG ou WebP, au moins
1600 px de large — idéalement 2048 ou plus, le script ne fait **jamais**
d'agrandissement.

---

## 1. `villa-ambatoloaka` — Villa vue lagon, Nosy Be · 185 000 Ar · 6 pers.

> **Signature à répéter :** villa contemporaine à Nosy Be, murs blancs chaulés,
> sol en ciment ciré gris clair, menuiseries en palissandre sombre, textiles
> écrus et bleu lagon, lumière tropicale de fin d'après-midi.

| Fichier | Prompt |
|---|---|
| `villa-ambatoloaka-exterieur.png` | Façade d'une villa contemporaine à Nosy Be vue depuis le jardin, murs blancs chaulés, toit à faible pente, grandes baies à menuiseries en palissandre sombre, terrain clos planté de manguiers adultes, bougainvillier rose contre le mur, sol en latérite rouge par endroits, cuve d'eau blanche visible sur le toit, fin d'après-midi. |
| `villa-ambatoloaka-piscine.png` | Piscine rectangulaire à margelle de ciment ciré gris clair devant une villa blanche chaulée de Nosy Be, entièrement à l'ombre des manguiers, deux transats en bois de palissandre et coussins écrus, le lagon turquoise visible en contrebas entre les arbres, fin d'après-midi. |
| `villa-ambatoloaka-varangue.png` | Grande varangue couverte d'une villa de Nosy Be, longue table en palissandre massif pour huit, chaises à assise tressée, sol en ciment ciré gris clair, murs blancs chaulés, ventilateur de plafond, vue dégagée sur la baie turquoise par-dessus la rambarde, fin d'après-midi. |
| `villa-ambatoloaka-salon.png` | Séjour d'une villa contemporaine de Nosy Be, murs blancs chaulés, sol en ciment ciré gris clair, canapé en lin écru, table basse en palissandre sombre, coussins bleu lagon, ventilateur de plafond, baie vitrée ouverte sur la varangue et la mer, lumière naturelle de fin d'après-midi. |
| `villa-ambatoloaka-chambre.png` | Chambre d'une villa de Nosy Be, lit queen size drapé d'une moustiquaire blanche bordée, tête de lit en palissandre sombre, murs blancs chaulés, sol en ciment ciré gris clair, volets en bois entrouverts sur la végétation, linge écru, ventilateur de plafond. |
| `villa-ambatoloaka-salle-eau.png` | Salle d'eau d'une villa de Nosy Be, douche à l'italienne sans porte, murs en ciment ciré gris clair, vasque en pierre posée sur un plan en palissandre sombre, serviettes écrues empilées, petite fenêtre haute laissant entrer la lumière du jour, plantes vertes. |

## 2. `bungalow-madirokely` — Bungalow pieds dans l'eau, Nosy Be · 95 000 Ar · 2 pers.

> **Signature :** petit bungalow en bois brut à quinze mètres du sable, planches
> patinées par le sel, toit en falafa, mobilier simple, textiles blancs et bleus,
> lumière du matin.

| Fichier | Prompt |
|---|---|
| `bungalow-madirokely-exterieur.png` | Petit bungalow en planches de bois brut patiné par le sel, toit en feuilles de palmier tressées, posé sur le sable à quinze mètres de l'eau sur une plage de Nosy Be, petite terrasse en bois avec deux chaises, cocotiers penchés, mer turquoise calme, lumière du matin. |
| `bungalow-madirokely-chambre.png` | Intérieur d'un bungalow de plage en planches de bois brut à Nosy Be, un lit double sous une moustiquaire blanche bordée, ventilateur de plafond, sol en bois, une fenêtre à volets ouverte sur le sable et la mer, linge de lit blanc, décor très simple, lumière du matin. |
| `bungalow-madirokely-paillote.png` | Paillote à toit de feuilles de palmier sur le sable devant un bungalow en bois de Nosy Be, table basse en bois et deux fauteuils en rotin, petit-déjeuner tropical servi — fruits frais, café, pain — mer turquoise en arrière-plan, aucune personne, lumière du matin. |
| `bungalow-madirokely-douche.png` | Douche extérieure en bois brut adossée à un bungalow de plage, pomme de douche fixée à un poteau, sol en galets, muret de bambou pour l'intimité, serviette blanche suspendue à un crochet, végétation tropicale autour, lumière du matin. |
| `bungalow-madirokely-terrasse.png` | Terrasse en planches de bois brut d'un bungalow de plage à Nosy Be vue depuis l'intérieur, deux chaises en bois, un hamac tendu, panneaux solaires visibles sur le toit voisin, sable blanc puis mer turquoise, cocotiers, lumière du matin. |

## 3. `maison-itasy` — Maison au bord du lac, Ampefy · 140 000 Ar · 8 pers.

> **Signature :** maison familiale des hautes terres en brique de terre cuite,
> volets bois vert d'eau, sol en parquet ancien, mobilier robuste, lumière
> franche et fraîche des hautes terres.

| Fichier | Prompt |
|---|---|
| `maison-itasy-exterieur.png` | Maison familiale malgache des hautes terres en brique de terre cuite rouge, deux niveaux, volets en bois vert d'eau, véranda en bois, bâtie sur la rive herbeuse du lac Itasy, collines volcaniques en arrière-plan, ciel clair, lumière franche de milieu de matinée. |
| `maison-itasy-ponton.png` | Ponton en bois privé s'avançant sur le lac Itasy, une pirogue traditionnelle en bois amarrée à un pieu, eau calme, collines volcaniques vertes en arrière-plan, herbe rase sur la rive, aucune personne, lumière franche de milieu de matinée. |
| `maison-itasy-salon.png` | Séjour d'une maison familiale des hautes terres malgaches, cheminée en brique de terre cuite avec bûches empilées, parquet ancien, canapés en tissu épais couleur terre, plaid en laine, fenêtres à volets bois vert d'eau ouvertes sur le lac, lumière froide et franche. |
| `maison-itasy-table.png` | Grande salle à manger d'une maison familiale malgache, longue table en bois massif pour dix, chaises dépareillées en bois, parquet ancien, murs blancs, buffet ancien, fenêtres à petits carreaux donnant sur le lac et les collines, lumière naturelle. |
| `maison-itasy-chambre.png` | Chambre d'une maison familiale des hautes terres malgaches, lit double avec couverture en laine épaisse, parquet ancien, murs blancs, armoire en bois ancienne, fenêtre à volets bois vert d'eau ouverte sur le lac, lumière fraîche de matin. |
| `maison-itasy-cuisine.png` | Cuisine d'une maison familiale malgache, plan de travail en bois, gazinière, étagères ouvertes avec vaisselle en émail, casseroles suspendues, carrelage ancien au sol, fenêtre au-dessus de l'évier donnant sur le jardin et le lac, lumière naturelle. |

## 4. `front-de-mer-amborovy` — Appartement front de mer, Majunga · 120 000 Ar · 4 pers.

> **Signature :** appartement moderne et clair, carrelage blanc cassé, murs
> blancs, mobilier contemporain simple, touches de bois clair, lumière chaude et
> dorée du coucher de soleil de la côte ouest.

| Fichier | Prompt |
|---|---|
| `front-de-mer-amborovy-balcon.png` | Balcon d'un appartement au troisième étage à Majunga, deux fauteuils en rotin et une table basse, garde-corps blanc, vue plein ouest sur le canal du Mozambique au coucher du soleil, ciel orangé, quelques cocotiers en contrebas, aucune personne. |
| `front-de-mer-amborovy-salon.png` | Séjour moderne d'un appartement de bord de mer à Majunga, murs blancs, carrelage blanc cassé, canapé gris clair, table basse en bois clair, climatiseur mural, grande baie coulissante ouverte sur le balcon et la mer, lumière dorée de fin de journée. |
| `front-de-mer-amborovy-cuisine.png` | Cuisine ouverte sur le séjour d'un appartement moderne à Majunga, meubles blancs mats, plan de travail en bois clair, plaque de cuisson, réfrigérateur, îlot avec deux tabourets, carrelage blanc cassé au sol, lumière dorée de fin de journée. |
| `front-de-mer-amborovy-chambre.png` | Chambre climatisée d'un appartement moderne à Majunga, lit double, linge blanc, tête de lit en bois clair, climatiseur mural, murs blancs, carrelage blanc cassé, rideaux légers écartés sur une fenêtre donnant sur la mer, lumière dorée de fin de journée. |
| `front-de-mer-amborovy-salle-eau.png` | Salle de bain d'un appartement moderne à Majunga, faïence blanche, douche avec paroi vitrée, meuble vasque en bois clair, miroir rectangulaire, serviettes blanches, petite fenêtre en hauteur, lumière naturelle. |

## 5. `villa-coloniale-antsirabe` — Villa coloniale, Antsirabe · 160 000 Ar · 10 pers.

> **Signature :** villa des années trente, murs crépis crème, moulures, parquet
> à chevrons, hauts plafonds, mobilier ancien en bois sombre, lumière froide des
> hautes terres.

| Fichier | Prompt |
|---|---|
| `villa-coloniale-antsirabe-exterieur.png` | Villa coloniale malgache des années trente à Antsirabe, façade crépie crème, encadrements de fenêtres moulurés, toit à quatre pans en tuiles, véranda à colonnes, jardin arboré clos par un muret, allée en gravier avec une voiture ancienne garée dans la cour, lumière froide de matin des hautes terres. |
| `villa-coloniale-antsirabe-salon.png` | Grand salon d'une villa coloniale des années trente à Antsirabe, hauts plafonds à moulures, parquet à chevrons, cheminée en pierre avec un feu allumé, canapés anciens en velours, tapis usé, hautes fenêtres à petits carreaux, lumière froide et rasante. |
| `villa-coloniale-antsirabe-chambre.png` | Chambre d'une villa coloniale des années trente à Antsirabe, lit en fer forgé, couverture en laine épaisse, parquet à chevrons, hauts plafonds, armoire ancienne en bois sombre, radiateur en fonte sous une haute fenêtre à petits carreaux, lumière froide de matin. |
| `villa-coloniale-antsirabe-cuisine.png` | Grande cuisine d'une villa coloniale malgache dimensionnée pour recevoir, plan de travail long en bois, gazinière à six feux, batterie de casseroles en cuivre suspendue, étagères ouvertes, carrelage ancien noir et blanc au sol, grande table de travail centrale, lumière naturelle. |
| `villa-coloniale-antsirabe-salle-eau.png` | Salle de bain d'une villa coloniale des années trente, baignoire ancienne sur pieds, carrelage blanc à listel noir, lavabo sur colonne, miroir ancien, radiateur sèche-serviettes en fonte, haute fenêtre à petits carreaux dépolis, lumière froide. |
| `villa-coloniale-antsirabe-jardin.png` | Jardin clos et arboré d'une villa coloniale à Antsirabe, pelouse, vieux jacarandas, muret de pierre, table et chaises de jardin en fer forgé, la véranda à colonnes de la villa en arrière-plan, lumière froide de matin des hautes terres. |

## 6. `case-ifaty` — Case sur le sable, Ifaty · 78 000 Ar · 3 pers.

> **Signature :** case très simple en bois et falafa, sans électricité de ville,
> sable blanc, lumière crue du sud-ouest, aucun luxe — la promesse c'est le
> récif, pas le confort.

| Fichier | Prompt |
|---|---|
| `case-ifaty-exterieur.png` | Case en bois et feuilles de palmier tressées posée directement sur le sable blanc à Ifaty, sud-ouest de Madagascar, très simple, petit auvent d'entrée, panneaux solaires posés sur le toit, pirogues à balancier échouées plus loin, mer et barrière de corail à l'horizon, lumière crue de midi. |
| `case-ifaty-chambre.png` | Intérieur très simple d'une case en bois à Ifaty, un lit double sous une grande moustiquaire blanche, sol en sable damé et nattes tressées, cloisons en planches de bois avec des interstices laissant passer la lumière, lampe solaire posée sur une caisse en bois, aucun luxe. |
| `case-ifaty-douche.png` | Douche extérieure très rustique adossée à une case en bois à Ifaty, cloison en branches et feuilles de palmier tressées, pomme de douche fixée à un poteau, sol en galets, seau et bidon d'eau, sable blanc autour, lumière crue de midi. |
| `case-ifaty-recif.png` | Vue depuis la terrasse en bois d'une case sur le sable à Ifaty, deux chaises en bois usées, à deux cents mètres la barrière de corail marquée par la ligne d'écume, pirogues à balancier à voile au mouillage, sable blanc, aucune personne, lumière crue de midi. |

## 7. `lodge-andasibe` — Lodge en lisière de forêt, Andasibe · 175 000 Ar · 4 pers.

> **Signature :** lodge en bois sur pilotis en lisière de forêt humide, bois
> sombre, textiles verts et bruns, brume matinale, lumière verte filtrée par la
> canopée.

| Fichier | Prompt |
|---|---|
| `lodge-andasibe-exterieur.png` | Lodge en bois sur pilotis en lisière de forêt humide à Andasibe, Madagascar, bardage en bois sombre, toit à forte pente, escalier en bois menant à la terrasse, fougères arborescentes et végétation dense tout autour, brume matinale entre les arbres, lumière verte filtrée. |
| `lodge-andasibe-terrasse.png` | Terrasse en bois sur pilotis d'un lodge d'Andasibe donnant sur la canopée d'une forêt humide, deux fauteuils en bois avec coussins verts, table basse, plaid, tasse de café fumante posée sur la table, brume matinale au-dessus des arbres, aucune personne. |
| `lodge-andasibe-salon.png` | Séjour d'un lodge en bois à Andasibe, murs et plafond en lambris de bois sombre, canapé en tissu vert profond, plaids en laine, table basse en bois brut, lampes chaudes, grande baie vitrée donnant sur la forêt humide et sa brume, lumière verte filtrée. |
| `lodge-andasibe-chambre.png` | Chambre d'un lodge en bois sur pilotis à Andasibe, lit double sous moustiquaire, murs en lambris de bois sombre, couverture en laine, lampe de chevet chaude, fenêtre donnant directement sur les fougères arborescentes et la forêt humide, brume matinale, lumière verte filtrée. |
| `lodge-andasibe-salle-eau.png` | Salle d'eau d'un lodge en bois à Andasibe, douche carrelée de tomettes sombres, plan vasque en bois brut, miroir rond, serviettes vertes, plantes tropicales, fenêtre en hauteur donnant sur la forêt, lumière verte filtrée. |

## 8. `studio-thermal` — Studio design, Antsirabe · 70 000 Ar · 2 pers.

> **Signature :** studio rénové compact, murs blancs, parquet clair, mobilier
> contemporain sobre, une touche de terre cuite, lumière froide et claire des
> hautes terres.

| Fichier | Prompt |
|---|---|
| `studio-thermal-ensemble.png` | Vue d'ensemble d'un studio rénové compact de 32 m² à Antsirabe, murs blancs, parquet clair, lit double d'un côté, coin bureau de l'autre, kitchenette au fond, mobilier contemporain sobre, une grande fenêtre, lumière froide et claire des hautes terres. |
| `studio-thermal-bureau.png` | Coin bureau d'un studio rénové à Antsirabe, bureau en bois clair placé face à une grande fenêtre, chaise de travail confortable, lampe d'architecte, étagère murale avec quelques livres, murs blancs, parquet clair, lumière froide et claire. |
| `studio-thermal-cuisine.png` | Kitchenette complète d'un studio rénové à Antsirabe, meubles blancs mats, plan de travail en bois clair, plaque à deux feux, petit four, réfrigérateur encastré, étagère avec vaisselle simple, crédence en carreaux de terre cuite, lumière naturelle froide. |
| `studio-thermal-salle-eau.png` | Petite salle d'eau d'un studio rénové à Antsirabe, douche à l'italienne avec paroi vitrée, faïence blanche, meuble vasque en bois clair, miroir rond, serviettes grises, radiateur sèche-serviettes, lumière naturelle. |

---

## Quand vous avez les fichiers

```bash
python3 scripts/import-photos-ia.py ~/Desktop/photos-vayla
```

Le script vérifie que chaque fichier attendu est là, refuse ceux qui font moins
de 1600 px, recadre en 4/3 centré, écrit les trois résolutions dans
`laravel/public/images/lieux/`, et affiche les blocs PHP à coller dans
`PhotoSeeder` et `ListingSeeder`. Rien n'est modifié en base tant que le seeder
n'a pas tourné.
