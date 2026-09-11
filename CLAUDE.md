# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Vue d'ensemble

Vayla — plateforme de locations meublées vérifiées à Madagascar. Le dépôt est né du starter
« CoreKit » (Laravel + Inertia + Vue) puis a été spécialisé : `README.md` décrit encore le
starter et ses valeurs sont périmées (ports, nom de projet, base). **Le `Makefile` et
`docker-compose.yml` font foi.**

L'interface, les commentaires de code et les commits sont en français.

## Commandes

Tout passe par Docker : aucun PHP, Composer ou Node en local. Les cibles Make enveloppent
`docker compose exec`.

```bash
make init                 # ré-init après clone (build, up, composer, npm, schéma, migrate)
make up / down / restart
make logs-app             # nginx + php-fpm
make shell                # sh dans le conteneur app (Alpine)
make shell-db             # psql

make migrate | fresh | seed | rollback
make db-schema            # crée le schéma PostgreSQL `vayla` (requis avant la 1re migration)

make npm-dev              # redémarre Vite et suit ses logs (HMR :5174)
make npm-build

make artisan cmd="route:list"
make composer cmd="require vendor/pkg"
make test
make cache                # optimize:clear — voir « Pièges » ci-dessous
```

Un seul test / un seul fichier :

```bash
make artisan cmd="test --filter=la_categorie_verifie_se_deduit_du_niveau_quatre"
docker compose exec app php artisan test tests/Feature/ExampleTest.php
```

Formatage PHP (Pint est en require-dev, pas de cible Make) :

```bash
docker compose exec app ./vendor/bin/pint
```

Accès : app `http://localhost:8070`, back-office `http://office.localhost:8070`, Vite
`http://localhost:5174`, PostgreSQL `localhost:5437`.
**5174 et non 5173** : un autre projet occupe 5173 sur cette machine — voir « Pièges ».

## Architecture

Deux niveaux : la racine porte l'orchestration (`docker-compose.yml`, `Makefile`), `laravel/`
porte l'application. Trois services : `app` (image multi-stage composer → vite → PHP 8.5-FPM +
Nginx + Supervisor), `postgres:16`, `node:20` (serveur Vite seul, `npm run dev` en continu).
Le réseau `vayla-network` est **externe** : `make network` avant tout.

### Inertia pour le site, API v1 pour le mobile

Vue 3 monté par Inertia depuis `resources/js/app.js` ; `resources/views/app.blade.php` est la
seule vue Blade (avec `errors/404.blade.php`, volontairement en Blade : une page d'erreur doit
s'afficher même si le paquet JavaScript ne se charge pas). Les contrôleurs renvoient
`Inertia::render('Home/Index', [...])` et les props arrivent dans
`resources/js/Pages/<Écran>/Index.vue`. Pas de routeur côté client : ajouter un écran = une
route + un contrôleur + un dossier dans `Pages/`. `HandleInertiaRequests` partage `auth.user`
globalement.

Côté public : `/` (accueil), `/logements` (catalogue filtrable), `/logements/{slug}` (fiche),
`/logements/{slug}/reserver`, `/reservations/{reference}`, `/destinations` (l'atlas),
`/destinations/{slug}`, `/connexion` (l'aiguillage) et `/connexion/client`. Derrière la connexion,
`/proprietaire` et ses écrans.

**Le séjour suit le clic.** Les dates saisies dans le moteur voyagent dans l'URL du catalogue
puis dans celle de la fiche, où elles pré-remplissent le calendrier. Sur la fiche c'est **une
suggestion, pas un critère** : elle n'est pas validée côté serveur (`SejourData::depuis()` rend
`null` sur n'importe quoi) et `useStayDates.preselectionner()` refuse un séjour qui chevauche
une nuit prise — tout ou rien, jamais une demi-sélection. Faire échouer une page pour un
confort d'affichage serait disproportionné.

**La page destination n'est pas un catalogue filtré** — `/logements?destination=nosy-be` le
fait déjà, et mieux. Elle existe pour les trois choses que la grille ne dira jamais : *quand
venir* (le ruban saisonnier, dès le hero), *comment y aller* (avion ou route, durée réelle
depuis Tana), et *jusqu'où la vérification est allée ici* (la répartition par barreau). Si un
jour elle ne porte plus que des cartes d'annonces, elle n'a plus lieu d'être.

**Le moteur de recherche a une seule source de vérité** (`useSearchQuery`, tenue par
`Pages/Home/Index.vue`) : le moteur du hero, sa forme compacte dans l'en-tête, l'atlas et la
grille regardent le même état. Chaque exemplaire recopiait auparavant les critères dans ses
propres `ref` et les renvoyait par un événement — un aller-retour qui rendait possible que le
hero et l'en-tête affichent deux recherches différentes.

**Chercher emmène au catalogue ; changer de destination filtre sur place.** Une recherche par
dates ne peut pas se faire en mémoire — le navigateur n'a ni les périodes déclarées ni les
réservations en cours. Le bouton « Chercher » visite donc `/logements` avec les critères dans
l'URL. Changer de destination continue de filtrer la grille d'accueil à l'instant : c'est le
geste le plus fréquent et il n'a pas à faire changer de page.

**Le décompte sous le moteur est le premier usage réel de `ListingService`**, et c'est le
moment que l'architecture attendait : il doit dire la vérité sur *tout* le catalogue, pas sur
les huit annonces que la page tient dans ses props. Même requête que le catalogue, `per_page=1`
— on ne veut que `meta.total`. Débounce de 250 ms et jeton de course : une réponse arrivée
après une frappe plus récente est ignorée, sinon le décompte revient en arrière.

**Les deux grilles ne filtrent pas au même endroit, et c'est délibéré.** L'accueil filtre
**en mémoire** (`useListingFilters`) : la grille est petite et le rail doit répondre à
l'instant. Le catalogue filtre **côté serveur** (`useCatalogueFilters` → visite Inertia
partielle) : il pagine, et surtout l'URL doit porter les critères — un filtre se partage, se
met en favori, survit au retour arrière et s'indexe. Les visites y sont partielles
(`only: ['listings', 'meta', 'filtre']`) : le vocabulaire des équipements et les destinations
ne changent pas entre deux clics.

Le site et l'API partagent **la même classe de validation** (`Http\Requests\ListingIndexRequest`)
et donc exactement les mêmes bornes. Un critère invalide donne un 422 en JSON — un client qui
envoie n'importe quoi doit l'apprendre bruyamment — et une redirection vers `/logements` sur le
site : un lien partagé au filtre périmé doit rouvrir le catalogue, pas éjecter vers l'accueil
(c'était le comportement par défaut, faute de page précédente).

À côté, `routes/api.php` expose **`/api/v1`** en lecture publique, la surface que consommera
l'application Flutter. Les deux entrées traversent **les mêmes services** : le site et
l'application ne peuvent pas diverger sur ce qu'est une annonce ou sur son niveau de
confiance. Voir `docs/architecture/api-v1.md`.

Sanctum est installé (jetons personnels migrés) mais **aucune route protégée n'est ouverte** :
l'écriture viendra avec la demande de séjour et l'espace propriétaire.

### PostgreSQL : schéma dédié

L'application vit dans le schéma `vayla`, pas `public` (`DB_SCHEMA` →`search_path` dans
`config/database.php`). Une base fraîche ne migre pas tant que `make db-schema` n'a pas tourné.

### Front : design system maison

`resources/css/app.scss` (~360 lignes) est la seule feuille globale : tokens en variables CSS
(`--terre-*`, `--lagon-*`, `--unverified`, `--ink*`, `--white`/`--off*`, `--text*`, `--line*`,
`--r-*`, `--sh-*`, `--gutter`, `--header-h`) et utilitaires partagés — `.shell`, `.section`,
`.bar`, `.display--*`, `.eyebrow`, `.uline`, `.lede`, `.num`, `.chip`, `.card`, `.gauge`,
`.btn` et ses variantes, `.rise`, `.no-bar`. La direction assumée est **« terre & lagon »** :
blanc franc dominant, angles généreux (`--r-lg: 26px`, pilules), ombres douces.

**La règle qui prime sur l'esthétique : un contrôle doit se voir comme un contrôle.**

Vayla sert deux publics qui n'ont pas le même bagage numérique — des voyageurs étrangers
rompus aux plateformes, et des utilisateurs malgaches dont beaucoup réservent un logement en
ligne pour la première fois, presque tous au téléphone. Ce qui va de soi pour les premiers ne
va pas de soi pour les seconds, et c'est toujours au détriment des seconds qu'on tranche quand
on privilégie la légèreté visuelle. Quatre conséquences, toutes constatées sur du code écrit
ici :

- **Pas de bouton sans contour ni fond.** Les flèches du calendrier étaient un chevron gris de
  16 px, sans bordure : personne ne pouvait deviner que ça se cliquait. Contour franc, fond
  blanc, 2,75 rem — la cible tactile minimale.
- **Un état désactivé se lit, il ne s'efface pas — et ne se déguise pas en actif.** À 30 %
  d'opacité le bouton disparaissait au lieu de dire « il n'y a rien avant ce mois ». À l'inverse,
  `.btn` n'avait **aucune** règle `:disabled` : un « Valider » désactivé gardait la terre pleine et
  son `cursor: pointer`, donc il appelait un clic qui ne faisait rien — sur tous les boutons du
  site. Ce qui dit « pas encore », c'est le **changement de famille de couleur** (neutre au lieu de
  terre), pas une opacité réduite ; le texte reste à 8,4:1 sur le fond, et c'est la phrase en
  dessous qui nomme ce qui manque.
- **Écrire la consigne, pas décrire l'écran.** Le calendrier disait « les nuits prises sont
  barrées » sans jamais dire *cliquez sur une date*. Il porte maintenant une consigne numérotée
  qui avance avec l'utilisateur : ① date d'arrivée → ② date de départ → ✓ n nuits.
- **Rien d'interactif au seul survol.** Le ruban des saisons ne réagissait qu'à `mouseenter` :
  inerte au doigt, donc inerte pour la majorité de nos utilisateurs. Tout `@mouseenter` doit
  avoir son `@click`.
- **Jamais de faux signal** : une ligne soulignée qui n'est pas un lien, un champ libre là où
  une liste déroulante suffit (`voyageurs` était un `type="number"` vide portant « Peu
  importe »).

**La règle qui tient tout le système chromatique : deux couleurs, deux métiers.**

- **La terre de latérite** (`--terre-500: #C9452A`) porte la marque, l'envie, l'action —
  boutons, moteur de recherche, sur-titres, dalle propriétaires, carte de Madagascar,
  illustrations. C'est la couleur de l'Île Rouge.
- **Le lagon** (`--lagon-500: #0E9080`) ne dit **qu'une chose : « vérifié »**, et n'apparaît
  nulle part ailleurs — jauge de confiance, badge et sceau des annonces, échelle de confiance,
  gecko qui la grimpe. **Ne jamais l'employer pour décorer** : dès qu'il sort de ce rôle,
  l'échelle de confiance cesse d'être lisible d'un coup d'oeil.
- `--unverified` (gris) tient le niveau 1 : « déclarée » n'est pas une vérification, le vert
  ne commence qu'au niveau 2.

**Une seule famille de caractères : Plus Jakarta Sans** (300–800), la cousine
libre d'Airbnb Cereal — le contraste vient du poids et du crénage, pas d'un second caractère.
La page n'a **qu'une seule dalle de couleur pleine**, la section propriétaires en terre ;
tout le reste est blanc. Tout le reste est en `<style scoped>` dans les composants et les
partiels de page. **Bootstrap et Tailwind
sont dans `package.json` mais ne sont chargés nulle part** (résidus du starter) : ne pas écrire
de classes Bootstrap ou Tailwind, étendre le design system.

### Front : découpage des pages

`Pages/Home/Index.vue` est un **orchestrateur** : il reçoit les props, branche
`useListingFilters` et `usePageMotion`, assemble six partiels de `Pages/Home/Partials/`
(hero, offres, confiance, atlas, demande, propriétaires). Il ne porte **ni balisage de section
ni règle de style** — chaque partiel a son `<style scoped>`. Ne pas y remettre de balisage :
c'est ce qui a fait passer la page de 944 lignes en un fichier à six fichiers lisibles.

L'état vit au plus près de qui en a besoin : `query` et `category` dans `useListingFilters`
(partagés par le hero, le rail, l'atlas et l'en-tête) ; la destination survolée reste **locale
à `AtlasSection`** ; l'ancre du moteur est exposée par `HeroSection` via `defineExpose`.

`Pages/Listings/Index.vue` est l'orchestrateur du catalogue (trois partiels : `FilterPanel`,
`ResultsBar`, `Pagination`) et `Pages/Listings/Show.vue` celui de la fiche (`Gallery`,
`EnergyPanel`, `TrustPanel`, `AmenityGroups`). Même règle que l'accueil : l'orchestrateur ne
porte ni balisage de section ni règle de style.

Quatre partis pris à ne pas défaire :

- **La capacité se lit avant la photographie**, dans son propre bloc en tête de fiche
  (`Partials/CapacityBar.vue`) : « est-ce que ça nous loge ? » est la question qui écarte un
  logement quelles que soient les images, et elle tenait sur une ligne de texte gris sous la
  galerie. **Deux chiffres décident — capacité et chambres — trois renseignent** : couchages,
  salles d'eau, surface passent en seconde ligne, plus petits. Le bloc se loge dans la place
  vide à droite du titre : il ne coûte pas un pixel de hauteur à la photographie. Il reste
  **neutre** — la terre porte la marque et l'action, le lagon ne dit que « vérifié » ; colorer
  une capacité affaiblirait l'échelle de confiance.
- **« 6 personnes max. » côté logement, « voyageurs » côté recherche, et ce n'est pas une
  incohérence.** `listings.guests` est un **plafond** : « voyageurs » énonçait un nombre sans
  dire qu'il s'agissait d'une limite, alors que la question posée est « est-ce qu'on rentre à
  cinq ? ». Les filtres, eux, demandent la taille du groupe qui vient — deux questions
  différentes, deux mots différents. Les fondre en un seul brouillerait « ce que le logement
  contient » et « qui se déplace ».
- **Sur la fiche, le prix vient en troisième**, après la galerie et le niveau de vérification.
  Partout ailleurs il vient en second ; ici ce qui se vend n'est pas le tarif mais la certitude
  que le logement existe et correspond.
- **`TrustPanel` montre les quatre barreaux, pas seulement celui atteint**, et écrit noir sur
  blanc ce qui manque. Un logement de niveau 2 qui ne dirait pas ce qui lui reste à franchir
  ferait de « vérifié » un mot creux. Le gecko s'arrête au barreau atteint : sa position **est**
  la donnée, pas une décoration.
- **`EnergyPanel` (« Ce qui tient quand la ville lâche ») dit aussi la mauvaise nouvelle.** Un
  logement branché sur le seul réseau JIRAMA affiche « rien ne prend le relais pendant les
  coupures ». C'est le prix pour que « groupe électrogène » veuille dire quelque chose sur les
  autres fiches — un panneau qui ne saurait que rassurer ne renseignerait rien. Tout s'y déduit
  des équipements déclarés, rien n'y est saisi.
- **Les dates vivent dans `useStayDates`, pas dans le calendrier.** Trois surfaces les
  partagent : la grille des mois, les champs de l'encart et le total. Deux états séparés
  finissent toujours par afficher deux séjours différents. Tout y est manipulé en chaînes
  `AAAA-MM-JJ` et en dates **UTC** : `new Date()` en heure locale décale d'un jour à
  Madagascar (UTC+3), et le calendrier sélectionnait la veille.
- **L'encart affiche le total du séjour dès que les dates sont posées**, pas le prix à la
  nuit. Un tarif nuitée seul oblige le voyageur à faire la multiplication, et c'est là qu'on
  perd les gens. Mais **aucun montant n'est encaissé** : pas de « 0 € aujourd'hui » qui
  laisserait croire qu'un paiement viendra — le voyageur règle sur place.
- **La galerie n'est pas la mosaïque à cinq tuiles d'Airbnb, et c'est délibéré** : une
  photographie de tête sur toute la largeur (2/1 au-delà de 760 px, 4/3 en dessous, hauteur
  bornée à `min(58vh, 540px)` pour ne pas chasser le prix hors de l'écran), puis une pellicule
  qui défile. Trois raisons, aucune décorative : ici les photographies sont **de vraies
  photographies créditées** et les empiler cinq par cinq les traite en vignettes ; la mosaïque
  demandait quatre dispositions selon le nombre de photos et affichait « +7 » au-delà de cinq ;
  et le **niveau de vérification a enfin un endroit où se poser**, sur la photo, à l'instant où
  l'on regarde le logement — ce que les plateformes du Nord n'ont pas à mettre là. En dessous
  de cinq vignettes, la pellicule se partage exactement la largeur (`--n`, posé par le
  composant) ; au-delà elle en montre cinq et défile. **La largeur va sur le `<li>`, jamais sur
  le bouton** : un pourcentage posé sur l'enfant d'un élément flex dimensionné par son contenu
  se résout contre lui-même et la vignette s'effondre à quelques pixels.
- **Le sceau étant sur la photo, il n'est plus répété à côté du titre.** Deux fois le même
  niveau à trente pixels d'écart affaiblissait les deux.
- **La tête et la pellicule chargent sans différer** : elles sont au-dessus de la ligne de
  flottaison, et `loading="lazy"` y laisse des rectangles gris. Seules les vignettes de la
  visionneuse restent en `lazy`.

**Les raccourcis de la fiche : six pictogrammes légendés, flottants, à gauche.**
`Components/SectionDock.vue` pose six raccourcis — Photos, Énergie, Vérification, Avis,
Équipements, Calendrier. Une fiche fait trois mille pixels et ne se lit pas dans l'ordre : celui
qui cherche « y a-t-il un groupe électrogène » ou « quelqu'un y a-t-il vraiment dormi » n'a aucune
raison de traverser la description pour y arriver.

**Les libellés sont écrits, toujours, sous chaque pictogramme.** Une première version ne les
déployait qu'au survol — la faute des chevrons gris du calendrier sous une forme plus jolie : au
doigt il n'y a pas de survol, et un éclair ne dit pas « Énergie » à qui découvre. Le pictogramme se
reconnaît, le mot s'assure. Un test regarde les deux façons de cacher un mot sans le retirer :
`aria-hidden`, ou une largeur nulle.

**Un seul objet, deux ancrages** — c'est la seule chose que `variante` décide :

- **`flottant`** : **collé au défilement et toujours visible**, dans un **couloir réservé** à
  gauche du contenu. La fiche pose `--couloir: 5.5rem` et ajoute cette largeur à la marge gauche
  de `.shell` au-delà de 1180 px : laissés dans la seule gouttière, les raccourcis se collaient au
  bord de la photographie, et se posaient dessus dès 1440 px. **Toute retouche du couloir demande
  de refaire le `sizes` de la galerie** (la photo de tête perd sa largeur : 1 216 px au-delà de
  1400).

  **`sticky`, jamais `fixed` : la descente maximale est la fin de la fiche.** En `position:
  fixed`, le dock ignorait la page et finissait posé sur le pied de page — une butée qu'un
  élément fixé à la fenêtre ne peut pas connaître. Il colle maintenant dans `.fiche__couloir`, une
  bande **absolue de toute la hauteur de `.shell`**, dans le `<main>` : la fiche finie, la bande
  finie, et il remonte avec elle au-dessus des crédits photo. Aucun calcul, aucun écouteur, et plus
  de `(100vw - 1400px) / 2` à tenir à jour — la bande suit `.shell` d'elle-même. Il colle à la même
  hauteur que l'encart de réservation (`--header-h + 1.5rem`) : les deux colonnes qui suivent le
  lecteur partent de la même ligne. **Aucun ancêtre ne doit porter `overflow: hidden`** — un
  élément `sticky` s'y arrête net ; `.page` est en `overflow-x: clip` pour cette raison.
- **`photo`** : une **rangée au bas de la photographie**, sous 1180 px. Horizontale, parce que six
  pictogrammes légendés empilés feraient 290 px — la hauteur de la photo entière sur un
  téléphone. En bas, jamais en haut : le coin haut-gauche porte le sceau de vérification et le
  haut-droit « voir les photos ». Elle défile si l'écran est trop étroit, et **ne coupe jamais
  une légende**. La rubrique « Photos » n'y figure pas — un raccourci vers l'endroit où l'on se
  trouve déjà est un bouton qui ne fait rien.

**Les pictogrammes sont dessinés pour la fiche** (`Components/SectionGlyph.vue`), et chacun dit ce
que la section contient *sur Vayla* : une photographie de voyage (cadre, relief, soleil) plutôt
qu'un appareil ; un éclair et ses étincelles ; un écusson portant **la coche du monogramme** — le V
de Vayla est lui-même une coche ; **une liste cochée, ni bulle ni étoile**, parce que Vayla n'a pas
de note sur cinq et que les voyageurs y cochent des faits ; quatre cases et un pion pour un
inventaire ; une page de mois **traversée par une plage**, parce que sur la fiche on pose une
arrivée et un départ, pas un jour. **Bicolores comme le monogramme et les pictogrammes de
`/connexion`** : corps à l'encre, accent à la terre. Le lagon n'y entre pas, pas même sur
*Vérification* : un pictogramme d'orientation n'est pas une vérification.

**Chaque pictogramme a son geste** (`Composables/useGlyphMotion.js`), et le geste joue le mot de la
section — comme la clé qui tourne sur `/connexion` : le soleil se lève, l'éclair vacille comme une
ampoule sur un réseau fragile puis tient, la coche se trace, les faits se cochent un à un, le pion
pointe une case, la plage du séjour se pose. Il joue **au survol, au focus *et* à l'arrivée dans la
section** — jamais au seul survol : c'est l'arrivée qui le montre au doigt, et c'est ce qui apprend
l'icône. Moins de 0,7 s, **chaque pièce revient à sa position de repos**, une chronologie par
pictogramme relancée plutôt que superposée, et rien sous `prefers-reduced-motion`. **L'entrée les
présente un par un** : les pastilles arrivent en cascade, puis chaque geste se joue à la suite —
c'est le seul moment où tous bougent.

**Le fil dit où l'on en est** : il se remplit à mesure qu'on descend (`ScrollTrigger`, jamais un
IntersectionObserver maison — lui seul rate le défilement rapide, l'arrivée par une ancre et
l'onglet en arrière-plan). Il **longe** la colonne au lieu de la traverser : passé au centre des
pastilles, il aurait barré les légendes. Sur la photographie il n'apparaît pas.

**La terre dit « vous êtes là », sans remplir la pastille** : fond de terre très pâle, filet de
terre, légende en terre foncée. Une pastille entièrement terre aurait avalé le pictogramme
bicolore, et son geste avec.

- **Six rubriques, pas huit** : la description et les règles de séjour sont dans le flux, juste
  après ce qui les amène. Un raccourci qu'on parcourt pour choisir n'en est plus un.
- **L'ordre suit celui de la page**, et un test le vérifie en même temps que l'existence de chaque
  ancre, d'un tracé et d'un geste pour chaque rubrique.
- **Aller à une section n'est pas un saut d'ancre** : l'en-tête est fixe, `href="#photos"` poserait
  le titre dessous. On défile à la main, décalé de `--header-h` — et d'un coup sous
  `prefers-reduced-motion`.
- **Les deux ancrages lisent le même état**, tenu par la page : chacun appelant le composable
  aurait posé deux jeux de `ScrollTrigger` sur les mêmes sections.
- **La surcouche épouse la photographie de tête, pas la galerie entière** : sa géométrie vit en
  jetons sur `.gal`, lus par la photo et par la surcouche.

`Components/AmenityIcon.vue` porte les 64 pictogrammes d'équipements, même grille de 24 px que
le rail. Une clé inconnue retombe sur un point neutre : le vocabulaire peut s'allonger en base
sans casser le front ni les versions installées de l'application mobile.

`Composables/useFicheMotion.js` tient la chorégraphie de la fiche. **Discrète par contrainte de
fond** : sur une page qui promet de la vérification, un mouvement démonstratif décrédibilise —
rien ne dépasse 18 px de course ni 0,8 s. Mêmes règles que l'accueil : `gsap.context()` pour le
nettoyage, `gsap.matchMedia()` pour couper sous `prefers-reduced-motion`, et la coupure rend
visible plutôt que de laisser la page à moitié transparente.

`SiteHeader` et `SiteFooter` prennent un booléen `home` : les ancres de section (`#confiance`,
`#atlas`, `#demande`) n'existent que sur l'accueil et doivent repartir en `/#…` ailleurs.

**L'en-tête suit la session, et « Connexion » ne disparaît jamais — il change de destination.**
Le retirer une fois connecté laisserait quelqu'un sans aucun chemin vers son espace depuis le site
public : la même impasse que celle qu'on venait de corriger, dans l'autre sens. Le garder tel quel
est un faux signal — proposer de se connecter à qui l'est déjà fait douter d'avoir été déconnecté.
Il devient donc « Mes réservations » (`/mes-reservations`) pour un voyageur et « Mon espace »
(`/proprietaire`) pour un propriétaire, dans les actions comme dans le tiroir mobile.

**Le libellé nomme l'espace seulement une fois la garde connue.** « Connexion » reste le mot juste
pour un visiteur — « Mon espace » lui demanderait de savoir lequel des deux — mais l'ambiguïté
disparaît dès qu'on sait qui est connecté. Si les deux sessions sont ouvertes, le propriétaire
l'emporte : c'est celle qui a des demandes qui expirent.

**« Devenir hôte » se retire pour un propriétaire** : ça n'a aucun sens pour quelqu'un qui l'est
déjà — c'est la même raison qui vaut à l'intérieur de son espace.

**La barre ne porte plus qu'une action, et « Demander un séjour » n'en était pas une.** Ce bouton
pointait sur `#demande`, la section « le sens inverse » de l'accueil, dont le propre bouton pointait
alors sur lui-même : la barre du site entier promettait un geste qui n'existait nulle part. (Ce geste
existe depuis — `/demande`, voir « La demande dans l'autre sens » — mais la raison qui suit tient
toujours.) Et même réparé, il serait faux — **on ne demande pas un séjour dans l'abstrait, on le demande pour un
logement**, sur `/logements/{slug}/reserver`, une fois qu'on en a choisi un. Un appel à l'action
posé avant ce choix court-circuite l'étape qui donne son sens à tout le reste, et le voyageur a
déjà le moteur de recherche sous les yeux. Le propriétaire, lui, n'a que ce chemin : c'est donc
« Devenir hôte » qui reste.

**« Propriétaires » sort de la navigation avec lui.** Les deux menaient à la même ancre, à trente
pixels d'écart : deux libellés pour une destination, exactement ce qu'on a retiré de `/connexion`.
Le bouton reste, et il dit ce qu'on y fait. Aucune des quatre portes du propriétaire déjà inscrit
n'est touchée — cette ancre-là recrute.

**Un seul objet plein dans la barre, et « Connexion » n'est qu'un mot.** Le bouton garde **l'encre**
(`.btn--ink`, qui passe à la terre au survol) : la terre était pourtant libre, plus rien ne la lui
disputait, mais une barre **fixe**, qui suit la page entière, n'est pas l'endroit où la dépenser —
elle appartient au moteur de recherche et aux boutons du contenu, ceux qui font vraiment avancer.
Et « Connexion » reste un lien de texte : deux pastilles côte à côte pèseraient en permanence sur ce
qu'on lit dessous. **C'est le contraste de poids qui dit lequel des deux est une action**, pas le
contour — l'exception assumée à « pas de bouton sans contour ni fond », parce qu'il ne s'agit pas
ici de distinguer un contrôle d'un décor mais deux contrôles l'un de l'autre, et que le mot
« Connexion » est de toute façon compris de tous. Les deux prennent la hauteur du bouton de menu —
2,625 rem, soit 42 px — pour que le bord droit tienne sur une seule ligne optique.

### Le menu du compte

**Connecté, le mot cède la place à une pastille d'initiales** (`Components/AccountMenu.vue`), et
elle répare une impasse plutôt que d'ajouter un ornement : **se déconnecter n'existait que *dans*
`/mes-reservations` et *dans* l'espace propriétaire**. Depuis l'accueil, une fiche ou le catalogue,
un voyageur connecté n'avait aucune sortie. Sur un téléphone partagé — le cas courant chez nous —
ce n'est pas une gêne, c'est la session de quelqu'un d'autre qu'on prend pour la sienne.

**La pastille dit qui est connecté avant même qu'on l'ouvre**, ce qu'un libellé « Mon espace » ne
fait pas : on lit ses initiales, donc on sait que la session est la sienne. C'est la seule raison
pour laquelle un avatar vaut mieux qu'un mot ici. Elle est **neutre, jamais terre** — un avatar dit
une identité, pas une action, et la barre ne porte qu'un seul objet coloré. Bordée et pleine en
revanche, avec un chevron : un rond d'initiales seul se prend pour une image, et c'est précisément
la convention que la moitié de notre public n'a pas encore rencontrée.

**Elle est visible à toutes les largeurs**, contrairement au mot « Connexion » qu'elle remplace
(caché sous 460 px, où le tiroir prend le relais) : c'est le seul endroit du site public d'où l'on
se déconnecte, et le cacher sur un téléphone reviendrait à le retirer là où il sert le plus.

**Aucun lien inventé.** Chaque entrée pointe sur une route qui existe — c'est la règle qui a fait
retirer « Demander un séjour » de la barre, et un menu de compte est justement le premier endroit
où l'on ajoute « Mon profil » ou « Paramètres » avant d'avoir l'écran derrière. `AccessTest` relit
les destinations écrites dans le composant et **les demande vraiment**, sous la bonne garde.

- **Voyageur** : Mes réservations, puis se déconnecter. C'est tout ce que le compte porte
  aujourd'hui, et l'écrire honnêtement vaut mieux que meubler.
- **Propriétaire** : Demandes, Réservations (avec la pastille de conversations en attente, la même
  que l'onglet de l'espace), Mes logements, Publier un logement — **ordonnés par urgence, pas par
  catégorie**, comme l'espace lui-même.

**Les deux gardes peuvent être ouvertes en même temps** — `web` et `proprietaire` sont deux
sessions distinctes. Le menu montre alors les **deux** espaces, chacun avec sa sortie nommée
(« Quitter l'espace propriétaire »), et le dit en toutes lettres. C'est le seul endroit du site où
cet état est visible ; le taire ferait croire à une déconnexion qui n'a pas eu lieu.

**Le panneau est léger, et chaque ligne porte son pictogramme.** Les intitulés sont en **400** —
c'est une liste de lieux où aller, pas une liste d'alertes ; ils étaient en 600, la sortie en 700
et le nom en 800, et le panneau pesait plus lourd que la page qu'il ouvrait. Le poids est gardé
pour **la ligne où l'on est** (600, pictogramme en terre) : ouvrir le menu depuis « Messages » et y
voir « Messages » comme les autres ne disait pas qu'on y était. **Une seule ligne allumée, la
correspondance la plus précise** — sur `/proprietaire/logements/nouveau`, « Mes logements » et
« Publier un logement » correspondaient toutes deux. Les pictogrammes viennent de `SpaceIcon` et
de la clé `icone` des rubriques de `Support/espaces.js`, au trait et un ton sous le libellé : ils
repèrent, le mot informe. Un test exige un pictogramme **existant** sur chaque ligne. Le panneau
n'a plus de bordure franche mais une ombre longue et douce, s'ouvre depuis la pastille (origine
haut-droite), et la sortie vit en bas, seule, derrière un filet — avec deux sessions, une sortie
nommée par espace.

**Ce n'est pas un `role="menu"`.** Le motif ARIA « menu » impose un focus roulant et casse la
tabulation à laquelle les gens s'attendent ; ce panneau ne contient que des liens et un bouton,
donc c'est une **divulgation** — `aria-expanded` sur le déclencheur, Tab qui traverse, Échap qui
referme et rend le focus, clic dehors et défilement qui referment. Les flèches marchent en plus,
pour qui les essaie.

**La déconnexion est un POST, jamais un lien**, et `AccessTest` tient les deux bouts : le composant
ne pose aucun lien vers `/deconnexion`, et les deux routes répondent **405 au GET**.

Le test regarde les deux couches, parce qu'aucune ne suffit : les **props partagées** côté serveur
(`auth.user`, `auth.owner`), et le **balisage** de `SiteHeader` — l'en-tête étant rendu côté
client, aucun libellé n'existe dans le HTML servi et `assertSee` n'y verrait rien.

`Services/` (`api.js`, `ListingService`, `DestinationService`, `AmenityService`) est le client
de l'API v1 —
`fetch`, aucune dépendance ajoutée. **L'accueil ne l'appelle pas encore** : Inertia lui livre
ses props et le filtrage tient en mémoire. Il entre en jeu le jour où la grille pagine, et
c'est ce que l'application Flutter reproduira. Détail dans
`docs/architecture/frontend-architecture-laravel-vue.md`.

Quatre éléments spécifiques :

- `Composables/usePageMotion.js` — toute la chorégraphie GSAP de la page d'accueil
  (entrée orchestrée, parallaxe, révélations au scroll, tracé du littoral, compteurs). Tout
  passe par `gsap.context()` — le nettoyage au démontage est garanti — et `gsap.matchMedia()`
  coupe le mouvement sous `prefers-reduced-motion`. Le template déclare l'intention par
  attributs : `data-hero-line`, `data-hero-item`, `data-hero-search`, `data-underline`,
  `data-count`, `data-anim`, `data-anim-group`, `data-depth`. **Ne pas réintroduire de
  directive de révélation maison** : ScrollTrigger couvre nativement les cas que
  l'IntersectionObserver seul ratait (scroll rapide, ancre, onglet en arrière-plan).
- `Components/MadagascarMap.vue` — le littoral est la **géométrie réelle du pays** (Natural
  Earth 10 m, domaine public), 561 sommets simplifiés par Douglas-Peucker à 0,45 unité, soit
  sous le pixel à la taille d'affichage : cap d'Ambre, baies de Narinda et d'Antongil, presqu'île
  de Masoala, cap Sainte-Marie sont à leur vraie forme. **Ne jamais retoucher ce tracé à la
  main** : le régénérer depuis la géométrie MDG. Les repères sont **projetés** par les mêmes
  formules, jamais placés à l'œil : `x = 46,100·lon − 1982,1` et `y = 48,574·|lat| − 531,5`
  (équirectangulaire corrigée par le cosinus de la latitude moyenne de l'île). Pour ajouter une
  ville, appliquer ces deux formules à ses coordonnées réelles. Chaque repère porte un `side`
  (`left`/`right`/`top`) et un `dy` réglés à la main : au-delà de dix repères, un placement
  automatique fait chevaucher les étiquettes d'Antananarivo, Ampefy et Andasibe, qui sont sur
  la même bande horizontale. Nosy Be et Sainte-Marie sont des **îles** : leurs repères tombent
  au large du littoral et ont leur propre contour (`.map__isle`), aux mêmes données et sans
  simplification. Nosy Be est à l'échelle exacte ; Sainte-Marie, large de 5 km pour 57 km de
  long, ne ferait que 2,2 unités et disparaîtrait sous son repère : son contour réel est
  agrandi de moitié autour de son centre — la silhouette est juste, la taille est forcée.
- `Components/VaylaMark.vue` — le monogramme : **un V qui est aussi une coche**, la lettre du
  nom et le signe de la vérification dans la même forme. Tracé calculé (bande à largeur
  variable, bras long cambré, coupes d'extrémité inclinées), ajusté par deux quadratiques :
  97 caractères de chemin. Le corps prend `currentColor`, la pointe prend
  `var(--mark-accent, var(--lagon-500))` — sur un fond coloré, redéfinir `--mark-accent`.
  Pas de cadre autour : la forme est libre. Le favicon reprend le même tracé, en dur, dans
  `app.blade.php` ; **son data URI doit rester en guillemets simples avec `<`, `>` et `#`
  encodés**, sinon l'attribut `href` se referme et le SVG fuit dans le document.
- `Components/TrustGauge.vue` — la jauge à quatre segments. C'est la signature de la maison :
  elle apparaît sur chaque annonce, dans l'échelle détaillée et dans la maquette téléphone.
  Toute nouvelle surface qui nomme un logement doit l'afficher.
- **Photos : uniquement de vraies photographies de Madagascar.** Issues de Wikimedia Commons
  sous licence libre, dans `public/images/lieux/<clé>-<largeur>.webp`, déclarées par
  `PhotoSeeder` avec légende, auteur, licence, page source et largeur.
  `photoCredits()` alimente le bloc « Crédits photo » du pied de page : **CC BY et CC BY-SA
  l'exigent, ne pas supprimer ce bloc**. Deux usages :
  - les **destinations** de l'atlas (560 × 420) ;
  - les **annonces de démonstration**, clés préfixées `an-` (800 × 600). Elles n'existent que
    pour présenter la mise en page : le bandeau « Aperçu » dit à l'écran que les annonces sont
    fictives, et la légende du crédit nomme le vrai lieu photographié. Ces clés `an-`
    disparaissent le jour où de vraies annonces arrivent.
  - **Les intérieurs des annonces de démonstration sont des images générées, et le disent.**
    Aucune banque libre ne fournit de chambre, de séjour ou de cuisine à Madagascar, et on ne
    juge pas une fiche de logement sur une photo de place de village. Ces images portent la clé
    `ia-`, la colonne `is_ai`, la mention « image générée » dans la visionneuse et une liste
    **séparée** au pied de page — les fondre dans les crédits Commons les ferait passer pour
    des prises de vue. `PhotoFilesTest` vérifie la règle dans les deux sens : pas de clé `ia-`
    sans `is_ai`, pas de `is_ai` sans clé `ia-`, et jamais de page source sur une image
    générée. Les prompts sont dans `docs/photos-annonces-ia.md`, la chaîne d'import dans
    `scripts/import-photos-ia.py`. Ces clés disparaissent avec les `an-`.
  - **Les destinations et l'atlas, eux, ne prennent que de vraies photographies.** Là il
    s'agit de lieux réels : une image générée y serait un mensonge sur Madagascar, pas une
    maquette.
  - **Aucune banque d'images générique** (Unsplash, Pexels…) : si ce n'est pas Madagascar, ça
    n'entre pas. Une plage des Maldives étiquetée « Nosy Be » ferait exactement ce que Vayla
    reproche aux annonces volées. Une galerie **reste cohérente avec sa région** — une photo de
    Foulpointe sous une annonce de Majunga viole la même règle, bandeau « Aperçu » ou pas.
  - **Regarder chaque photo avant de la retenir.** Un titre convaincant sur Commons ne dit rien
    du cadrage : trois candidates ont été écartées après coup (une voiture dans une rue, un
    portrait de personne, une image illisible). Une planche-contact suffit à trancher.
  - **Trois résolutions par photo — 800, 1600 et 3200 px** (recadrage centré 4/3, WebP), et
    **jamais d'agrandissement** : la colonne `photos.width` porte la plus grande réellement
    produite, et `resources/js/Support/photo.js` arrête le `srcset` là. Un `srcset` qui
    promettrait un 3200 inexistant ferait télécharger un 404 — et sur une connexion malgache un
    aller-retour perdu se paie cher. Les cinq fichiers qui étaient des **agrandissements d'un
    original de 1 000 px** ont été remplacés par des photographies de 7 600 à 8 700 px : la
    photo de tête de la villa d'Ambatoloaka était un flou à 1 024 px sur un cadre de 1 300.
  - **`sizes` se mesure, il ne s'estime pas.** La photo de la page destination annonçait 660 px
    pour un cadre de 1 304 : le navigateur descendait au fichier 800 et l'affichait mou.
    Ouvrir la page et lire `getBoundingClientRect().width` est le seul moyen sûr.
  - **`PhotoSeeder` ne supprime que dans `lieux`, le dossier qu'il possède.** Sa suppression des
    clés retirées visait toute la table : un `make seed` effaçait les photos téléversées par les
    propriétaires (`annonces`) et par l'équipe (`destinations`), fichiers laissés orphelins. Un test
    rejoue le seeder et vérifie qu'elles ont tenu.
  - **Les crédits du pied de page ne listent pas les photos des propriétaires** : elles sont à eux,
    sans auteur ni licence à citer (`PhotoService::credits`). Un crédit sans page source ou sans
    page de licence s'affiche sans lien plutôt qu'avec un lien vide.
  - `images/annonces/`, `images/destinations/` et `images/proprietaires/` portent chacun un
    `.gitignore` : ce qu'on y téléverse en développement — photos de logements, portraits — n'a
    rien à faire dans le dépôt.
  - `PhotoFilesTest` vérifie qu'aucune clé n'est sans fichier, qu'aucun fichier n'est orphelin,
    qu'aucune photo n'est publiée sans auteur, et que `width` ne dépasse pas ce que porte le
    disque. `PhotoSeeder` **supprime** les clés retirées du catalogue : sans ça elles restaient
    créditées au pied de page alors qu'elles ne s'affichaient plus nulle part.
- `Components/SceneArt.vue` — illustrations SVG générées par variante (`lagoon`, `lake`,
  `sunset`, `highland`, `beach`, `forest`). Sert désormais de **repli** quand une annonce n'a
  pas de photo, et illustre la maquette téléphone.
- Le rail de catégories garde des **pictogrammes au trait**, pas des photos : c'est un filtre,
  pas une galerie. Son titre (« Les envies du moment ») est **éditorial, jamais statistique** :
  cette position a vocation à être vendue à des propriétaires ou des offices de tourisme, et
  « populaire » ou « les plus visités » deviendraient alors un mensonge mesurable — intenable
  sur un site dont toute la promesse est la vérification. Une place achetée devra porter une
  mention explicite.
- **L'accroche du hero contraint l'échelle typographique.** Sa seconde ligne porte le
  surlignage `.uline`, qui est en `white-space: nowrap` : au-delà d'environ 20 caractères elle
  déborde à 390 px. Les deux bornes de `.display--xl` sont calées là-dessus — le minimum sur
  « Vérifiés avant vous. », le maximum pour que « Villas et appartements meublés. » tienne sur
  une ligne. Changer le texte de l'accroche impose de revérifier les deux.
- `Components/SearchPanel.vue` — le moteur, en deux formes : complet (hero) et `compact`
  (résumé encastré dans l'en-tête au-delà de 320 px de défilement). Les deux partagent l'état
  via `modelValue`, la grille n'a qu'une source de vérité.

### Couches : la règle qui structure tout le backend

`Http` → `Data` → `Services` → `Repositories` (via `Contracts`) → `Models`. Le haut connaît le
bas, jamais l'inverse. Les schémas complets et le détail des divergences assumées avec le
projet de référence sont dans **`docs/architecture/`** — les lire avant d'ajouter une couche.

En une phrase par couche :

- **`Http/Controllers`** reçoit la Request, appelle un service, retourne la réponse. Ni requête
  Eloquent, ni règle métier, ni `$request->validate()` en ligne. Les services s'injectent dans la
  méthode quand un seul geste s'en sert.
- **`Http/Requests`** valide et borne (`per_page` non borné = déni de service), puis **fabrique le
  DTO** (`toDto()`) : le service ne voit jamais la Request. Une page en GET qui lit des critères
  d'adresse (`/demande`, `/phototheque`) **nettoie dans `prepareForValidation()`** plutôt que
  d'échouer — un lien partagé au critère périmé doit ouvrir la page.
- **`DTOs`** (classes `final readonly`, sans magie) : **ce qui entre** dans un cas d'usage —
  `UploadTeamPhotoDto`, `SubmitStayRequestDto`… Plus de tableaux associatifs entre couches.
- **`Data`** (spatie/laravel-data) : **ce qui sort** vers le front et l'API — le contrat public,
  là qu'on absorbe l'écart entre le schéma et l'écran. Une page Inertia peut être un `Data` entier
  (`PhotoLibraryPageData`) : `Inertia::render()` accepte un `Arrayable`.
- **`Services`** portent les règles, **une classe par cas d'usage** (`TeamPhotoUploader`,
  `PhotoCreditEditor`, `StayRequestWorkflow`…) plutôt qu'un service fourre-tout ; les lectures à
  part (`…Query`). Transactions et journal ici.
- **`Contracts`** : toute dépendance métier s'injecte par son interface — `Contracts/Repositories`
  pour les données, `Contracts/Photos` (traitement d'image, stockage), `Contracts/Destinations`,
  `Contracts/Office` (journal, mots de passe), `Contracts/Settings`, `Contracts/Ai`,
  `Contracts/Currency` (le taux de change), `Contracts/Verification` (les canaux de code),
  `Contracts/Listings`, `Contracts/Invoices`, `Contracts/Bookings`. Liaisons dans
  `AppServiceProvider::register()`. Des interfaces
  **étroites** : la photothèque a son `PhotoLibraryRepositoryInterface` plutôt que d'alourdir celui
  de la lecture publique.
- **`Repositories`** sont le seul endroit qui parle Eloquent — lecture **et écriture** : un
  service n'appelle ni `->save()` ni `->forceFill()`. `DB::transaction` reste au service : c'est
  lui qui sait ce qui forme un tout.
- **`Models`** : relations, casts, scopes simples. **Les règles qui dépendent d'une valeur vivent
  sur l'enum** (`PhotoProvenance`, `PhotoLicence`, `BookingOutcome`, `StatsPeriod`, comme
  `TrustLevel`) : testables sans base.
- **`Exceptions`** : les refus métier (`OfficeRefusal`, `OfficeThrottled`, `BookingRefusedException`,
  `PhotoRefusedException`, `CodeThrottled`, `LiaisonRefusee`…) et les « introuvable » qui sortent en
  404 (`ListingNotFoundException`, `DestinationNotFoundException`, `BookingNotFoundException`,
  gestionnaire unique dans `bootstrap/app.php`), jamais rangés avec les services qui les lèvent.
- **`Middleware`** : ce qui traverse tous les écrans (compteurs de la colonne, racine des URL),
  sous les mêmes règles qu'un contrôleur — `OfficeContext` passe par `OfficeCountersQuery`.

Deux pièges rencontrés en appliquant ces règles :

- **Un `Data` rendu tel quel répond `201 Created` à un POST.** C'est le comportement de
  spatie/laravel-data ; pour une lecture en POST (l'aperçu d'une page), `response()->json($data)`.
- **Le cache ne garde que des valeurs simples** : `cache.serializable_classes` est à `false`, un
  objet `Data` y reviendrait en `__PHP_Incomplete_Class`. `SitePages` met en cache des tableaux et
  construit les `Data` à la lecture. Même règle que la session, sérialisée en JSON.

**Tout le backend suit ces règles, et `ArchitectureTest` les tient sur des dossiers entiers** —
`Http/Controllers`, `Http/Middleware`, `Services`, `Console/Commands` : un fichier qu'on ajoute est
tenu d'emblée. Le test y interdit Eloquent (lecture comme écriture), le SQL, la validation en
ligne et la Request dans un service. La mise en conformité s'est faite en trois lots : la
photothèque et les demandes de séjour (1), le back-office (2), puis le site public, la
réservation, les deux espaces et l'inscription (3). Seules exceptions, nommées dans le test : les
deux commandes qui fabriquent un jeu de démonstration (`vayla:historique-demo`,
`vayla:comptes-test`), locales et assimilées aux seeders.

Ce que le lot 3 a posé, et qui sert partout :

- **`OwnerSpaceRepository` + `OwnerSpace` sont la portée de l'espace propriétaire.** Toute
  réservation, tout logement s'y cherche **avec le propriétaire** ; celui d'un confrère répond
  exactement comme ce qui n'existe pas (404 par `BookingNotFoundException` /
  `ListingNotFoundException`). Le contrôle vivait dans chaque contrôleur ; il vit maintenant à un
  endroit.
- **`DemoMode` est le seul lecteur de `vayla.demo`** : les services le reçoivent, les contrôleurs
  et l'API ne lisent plus la configuration.
- **Les pages sortent en `Data` jusque dans leurs sous-parties** — le calendrier
  (`ListingCalendarData`, avec `sansBornes()` pour l'espace propriétaire), la saison
  (`SeasonData`), la facture (`InvoiceData`), le fil (`MessageData`), la fiche d'annonce
  (`ListingFormData`, reprise par le back-office via `champs()`).
- **Les contrats partagés avec le back-office prennent des DTO** : `ListingDrafting` reçoit un
  `ListingFicheDto` (le même que celui du back-office, construit par `OwnerListingRequest` et
  hérité par `OfficeListingRequest`), `InvoiceCalculator` rend des `InvoiceData`.
- **Un refus d'image est une `PhotoRefusedException`**, pas une `RuntimeException` quelconque : un
  contrôleur qui attrapait `RuntimeException` pour afficher « il faut 1 200 pixels » avalait aussi
  le `ListingNotFoundException` d'une annonce qui n'était pas la sienne, et répondait 302 au lieu
  de 404.

Deux pièges de plus :

- **`champs()` s'écrit à la main, jamais `get_object_vars($this)`** : sur un `Data`, il rend aussi
  les propriétés internes de spatie/laravel-data, et l'étalement dans un constructeur échoue.
- **Une sous-classe de FormRequest ne peut pas redéfinir `toDto()` avec un autre type de retour.**
  `OfficeListingRequest` hérite d'`OwnerListingRequest` : le propriétaire a `toDraftDto()`, le
  back-office `toDto()`, et les deux partagent `ficheDto()`.

### Domaine

Dix tables — `photos`, `destinations`, `categories`, `listings`, `amenities`,
`unavailabilities`, `stay_confirmations`, `owners`, `bookings` (+ les pivots
`category_listing`, `amenity_listing` et `listing_photo`) — et douze invariants qui ne se
négocient pas :

- **L'échelle de confiance est un enum, pas une table** (`App\Enums\TrustLevel`). Quatre
  niveaux : déclarée, contact confirmé, logement visité (visio ou correspondant local), séjour
  confirmé. Les rendre éditables permettrait qu'un niveau change de sens sous les annonces
  déjà vérifiées. Toute surface qui nomme un logement lit cet enum ; aucun libellé de niveau
  n'est réécrit ailleurs (l'API les publie sur `/api/v1/trust-levels` pour l'app mobile).
- **« Séjour confirmé » n'est jamais stocké** dans le pivot : il se déduit de
  `trust_level = 4`, dans `ListingData` et `ListingRepository`. Deux écritures pour un même
  fait, et l'échelle finit par mentir sur l'une des deux.
- **Ni `place` ni `region` sur `listings`** : ils se lisent sur la destination, et
  `ListingData` les remet à plat pour le front. Les compteurs par destination sont **calculés**
  depuis les annonces — aucun chiffre affiché n'est saisi à la main.
- **`config('vayla.demo')`** (env `VAYLA_DEMO`, `true`) tient deux choses ensemble : les huit
  annonces fictives servies **et** le bandeau « Aperçu » qui le dit à l'écran. Les séparer
  rendrait possible d'afficher des annonces fictives sans le signaler. À `false`, la grille se
  vide et la page bascule sur son état « aucun logement ». Les annonces de démo portent
  `is_demo` et viennent de `ListingSeeder`, qui ne doit plus tourner en production le jour où
  de vraies annonces arrivent.

- **Les équipements sont une table, leurs rubriques un enum** (`App\Enums\AmenityGroup`).
  La liste des équipements s'allongera au contact des logements réels — ajouter une ligne est
  une opération de donnée. Le sens d'une rubrique, lui, ne doit pas glisser sous les annonces
  déjà remplies, donc onze rubriques figées. L'ancien `perks` en JSON de texte libre est
  **supprimé** : il ne se filtrait pas, et « Wifi fibre », « wifi » et « WIFI » y étaient trois
  choses différentes.
- **« Y aller » est de la donnée, pas de la prose.** Sept colonnes typées sur `destinations`
  (aéroport, vol, route nationale, kilomètres, durée, note) plutôt qu'un paragraphe : « à
  moins de quatre heures de Tana » doit pouvoir devenir un filtre. `road_hours` reste une
  chaîne parce que la vérité est une fourchette — « 3 à 4 h » — et qu'un entier laisserait
  croire à une précision que les routes malgaches n'ont pas. Le bloc affiche toujours son
  avertissement : durées indicatives, très variables selon la saison. Antananarivo n'a ni vol
  ni route depuis elle-même, et les colonnes nullables portent ce cas sans rien inventer.
- **Sur une destination, la répartition par barreau, jamais une moyenne.** « 6 logements,
  dont 2 au niveau séjour confirmé » informe ; « niveau moyen 2,8 » ne veut rien dire. Les
  barreaux à zéro s'affichent aussi : une échelle amputée de ses barreaux vides ne se lit plus
  comme une échelle.
- **Cinq destinations sur onze n'ont aucune annonce, et c'est le cas majoritaire.** L'atlas
  affiche « aucun logement » plutôt que de masquer la ligne, et la page destination donne à
  son état vide toute la place de la grille — c'est là que la demande de séjour prend le
  relais. Un atlas qui ne montrerait que les destinations pourvues serait flatteur et faux.
### L'inscription, par adresse e-mail

**En développement, les codes partent dans un bac à sable.** `MAIL_MAILER=log` reste le défaut de
`.env.example` — envoyer pour de vrai doit être un choix explicite, jamais le résultat d'un oubli
de configuration, sinon la mise au point d'un écran part sur de vraies boîtes. Pour recevoir les
codes localement, un bac à sable SMTP (Mailtrap, Mailpit…) : les messages y sont **capturés,
jamais remis à un vrai destinataire**, donc on peut tester avec une adresse réelle sans écrire à
personne. Les identifiants vont dans `laravel/.env`, **jamais dans `.env.example`**, qui est
versionné.

Et le piège habituel s'applique : `env_file` fige le `.env` à la **création** du conteneur — après
la bascule, `docker compose up -d --force-recreate app`, sinon la variable garde son ancienne
valeur. `php artisan vayla:code <adresse>` vérifie la chaîne complète et **dit le canal** avant
d'envoyer.

**L'e-mail est le canal d'inscription parce que c'est le seul qui part automatiquement** sans
entreprise enregistrée ni carte bancaire. WhatsApp exige une entreprise vérifiée chez Meta, le
SMS un compte payant : les deux attendent derrière la même interface (`CodeSender`), et le jour
où ils s'ouvrent, seule la résolution du canal change.

**Le compte n'existe qu'après le code.** L'inscription vit en session (`PendingRegistration`)
jusqu'à la saisie. Créer une ligne « non vérifiée » à la première étape permettrait de **squatter
l'adresse de quelqu'un d'autre**, qui se verrait ensuite refuser son inscription — un test le
vérifie en abandonnant une inscription puis en la reprenant. Le mot de passe est haché dès la
première étape : il traverse la session, et un mot de passe en clair qui transite quelque part
finit par y rester.

**Un code, pas un lien.** Un lien de vérification ouvre le navigateur par défaut du téléphone,
qui n'est pas celui où la session est ouverte : l'utilisateur atterrit déconnecté sur une page
qui dit « vérifié » sans que rien n'avance. Le code est **dans l'objet du message** — sur un
téléphone, la notification affiche l'objet, on le lit sans ouvrir la boîte. Et **aucun lien
cliquable dans l'e-mail** : un message de compte qui apprend à cliquer prépare l'hameçonnage
suivant.

**Les cinq bornes du code sont écrites une fois** (`VerificationCodeService`) et valent pour
l'adresse comme pour le numéro : hachage, cinq essais par code, soixante secondes avant renvoi,
cinq codes par heure, invalidation du précédent. Les recopier par canal aurait garanti qu'une des
deux versions finisse par mentir.

**`Auth/Code.vue` sert les deux inscriptions.** Deux écrans identiques auraient divergé sur le
renvoi ou sur le message d'erreur, et l'un aurait fini par laisser passer ce que l'autre refuse.

**Six cases dessinées, un seul champ réel dessous.** C'est le point qui décide de tout le reste :
six `<input>` séparés — la solution qu'on écrit d'instinct — cassent le collage du code, cassent le
remplissage automatique `one-time-code` (celui qui propose le code depuis la notification, sans
changer d'application), et obligent à déplacer le focus à la main, ce qui se retourne toujours
contre le clavier et les lecteurs d'écran. Le champ est unique, transparent, posé **par-dessus**
les cases ; les cases ne sont qu'un affichage (`aria-hidden`). `AccessTest` compte les `<input>` —
l'écran *ressemble* à six champs, et c'est exactement ce qui donnera envie d'en écrire six à la
prochaine refonte.

Ce que les cases apportent : **on voit combien il en reste** sans le lire, ce qu'un champ unique de
six chiffres ne dit pas — et c'est le besoin de quelqu'un qui recopie en va-et-vient entre sa
notification et l'écran. La case qui attend porte la terre et un curseur qui bat : une bordure
colorée seule ne dit pas « tapez ici ».

**`useCodeMotion` porte trois gestes, et chacun est une information, pas un agrément** — c'est le
seul écran du parcours où l'on attend quelque chose. Le chiffre tombe dans sa case (l'accusé de
réception que le clavier virtuel ne donne pas) ; le refus **secoue la rangée entière**, jamais une
case — le serveur ne dit pas quel chiffre est faux, et il aurait tort de le dire ; le code complet
se referme en cascade. On ne réagit qu'à l'**allongement** de la saisie : effacer ne doit pas
déclencher la même pression, sinon le geste dit « c'est bon » au moment où l'on corrige.

**Le sixième chiffre valide tout seul.** Sur un téléphone, le clavier occupe la moitié de l'écran
et cache le bouton : beaucoup tapent le code puis attendent. Le bouton reste — il est le contrôle
visible — mais il n'est plus le seul chemin.

**Le renvoi porte son délai dans la phrase** : « Vous n'avez pas reçu d'e-mail ? Veuillez vérifier
vos spams ou demander un nouveau code dans 42 secondes. » Le lien reste **lisible** pendant
l'attente au lieu de s'effacer — c'est lui qui porte l'information. « Spams » et non
« indésirables » : c'est le mot que les gens lisent dans leur boîte, et un écran de secours n'est
pas l'endroit où corriger le vocabulaire de quelqu'un.

Trois défauts que cet écran a révélés, tous invisibles en lisant le code :

- **Le décompte ne repartait pas après un renvoi.** `reste` était initialisé une fois par
  `ref(props.attente)` ; Inertia réutilisant le composant, la nouvelle valeur n'était jamais lue,
  et on pouvait recliquer aussitôt. Il **suit la prop** maintenant — le délai est calculé par
  `VerificationCodeService`, seul à savoir quand le dernier code est parti, et le `60` écrit côté
  client était une seconde source de vérité.
- **Le renvoi refusé ne disait rien.** La validation du code passe par `useForm`, donc par
  `form.errors` ; le renvoi part en `router.post` et ses erreurs atterrissent dans les props de
  page, **jamais** dans `form.errors`. Un refus — cinq codes par heure — était donc muet.
- **Le lien restait actif pendant l'envoi.** L'appel SMTP est synchrone : plusieurs secondes
  s'écoulent entre le clic et la réponse, pendant lesquelles rien ne bougeait. On recliquait, et un
  second code partait pour rien. Le lien se ferme et dit « envoi en cours… ». *Le jour où le
  volume le justifiera, c'est l'envoi qu'il faudra mettre en file — pas l'écran qu'il faudra
  retoucher.*

#### Côté voyageur : une adresse, un code, et rien d'autre

`/inscription` — **un seul champ.** L'écran demandait un nom, une adresse et un mot de passe à
saisir deux fois. Il ne demande plus que l'adresse : un code part, on le saisit, on est dedans.

**La même porte inscrit et connecte.** `/inscription` et `/connexion/client` posent la même
question et **postent sur la même route** ; c'est le code qui décide — le compte existe, on entre,
il n'existe pas, il s'ouvre. Faire choisir *avant* entre « créer un compte » et « se connecter »,
c'est demander de trancher une question dont beaucoup n'ont pas la réponse : on ne sait plus si on
s'est inscrit un jour, et on se trompe de porte. Seule l'accroche des deux écrans diffère, pour
que celui qui revient et celui qui découvre se reconnaissent chacun.

**Plus de mot de passe du tout.** Le code prouve l'adresse **à chaque connexion** ; un mot de
passe ne prouve jamais que ça — seulement qu'on connaît une chaîne. Et sur un premier compte en
ligne, ce n'est pas la connexion qui fait perdre les gens, c'est le mot de passe oublié. Il n'y a
donc plus rien à inventer, à retenir, ni à récupérer. Conséquence : `users.password` et
`users.name` sont **nullables**, et `/connexion/client` n'a plus de champ mot de passe.

**Le nom n'est plus demandé à l'inscription.** Il l'est à la demande de séjour, où il a une raison
d'être — le propriétaire doit savoir qui arrive. `/mes-reservations` retombe donc sur « Vos
séjours » quand il n'y a pas encore de nom : `traveller.name.split(...)` levait sur tout compte
neuf.

**La porte ne dit jamais si l'adresse est connue.** La règle `unique:users,email` a sauté — elle
refusait une adresse déjà connue, ce qui n'a plus de sens ici, mais surtout elle **disait** qu'un
compte existait, adresse par adresse. Le code, lui, part dans tous les cas : seul celui qui relève
la boîte apprend quelque chose. Un test compare les deux réponses, connue et inconnue.

**Six chiffres, pas six caractères alphanumériques** comme le fait Booking.com. Sur un clavier de
téléphone, un code numérique ouvre le pavé chiffres, se dicte sans épeler, et évite les O/0 et
I/1 — l'ambiguïté que le format des références nous a déjà coûtée une fois.

Ce que le compte apporte : `/mes-reservations`, où **les séjours se rattachent par l'adresse
e-mail**. Celui qui a réservé avant de créer son compte les retrouve dès l'inscription, sans
recollage manuel. Le compte ne conditionne toujours rien — demander un séjour reste possible sans
lui.

#### Côté propriétaire

`/proprietaire/inscription` — **trois temps : adresse, code, fiche.**

**L'adresse d'abord, seule.** Un formulaire de six champs devant quelqu'un qui n'a encore rien
reçu de Vayla est un formulaire qu'on quitte. Le nom, le numéro WhatsApp et le mot de passe
arrivent **après** le code (`/proprietaire/inscription/fiche`), quand la personne a déjà fait un
geste et vu que le service répond. Le compte naît à la troisième étape, pas au code : rien en base
tant que la fiche n'est pas remplie, donc aucun compte propriétaire vide à nettoyer. Un garde
refuse la fiche sans adresse vérifiée en session — sans lui, on créerait un compte en postant
directement.

**Le propriétaire garde son mot de passe, et c'est la seule asymétrie avec le voyageur.** Son
identifiant de connexion est **son téléphone** : il revient toutes les semaines, souvent pour
répondre à une demande qui expire, et son e-mail n'est pas la boîte qu'il relève — attendre un
code à chaque connexion le mettrait dehors au pire moment. Le voyageur revient trois fois par an
et relève ses mails : le code lui suffit.

Le reste tient toujours :

**La ville du logement a été retirée.** Elle décrivait le *logement*, pas le compte, et la fiche
d'annonce la demande déjà — la destination y est un champ à part entière, **choisi dans une liste**
plutôt que tapé à la main. La demander deux fois ouvrait la possibilité que les deux réponses
diffèrent, et faisait payer un champ de plus au moment où l'abandon coûte le plus cher. La colonne
`owners.city` **reste** : elle est nullable, l'espace propriétaire et `vayla:rotate-owner-key`
l'affichent, et elle se remplit à l'appel de vérification.

**S'inscrire n'est pas publier, et l'écran le dit avant le formulaire.** Le compte s'ouvre tout seul, mais l'annonce naît au niveau 1 et c'est l'appel de
vérification qui la met en ligne. Un propriétaire qui découvre trois jours plus tard que son
annonce n'était pas publiée ne revient pas ; celui qui le sait dès le départ attend l'appel. Les
trois étapes et la jauge de niveau 1 sont affichées au-dessus des champs.

Après la validation du code, il est déposé **directement sur le formulaire de son premier
logement** : c'est ce qu'il est venu faire, et un tableau de bord vide ne le lui dirait pas.

**Le numéro WhatsApp est obligatoire** — c'est par là que Vayla rappelle — mais pas encore
prouvé : `phone_verified_at` reste nul, c'est l'e-mail qui l'est. Le numéro le sera à l'appel,
qui est de toute façon le passage obligé.

**Le numéro et l'adresse sont normalisés dans `prepareForValidation()`, pas après.**
`unique:owners,phone` compare des chaînes : la saisie brute « +261 34 00 000 01 » ne ressemble
pas au « +261340000001 » stocké, et la règle laissait donc passer un **second compte sur le même
numéro écrit autrement** — deux comptes indiscernables au téléphone, dont un seul recevrait le
lien d'accès. Un test le tient.

**Les trois boutons « Publier un logement » mènent enfin quelque part** : ils pointaient tous sur
l'ancre `#proprietaires`, c'est-à-dire sur eux-mêmes.

### Les écrans d'accès partagent un cadre : `AccessShell`

**Huit écrans, une seule architecture** — `/connexion`, `/connexion/client`, `/inscription`,
`/inscription/code`, `/proprietaire/connexion`, `/proprietaire/inscription`,
`/proprietaire/mot-de-passe` et `/proprietaire/mot-de-passe-oublie`. Chacun portait sa propre
carte blanche sur un aplat gris : huit fois le même fond, huit barres de marque, huit jeux de
classes aux préfixes différents (`.rg__`, `.lg__`, `.cd__`, `.or__`, `.pw__`, `.fg__`…). Ils
avaient **déjà commencé à diverger** — deux rayons de bordure, trois tailles de titre — et chaque
correction demandait huit passages. C'est la leçon d'`OwnerShell`, appliquée un cran plus tôt.

`Components/AccessShell.vue` porte le fond (lueur de latérite + grain), le monogramme tracé, la
sortie vers l'accueil, le sur-titre, le titre et l'accroche. `resources/css/app.scss` porte les
primitives de formulaire (`.acces__panel`, `.acces__field`, `.acces__input`, `.acces__err`…) —
c'est la feuille globale, là où vivent déjà `.btn` et `.chip`. **La hauteur de champ n'est pas
négociable : 2,75 rem**, la cible tactile minimale.

Deux réglages, pas plus : `largeur` (`etroit` pour un formulaire, `large` pour l'aiguillage et
l'inscription propriétaire) et `sortie`. `AccessTest` vérifie que les huit écrans montent le cadre,
et que **seuls les écrans d'étape** passent `:sortie="false"`.

**Le vert d'une règle satisfaite est de l'encre, pas du lagon.** « Huit caractères au minimum » qui
passerait au lagon une fois rempli ferait sortir la couleur de son seul rôle — « vérifié » — et
rendrait l'échelle de confiance illisible d'un coup d'œil.

**Une auto-marge en ligne désactive l'étirement en grille.** `.acces__panel` se centre par
`margin-inline: auto` quand il est seul sous le cadre ; posé dans la grille à deux colonnes de
l'inscription propriétaire, cette même marge absorbait l'espace au lieu de le remplir et laissait
240 px de colonne vides. La grille remet `margin-inline: 0`.

### Ces écrans ne portent pas le chrome du site

Aucun d'eux n'a **ni en-tête ni pied de page**. Une page qui demande « qui
êtes-vous ? » ou un mot de passe ne peut pas rouvrir au même instant le catalogue, les
destinations et « Devenir hôte » : ce sont exactement les chemins qu'elle vient de refermer pour
poser sa question. Le pied de page fait pire — il rouvre le site entier sous une question qui
n'attend qu'une réponse.

**Mais aucun de ces écrans n'est un cul-de-sac.** La sortie est **bordée et pleine** plutôt que
posée nue : un logo qui se clique est une convention, et c'est précisément la sorte de savoir que
la moitié de notre public n'a pas. Sur un téléphone, la seule autre issue serait le bouton retour
du navigateur, que personne ne cherche quand on ne sait pas qu'on s'est trompé de page.

**Elle revient d'un pas, pas à l'accueil.** Elle y ramenait : depuis un écran atteint par
l'aiguillage, c'était deux pas en arrière au lieu d'un, et il fallait refaire le choix qu'on venait
de faire. Chaque écran déclare donc son parent (`retour`) :

| Écran | Revient sur |
|---|---|
| `/connexion` | `/` |
| `/connexion/client`, `/inscription`, `/proprietaire/connexion`, `/proprietaire/inscription` | `/connexion` |
| `/proprietaire/mot-de-passe-oublie` | `/proprietaire/connexion` |

**Un parent déclaré, et surtout pas `history.back()`.** Cette version a été écrite, essayée, et
jetée : `history.length > 1` compte la page « nouvel onglet », si bien qu'un onglet ouvert sur un
lien reçu par WhatsApp — le cas majoritaire chez nos propriétaires — sortait du site au premier
clic (constaté : atterrissage sur `chrome://newtab`). Le navigateur ne laisse pas lire les entrées
d'historique, donc rien ne permet de savoir si la précédente est chez nous. Un parent déclaré est
toujours juste, et ces écrans n'ont de toute façon qu'un seul pas en amont.

**La sortie ne dit donc plus « vayla », elle dit « Retour ».** Un monogramme qui ne ramène pas à
l'accueil est un faux signal, et la convention est trop installée pour qu'on la retourne en
silence. La marque reste dans la pastille comme ancrage, le mot dit ce que le clic fait. Sur les
deux écrans d'étape, où il n'y a pas de sortie, la marque **inerte** garde « vayla » : elle situe
la page sans promettre d'action.

**Deux exceptions, et la même raison pour les deux** : on n'y arrive pas, on y est *pendant*
quelque chose. Sur `Auth/Code`, l'inscription vit en session — partir coûte tout ressaisir, et la
sortie est « Corriger », qui revient au formulaire sans rien perdre. Sur `Owner/Password`, le garde
ramène ici tant que le mot de passe n'est pas posé : un lien qui ne mène nulle part est un faux
signal. Les deux passent `:sortie="false"`, et le cadre rend alors la marque **inerte** — sans
bordure ni fond, pour ne pas annoncer une action qui n'existe pas.

### L'aiguillage de connexion

`/connexion`, atteint par **« Connexion » dans l'en-tête** — le mot que tout le monde connaît,
placé avant « Devenir hôte » : celui qui revient cherche une porte, celui qui découvre lit
d'abord la page.

**Deux cartes, deux grosses icônes, aucun formulaire.** La version précédente mettait les deux
formulaires côte à côte : on arrivait sur quatre champs et deux boutons **sans avoir décidé où
l'on allait**, et un voyageur pouvait commencer à taper dans le formulaire du propriétaire. Une
page qui ne demande qu'une chose — « qui êtes-vous ? » — se traverse sans y penser, et ce qui
suit est sans ambiguïté. Un test le tient, en regardant le **balisage** et non le fichier entier :
sa première version cherchait le nom du composable n'importe où et se déclenchait sur le
commentaire qui explique justement la règle — un test qui échoue parce qu'on a documenté ce
qu'il vérifie n'apprend rien.

`Composables/useAccessMotion.js` porte l'entrée : le titre, puis les cartes décalées de 0,1 s —
simultanées, elles se liraient comme un seul bloc et le choix disparaîtrait — puis les icônes.
**Une seule entrée orchestrée, et rien après** : cette page n'existe que pour poser une question,
et un mouvement qui continuerait ferait hésiter au moment de choisir. Mêmes règles que partout :
`gsap.context()` pour le nettoyage, `gsap.matchMedia()` pour couper sous `prefers-reduced-motion`,
et **la coupure rend visible** plutôt que de laisser la page à moitié transparente.

**Le seul mouvement qui survit à l'entrée est celui du survol, et il dit ce que fait le geste** :
la clé **tourne de 24°** dans la serrure, la loupe **glisse en diagonale** comme on balaie du
regard. Ce sont les deux verbes de la page joués par les pictogrammes eux-mêmes ; un simple
grossissement au survol aurait fait le même bruit sans rien signifier. Le retour est **plus court
que l'aller** (0,45 s contre 0,6 s) : on avance avec de l'élan — `back.out(2.2)` sur la clé, qui
dépasse et revient comme un vrai barillet — et on se rétracte sans traîner.

Trois contraintes tiennent ce mouvement :

- **La dalle sous l'icône ne bouge pas, c'est le dessin qui bouge.** Faire pivoter le carré
  déplacerait la carte entière du regard ; l'objet tourne *dans* son cadre, comme une clé dans
  une porte qui, elle, reste en place.
- **Le pivot est en coordonnées `viewBox`, pas en CSS.** `gsap.to(…, { svgOrigin: '20 21.4' })`,
  doublé de `transform-box: view-box` : sur un `<g>` SVG, `transform-origin` se résout contre une
  boîte dont la définition varie d'un moteur à l'autre — la clé partait hors de la maison au
  premier essai. `svgOrigin` prend des coordonnées du dessin et ne dépend de rien.
- **Survol *et* focus, jamais l'un sans l'autre** : la règle « rien d'interactif au seul survol »
  s'applique ici comme partout, et sur ces cartes elle est aussi la version clavier du même
  signal.

Le reste des partis pris de l'écran, du dessin des pictogrammes à la place du monogramme :

- **Une loupe et une clé** (`Components/AccessIcon.vue`), dessinées pour cette page et
  **bicolores comme le monogramme** : corps à l'encre, accent à la terre — exactement la
  convention de `VaylaMark`, où le corps prend `currentColor` et la pointe `--mark-accent`. Le
  lagon n'y entre pas : il ne dit que « vérifié », et un pictogramme d'orientation n'est pas une
  vérification. L'accent porte précisément le mot qui distingue les deux cartes — le manche de la
  loupe dit *chercher*, la clé dit *posséder*. Les deux cartes portaient auparavant des maisons :
  à cinquante pixels on ne distingue pas « la maison où je vais » de « la maison que je loue ».
  Grille de **40** et non 24 : ce sont les deux seuls pictogrammes de la page, affichés à 4 rem
  dans des dalles de 7 rem — à cette taille une grille de 24 oblige à des demi-pixels sur les
  arrondis et le trait devient mou.
- **L'accent est plein, le corps est au trait.** La première version dessinait tout au trait, y
  compris la clé : à 2,6 rem elle se lisait, agrandie elle s'est révélée pour ce qu'elle était —
  un fil de fer dans une maison vide. Le manche de la loupe est maintenant un trait épais à bouts
  ronds, la clé un aplat de terre avec son anneau, son trou, sa tige et ses deux dents. **Le trou
  de l'anneau prend `--icon-hole`, qui suit le fond de la dalle** : figé en blanc, il apparaissait
  comme une tache claire dès que la dalle passait en terre au survol.
- **Ce qui n'est pas l'accent reste neutre et fermé** : la maison entoure la clé, le toit se pose
  sur la loupe. C'est le contenant qui dit « logement » et l'accent qui dit ce qu'on en fait —
  deux pictogrammes entièrement terre auraient fait deux taches identiques à distance de lecture.
- **Le titre est à la première personne** — « Je cherche un logement », « Je loue mon logement ».
  On choisit ce qu'on **est**, pas un nom de rubrique ; le nom de l'espace reste en bas de carte,
  comme confirmation de la destination.
- **La sortie est sur l'axe, en haut de la pile.** Posée dans un angle, elle serait le seul
  élément hors du centre et prendrait l'œil avant la question. Au sommet de la composition, elle
  la couronne sans la concurrencer. Sur `/connexion/client`, où le contenu est aligné à gauche,
  les deux sorties tiennent au contraire sur une ligne — la marque à gauche, là où on la cherche
  partout ailleurs, « Changer d'espace » à droite, contre le contenu qu'elle quitte. Empilées,
  on les lirait comme une navigation à deux niveaux, ce qu'elles ne sont pas.
- **Tout est centré, et c'est ce qui fait la page.** Titre, sous-titre, cartes, contenu des
  cartes, ligne d'inscription : un seul axe. La composition alignée à gauche donnait un poids au
  bord gauche et faisait lire les deux cartes de gauche à droite, donc la première avant la
  seconde — exactement la hiérarchie qu'une page de choix ne doit pas avoir. Sur un axe unique,
  les deux propositions sont à égale distance de l'œil.
- **Le V du monogramme est lui-même un carrefour**, et c'est le parti pris de la page : deux
  branches qui divergent depuis un point. Il se **trace** en fond (`useAccessMotion`) — le même
  geste que le littoral de Madagascar sur l'accueil, même langage. Il est **entier et centré**,
  derrière le titre et les cartes, à `min(74vh, 46rem)` et 7 % d'opacité : à cette valeur c'est
  une texture, pas une image — au-delà il se met à concurrencer le texte qu'il porte. Une version
  intermédiaire le posait dans le tiers droit pour équilibrer une composition alignée à gauche ;
  la composition étant centrée, la raison a disparu avec elle. Sous 900 px il s'efface : il
  passerait derrière les cartes empilées, où il brouillerait le texte au lieu de l'asseoir.
- **Le fond n'est pas un aplat** : une lueur de latérite et un grain très fin donnent de la
  matière. La règle « une seule dalle de couleur pleine par page » tient, parce qu'il n'y a pas de
  dalle — il y a de l'air. Sans le grain, les deux dégradés se voient comme des taches sur un
  écran de bureau.
- **Aucune carte n'est mise en avant.** Le rôle de la page est de distinguer, pas d'orienter :
  donner plus de poids à l'une ferait cliquer dessus par défaut, ce qui est exactement l'erreur
  qu'on veut éviter.
- **La terre n'apparaît qu'au survol et au focus** — dalle de l'icône, bordure, libellé et flèche
  ensemble. C'est la couleur de l'action, et une carte au repos n'en est pas une.
- **Il n'y a plus de lien d'inscription sous les cartes.** Il y en avait deux — « Créer un compte
  voyageur », « Inscrire mon logement » — hérités du temps où s'inscrire et se connecter étaient
  deux gestes distincts. Depuis que chaque espace n'a qu'**une porte**, où le code décide si le
  compte s'ouvre ou s'ouvrait déjà, ils menaient exactement où mènent les deux cartes : quatre
  chemins pour deux destinations, sur une page qui ne pose qu'une question.
- La carte entière est le lien : elle ne mène qu'à un seul endroit, donc aucune zone ne se rate
  au doigt — contrairement à une carte de logement, qui porte deux destinations et garde donc son
  bouton séparé.

#### L'espace client

`/connexion/client` ne porte plus qu'**un seul chemin : la connexion au compte.**

**L'entrée par référence a été retirée.** L'écran offrait aussi un champ « J'ai réservé sans
compte » où l'on collait sa référence `VY-…`. Deux portes sur un écran de connexion demandent de
choisir avant de savoir ce qu'on choisit, et la seconde n'était pas une connexion : elle ouvrait
une réservation, pas un compte. Le `POST /connexion/reservation` qui la servait a disparu avec
elle — un point d'entrée mort qui accepte encore des requêtes est une surface d'énumération
gardée sans raison.

**Mais la référence ouvre toujours une réservation** : `GET /reservations/{reference}` n'a pas
bougé, c'est le lien que porte le message de confirmation, et le supprimer aurait cassé toutes les
demandes déjà envoyées. `KeyAccessTest` tient sa limite de débit. Celui qui a perdu son message
passe par « Écrivez-nous », qui était déjà le recours.

**Les références de démonstration doivent avoir exactement la forme de la production** — `VY-`
plus **cinq** caractères, sans O/0 ni I/1. Celles du seeder en portaient six : le formulaire les
refusait, et c'est lui qui a révélé l'écart. Un jeu de démonstration qui s'écarte du format fait
passer pour cassé tout écran qui le valide. `AccessTest` tient la règle sur **toutes** les
réservations en base, la longueur comme les caractères ambigus.

### L'espace propriétaire

`/proprietaire` — **une adresse et un code, comme le voyageur. Plus de mot de passe.**

**L'identifiant est devenu l'adresse.** Il a été successivement une adresse-clé, puis un numéro de
téléphone avec un mot de passe. L'argument du numéro était que beaucoup de propriétaires n'ont pas
de boîte qu'ils relèvent — il ne tient plus depuis que **l'inscription exige une adresse à
laquelle un code arrive vraiment** : sans elle, aucun compte ne s'ouvre. Le téléphone reste ce par
quoi Vayla **appelle** pour la vérification, ce qu'il a toujours été de plus utile.

Ce que la disparition du mot de passe emporte avec elle : `EnsureOwnerHasPassword`,
`Owner/Password.vue` (la pose), `Owner/Forgot.vue` (« mot de passe oublié »),
`OwnerAuthService::connecter` et `poserMotDePasse`, `OwnerLoginRequest` et `OwnerPasswordRequest`.
Il n'y a plus de mot de passe à oublier, donc plus d'écran pour le récupérer. `OwnerAuthTest`
vérifie qu'aucun `type="password"` ne revient sur les trois écrans propriétaire, et que les deux
routes répondent 404.

**Le lien WhatsApp reste, et redevient simple** : il ouvre l'espace en un geste. Le jour où une
demande expire dans quelques heures, aller relever ses mails est un détour de trop — c'est le
chemin court, là où le propriétaire lit vraiment. Il ne pose plus de mot de passe, et c'est
toujours lui qui **prouve le numéro** (`phone_verified_at`) : s'en servir démontre qu'on tient
cette ligne. Entrer par l'adresse ne prouve rien sur le numéro et ne l'écrit donc pas.

**Les propriétaires de démonstration ont une adresse en `@demo.vayla.test`.** Le domaine `.test`
est réservé par la RFC 6761 et non routable : sur `example.com` ces adresses entrent en collision
avec ce qu'un humain tape en essayant le produit — c'est arrivé, un compte réel occupait déjà
`hanta@example.com` — et surtout un code de démonstration ne doit jamais pouvoir partir vers une
boîte qui existe.

**Ce qui suit décrit le modèle précédent et n'est gardé que pour la mémoire du raisonnement.**

**L'identifiant était le téléphone, pas l'e-mail.** C'est par WhatsApp qu'on joint les
propriétaires malgaches ; beaucoup n'ont pas de boîte qu'ils relèvent, et l'exiger écarterait
précisément le public qu'on veut servir. Le numéro est comparé **chiffre à chiffre sur ses neuf
derniers** : « +261 34 00 000 01 », « 034 00 000 01 » et « 0340000001 » ouvrent le même compte.
Refuser une connexion pour une histoire d'espaces serait une porte fermée sans raison — et sur
un téléphone, personne ne cherche pourquoi.

**Le lien d'accès WhatsApp n'a pas disparu : il a changé de rôle.** Il n'ouvre plus l'espace, il
ouvre **le compte** — première connexion, et récupération le jour où le mot de passe est perdu.
C'est ce qui rend un mot de passe tenable ici : celui qui l'oublie, deux mois plus tard, au
moment précis où une demande expire, n'a ni boîte mail à relever ni SMS à attendre — il rouvre
sa conversation WhatsApp. `EnsureOwnerHasPassword` **impose** ensuite d'en poser un : un compte
qui resterait ouvert par le seul lien ramènerait au modèle où l'adresse était le droit d'accès,
avec tout ce qu'il implique dès qu'une conversation est transférée.

**Quatre portes d'entrée depuis le site, et aucune n'était là au départ.** Tous les liens
« Propriétaires » du site public menaient à l'ancre `#proprietaires` de l'accueil, qui **recrute**
(« Votre annonce, enfin crédible ») — un propriétaire déjà inscrit qui avait perdu son lien
WhatsApp n'avait aucun chemin. Elles pointent toutes sur `/proprietaire` et non sur
`/proprietaire/connexion` : celui qui a coché « rester connecté » entre directement, l'autre est
redirigé par le garde.

- **Le pied de page**, colonne Propriétaires — la place de référence, celle qu'on cherche.
- **La dalle terre de l'accueil**, sous les deux boutons : « Déjà propriétaire sur Vayla ? » —
  c'est la seule section où un propriétaire déjà inscrit se reconnaît.
- **L'aiguillage `/connexion`**, atteint par « Connexion » dans l'en-tête — voir plus haut.
- **Le tiroir du menu mobile**, qui pointe sur l'aiguillage et non sur l'espace propriétaire : il
  sert aussi aux voyageurs, et deux entrées séparées y obligeraient à choisir avant d'avoir lu ce
  que chacune ouvre.

**Le numéro est normalisé, pas seulement comparé.** `App\Support\Telephone` ramène toute
saisie en E.164 (`+261340000001`), qui est **la forme de stockage**. La version précédente
rapprochait les **neuf derniers chiffres** : ça marchait pour Madagascar, mais ça reposait sur
une coïncidence de longueur — deux numéros de pays différents finissant pareil auraient ouvert le
même compte. On compare maintenant des numéros, pas des fins de chaîne.

Cinq écritures sont reconnues, et elles viennent toutes du terrain : internationale, `00` du
clavier fixe, zéro national, indicatif sans `+` (ce que produit un copier-coller depuis WhatsApp)
et numéro nu. Les préfixes malgaches sont vérifiés — Orange 32, Airtel 33, Telma 34 et 38, fixe
20 : `+261 99 …` n'existe pas, et le laisser passer ferait échouer un envoi WhatsApp sans que
personne ne sache pourquoi. **Les numéros étrangers passent tels quels** : une part des
propriétaires vit en France ou à La Réunion et loue à Nosy Be — refuser un `+33` écarterait
exactement ceux qui ont les moyens d'équiper un logement. Aucune dépendance ajoutée :
`libphonenumber` pèse dix mégaoctets de métadonnées mondiales pour un produit qui sert un pays.

**La possession du numéro est prouvée par le lien, pas par un code.** `phone_verified_at`
n'invente aucune vérification : il en **enregistre une qui existait déjà**. Vayla envoie le lien
d'accès sur le WhatsApp du propriétaire ; s'en servir pour ouvrir son compte démontre qu'il tient
cette ligne — c'est exactement ce que prouve un code à usage unique, sans fournisseur de SMS ni
coût par message. Se connecter par mot de passe, en revanche, ne prouve rien sur le numéro et ne
l'écrit pas.

Une saisie qui ne désigne aucun numéro est refusée **sur le champ**, avec un exemple, et non par
« numéro ou mot de passe incorrect » — ce dernier laisserait chercher du côté du mot de passe
alors que le problème est ailleurs.

`Owner` est **authentifiable lui-même**, sur la garde `proprietaire`, plutôt que relié à un
`User` générique : il porte des logements, des réservations et une facture — une jointure de plus
à chaque écran n'aurait rien exprimé de neuf. Deux gardes veulent aussi dire deux sessions : un
administrateur ne devient jamais propriétaire par accident.

**Être connecté dit qui l'on est, pas ce qu'on a le droit de toucher.** Chaque action revérifie
l'appartenance — la réservation porte-t-elle sur un logement de ce propriétaire, le slug est-il
le sien. Les références sont courtes et se dictent au téléphone ; sans ce contrôle, une session
valide plus une référence devinée suffiraient à répondre à la place d'un confrère.
`OwnerSpaceTest` et `OwnerCalendarTest` le vérifient.

Sur le mot de passe : **huit caractères, et rien d'autre comme règle de forme.** Pas de majuscule
obligatoire, pas de chiffre imposé — ces exigences produisent des mots de passe notés sur un
papier à côté du téléphone. On refuse en revanche ceux qui figurent dans des fuites connues
(`Password::uncompromised()`) : c'est la seule contrainte qui protège vraiment, et elle ne dit
rien tant qu'on n'a pas tapé « 12345678 ». Le champ est **visible par défaut** à la pose : un
mot de passe qu'on invente, masqué, sur un clavier de téléphone, à saisir deux fois, c'est la
recette d'un compte dont on est enfermé dehors la minute suivante.

**Un échec de connexion ne dit jamais laquelle des deux valeurs est fausse.** « Ce numéro
n'existe pas » confirmerait à un inconnu qu'un autre numéro, lui, est bien celui d'un
propriétaire de Vayla.

**L'écran est ordonné par urgence, pas par catégorie.** Le propriétaire n'ouvre pas Vayla par
curiosité : il l'ouvre parce qu'un message lui dit qu'une demande attend. Les demandes à
répondre viennent donc en premier et sont **le seul bloc qui porte des boutons** ; viennent
ensuite les séjours à venir, les logements, la facture. L'écran porte l'en-tête du site comme
l'espace client — voir plus bas : « Devenir hôte » s'y retire de lui-même pour un propriétaire.

Trois détails qui décident de l'usage :

- **Le temps restant s'écrit en heures**, pas en date d'expiration : « il vous reste 41 h » se
  comprend sans calcul, « expire le 5 à 14 h 12 » demande de savoir quelle heure il est.
- **La commission s'affiche à côté du total, avant la réponse.** La découvrir sur la facture de
  fin de mois, c'est se sentir piégé — et avoir raison. Le taux est figé à la réservation, donc
  connu au moment où on demande de répondre.
- **Refuser demande une confirmation, accepter non.** Accepter est le chemin normal et reste
  réversible ; refuser rend les nuits et ferme la demande. Une main qui glisse sur un téléphone
  ne doit pas coûter une réservation. Le motif saisi part au voyageur : un refus sans
  explication use la relation des deux côtés.
- **Le message de succès est neutre, jamais lagon.** Le lagon ne dit que « vérifié » ; s'en
  servir pour « ça a marché » rendrait l'échelle de confiance illisible d'un coup d'œil.
  **Neutre ne veut pas dire sombre** : c'était un aplat d'encre plein, la chose la plus sombre de
  l'écran au-dessus d'un formulaire blanc — il prenait l'œil comme une alerte alors qu'il dit
  « c'est fait ». C'est une **carte blanche bordée**, portée par une petite marque ronde dont la
  coche se trace à l'arrivée ; l'erreur est la même carte en terre.

  **La coche est verte, et c'est la seule exception à « le lagon ne dit que vérifié ».** Demandée
  explicitement : un disque d'encre se lisait comme un trou noir, pas comme une réussite. Elle est
  **bornée** — la coche seule (fond `--lagon-050`, trait `--lagon-600`), jamais la carte ni le
  texte ; et une **forme que la vérification n'emploie pas** : un rond coché, là où la jauge est une
  barre à quatre segments et le sceau une pastille légendée. La jauge apparaît aussi dans les
  espaces (« Mes logements », le tableau de bord) : c'est la forme, pas l'endroit, qui empêche la
  confusion. **Ne pas étendre cette exception** — une carte ou un bouton verts pour « ça a marché »
  referaient exactement ce que la règle interdit.

  **La coche est visible au repos ; l'animation ne fait qu'y arriver.** La première version la
  posait invisible et comptait sur l'animation pour la révéler : là où elle ne se jouait pas, il ne
  restait qu'un disque vide. Et **l'espace entre les deux phrases est dans l'interpolation** : laissé
  en tête d'un nœud de texte, le compilateur le condensait — « Compte créé.Décrivez ». **Le fait en gras, la
  suite en romain** : `SpaceShell` coupe le message à sa première phrase, parce que « Compte créé.
  Décrivez votre logement… » disait deux choses de même poids et qu'on lisait la consigne comme la
  fin du constat.
- **La déconnexion est un POST, jamais un lien.** Un GET destructeur se déclenche au
  préchargement d'un navigateur ou d'un antivirus, et le propriétaire se retrouve dehors sans
  avoir rien touché.

### Les deux espaces partagent une barre latérale : `SpaceShell`

Le cadre commun — barre, contenu, retour, messages de retour, compte, sortie — vit dans
`Components/SpaceShell.vue`, et il sert **l'espace client comme l'espace propriétaire**. C'est la
leçon d'`AccessShell` appliquée un cran plus loin : deux barres recopiées auraient divergé au
premier ajustement, et l'une aurait fini par ne plus dire ce que l'autre dit.
`Owner/Partials/OwnerShell.vue` ne décide donc plus que d'**une** chose : ce qu'il y a dedans.

**Les rubriques étaient des onglets en travers du haut ; elles sont passées à gauche.** Ça tenait
à trois — Demandes, Réservations, Logements — et ça ne tient plus dès qu'arrivent la facturation,
les messages et le profil : passé quatre ou cinq, une rangée horizontale défile, et **ce qui
dépasse de l'écran n'existe plus** pour celui qui ne pense pas à faire glisser. Une colonne, elle,
s'allonge sans rien cacher. C'est la seule raison de la reprendre — pas la ressemblance avec les
autres outils.

**Les rubriques ont toutes été construites, plutôt qu'annoncées.** Elles étaient d'abord posées en
« Bientôt » — un mot, pas un lien, sous un intitulé qui l'annonçait. Elles existent maintenant, et
c'est mieux : une rubrique promise engage le produit sans rien livrer, et celles-là étaient toutes
adossées à des données déjà en base. `SpaceShell` garde la capacité (un groupe dont aucun `item`
n'a de `href` est titré et rendu inerte) pour la prochaine qui sera décidée avant d'être écrite.

**Côté propriétaire** — *Mon activité* : Demandes, **Messages**, Réservations, Mes logements ;
*Mon compte* : **Facturation**, **Mes informations**. **Côté client** : Mes réservations,
**Messages**, **Mes informations**. Deux rubriques ont été écartées au lieu d'être promises :
« Mes favoris », qui suppose un bouton d'enregistrement sur tout le catalogue et une décision sur
la place du compte dans le produit ; et « Aide », qui serait une page de politiques qu'on n'a pas
tranchées — le recours est WhatsApp, et il est déjà écrit au pied de la barre.

**Les rubriques ne sont écrites qu'une fois** — `resources/js/Support/espaces.js` — et **trois
surfaces les lisent** : la barre latérale, le menu du compte de l'en-tête et le tiroir mobile.
Elles vivaient d'abord dans les composants qui les affichent, et les deux listes avaient déjà
divergé : le menu déroulant ignorait « Messages » et « Mes informations » côté client, si bien que
la même personne voyait deux menus différents selon qu'elle cliquait sur sa pastille ou qu'elle se
trouvait dans son espace. Un test regarde la **cause** — une rubrique écrite en dur dans une
surface — et pas seulement le symptôme. Une rubrique sans `href` est « à venir » : la barre la rend
inerte, le menu déroulant l'ignore (il n'a pas la place d'expliquer).

`AccessTest` demande aussi vraiment chaque `href`, sous la bonne garde : une rubrique qui ne mène
nulle part ne passe pas.

**La pastille a changé de rubrique.** Elle comptait des conversations en attente et vivait sur
« Réservations » : elle disait donc « deux réservations », ce qui n'a jamais été son sens et
envoyait chercher au mauvais endroit. Elle est sur « Messages », des deux côtés
(`ownerUnread`, `travellerUnread`).

#### Les deux boîtes

**Un message se rate, et c'est ce que les boîtes réparent.** Les fils ne vivaient que *dans* chaque
réservation : pour savoir si quelqu'un attendait une réponse, il fallait ouvrir les réservations
une par une. Côté voyageur, c'était pire — le fil ne s'atteignait que par une référence reçue dans
un message, donc perdu avec lui.

**Elles mènent au fil, elles ne le rendent pas.** La conversation s'ouvre sur la réservation, avec
ses dates, son total et ses boutons : « c'est possible d'arriver plus tard ? » se répond en
regardant la date d'arrivée. Une messagerie affichant le fil seul obligerait à retrouver de quoi il
parle.

**`ConversationList` sert les deux côtés**, comme `Conversation` pour le fil lui-même : seule
change la destination du lien. Une ligne répond, dans l'ordre où on se le demande : est-ce que ça
m'attend (une pastille de terre, pas un gras qui se confondrait avec un titre), de quoi ça parle,
qu'est-ce qui a été dit en dernier, et quand.

**L'extrait montre le dernier message même s'il est de moi**, préfixé de « Vous : ». Ne montrer que
ce qu'on a reçu ferait disparaître sa propre réponse : on ne saurait plus si on a répondu, ce qui
est justement la question qu'on se pose en ouvrant une boîte.

**L'ancienneté, pas la date** (`ilYA` dans `Support/format.js`) : devant une liste on se demande
« est-ce que ça vient d'arriver ? », et une date absolue oblige à savoir quel jour on est. Au-delà
d'une semaine la question s'inverse — on cherche alors *quand* — et la date reprend la main.

**Une réservation sans message n'est pas une conversation** : la faire figurer donnerait une boîte
de lignes muettes où le vrai message se perdrait. Le tri se fait sur le **dernier message**, pas
sur la réservation : une conversation qui reprend six mois plus tard doit remonter.

#### La facturation

**C'est la rubrique qui décide si un propriétaire reste.** Le tableau de bord n'affichait qu'une
facture, la dernière : « pourquoi ce montant » n'avait pas de réponse trois mois après.

**Le mois en cours vient avant les factures, et n'en est pas une.** C'est ce qui s'accumule — le
cacher jusqu'au premier du mois suivant ferait découvrir un montant qu'on aurait pu voir venir, et
c'est exactement ce qui fait qu'une commission se sent comme un piège. L'écran écrit que rien n'est
encore dû, et qu'un séjour ne compte qu'une fois confirmé par le voyageur.

**Les mois sans séjour confirmé sortent de l'historique** — une ligne à zéro n'apprend rien — mais
le mois en cours reste affiché même vide : « rien à venir » est une information, pas un blanc.
Chaque facture se déplie (`<details>` bordé, avec son chevron : un vrai contrôle), la plus récente
ouverte. **Aucun euro sur cette page**, et **aucun bouton « Payer »** : Vayla n'encaisse pas, le
propriétaire pousse son règlement par mobile money.

#### « Mes informations », des deux côtés

**Rien n'était modifiable.** Le propriétaire qui changeait de numéro recevait ses demandes sur une
ligne qu'il n'avait plus — au pire moment, une demande expirant en 48 h.

**L'adresse e-mail n'a de champ nulle part, et les deux écrans disent pourquoi.** Elle est
l'identifiant de connexion depuis la disparition des mots de passe : modifiable depuis une session
ouverte, elle offrirait le compte à qui a emprunté le téléphone. Côté voyageur elle **rattache en
plus les séjours**, et l'écran écrit combien en dépendent — un chiffre explique mieux qu'une règle.
Ce n'est pas un champ grisé mais un libellé : un champ qu'on ne peut pas remplir sans savoir
pourquoi est un faux signal de plus.

**Le portrait et l'adresse exacte** complètent la fiche du propriétaire, et chacun a dû justifier
sa présence — Vayla ne constitue pas de dossier.

- **Le portrait** (`owners.portrait`, `OwnerPortraitService`) ne passe **pas** par la table
  `photos` : celle-ci porte les photographies de Commons et des logements, avec crédit obligatoire,
  trois résolutions et `PhotoFilesTest` qui la surveille fichier par fichier — un visage y entrerait
  comme un orphelin sans auteur. Une colonne suffit : un portrait appartient à un compte, n'est
  jamais partagé, et n'a pas de légende. **Carré, 160 et 480 px** : il s'affiche à 2 rem dans la
  colonne et 4 rem sur son écran ; un 1600 de plus serait un fichier que personne ne télécharge. Le
  **seuil est bas — 200 px** : la règle des 1200 protège une photo de tête qu'on regarde en grand,
  et refuser l'unique photo que quelqu'un a de lui-même reviendrait à refuser le portrait tout
  court. Le recadrage est **centré haut**, pas centré : sur un portrait en pied, un carré pris au
  milieu coupe la tête. Il **part seul, dès qu'il est choisi** — un fichier de dix mégaoctets qui
  repartirait à chaque correction de numéro serait une minute d'attente et un enregistrement perdu
  quand la ligne coupe. Le remplacement **efface l'ancien fichier** : un visage qui traîne n'est pas
  un octet comme un autre. Il s'affiche là où les initiales étaient — pastille de l'en-tête, pied de
  la colonne — et **nulle part publiquement**, ce que l'écran écrit.
- **L'adresse exacte** (`owners.address`) sert à deux choses, écrites sous le champ : la facture de
  fin de mois, qui doit désigner quelqu'un pour être payable, et la vérification — celui qui passe
  voir un logement doit savoir où aller. La ville reste à côté, approximative, et suffit à tout le
  reste. **Jamais publiée**, et un test le vérifie sur la fiche et l'accueil.

**Ce test-là a révélé une fuite qui n'avait rien à voir.** `auth.user` partageait
`$request->user()`, c'est-à-dire le **modèle entier** : pour un compte propriétaire, dont `$hidden`
ne masque que la clé d'accès et le mot de passe, cela publiait l'adresse exacte, l'e-mail, la date
de dernière connexion et l'état de vérification dans le `data-page` de **chaque** écran. La prop est
maintenant une **projection explicite** (`AuthUserData`, `AuthOwnerData`), et la **garde est nommée** (`$request->user('web')`) :
sans elle, `actingAs($owner, 'proprietaire')` change la garde par défaut et un propriétaire se
retrouve publié dans `auth.user`.

**Changer de numéro annule sa confirmation, et l'écran le dit avant.**
`phone_verified_at` n'enregistre pas une vérification maison : il enregistre le fait que le lien
d'accès envoyé sur ce WhatsApp a servi. Le numéro changé, cette preuve ne porte plus sur rien — la
garder ferait mentir le compte sur une vérification, ce que Vayla reproche précisément aux annonces
qu'elle vérifie. `unique:owners,phone` **ignore le compte lui-même** : sans ça, enregistrer sans
toucher au numéro échouerait sur « déjà pris », par soi.

**Le compte voyageur tient en trois champs — nom, prénom, téléphone — et chacun sert deux fois.**
Ni mot de passe, ni date de naissance, ni adresse postale : le nom et le prénom sont ce que le
propriétaire lit quand il décide d'accepter quelqu'un chez lui, le numéro est ce par quoi il
rappelle, et **les trois pré-remplissent la demande de séjour**. C'est la seule raison pour
laquelle Vayla les garde : sans ce second usage, ce serait un dossier de plus à constituer. Tout
est facultatif, et le formulaire de séjour redemandera ce qui manque — **demander un séjour ne
réclame toujours aucun compte**, un visiteur arrive sur un formulaire vide.

**Nom et prénom sont séparés parce qu'un champ unique ne se relit pas.** « RAKOTOBE Jean » est une
écriture courante ici, « Jean Rakotobe » l'est ailleurs, et rien ne dit laquelle on a sous les
yeux : l'écran des réservations en avait fait « Bonjour RAKOTOBE », un patronyme crié à quelqu'un
qu'on voulait accueillir. **La colonne `name` a disparu** plutôt que de rester à côté des deux
parties — deux endroits pour un même fait finissent toujours par diverger. Le nom complet est
**dérivé** par un accesseur du modèle, comme `perks` l'est des équipements marqués ; un mutateur
sépare un nom entier, parce que la connexion sociale en reçoit un d'un seul tenant et écrit
`['name' => …]` indifféremment sur `User` ou sur `Owner`. La coupe tombe au premier espace — juste
pour « Jean Rakotobe », fausse pour « RAKOTOBE Jean », et corrigeable en dix secondes sur l'écran
du compte, ce qui n'était pas le cas avant. **`users.phone` n'est pas unique** : côté propriétaire
le numéro identifie un compte, ici il ne fait que pré-remplir, et deux personnes d'un même foyer
partagent une ligne.

**Aucun bouton de suppression de compte** : effacer un compte voyageur toucherait des réservations
qui appartiennent aussi à un propriétaire, et tant que cette question n'est pas tranchée, le bouton
mentirait.

**La rubrique où l'on est prend la terre** — fond `--terre-050`, texte `--terre-700`, et une barre
de terre à gauche. C'est déjà ce que faisait le trait sous l'onglet actif : le repère a seulement
tourné d'un quart de tour.

**« Publier un logement » n'est pas une rubrique, c'est un geste.** Il est en tête de barre, dans
le seul bouton plein de l'écran ; rangé dans la liste, il serait devenu un endroit où l'on va
alors que c'est ce qu'on vient faire.

**Les deux espaces portent le même en-tête : celui du site.** L'espace propriétaire n'en avait pas,
et la raison était précise — la barre **recrutait**, et « Devenir hôte » n'a aucun sens pour
quelqu'un qui l'est déjà. **Cette raison a disparu** : l'en-tête suit la session, le bouton se
retire pour un propriétaire, et « Connexion » est devenu le menu de son compte, où vit la
déconnexion. La raison tombée, la règle tombe avec elle — deux barres différentes pour deux espaces
du même produit obligent à réapprendre où sont les choses en changeant de casquette.

Le chemin y a coûté deux essais, et les deux erreurs sont instructives : la colonne a d'abord
**absorbé** le bandeau du propriétaire (marque, espace, sortie), et l'application s'est retrouvée
sans repère en haut de fenêtre — « le header a disparu » ; puis un bandeau **propre à l'espace** a
été rendu, ce qui donnait deux barres différentes selon la casquette. `SpaceShell` ne porte donc
plus **aucun** en-tête à lui, et un test le vérifie : ni `<header>` dans le cadre, et
`<SiteHeader :search="false" />` sur les quatre écrans qui montent un espace.

**`:search="false"` n'est pas un détail** : la forme compacte de l'en-tête efface la navigation pour
y encastrer le moteur de recherche. Sans moteur à encastrer, elle laisse une barre vide au premier
défilement — le piège est documenté plus bas, et les trois écrans du client venaient de tomber
dedans.

**Le monogramme se trace en filigrane** au pied de la barre, à 5 % — le même geste que le littoral
de l'accueil et le V de `/connexion`. C'est ce qui fait de ces écrans des pages de Vayla plutôt
qu'un tableau de bord interchangeable.

**Sous 960 px, la colonne redevient une rangée qui défile**, jamais un bouton hamburger : un menu
caché derrière trois traits n'est pas une navigation pour quelqu'un dont c'est le premier outil en
ligne. Les rubriques à venir sortent de cette rangée et se replient en une ligne de texte en
dessous, derrière le mot qui les annonce — une seule rangée pour les deux ferait sortir de l'écran
ce qui marche aujourd'hui, poussé par ce qui n'existe pas encore.

**Les titres d'un écran de travail ont deux tailles, écrites une fois dans `app.scss`** :
`.espace__titre` pour le titre de l'écran, `clamp(1.35rem, 2.4vw, 1.6rem)`, et `.espace__section`
pour les titres de section, 1,1 rem — 26 px, puis 18. **`.display--*` est l'échelle des pages de
vente, pas celle des outils** : un `.display--lg` montait à 3,9 rem, soixante-deux pixels au-dessus
de deux réservations.

Le titre d'écran a été réglé deux fois. D'abord à 2,1 rem : 34 px au-dessus d'une barre latérale en
15 px et d'une carte titrée en 20, il écrasait ce qu'il introduisait — et le crénage à −0,045 em,
juste pour une accroche d'accueil en 60 px, **collait les mots** à cette taille (« Décrivezvotre
logement »). Il est à −0,03 em. Les **titres de section**, eux, étaient **recopiés à l'identique
dans neuf composants** (1,3 rem, 800, −0,032 em) : la garantie qu'un des neuf finirait par
diverger, et à 1,3 rem sous un titre de 1,6 on ne savait plus lequel introduisait l'autre. Chaque
composant ne garde que ses **marges**, qui dépendent de ce qui suit et pas du rang du titre. Un test
vérifie la borne du titre d'écran et que **tout `<h2>` d'un espace passe par `.espace__section`** —
sauf le nom d'un logement dans sa carte, qui n'est pas un titre de section. Seul le **montant dû**
de la facture reste plus gros (1,6 rem) : c'est un chiffre, et c'est lui qu'on vient lire.

**On n'annonce que ce qui n'est pas déjà visible.** Le bandeau « Vous êtes connecté » barrait le
haut de l'espace à chaque entrée — client comme propriétaire, par code comme par Google — pour
répéter ce que l'écran montre tout seul : l'espace, et le nom dans le menu du compte. Il fallait le
lire avant d'atteindre ce qu'on venait faire. Il est retiré des quatre endroits qui le posaient.
« Votre compte est ouvert. » reste : c'est un fait neuf, et il ne se dit qu'une fois.

**Un écran de travail se nomme, il ne salue pas.** « Bonjour X » prenait le premier mot du nom — et
ce mot est le **nom de famille** dès qu'il est écrit à la malgache, « RAKOTOBE Hariony ». On criait
donc un patronyme en capitales à quelqu'un qu'on voulait accueillir. Le nom du compte ne dit de
toute façon rien de plus que le menu de l'en-tête et le pied de la colonne, qui le portent déjà :
l'écran s'appelle « Mes réservations » ou « Demandes », comme sa rubrique, ce qui confirme où l'on
est. La règle vaut pour les **deux** espaces — corrigée d'abord côté client, elle a survécu deux
échanges de plus sur le tableau de bord du propriétaire, et un test la tient maintenant sur tous
les écrans des deux espaces.

**La sortie est au pied de la colonne *et* dans le menu de l'en-tête.** Ce n'est pas une redondance
à supprimer : dans un espace où l'on reste — le propriétaire y passe ses semaines — la colonne est
ce qu'on parcourt, et c'est là qu'on cherche à sortir ; le menu de l'en-tête sert **partout
ailleurs sur le site**, où il n'y a pas de colonne. Deux surfaces différentes, pas deux fois la
même. Le cadre déduit le compte et la porte de sortie de sa **garde** (`garde="proprietaire"`)
plutôt que de les recevoir : trois écrans client montent ce cadre, et les leur faire recomposer
chacun aurait garanti qu'un des trois finisse par afficher autre chose — ou par déconnecter la
mauvaise session, les deux gardes pouvant être ouvertes en même temps.

**L'entrée est une seule chorégraphie, et rien après** (`Composables/useSpaceMotion.js`) : les
rubriques se décalent de 28 ms, puis le contenu monte de 10 px. C'est un outil, pas une vitrine —
le propriétaire l'ouvre parce qu'une demande expire dans quarante et une heures, et un mouvement
qui se remarque met du temps entre lui et sa réponse.

**Le client garde l'en-tête du site, le propriétaire non** (`marque`). Le voyageur n'est pas dans
un outil : il est sur le site et il en repartira vers le catalogue — lui retirer les destinations
pour lui montrer ses deux réservations serait l'enfermer dans une pièce vide. Sa barre ne répète
donc ni la marque ni le compte, que l'en-tête porte déjà quinze centimètres plus haut ; **et son
écran n'a plus son propre bouton « Se déconnecter »**, puisque le menu du compte en a un sur toutes
les pages du site.

#### La saisie d'une annonce

`/proprietaire/logements` — **le propriétaire remplit, Vayla publie.**

C'est lui qui connaît son logement : les couchages, le groupe électrogène, la piste en 4×4. Le
faire dicter au téléphone à quelqu'un de Vayla était tenable pour huit annonces, pas pour trente.

**Mais il ne publie pas, il soumet.** `ListingStatus::Submitted` est le palier qui protège tout
le produit : `Draft` → `Submitted` (le propriétaire) → `Published` (Vayla). Sans lui, n'importe
qui se mettrait en ligne, et « vérifié » ne voudrait plus rien dire. **`trust_level` n'apparaît
dans aucun formulaire** — il n'est pas dans `OwnerListingRequest`, donc il ne peut pas entrer par
là ; une annonce neuve part de « déclarée », quoi qu'on poste. `OwnerListingTest` le vérifie en
postant `trust_level: 4` et `status: published`.

**Après vérification, la fiche se referme partiellement.** `LIBRES_APRES_VERIFICATION` liste ce
qui bouge encore — tarif, description, durées, règles de séjour — parce que rien de tout ça ne
remet en cause ce que Vayla est allé voir. Capacité, type, destination, équipements et photos ne
bougent plus : une annonce visitée en visio dont on pourrait changer les photos ferait de la
vérification un tampon sans objet. L'écran le **dit** au lieu de griser sans expliquer.

**Un assistant en cinq étapes, où chaque étape se clique** — L'essentiel, Capacité, Tarif et
séjour, Équipements, Photos (`Owner/Listings/FormSteps.vue` pour la barre). La fiche était d'abord
un long formulaire à sections, par crainte de l'assistant classique : doux à la première saisie,
insupportable à la quinzième correction, puisqu'on corrige un tarif dix fois pour une création. Ce
qui le rendait insupportable, c'était **l'ordre imposé** ; ici chaque étape s'atteint directement
depuis la barre, et en modification **« Enregistrer » reste disponible à chaque étape**. On garde
la douceur de la première saisie — une question à la fois — sans payer la quinzième correction.

- **« Suivant » ne bloque jamais.** Un champ vide ne retient personne sur une étape : la barre
  marque ce qui reste, et c'est le bouton final qui refuse **en nommant ce qui manque** — « il
  manque le nom du logement et le prix pour une nuit », chaque manque étant un lien vers son étape.
- **Une erreur du serveur ramène à son étape.** La validation reste celle d'`OwnerListingRequest`,
  une fois ; mais une erreur sur le titre reçue depuis l'étape des photos serait invisible. Chaque
  étape déclare **ses champs**, on saute à la première fautive, et la barre marque les étapes en
  erreur. Un test vérifie que **chaque règle du Request appartient à une étape** : un champ oublié
  serait une erreur qu'aucun écran n'afficherait.
- **Trois états, trois formes** : l'étape courante en rond plein de terre, une étape remplie en
  rond d'encre coché, une étape en erreur en rond cerclé de terre avec « ! ». Plein pour « ici »,
  cerclé pour « à reprendre » : on ne les confond pas même en noir et blanc. **Pas de vert** —
  l'exception du lagon est bornée à la coche du message de retour.
- **`v-show`, pas `v-if`** : les champs restent montés d'une étape à l'autre, rien de ce qu'on a
  tapé ne se perd.
- **L'étape vit dans l'adresse** (`?etape=photos`), posée par `history.replaceState` **en gardant
  `history.state`** — Inertia y range sa page, le vider casserait le bouton retour. C'est ce qui
  permet à la création de **déposer directement sur l'étape des photos**, la seule que la création
  ne pouvait pas faire ; et en création, cette étape existe déjà pour l'annoncer et porter le
  bouton « Créer le logement ».
- **« Envoyer à Vayla » refuse une fiche non enregistrée**, équipements compris : `form.isDirty`
  ne voit pas les cases cochées, qui ne passent dans le formulaire qu'à l'enregistrement — on
  pouvait envoyer à la vérification une fiche qui ne portait pas le groupe électrogène qu'on
  venait de cocher.

La barre d'action est collée en bas, sur toutes les étapes : un bouton qu'il faut aller chercher
est un formulaire qu'on quitte sans enregistrer. On revient à gauche, on avance à droite, et la
seule action pleine de terre est celle qui termine.

**Les 102 équipements sont groupés par rubrique et repliés**, avec le compte par rubrique. Une
seule liste de cent deux cases est illisible et on abandonne avant la moitié. Le marqueur
« mettre en avant » est ce qui alimente `perks` — les trois arguments de la carte sont **dérivés**
de `highlight`, jamais saisis à part.

**Le slug ne bouge jamais après la création.** C'est l'adresse publique de la fiche : un lien
partagé qui casse est un voyageur perdu.

#### Les photos téléversées

`PhotoUploadService`, en GD — aucune dépendance ajoutée. Recadrage 4/3 centré, sortie WebP en
800/1600/3200, **jamais d'agrandissement** : `photos.width` doit dire la vérité sur ce que porte
le disque. Une photo de moins de 1200 px est **refusée avec sa taille dans le message** — un
original de 900 px est flou sur une photo de tête de 1300, et un logement flou ne se réserve pas.
L'orientation EXIF est redressée : sans ça, une photo prise à la verticale s'affiche couchée.

**Les gros fichiers passent, et c'est le résultat de quatre corrections, pas d'une.** Une photo de
48 Mpx (12 Mo) — ce que produit un téléphone récent en mode haute résolution — échouait à chaque
maillon :

1. **nginx** ne pouvait pas écrire le corps de la requête dans son dossier temporaire
   (`/var/lib/nginx` est à `nginx` en 750, `www-data` n'y entre pas) : **toute** photo au-delà de
   quelques kilo-octets tombait en 500 avant d'atteindre PHP. Les tests ne passent pas par nginx
   et ne pouvaient pas le voir. Les dossiers temporaires sont dans `/tmp`
   (`docker/nginx/nginx.conf`) ; vérifié par un vrai envoi HTTP de 12 Mo.
2. **La mémoire** : l'original était décodé, pivoté, puis recadré — trois copies pleine taille,
   534 Mo mesurés pour 256 autorisés. `Services/Images/ImageSource` lit les dimensions dans
   l'en-tête, recadre et réduit **en une seule passe** depuis l'original, applique l'orientation
   sur la petite image, et accorde la mémoire calculée pour ce seul traitement : 289 Mo, 2,5 s.
   Au-delà de 80 Mpx, refus avec la taille, **avant** décodage. Les portraits y passent aussi.
3. **Le redressement ne s'était jamais fait** : il reposait sur `exif_read_data`, que l'image PHP
   n'embarque pas, derrière un `function_exists`. `ImageSource` lit l'étiquette d'orientation
   dans l'en-tête JPEG lui-même ; un test fabrique une photo couchée et vérifie qu'elle sort droite.
4. **Le navigateur réduit avant d'envoyer** (`Support/preparerPhoto.js`) : à la largeur que le
   serveur gardera après recadrage (3 200 px, 960 pour un portrait), en JPEG 0,92 — quatre à six
   fois moins à transférer sur une connexion mobile, et l'orientation déjà appliquée. Au moindre
   doute l'original part : le serveur sait le traiter. Les quatre écrans d'envoi l'utilisent, et
   montrent l'envoi avancer (`Components/Office/PhotoDepot.vue` au back-office).

Le plafond est de **40 Mo** (`PhotoProcessor::POIDS_MAX_KO`, lu par les trois `FormRequest`),
sous les 50 de PHP et les 100 de nginx.

**La compression : une qualité par palier, chaque palier réduit depuis le précédent.** 82 en 800,
79 en 1600, 74 en 3200 : le 3200 n'est demandé que par les écrans à haute densité, où la
compression se voit deux fois moins. Mesuré sur trois originaux de Commons contre l'ancien
traitement (qualité 82 partout, chaque palier depuis l'original) : 10 à 23 % de disque en moins,
aucune différence visible sur des détails à 100 %. **L'accentuation a été essayée et écartée** :
elle alourdissait les fichiers sans gain qu'on voie. GD n'a pas l'AVIF dans cette image ; le jour
où il l'aura, c'est ici que ça se jouera. Les photos de Commons déjà en place n'ont pas été
réencodées — un WebP recompressé perd à chaque passage.

**`photos.folder` sépare deux provenances qui ne se mélangent pas.** `lieux` porte les
photographies de Commons — auteur, licence et page source obligatoires, c'est ce qu'exigent CC BY
et CC BY-SA — et `annonces` celles du propriétaire, qui sont à lui. Les fondre ferait apparaître
les secondes dans le bloc « Crédits photo » du pied de page, et `PhotoFilesTest` les signalerait
comme orphelines. Corollaire : retirer une photo de propriétaire **efface ses fichiers**, retirer
une photo de Commons ne fait que la détacher — une même photographie illustre une destination et
une annonce, avec un seul crédit.

**La position 0 est la couverture, et il n'y a pas d'autre bouton pour la désigner** — pas de
colonne `photo_id` à côté, qui aurait permis qu'une couverture n'appartienne pas à la galerie. On
réordonne par **deux flèches bordées de 2,75 rem**. Dans le back-office, le **glisser-déposer
s'y ajoute** (`Composables/useRangement.js`) — **il ne les remplace jamais** : sans souris ni
écran tactile, les flèches sont le seul chemin. Chacun range avec l'outil qu'il a sous la main,
et l'ordre change à l'écran avant la réponse du serveur.

**Le glisser est écrit aux évènements `pointer*`, pas avec `draggable`.** La première version
était native, et c'est tout ce qu'elle ne faisait pas qui l'a condamnée : rien ne bougeait avant
le lâcher, donc on ne voyait pas où la photo tomberait ; les interstices de la grille refusaient
le dépôt ; le fantôme était la vignette translucide du système ; et rien ne marchait au doigt.
Maintenant **les autres photos s'écartent pendant qu'on tient** (GSAP Flip) — l'emplacement en
pointillé de terre *est* l'endroit où elle tombera —, une copie de la carte suit le pointeur, la
page défile seule près des bords, Échap annule, et l'ordre ne part au serveur qu'au lâcher, une
fois. Trois règles tiennent la mécanique :

- **On vise une place, pas une photo.** Les places de la grille sont mesurées une fois, au départ,
  en coordonnées de page ; viser les cartes elles-mêmes, en pleine animation, faisait osciller
  l'ordre d'avant en arrière. Et **un réordonnancement par image**, pas un par mouvement de souris.
- **Un appui sans mouvement reste un clic** (seuil de 6 px) : c'est lui qui ouvre le gros plan.
  Le clic qui suit un lâcher est avalé, sinon ranger une photo l'ouvrirait aussi.
- **Au doigt, on attrape par la poignée** (`data-poignee`, seule en `touch-action: none`) : sur le
  reste de la carte, le doigt fait défiler la page, comme partout ailleurs. Les boutons d'une carte
  n'attrapent rien — sauf celui qui porte `data-prise`, la vignette qu'on clique.

**Sur une destination, cliquer une photo la montre en grand** (le « gros plan »), avec ses crédits
et ses gestes : avancer, reculer, mettre en couverture, retirer. Une vignette de 7 rem ne dit ni si
la photo est nette, ni ce qu'elle cadre — et les gestes posés là laissent la grille n'être qu'une
grille de photos qu'on range. La photothèque passe par le même gros plan : un clic la
montre, « Ajouter à la galerie » l'ajoute. Un clic qui ajoutait directement faisait entrer une photo qu'on
voulait seulement regarder.

#### L'échange voyageur ↔ propriétaire

**Un fil par réservation, jamais une messagerie générale.** Vayla ne cache pas les numéros de
téléphone — tout le modèle est la mise en relation directe — et le va-et-vient rapide se fera sur
WhatsApp de toute façon : les deux parties y sont déjà. Vouloir remplacer WhatsApp serait perdu
d'avance, et un « chat » qui ne notifie rien ferait rater des messages.

Ce que WhatsApp ne donne pas, c'est **une trace rattachée à un séjour**. Le jour où un voyageur
affirme qu'on lui avait promis la climatisation, ou qu'un propriétaire dit avoir prévenu d'une
coupure d'eau, il faut pouvoir relire ce qui a été écrit et quand. C'est la seule raison d'être
de ce fil.

- **Pas de conversation sans réservation.** Un « contacter le propriétaire » ouvert à tous serait
  une surface de démarchage sans responsabilité.
- **Le message déposé avec la demande ouvre le fil.** `BookingService` l'y écrit à la création, et
  la migration a converti les lignes existantes : le laisser dans sa seule colonne ferait deux
  endroits où vivent les mots d'un voyageur, et l'écran finirait par n'en montrer qu'un des deux.
- **`author` est un rôle** (`MessageAuthor`), pas un identifiant : un voyageur n'a pas de compte —
  c'est délibéré — et le fil doit rester lisible si la réservation change de main.
- **`Vayla` est un auteur possible.** Quand on intervient dans un litige, ça se voit dans le fil
  plutôt que d'arriver par un canal séparé dont l'autre partie n'aurait pas connaissance. Et
  l'écran **dit** que Vayla peut lire : une trace dont personne ne sait qu'elle est lisible ne
  sert de médiation à personne.
- **Ouvrir vaut lecture, écrire aussi.** Un bouton « marquer comme lu » serait un geste de plus à
  comprendre, et un compteur qui ne redescend pas seul finit par être ignoré — donc par ne plus
  rien signaler. Les deux horodatages vivent sur `bookings`, pas sur chaque message.
- **La pastille compte les conversations, pas les messages.** « 2 » veut dire « deux échanges vous
  attendent », pas « quatorze lignes de texte » : un compte de messages ferait paniquer pour un
  voyageur bavard.

`Components/Conversation.vue` sert **les deux côtés** : deux écrans qui rendraient le même fil
avec deux mises en page finiraient par ne plus dire la même chose. La seule différence est `moi`,
posé par le serveur selon qui lit. Pas de bulles, pas d'indicateur de frappe, pas de
rechargement : la mise en page d'un chat promettrait une instantanéité qui n'existe pas.

#### La demande dans l'autre sens

`/demande` (`StayRequestController`, `StayRequestSubmitter`, `Pages/Demande/Create`) — **le voyageur
décrit le séjour qu'il cherche, l'équipe va le chercher.** C'est la promesse de la section « Vous ne
trouvez pas ? » de l'accueil et de « Comment ça marche », et **rien ne la tenait** : ses quatre
boutons « Déposer une demande » (la section elle-même, l'état vide de la grille, l'atlas, le pied
de page) et celui des destinations sans logement pointaient sur `#demande` — la section où se
trouvait le premier. Un clic, et rien.

- **La recherche en cours voyage avec le clic** (`Support/liens.js`) : destination, dates et
  voyageurs arrivent dans l'adresse, et `useSearchQuery` les tient — même calendrier, même
  consigne que le moteur. **Et la page dit ce qui existe déjà** : si des logements correspondent,
  elle y mène — faire attendre quelqu'un pour ce qu'il a sous les yeux serait absurde.
- **Seul ce qui permet de répondre est obligatoire** : un nom, et WhatsApp ou un e-mail (le numéro
  normalisé en E.164). Destination, dates, budget : « je ne sais pas encore » est une réponse
  valable. Ni compte ni mot de passe — l'accueil le promet.
- **Elle n'engage personne** : aucune nuit bloquée, aucun propriétaire prévenu d'office, et la page
  l'écrit avant et après l'envoi. Dix envois par heure et par IP, et un champ piège (`site`).
- **Au back-office, une file** (`/demandes`, groupe « Séjours », avec son compteur) : chaque carte
  porte WhatsApp prêt à écrire, le catalogue déjà filtré sur la demande, et qui s'en occupe.
  **Prendre une demande l'écrit** — deux personnes qui écrivent au même voyageur, c'est une
  question posée deux fois — et **la clore demande une note** (ce qui a été proposé, ou pourquoi
  rien). Les nouvelles viennent **la plus ancienne en tête**.

**Les autres liens de l'accueil, relus un par un** (et tenus par `StayRequestTest`) :

- **Les repères de la carte se donnaient pour des boutons** (`role="button"`, focalisables, curseur
  main) sans rien faire au clic : ils filtrent maintenant la grille comme la ligne de la liste
  voisine, au clic comme au clavier, et mènent à la destination sur `/destinations`.
- **Choisir une région défile jusqu'à la grille** (`allerA('offres')`, sous l'en-tête fixe) : le
  filtre s'appliquait deux sections plus haut, hors de l'écran, et le clic semblait ne rien faire.
- **« Tous les logements, avec les filtres » ouvre le catalogue sur la même recherche** — catégorie
  comprise. Il repartait de zéro.
- **Toute la carte d'annonce se clique** : elle se soulève au survol comme un lien, mais seuls la
  photo et le titre en étaient un. Le lien du titre s'étend sur la carte (`::after`).
- **« Publier un logement » du pied de page** mène à l'inscription, comme la colonne voisine, et non
  à une ancre de recrutement qui n'existe que sur l'accueil.

#### L'historique des réservations

`/proprietaire/reservations` répond à la question que le tableau de bord ne traite pas : **qui est
venu, qu'est-ce qui a été refusé, qu'est-ce qui a expiré**. Sans lui, une demande répondue
disparaissait de l'espace. Chaque ligne dit si elle est **facturable** — seul un séjour confirmé
par le voyageur l'est — ce qui évite le « pourquoi cette ligne n'est pas sur ma facture » de fin
de mois.

#### Le calendrier d'un logement

`/proprietaire/{cle}/logements/{slug}/calendrier`. Il existe parce que **les propriétaires
louaient déjà par WhatsApp avant d'arriver ici, et continueront** : sans un endroit où déclarer
leurs nuits vendues ailleurs, ils reçoivent des demandes sur des dates déjà prises et n'ont
d'autre choix que de les refuser une par une. Le refus abîme la relation avec le voyageur et ne
corrige rien — la semaine suivante, une autre demande arrive sur les mêmes nuits.

**Une page à part, atteinte par un bouton posé sur le logement.** Ce qui compte à l'ouverture de
l'espace, c'est la demande qui expire dans 48 h ; douze mois de calendrier par logement
l'auraient noyée. Un bouton n'est pas une navigation à apprendre, contrairement à des onglets —
et il porte le nombre de périodes déjà fermées, sinon on ne sait pas ce qu'il y a derrière.

**Toute la difficulté tient dans une phrase, et elle est écrite en toutes lettres.** « Je suis
pris du 12 au 18 » ne veut rien dire tant qu'on n'a pas dit si la nuit du 18 est prise. Le
récapitulatif tranche à chaque fois : « 6 nuits à fermer, de la nuit du 12 à celle du 17. Le 18,
le logement est de nouveau réservable. » C'est l'invariant `ends_on` = dernière nuit occupée
**rendu lisible** plutôt que documenté. Le propriétaire pose une arrivée et un départ comme un
voyageur, et c'est `SejourData::derniereNuit()` qui fait la soustraction — jamais le service,
jamais le contrôleur.

**Les bornes `min_nights` / `max_nights` sont retirées du calendrier envoyé ici.** Elles encadrent
ce qu'un voyageur réserve, pas ce qu'un propriétaire ferme : un logement qui se loue au minimum
trois nuits doit pouvoir être fermé une seule soirée.

**Deux sources d'occupation, deux traitements.** Une période déclarée se rouvre d'un bouton ; les
nuits d'une réservation, non — un « Libérer » posé dessus ferait disparaître un séjour sans que le
voyageur l'apprenne. La liste dit où aller (refuser la demande, annuler la réservation) au lieu
d'afficher un bouton inerte. Rouvrir ne demande **pas** de confirmation : c'est réversible en
trois clics, et on ne confirme que l'irréversible.

**Un refus nomme ce qui bloque** : la référence de la réservation, ou les dates de la période
déjà posée. « Impossible » sur un téléphone est une impasse, pas un message. Deux périodes
déclarées ne fusionnent jamais en silence — le propriétaire croirait avoir ajouté une période et
en retrouverait une autre.

**Le motif est un enum de cinq valeurs** (`App\Enums\BlockReason`), pas un champ libre : la règle
« pas de champ libre là où une liste suffit » s'applique d'autant plus que le motif n'est jamais
une donnée de décision — il n'apparaît nulle part côté voyageur. « Loué en direct » vient en
premier parce que c'est le cas majoritaire.

`UnavailabilityRepository` prend toujours le `Listing` en paramètre : c'est **la** portée de
sécurité de l'espace. Une signature acceptant un identifiant de période nu permettrait de rouvrir
le calendrier d'un confrère avec une clé valide et un identifiant deviné — `OwnerCalendarTest` le
vérifie, comme il vérifie la borne « arriver le jour où la période précédente se libère ».

### Le back-office — `office.localhost:8070`

**Un hôte à part, pas un préfixe `/admin`.** Un `/admin` à côté de `/logements` partagerait le
cookie de session du site, se devinerait au premier essai, et chaque lien du site y serait à un
clic. Sur un autre hôte (`VAYLA_OFFICE_DOMAIN`, `office.localhost` par défaut — `*.localhost`
résout vers la machine sans toucher au fichier hosts), le cookie est à part : la session d'un
voyageur n'y voyage pas, celle d'un administrateur n'en sort pas.

**`routes/office.php` est chargé AVANT `web.php`** (`bootstrap/app.php`, `web: [office, web]`),
et c'est une règle de sécurité : les routes du site n'ont pas de domaine et répondent sur tous
les hôtes. Et **sa dernière route attrape tout le reste de l'hôte** (`NotFoundController`, un
contrôleur et non une fermeture — le cache de routes ne sérialise pas les fermetures) : sans
elle, `office.…/logements` ouvrait le catalogue public, et `office.…/proprietaire` l'espace d'un
propriétaire, dans l'outil de l'équipe. `OfficeTest` le vérifie ; vérifié aussi **avec le cache
de routes actif**, puisque l'entrypoint le compile à chaque démarrage.

**Les administrateurs ont leur table (`admins`) et leur garde (`admin`), jamais un drapeau sur
`users`.** Aucune route d'inscription : le premier membre se crée en ligne de commande
(`php artisan vayla:admin prenom@vayla.mg --nom="…"`, `--reinitialiser`, `--retirer`, `--liste`),
les suivants depuis l'écran « Membres ». On ne s'y retire pas soi-même, on n'y retire pas le
dernier membre.

**La porte : une adresse et un mot de passe — la seule du produit qui en ait un.** Elle a d'abord
été un code par e-mail, comme partout ailleurs ; ça tient pour des voyageurs et des propriétaires
qui reviennent quelques fois par mois, pas pour une équipe qui ouvre l'outil vingt fois par jour.
Ce qui la tient (`Services/Office/Auth` : `OfficeLogin` pour la porte, `AdminPasswordService` pour
les mots de passe) :

- **l'échec ne dit jamais laquelle des deux valeurs est fausse** (« Adresse ou mot de passe
  incorrect. », même message pour une adresse inconnue), **et le temps de réponse non plus** :
  une adresse inconnue passe par un `Hash::check` sur un leurre, sinon le chronomètre ferait le
  travail que le message refuse de faire ;
- **cinq essais par minute pour une adresse depuis une machine, vingt par heure pour une adresse
  tout court** — la seconde borne arrête qui devine depuis plusieurs machines ; l'écran dit dans
  combien de secondes réessayer ;
- **un mot de passe posé par quelqu'un d'autre est provisoire** (`password_set_at` nul) : il a été
  vu — par le collègue qui l'a dicté, par le terminal qui l'a affiché — et
  `EnsureAdminPasswordIsSet` n'ouvre alors que « Mon compte », jusqu'à ce que la personne en
  choisisse un. Publier sous un secret partagé ferait mentir le journal sur qui a agi ;
- **le provisoire ne part jamais par e-mail** — un mot de passe dans une boîte y reste. Il
  s'affiche **une fois**, en session flash, à celui qui ajoute le membre ou le réinitialise
  (seize caractères sans O/0 ni I/l/1, il se dicte) ;
- **douze caractères, pas de règle de forme, refus des mots de passe connus des fuites**
  (`Password::uncompromised()`) ; l'actuel est toujours redemandé pour en changer ;
- ni « rester connecté », ni connexion sociale.

**La porte ne ressemble à aucune de celles du site** (`Components/Office/OfficeGate.vue`, qui sert
aussi la page introuvable) : fond d'encre, papier quadrillé, le V du monogramme tracé en grand, la
fiche de connexion posée à droite. Les écrans d'accès du site sont blancs et baignés de latérite ;
un voyageur qui tomberait ici ne doit pas croire être au bon endroit. Un test vérifie qu'elle ne
monte pas `AccessShell`. Le « Afficher » du mot de passe est un bouton bordé et nommé, pas un œil.

**Les comptes de test : `php artisan vayla:comptes-test`** (local uniquement, refuse ailleurs).
Il crée un compte d'équipe au mot de passe définitif, trois voyageurs rattachés aux réservations
de démonstration par l'adresse, et imprime une fiche Markdown — propriétaires de démo, leurs
liens d'accès directs, identifiants — à coller dans `specs/access.md` (ignoré par git). Tout est
en `@demo.vayla.test` : en développement, les codes arrivent dans le bac à sable Mailtrap. **Pas
un seeder**, et c'est délibéré : `RegistrationTest` et `SocialAuthTest` comptent les voyageurs
après `seed()`, et un mot de passe en dur dans un seeder versionné finirait par tourner là où il
ne doit pas. À relancer après `make seed`, qui recrée les réservations sans adresse.

**Les gardes sont nommées partout** — `auth:web` sur l'espace client (c'était un `auth` nu),
`$request->user('admin')` dans les contrôleurs. Trois gardes cohabitent, et un `auth` nu lit la
garde par défaut, que l'authentification d'une autre garde a pu déplacer.

**Les liens vers le site public se génèrent depuis `APP_URL`, pas depuis l'hôte courant.**
`OfficeContext` force la racine des URL : sans ça, le lien d'accès qu'un administrateur remet dans
la file WhatsApp devenait `office.…/proprietaire/acces/…` — une page introuvable envoyée à un
propriétaire. Les routes du back-office portent leur domaine et n'en dépendent pas. Côté front,
« Voir sur le site » est un `<a>` vers `publicUrl`, jamais un `<Link>` Inertia : une visite Inertia
ne traverse pas d'hôte.

**C'est le seul endroit où `trust_level` s'écrit, et les règles vivent dans `ModerationService` :**

- **le niveau 2 exige un numéro vérifié** — il se lit « numéro et identité vérifiés » ; le numéro
  se confirme d'un bouton « Je l'ai eu au téléphone », qui enregistre un appel qui a eu lieu ;
- **le niveau 4 ne s'attribue pas, il s'atteint** par une confirmation de séjour ; et une annonce
  qui en porte ne redescend plus (ses confirmations contrediraient le niveau) ;
- **une annonce en ligne est au moins au niveau 2**, et **publier exige le niveau 2** : c'est
  l'appel qui met en ligne ;
- **renvoyer une fiche exige un motif** (`listings.review_note`), que le propriétaire lit en tête
  de sa fiche et de sa liste (« Vayla vous demande ») ; il s'efface quand il la renvoie.

**Les mêmes phrases servent au refus et à l'écran** (`pourquoiPasNiveau`, `pourquoiPasPublier`) :
chaque barreau fermé écrit sa raison dessous avant qu'on clique. Un refus (`OfficeRefusal`)
remonte en bandeau d'erreur par un gestionnaire unique dans `bootstrap/app.php`.

**Aucune règle métier réécrite** : annuler passe par `BookingService`, écrire par
`ConversationService` (au nom de `MessageAuthor::Vayla`, **dans le fil**, lu par les deux
parties — l'annulation y écrit aussi son motif), le lien d'accès par `OwnerNotifier`. Le
back-office est une troisième porte sur les mêmes services, comme le site et l'API mobile.
**Lire un fil depuis le back-office ne marque rien comme lu.**

**Le journal (`admin_actions`) est la contrepartie du pouvoir de publier.** Seuls les gestes qui
engagent quelqu'un s'y écrivent — consulter, non. Écrit par les services après que le geste a
réussi, jamais modifié (pas d'`updated_at`), et **le nom de l'administrateur y est recopié** : un
membre retiré ne rend pas ses décisions anonymes.

**La facture réglée se consigne (`invoice_settlements`), son montant ne se saisit pas** : il est
recalculé depuis la facture, seule la référence mobile money est un champ. Le mois en cours ne se
règle pas — ce n'est pas encore une facture.

**La file WhatsApp est à l'écran**, ce que faisait `vayla:whatsapp` (qui reste). Deux gestes
numérotés — ouvrir dans WhatsApp, puis « C'est parti » — et le second ne s'allume qu'après le
premier : marquer envoyé ce qui n'est pas parti ferait expirer une demande en silence.

**Le cadre (`OfficeShell`) est un gabarit persistant** (`defineOptions({ layout: OfficeShell })`) :
la colonne entre une fois, seul le contenu change — une entrée rejouée à chaque clic fatigue à la
vingtième. **La colonne est d'encre, la seule dalle sombre du produit**, pour qu'on ne publie
jamais depuis le mauvais onglet quand l'espace propriétaire est ouvert à côté. Rubriques dans
`Support/office.js` (testées comme celles des espaces), primitives `.of-*` dans `app.scss`
(lignes, pastilles d'état, champs — même 2,75 rem qu'ailleurs), `useOfficeMotion` pour les
comptes qui montent (`data-count`), les barres (`data-bar`) et les lignes qui sortent de la file
(`replier`). Le lagon n'y apparaît que sur ce qui est une vérification : l'échelle, « numéro
vérifié ». Tout ce qui attend quelqu'un prend la terre.

**Chaque écran sort en `Data`** (`app/Data/Office/…`) : aucun modèle n'arrive au front. Le back-office voit
plus que le site — adresses exactes, courriels, numéros — et c'est précisément pourquoi chaque
champ est nommé ; la clé d'accès, elle, n'en sort jamais (un test la cherche dans la réponse).

#### Les textes du site : pages éditoriales et textes de l'accueil

**Deux natures de texte, deux outils** — groupe « Contenu », « Textes du site » et « Pages ».

**Les pages éditoriales** (`pages`, `Services/Content/Pages`, écran public `Content/Show`) : « Comment ça
marche », « Tarifs », « Guide du propriétaire », « À propos », « Nous contacter », et les trois
pages légales. Écrites en **Markdown** — ce qu'on tape sans apprendre un éditeur —, avec une
barre d'outils qui pose les marques autour de la sélection, et un **aperçu rendu par le serveur**
(`POST /pages/apercu`), c'est-à-dire par le même moteur que la page publiée.

- **Le HTML tapé est retiré, pas échappé ni exécuté** (`Str::markdown`, `html_input: strip`,
  `allow_unsafe_links: false`) : un compte d'équipe compromis ne doit pas pouvoir poser un script
  sur le site. Un test poste `<script>`, `onerror` et un lien `javascript:`.
- **`{commission}` s'écrit depuis le réglage** au moment de l'affichage : recopié à la main, le
  taux de la page « Tarifs » mentirait le jour où il change.
- **Adresse courte, à la racine** (`/comment-ca-marche`) : la route `/{page}` est **la dernière
  de `web.php`**, un écran du site passe toujours devant, et `PageAddresses` refuse à une page
  l'adresse d'un écran — la liste est lue **sur le routeur**, pas recopiée. `deconnexion` est
  exclue du motif : POST seulement, un GET doit y répondre 405. **L'adresse se fige à la première
  publication** : elle a pu être partagée.
- **Publier est un geste à part d'enregistrer**, et une page qui porte encore « [à compléter] »
  refuse de se publier. **Les pages légales et le contact naissent ainsi**, en brouillon
  (`PageSeeder`) : ils demandent des faits que le dépôt n'a pas — éditeur, hébergeur, contact,
  droit applicable. Un texte juridique inventé serait pire que pas de texte. `internal_note` dit à
  l'équipe ce qui manque, et n'est jamais affichée.
- `is_system` : les pages attendues par le pied de page et la loi se dépublient, ne se suppriment
  pas, et gardent leur adresse.

**Le pied de page ne porte plus de lien mort.** Il avait cinq liens `#` (tarifs, guide, à propos,
contact, conditions). Il liste désormais les écrans du site, écrits dans `SiteFooter`, puis **les
pages publiées** de chaque colonne (prop partagée `pied`) ; les pages légales ont leur ligne en
bas. Une page en brouillon n'y apparaît pas. Un test vérifie qu'aucun `'#'` ne revient.

**Les textes de l'accueil et du pied de page** (`site_texts`, `Services/Content/Texts`,
`App\Support\SiteTextCatalog`, écran « Textes du site ») : titres, accroches, étapes, arguments.

- **L'original vit dans le code, la modification en base.** « Rétablir l'original » supprime la
  ligne ; un texte jamais touché n'est écrit qu'une fois, dans le catalogue.
- **Chaque texte porte sa borne et sa raison sous le champ** — la ligne soulignée du titre de
  l'accueil déborde d'un téléphone au-delà de vingt caractères (voir « L'accroche du hero contraint
  l'échelle typographique »). **Les points des clés sont échappés dans les règles**
  (`textes.accueil\.hero\.titre`) : sans ça Laravel lit un tableau imbriqué, ne trouve rien, et la
  borne ne s'applique jamais. Un test poste vingt et un caractères.
- **Ce que l'équipe tape est affiché, jamais interprété** : `useTextes().riche()` échappe d'abord,
  puis ne reconnaît que `**gras**` et le retour à la ligne. C'est la condition du `v-html`.
- **Ce qui n'est pas au catalogue ne se réécrit pas** : les boutons (des contrôles), les libellés
  des niveaux de confiance (ils viennent de `TrustLevel` — l'accueil doit dire ce que disent les
  fiches), tout ce qui est calculé.
- Lus sur chaque page, écrits une fois par mois : textes et liens du pied de page sont **en cache
  jusqu'à la prochaine modification**, et retombent sur le catalogue si la base ne répond pas.

Pour ajouter un texte modifiable : une entrée au catalogue (clé, libellé, borne, original), puis
`t('clé')` ou `v-html="riche('clé')"` dans le composant.

#### Les statistiques

`/statistiques` (`Services/Office/Stats`, `Pages/Office/Stats`) — des courbes, **et rien que des
comptes définis**.

**Trois écrans, rangés en sous-menu sous « Statistiques »** : *Demandes* (`/statistiques`, issue,
réponse, délai, destinations), *Séjours et commission* (`/statistiques/sejours`), *Catalogue et
inscriptions* (`/statistiques/catalogue`). Tout tenait sur une page qui gonflait à chaque
graphique. Séjours et commission partagent un écran parce qu'ils se rangent tous deux au mois du
départ : les chiffres de l'un expliquent ceux de l'autre. Chaque écran a sa requête
(`RequestStatsQuery`, `StayStatsQuery`, `CatalogueStatsQuery`) et ne calcule que ce qu'il montre ;
`StatsFrame` leur donne le cadre commun (période, démonstration, mois), et
`Partials/StatsHeader.vue` l'affiche.

**Le sous-menu** (`sous` dans `Support/office.js`) **s'ouvre sous sa rubrique quand on y est**, et
la rubrique mène au premier écran : la colonne reste courte, et les autres se découvrent au premier
clic. **Il emporte la période et la démonstration** d'un écran à l'autre — changer d'écran ne fait
pas repartir sur douze mois. Sous 960 px, il suit sa rubrique sur la rangée qui défile. Chaque graphique écrit sous son titre ce qu'il compte et à quelle date il le
range, parce que deux écrans qui ne tombent pas sur le même chiffre font douter des deux :

- une **demande** au mois où elle a été **faite**, par issue (acceptée, en attente, refusée,
  expirée, annulée) ;
- un **séjour** et sa **commission** au mois du **départ** — la règle de la facture ;
- un **règlement** au mois qu'il **solde**.

**Le délai de réponse est une médiane**, pas une moyenne ; **le taux de réponse ne compte que les
demandes tranchées** — une demande encore en attente n'a pas encore échoué ; **un mois sans
donnée n'est pas un zéro** (la courbe s'interrompt au lieu de plonger). **Pas de flèche de
tendance** : sur les volumes d'une plateforme qui démarre, « +200 % » veut dire « deux de plus ».
Des tests tiennent ces quatre règles.

**La commission a son bloc, en pleine largeur** (`CommissionStats`, `Stats/Partials/CommissionPanel.vue`) :
une courbe à deux traits disait « on facture plus qu'on ne reçoit » sans dire combien ni à qui
téléphoner. Le bloc répond dans l'ordre où on se le demande — les chiffres (volume des séjours,
taux appliqué, facturée, reçue, **reste à recevoir**, recouvrement, mois en cours), le graphique,
**qui doit encore** (par propriétaire, avec ses mois), puis le détail mois par mois, chaque mois
menant à sa facturation. Mêmes règles que la facture, pour que les deux écrans tombent sur les
mêmes montants : une facture est celle d'un propriétaire pour un mois de départ, un règlement
solde ce mois-là. **Le mois en cours n'est pas une facture** : il n'entre ni dans le reste ni
dans le recouvrement, et sa ligne dit « pas encore dû » — un reste affiché là ferait relancer un
propriétaire pour une somme qu'il ne doit pas encore. Un séjour confirmé **après** le règlement
de son mois rouvre un reste sur ce mois. Les mois sans séjour ni règlement sortent du tableau,
et l'écran dit combien. Le reste prend la terre ; le lagon n'y entre pas.

**La grille (`.of-graphes` dans `app.scss`) est en `grid-auto-flow: dense`** : un bloc pleine
largeur (`.of-graphes__large`) ne laisse pas de case vide derrière lui, à deux colonnes comme à
trois.

**La démonstration est incluse tant qu'elle existe, et l'écran le dit** dans un bandeau, avec un
bouton pour la retirer (`?demo=0`) : des courbes nourries de réservations fictives ne doivent
jamais passer pour l'activité réelle. La période (6, 12 ou 24 mois) vit aussi dans l'adresse.

**Les graphiques sont dessinés à la main, en SVG** (`Components/Office/OfficeChart.vue`, courbes,
barres ou barres empilées ; `OfficeHBars.vue` pour les comparaisons) — aucune bibliothèque, qui
pèserait plus que le back-office et imposerait ses couleurs. Le SVG est **mesuré à sa largeur
réelle** (`ResizeObserver`), jamais étiré par un `viewBox`, qui déformerait textes et traits. **Un
mois se lit au survol, au doigt et au clavier** (flèches), et son relevé s'écrit au-dessus du
graphique ; chaque graphique a son **tableau** replié, pour les lecteurs d'écran et pour recopier
un chiffre. GSAP trace les courbes et fait monter les barres, à l'arrivée et quand la période
change — rien sous `prefers-reduced-motion`. Le lagon n'y apparaît que sur l'échelle de confiance.

Le regroupement par mois se fait **en PHP**, pas en SQL : les fonctions de date diffèrent entre
PostgreSQL et SQLite, et les volumes tiennent en mémoire. Le jour où ils ne tiendront plus, ce
sera une vue matérialisée — et `OfficeStatsRepository` sera le seul fichier à changer.

**`php artisan vayla:historique-demo`** (local uniquement) écrit des mois de demandes **passées**,
toutes `is_demo`, pour voir les courbes vivre sur une base fraîche. Rien dans le futur : aucune
nuit bloquée, aucun calendrier touché. Et les règles tiennent aussi pour la démonstration : un
séjour n'est « effectué » qu'avec une confirmation de voyageur, sur une annonce de niveau 4 —
ailleurs il reste « accepté ». `--retirer` l'efface ; un `make seed` aussi.

**Le contenu du back-office va jusqu'aux bords de l'écran** : `.of__corps` n'a plus de largeur
maximale. Borné à 76 rem, il laissait un tiers d'un écran de travail vide pendant que les listes
se tassaient et que les graphiques se lisaient mal. Ce qui doit rester étroit — un texte, un
formulaire de mot de passe — porte sa propre mesure.

#### La photothèque

`/phototheque` (`PhotoLibraryQuery`, `TeamPhotoUploader`, `PhotoCreditEditor`, `PhotoRemover` ; `Office/Photos/Index`) — toutes les photographies du
site, **leurs crédits, et où chacune apparaît**. Une grille à gauche, la photo choisie en grand à
droite (la même lecture que la galerie d'une destination), avec ses usages en liens, son poids
sur le disque et ses trois tailles. La photo choisie vit dans l'adresse (`?photo=`) : le journal
et la page d'une destination y renvoient.

**Le crédit se corrige ici**, parce que c'est lui qui s'affiche au pied de chaque page. Ce qu'on
peut toucher dépend de la provenance, et ce qu'on ne peut pas n'est pas un bouton grisé mais une
phrase :

| Provenance | Légende | Auteur, source | Licence | Supprimer |
|---|---|---|---|---|
| Commons (`lieux`) | oui | oui | **non** — celle de l'auteur, souvent en 2.0 ou 3.0 | jamais |
| Équipe (`destinations`) | oui | oui | oui | seulement si elle n'illustre rien |
| Propriétaire (`annonces`) | oui (texte lu aux malvoyants) | — | — | depuis son annonce |
| Démonstration (`an-`, `ia-`) | non | non | non | disparaît avec elles |

- **Une photo de l'équipe qui n'illustre rien n'est pas créditée** (`PhotoRepository::credited`) :
  on ne crédite pas une image qu'on ne publie pas. On peut donc téléverser d'avance, sans
  destination, et l'ajouter à une galerie plus tard.
- **`PhotoSeeder` ne réécrit plus la légende, l'auteur ni la page d'origine** d'une photo
  existante — la règle des autres référentiels : un `make seed` effaçait sinon les corrections de
  l'équipe. Un test corrige puis rejoue le seeder.
- **Un seul chemin de téléversement** (`TeamPhotoUploader`), depuis la photothèque comme depuis la
  page d'une destination : il produit, crédite, et range dans la galerie quand une destination est
  donnée (`DestinationGallery`).

**Un formulaire ne s'imbrique pas dans un autre.** L'ajout de photo de la page destination était
un `<form>` dans le `<form>` de la destination : son `submit` remontait, l'enregistrement de la
destination partait aussi, et Inertia **annulait l'envoi de la photo** en cours de route. C'est un
groupe maintenant ; un balayage des templates n'en a pas trouvé d'autre.

#### La gestion de contenu

**Le back-office ne fait pas que modérer : il corrige.** Contenu des annonces, destinations,
catégories du rail, équipements, réglages — tout ce qui portait le site sans qu'on puisse le
toucher ailleurs que dans le code ou les seeders. Groupe « Contenu » de la colonne ;
`Services/Office/Content` : un `…Editor` pour les gestes, un `…Query` pour les écrans.

- **Le contenu d'une annonce, tout le contenu** (`/annonces/{id}/modifier`) — y compris ce que le
  propriétaire ne peut plus toucher après vérification : c'est le rôle de Vayla de corriger une
  capacité mal saisie ou de remplacer une photo floue. Mêmes bornes que la fiche du propriétaire
  (`OfficeListingRequest` **hérite** d'`OwnerListingRequest`, elle ne la recopie pas), plus la mise
  en avant et les catégories. **Le journal écrit les champs changés** (« titre, capacité,
  tarif »), et rien quand rien n'a changé. `trust_level`, `status` et `slug` n'entrent pas par
  là : ils ont leurs gestes à eux. Un long formulaire à sections, pas un assistant — l'équipe
  corrige, elle ne découvre pas —, avec une barre collée qui dit s'il reste quelque chose à
  enregistrer. Les photos passent par `PhotoUploadService`, comme côté propriétaire.
- **Saisir une annonce pour un propriétaire** qui la dicte au téléphone
  (`/proprietaires/{id}/annonces/nouvelle`) : elle naît en brouillon, au niveau 1. La saisir
  n'est pas la vérifier.
- **Une clé publique ne bouge jamais** — slug de destination, clé de catégorie ou d'équipement.
  Elle naît du libellé à la création, puis elle est figée : c'est une adresse, un filtre d'URL et
  un mot de l'API mobile. On renomme le libellé ; un test poste une autre clé et vérifie qu'elle
  est ignorée.
- **On ne supprime pas ce qui porte une déclaration** : un équipement coché par des logements,
  une destination qui a des logements. L'écran ne propose même pas le bouton, et le service
  refuse avec la raison.
- **« Tout » et « Séjour confirmé » sont des filtres, pas des étiquettes**
  (`StructuralCategory`) : ils se renomment et se déplacent, mais ne se posent sur aucune
  annonce et ne se suppriment pas. « Séjour confirmé » se déduit du niveau 4 — et **ne se vend
  pas**.
- **Une place achetée se dit** : `categories.sponsored` affiche « Sponsorisé » sous le libellé,
  dans le rail public. Le titre du rail reste éditorial ; une position vendue qu'on tairait
  ferait du rail un classement déguisé.
- **Une destination porte une galerie** (`destination_photo`), comme une annonce : la position 0
  est la couverture — l'atlas et l'en-tête de sa page —, et la page publique montre les autres en
  pellicule (un bouton par vignette, jamais de diaporama automatique). **`destinations.photo_id`
  reste**, parce que l'atlas, l'accueil et l'API la lisent, mais **elle n'a qu'un écrivain**,
  `DestinationGallery::synchroniserCouverture()` (`DestinationGalleryService`), qui la recopie depuis la position 0 à chaque
  geste ; un test vérifie qu'elles ne divergent jamais, et le formulaire de la destination ne
  l'écrit plus. On range (glisser-déposer et flèches), on voit chaque photo en grand, on ajoute depuis la photothèque (Commons,
  jamais une image générée, jamais une `an-` qui disparaîtra avec les annonces de démonstration),
  on retire : une photo **téléversée** qui ne figure plus dans aucune galerie est effacée avec ses
  fichiers, une photographie de **Commons** est seulement détachée. Chaque photo **se téléverse**
  aussi — la photothèque ne contenait que les onze photos déjà posées, une par destination. Une photo téléversée va dans
  `images/destinations/` — **jamais dans `lieux/`**, que `PhotoSeeder` possède et que
  `PhotoFilesTest` compare au disque —, passe par le même traitement que les photos d'annonce
  (`PhotoProcessor::produire`, implémenté par `GdPhotoProcessor` : 4/3, 800 à 3200 px, refus sous 1 200 px), et exige son
  **crédit** (légende, auteur, licence parmi l'enum `PhotoLicence`) et une **case
  cochée** : une vraie photographie de ce lieu, que Vayla a le droit de publier — la règle photo,
  déclarée à chaque fois. Elle arrive au bout de la galerie, et est créditée au pied de page.
- **Les pictogrammes se choisissent parmi ceux qui sont dessinés.** Ceux du rail vivent dans
  `Support/categoryIcons.js`, lu par le rail **et** par le back-office ; un test compare ses clés
  à l'enum `CategoryIcon` côté serveur, et celles de `DestinationScene` à `SceneArt`. Ceux des équipements
  sont ceux déjà en base — un nom inventé n'aurait pas de tracé.
- **Les réglages** (`settings`, `SettingsService`) : le taux de change **et sa date, saisie avec
  lui** — `SettingExchangeRate` remplace `ConfigExchangeRate` derrière `ExchangeRateProvider`, la
  couture prévue —, et le taux de commission **des nouvelles demandes** (chaque réservation fige
  le sien, un test le vérifie). Le `.env` reste le repli : une ligne absente, ou une table pas
  encore migrée, ne fait jamais tomber une page. Changer la commission demande une
  confirmation.

**Les seeders des référentiels créent ce qui manque, ils ne réécrivent plus.** `CategorySeeder`,
`AmenitySeeder` et `DestinationSeeder` étaient en `updateOrCreate` : un `make seed` aurait
rétabli les libellés d'origine et effacé en silence le travail de l'équipe. Ils sont en
`firstOrCreate` (la destination repose seulement une photo perdue). Conséquence assumée : corriger
un libellé dans un seeder ne change plus une base existante — c'est le back-office qui le fait.
Un test modifie deux lignes, rejoue les seeders et vérifie qu'elles ont tenu.

### L'euro à côté de l'ariary

`≈ 37 €` sous `185 000 Ar`. Un voyageur étranger ne sait pas ce que valent
185 000 Ar : il ne peut pas dire s'il regarde un studio ou une villa, exactement au moment où
il choisit. **Le site n'est pas multilingue et ne le sera pas avant d'avoir un vrai catalogue**
— l'interface se traduit une fois, le contenu se retraduit à chaque annonce — mais la devise,
elle, ne coûte rien et lève un blocage réel.

Quatre règles, et la première tient les trois autres :

- **L'ariary est le prix, l'euro est une aide à la lecture.** Jamais l'euro seul, jamais en
  premier, toujours plus petit et en gris (`.eur`). Le voyageur règle **sur place, en ariary** :
  un prix affiché en euros seul le ferait arriver avec une idée fausse de ce qu'il doit sortir.
- **Jamais de centimes.** `≈ 37 €`, pas `36,84 €` : une précision au centime sur une conversion
  indicative est une fausse précision, et sur un site dont l'argument unique est la
  vérification, un faux chiffre précis coûte plus cher qu'un chiffre rond.
- **Le taux porte sa date**, écrite là où l'argent se décide — encart de réservation, formulaire,
  confirmation. Un montant converti sans sa date est invérifiable. La date est **saisie avec le
  taux**, jamais déduite de `now()`.
- **Aucun euro sur ce que le propriétaire reçoit ou doit.** Il est réglé en ariary par mobile
  money : un euro à côté d'un total de séjour, d'une commission ou d'une ligne de facture serait
  un chiffre de plus à rapprocher, pour rien. **Une exception, et elle est le contraire d'une
  entorse : le champ « tarif » du formulaire d'annonce.** Ce montant-là n'est pas de l'argent
  qu'il touche, c'est le prix que des voyageurs européens vont lire sur sa fiche — le lui cacher
  pendant qu'il le fixe lui interdirait de voir son annonce comme la voit la moitié de ses
  clients. `ExchangeRateTest` énumère les écrans d'argent et les vérifie **statiquement**, sur les
  fichiers — le taux étant partagé globalement, une assertion sur la réponse ne prouverait rien.

**Rien n'est converti côté serveur.** Les montants restent des entiers d'ariary de bout en bout ;
ce sont **le taux** et sa date qui voyagent (prop partagée `devise`, et `/api/v1/exchange-rate`
pour l'application mobile, qui doit afficher les mêmes ordres de grandeur que le site). C'est la
seule façon de convertir un total qui n'existe que côté client — les nuits choisies × le tarif.
Conséquence assumée : deux écrans peuvent différer d'un euro sur des lignes arrondies
séparément ; le signe « ≈ » le dit, et Vayla n'encaisse aucun de ces montants.

**Le taux vient d'une interface, pas d'un `config()` semé dans les vues** :
`App\Contracts\Currency\ExchangeRateProvider`, aujourd'hui `ConfigExchangeRate`
(`VAYLA_EUR_RATE`, `VAYLA_EUR_RATE_DATE`), demain une API de change — seule la liaison
d'`AppServiceProvider` changera. L'implémentation réseau devra **ne jamais lever ni bloquer une
page** : une fiche de logement qui échoue parce qu'un service de change ne répond pas serait une
panne pour un agrément. Elle met en cache et retombe sur la dernière valeur connue.

Côté front, tout passe par `Composables/useDevise.js` — `euros()`, `eurosFourchette()`,
`mention` — jamais par une division écrite dans un composant.

**Le taux se règle maintenant depuis le back-office** (`SettingExchangeRate`, écran « Réglages »),
avec sa date ; `VAYLA_EUR_RATE` et `VAYLA_EUR_RATE_DATE` ne servent plus que de repli.

### Le code à usage unique (OTP)

`App\Services\Otp` — **envoi automatique, derrière une couture de fournisseur.**

**Le code lui-même est trivial ; tout ce qui compte est autour.** Cinq bornes, et chacune répare
une attaque réelle :

- **Le code est stocké haché** (bcrypt), jamais en clair. Une base copiée ne doit pas livrer des
  codes vivants : six chiffres se rejouent en une seconde.
- **Cinq essais par code.** Un million de combinaisons, cinq tirages : le hasard est hors de
  portée, et personne n'est bloqué pour deux fautes de frappe.
- **Le compteur d'essais est sur le code, pas sur le numéro.** L'inverse permettrait d'enfermer
  quelqu'un dehors en épuisant ses essais depuis l'extérieur.
- **Soixante secondes avant de pouvoir en redemander un.** Sans ce délai, « renvoyer » devient un
  distributeur de messages payants.
- **Cinq codes par heure et par numéro.** La seule borne qui protège à la fois le portefeuille de
  Vayla et le téléphone d'un tiers : sans elle, un inconnu fait envoyer vingt messages sur le
  numéro de son choix.

**Un nouveau code annule le précédent** : deux codes vivants doublent les chances d'un tirage au
sort, et celui qui en a reçu deux essaie le mauvais et croit le service cassé. **Un code qui n'est
pas parti est tué** — sinon il occupe le quota horaire et bloque la vraie tentative suivante.
`random_int` et non `rand` : ce dernier est prévisible à partir de quelques tirages.

**Trois canaux, une interface** (`OtpSender`), choisis par `VAYLA_OTP_DRIVER` :

| Pilote | Ce qu'il fait | Ce qu'il exige |
|---|---|---|
| `log` (défaut) | écrit le code dans `storage/logs` | rien |
| `whatsapp` | Meta Cloud API | modèle « Authentication » approuvé, `phone_number_id` |
| `sms` | Twilio | compte payant — **pas d'entreprise enregistrée** |

**`log` est le défaut, et ce n'est pas un détail** : envoyer pour de vrai doit être un choix
explicite, jamais le résultat d'un oubli de configuration — sinon la mise au point d'un écran
part sur de vrais téléphones, et se facture. Un test tient cette règle.

Côté WhatsApp, trois choses qui font échouer l'envoi sans rien expliquer : Meta **impose un
modèle approuvé** (pas de texte libre à qui n'a jamais écrit), le numéro part **sans le `+`**, et
l'identifiant à configurer est le `phone_number_id` de l'expéditeur, pas son numéro. Le compte de
test de Meta permet de tout mettre au point sans entreprise vérifiée — il n'envoie qu'à des
numéros déclarés à l'avance.

**`vayla:whatsapp-check` existe parce que les causes d'échec se ressemblent à l'écran.** « Le
message n'a pas pu partir » peut vouloir dire jeton périmé, modèle non approuvé, destinataire
absent de la liste de test, ou composant bouton qui ne correspond pas au modèle. Meta répond
précisément à chacune, et le service masque cette réponse à l'utilisateur — à raison. Le
diagnostic la montre en entier, et traduit les quatre codes qu'on rencontre vraiment (190,
131030, 132000, 132001). Il envoie `hello_world`, le modèle fourni par Meta qui ne prend aucun
paramètre : s'il part, les identifiants sont bons et ce qui reste à régler est le modèle. Sans
cette séparation, on corrige au hasard.

`WHATSAPP_OTP_COPY_BUTTON` existe pour la même raison : envoyer le composant bouton à un modèle
qui n'en a pas échoue, l'omettre sur un modèle qui en a un échoue aussi, et Meta ne nomme la
cause dans aucun des deux cas.

`php artisan vayla:otp <numéro>` envoie, `--code=XXXXXX` vérifie. La commande **dit le canal**
avant d'envoyer : sans ça on croit avoir testé WhatsApp alors qu'on écrivait dans un journal.

### Les notifications WhatsApp

**C'est le maillon qui rend tout le reste utile.** L'espace propriétaire, le calendrier, le fil
d'échange : rien ne sert si le propriétaire ne sait pas qu'on l'attend. Une demande a 48 h pour
être répondue — sans message, il faudrait qu'il pense à ouvrir Vayla dans cette fenêtre.

**WhatsApp, pas l'e-mail** : c'est là que sont nos propriétaires, beaucoup n'ont pas de boîte
qu'ils relèvent.

**Le message est écrit en file (`outbound_messages`), pas envoyé** — et la raison n'est pas
technique : **l'API WhatsApp Business de Meta exige une entreprise enregistrée**, que Vayla n'a
pas encore. L'envoi se fait donc à la main, par un lien `wa.me` prêt à cliquer :
`php artisan vayla:whatsapp` liste ce qui reste, `--envoye=<id>` marque comme parti,
`--test=<numéro>` vérifie le canal avant d'y mettre un vrai propriétaire. Ça ne coûte rien et
ça marche aujourd'hui.

Écrire d'abord, envoyer ensuite vaudra aussi le jour où l'API arrive : un message qui part
directement dans un appel HTTP est un message perdu quand l'appel échoue. Seul l'expéditeur
changera — les textes, la file et les règles de non-répétition restent.

Quatre motifs (`NotificationKind`), pas un de plus : nouvelle demande, rappel avant expiration,
message d'un voyageur, lien d'accès. Au-delà de ce qui exige vraiment une action, on devient
l'expéditeur qu'on met en sourdine — et le jour où une demande expire, le message qui comptait
est noyé.

- **Le fait d'abord, le lien en dernier.** On lit WhatsApp dans une notification tronquée : la
  première ligne doit suffire à savoir s'il faut ouvrir.
- **Ce qu'on risque à ne rien faire est écrit** : « sans réponse, les nuits repartent dans votre
  calendrier » est la seule phrase qui fait répondre.
- **Jamais deux fois le même message** (`dejaEnfile`). Le rappel avant expiration ne part qu'une
  fois, à un tiers du délai restant — assez tôt pour pouvoir encore appeler le voyageur, assez
  tard pour ne pas doubler le message d'arrivée. C'est la notification qui rattrape le plus de
  demandes : celui qui n'a pas répondu n'a en général pas décidé de refuser, il a oublié.
- **Seul le voyageur déclenche.** Prévenir le propriétaire de son propre message serait absurde,
  et le fil ne notifie pas le voyageur : il n'a pas de compte, et son numéro sert au
  propriétaire qui l'appelle.
- **Le corps est figé à l'écriture**, jamais recomposé à l'envoi : un message régénéré dirait
  « il vous reste 41 h » alors qu'il en reste douze.
- **Un numéro injoignable ne met pas de ligne morte dans la file** — un fixe malgache (020) ne
  reçoit pas WhatsApp, et la file est la liste de travail d'une personne.
- **L'urgent passe devant** : celui qui envoie à la main n'a pas le temps de trier.
- **Le message part après la transaction**, jamais dedans : écrit à l'intérieur, il annoncerait
  une demande qui n'existe pas le jour où la transaction est annulée.

Le lien `wa.me` prend le numéro **sans le `+`** : le service l'exige, et un `+` laissé là ouvre
une conversation vide sans dire pourquoi.

### Le modèle économique, en trois règles

Vayla **n'encaisse rien**. Une réservation met en relation et bloque des dates ; l'acompte se
convient entre le voyageur et le propriétaire, hors plateforme. La commission est facturée au
propriétaire **en fin de mois**, réglée par mobile money.

- **On facture le séjour effectué, jamais la réservation.** Une réservation acceptée n'est
  qu'une intention : facturer là-dessus reviendrait à facturer les no-shows, les annulations
  et les gens qui se sont arrangés ailleurs — le propriétaire refuserait de payer, et il aurait
  raison. Seul `BookingStatus::Completed` est facturable, et il ne s'atteint que par une
  **confirmation du voyageur**. C'est aussi ce qui rend la facture vérifiable : le propriétaire
  sait qui a dormi chez lui, et Vayla ne peut pas fabriquer de séjour.
- **Le prix et le taux sont figés à la réservation.** `price_per_night`, `total` et
  `commission_rate` sont recopiés sur la réservation. Le propriétaire peut réévaluer son tarif
  ou le taux peut monter : la ligne déjà engagée ne bouge pas. Une facture qui change après
  coup est une facture qu'on ne paie pas.
- **Une demande bloque les dates immédiatement, et les rend si personne ne répond.** Sans le
  blocage, deux voyageurs réservent la même semaine ; sans l'expiration
  (`vayla.booking.hold_hours`, 48 h, commande horaire `vayla:release-expired-bookings`), un
  propriétaire distrait verrait son calendrier se fermer tout seul et quitterait la plateforme.
  Les deux vont ensemble.

**Une nuit appartient à sa date d'arrivée** : `blocked` va de `arrival` à `departure − 1 jour`,
et le jour du départ reste réservable par le voyageur suivant. C'est la même règle que pour les
`unavailabilities`, et l'oublier retire une nuit vendable à chaque réservation.

**La soustraction de ce jour vit dans `App\Data\SejourData`, et nulle part ailleurs.** Elle
était déjà écrite dans `AvailabilityService` et `BookingService` ; le filtre de recherche en
aurait fait une troisième, et trois écritures d'une même règle garantissent qu'une des trois
finit par mentir. `derniereNuit()` et `couvre()` sont les seules portes d'entrée.

**La recherche par dates est un filtre serveur, jamais client.** `arrival` et `departure` vont
**par paire** — le contrat les refuse séparément, parce qu'un critère à moitié posé qui ne
filtre rien en silence est pire que pas de critère (c'était le défaut du moteur : deux champs
de dates collectés puis jetés). Le dépôt écarte trois cas : une période déclarée qui couvre les
nuits, une réservation bloquante non expirée qui les couvre, et un séjour hors des bornes
`min_nights` / `max_nights` du logement. `SearchDatesTest` tient les deux bornes qui coûtent
cher — arriver le lendemain de la dernière nuit occupée, et partir le jour où une période
commence.

`owners.mobile_money` est **un numéro de téléphone**, celui vers lequel le propriétaire pousse
son règlement — aucun jeton, aucun moyen de débiter quoi que ce soit. Le formulaire de
réservation ne demande ni carte, ni acompte, ni compte : un test vérifie qu'aucun champ
financier n'entre en base.

- **Il n'y a pas de note sur cinq, et il n'y en aura pas.** Les avis prennent la forme de
  `stay_confirmations` : un voyageur coche des **faits vérifiables** (`App\Enums\ConfirmationPoint` :
  photos, adresse, équipements, prix, propriétaire, propreté) et **signale ce qui ne
  correspondait pas**. Aucune colonne `rating`, aucune moyenne calculée nulle part — un
  chiffre unique finit toujours par remplacer les faits qu'il résume, et redevient la moyenne
  étoilée que tout le produit refuse. Sur les grandes plateformes tout le monde est à 4,8 :
  ça n'informe plus personne.

  **`flagged` s'affiche à côté de `confirmed`, à la même taille.** Enterrer les « non » est
  exactement ce que Vayla reproche aux autres. Un jeu de démonstration où tout le monde
  confirmerait tout ne prouverait rien — un test vérifie qu'il contient au moins un
  signalement.

  Seules les annonces de **niveau 4** en portent : le niveau se définit par « des voyageurs y
  ont dormi et ont confirmé ». Des confirmations sur une annonce de niveau 2 seraient une
  contradiction dans les données, et un test le refuse.

  Prénom seul, jamais de moyen de recontacter le voyageur. Les confirmations de démonstration
  portent `is_demo` **et** un avertissement dans le bloc lui-même : le bandeau « Aperçu » du
  haut de page ne suffit pas quand on rapporte les mots d'une personne.
- **Le calendrier ne dit pas « est-ce libre », il dit « est-ce le bon moment ».** C'est le
  seul endroit du produit qui n'a pas d'équivalent ailleurs, et c'est délibéré : un calendrier
  de disponibilités est un composant banal, et sur Vayla il est même plus faible qu'ailleurs
  puisque rien ne s'y réserve — une case libre ne prouve rien. La **saison**, en revanche,
  n'est écrite nulle part : Nosy Be en février est en saison cyclonique, Antsirabe en juillet
  descend sous dix degrés la nuit, Sainte-Marie en août ce sont les baleines. Un voyageur
  l'apprend aujourd'hui sur place, donc trop tard.

  Six façades (`App\Enums\ClimateZone`), douze mois chacune, six états
  (`App\Enums\SeasonKind`). **Enum et non table** : ce sont des normales climatiques, pas de
  la donnée éditable — et surtout, personne ne doit pouvoir effacer un avertissement gênant.
  Andasibe est en `est-foret` et non `est` : à trois heures de la mer, lui servir les baleines
  de Sainte-Marie ferait dire au calendrier une chose fausse.

  **Les mauvais mois sont écrits comme les bons** : c'est « risque cyclonique » en février qui
  rend « meilleure période » crédible en août. Et l'écran dit que ce sont des tendances de
  saison, **pas une prévision météo** — sur un site dont l'argument unique est la vérification,
  laisser croire à une prévision suffirait à tout casser.

  Le **ruban de l'année** (`SeasonRibbon.vue`) est l'élément signature : douze segments, plus
  c'est dense meilleure est la période. L'échelle est **neutre**, et la seule couleur qui y
  entre est la terre, sur le risque cyclonique. Un ruban arc-en-ciel aurait été plus joli et
  aurait cassé la règle chromatique : le lagon ne dit que « vérifié », jamais « beau temps ».
- **Le calendrier dit l'occupation, pas la disponibilité.** `unavailabilities` liste les
  périodes prises ; **une date est libre tant qu'aucune ligne ne la couvre**. L'inverse aurait
  obligé le propriétaire à déclarer chaque jour libre des douze prochains mois, et un oubli
  aurait fermé son calendrier au lieu de l'ouvrir. `ends_on` est la **dernière nuit occupée**,
  jamais la date de départ : une nuit appartient à sa date d'arrivée, un séjour du 12 au 15
  fait trois nuits et libère le 15. Confondre les deux retire une nuit vendable à chaque
  période — c'est l'erreur qui fait mentir le prix affiché.
- **Une annonce porte une galerie, pas une image**, et **la couverture est la photo de
  position 0** du pivot `listing_photo`. Aucune colonne `photo_id` à côté : elle aurait permis
  qu'une couverture n'appartienne pas à la galerie. Les photos restent partagées — une même
  photographie de Commons illustre une destination et une annonce, avec un seul crédit.
- **`perks` est dérivé, plus jamais saisi** : ce sont les libellés des équipements marqués
  `highlight` sur le pivot, triés par rubrique. Une carte ne peut donc plus vanter un
  équipement que la fiche ne détaille pas — un test le vérifie sur les huit annonces.
  `highlight` et `note` vivent sur le pivot parce qu'ils dépendent du logement : une piscine
  est l'argument d'une villa balnéaire et un détail dans un lodge de forêt.

Le vocabulaire est **écrit pour Madagascar, pas traduit d'une plateforme du Nord**. La rubrique
« Énergie et eau » (groupe électrogène, panneaux solaires, onduleur, puits ou forage, réserve
d'eau) n'existe nulle part ailleurs et c'est pourtant elle qui décide d'un séjour ici ; les
moustiquaires sont déclarées deux fois, aux ouvertures et au-dessus du lit, parce que les deux
ne se valent pas ; « Accès en 4×4 nécessaire » se dit avant la réservation, pas à l'arrivée du
voyageur devant une piste. `filterable` marque le sous-ensemble qui a droit à une case dans la
recherche : personne ne cherche un logement par grille-pain.

Le filtre par équipements de `/api/v1/listings` est **conjonctif** — un `whereHas` par clé
demandée. Cocher deux cases pose deux conditions ; un seul `whereHas` avec `whereIn` ramènerait
les logements qui n'en ont qu'un.

`make seed` rejoue tous les seeders et doit pouvoir se rejouer **le lendemain** sans rien
dupliquer. `UnavailabilitySeeder` et `StayConfirmationSeeder` calculent leurs dates depuis
`Carbon::today()` : s'en servir comme clé de `updateOrCreate` faisait glisser la clé d'un jour
à chaque nouvelle journée, la ligne de la veille n'était plus reconnue et une seconde était
créée — chaque avis affiché deux fois, chaque période bloquée en double. Ces deux-là
**reprennent leurs lignes à zéro** (elles leur appartiennent entièrement) au lieu d'essayer de
les reconnaître ; `SeederIdempotenceTest` rejoue les seeders un jour plus tard et compare les
comptages. Les autres restent en `updateOrCreate` sur une clé stable.

`ListingSeeder` **lève** si une clé d'équipement n'existe pas, plutôt que de créer une annonce
silencieusement amputée. Les tests appellent `$this->seed()` sans énumérer les seeders : lister
les classes obligeait à penser aux tests à chaque ajout, et c'est exactement ce qui a été
oublié.

### Service IA

`App\Services\Ai\AiService` parle à n'importe quelle API compatible OpenAI
(`/chat/completions`) : changer `AI_BASE_URL` suffit à changer de fournisseur (OpenAI, Mistral,
Groq, OpenRouter, Ollama). Exposé via `POST /ai/chat`, limité à `throttle:20,1`.

## Pièges

- **Plus Jakarta Sans ne dessine pas l'espace fine insécable (U+202F)**, celle que
  `Intl.NumberFormat('fr-FR')` met entre les milliers : le navigateur lui donne une chasse
  nulle et « 185 000 Ar » s'affiche « 185000 Ar » — sur toutes les cartes, tous les filtres,
  l'encart et la facture. Tout le formatage passe donc par `resources/js/Support/format.js`,
  qui repasse en U+00A0. **Ne pas rappeler `Intl.NumberFormat` directement.**
- **La forme compacte de l'en-tête suppose un moteur de recherche à encastrer.** Elle efface la
  navigation pour lui faire place ; sans moteur — fiche, catalogue, destination — l'en-tête se
  retrouvait vide au milieu au premier défilement. `SiteHeader` ne bascule en compact que si
  `search` est vrai.
- **`docker/entrypoint.sh` exécute `config:cache` + `route:cache` + `view:cache` à chaque
  démarrage du conteneur.** Une modification de `routes/`, `config/` ou d'un `.env` reste sans
  effet tant que `make cache` n'a pas tourné.
- **`env_file` fige le `.env` à la *création* du conteneur, pas à chaque commande.** Éditer
  `laravel/.env` puis lancer `make cache` ne suffit pas : la variable reste à son ancienne
  valeur dans l'environnement du conteneur, et Laravel lit `$_SERVER` en premier. C'est ce qui
  a fait sortir les dates des notifications WhatsApp en anglais malgré `APP_LOCALE=fr`. Il faut
  **recréer** le conteneur :

  ```bash
  docker compose up -d --force-recreate app
  ```
- **`APP_LOCALE=fr`**, et pas seulement pour la forme : `translatedFormat` sort « 18 Jan » au
  lieu de « 18 janv. » dans les messages WhatsApp, qui sont le seul endroit du produit où une
  date est composée côté serveur.
- **Ne pas clé un `updateOrCreate` de seeder sur une donnée qui bouge.** `OwnerSeeder` utilisait
  le téléphone ; depuis qu'il est un identifiant de connexion, il change — et le seeder ne
  reconnaissait plus la ligne, créant un second propriétaire du même nom, sans logement. La clé
  est maintenant `['name' => …, 'is_demo' => true]`, que le seeder possède entièrement.
- **`docker/nginx/nginx.conf` est copié dans l'image, pas monté** : le modifier demande
  `docker compose build app && docker compose up -d app`. C'est aussi vrai de `docker/php/*` et de
  `supervisord.conf`. Et une erreur nginx ne remonte jamais dans les tests : le journal est
  `/var/log/nginx/error.log`, dans le conteneur.
- `docker/php/php.ini` est un profil **développement** (`display_errors=On`,
  `opcache.validate_timestamps=1`). La bascule production est documentée en commentaire dans
  le fichier ; ne pas la faire à la légère.
- Les tests tournent sur SQLite en mémoire, pas sur PostgreSQL : le code qui dépend du schéma
  `vayla` ou de particularités pgsql ne sera pas couvert. **Ce qui le garantit tient à quatre
  lignes `<server force="true">` de `phpunit.xml`, pas aux `<env>`** : `docker-compose` exporte
  `DB_CONNECTION` et `DB_DATABASE` comme vraies variables du conteneur, Laravel les lit via
  `$_SERVER` (premier adaptateur consulté) et les `<env>` de PHPUnit n'écrivent que dans
  `putenv()` et `$_ENV`. Sans elles, `make test` tourne sur PostgreSQL et `RefreshDatabase`
  **vide la base de développement**. Ne pas les retirer ; après toute modification de
  `phpunit.xml`, vérifier que `make test` ne change pas le nombre de lignes en base.
- **`redirectGuestsTo` / `redirectUsersTo` sont globaux — ils ne connaissent pas la porte.** Ils
  pointaient tous les deux sur l'espace propriétaire, écrits à l'époque où il était le seul espace
  derrière une connexion. Depuis qu'un voyageur a un compte, c'était faux **dans les deux sens** :
  un voyageur connecté qui rouvrait `/inscription` atterrissait sur l'écran de connexion
  *propriétaire*, où on lui demandait un numéro de téléphone qu'il n'a jamais donné ; un voyageur
  déconnecté demandant `/mes-reservations` aussi. Le chemin se déduit maintenant de la **route
  demandée** (`$request->is('proprietaire*')`) et non du garde : au moment où ces fermetures
  s'exécutent, justement, il n'y a pas de garde authentifié.
- **Le cache de routes survit à `config:clear` — et fait échouer un test sur un 500 muet.**
  L'entrypoint compile aussi les routes. Une route supprimée reste alors servie, le contrôleur n'a
  plus la méthode, et la suite répond `Call to undefined method` là où on attendait un 404 : le
  message ne désigne jamais le cache. `make test` lance donc `route:clear` en plus de
  `config:clear`.
- **Le cache de configuration bat les `<server>` de `phpunit.xml`, et ça vide la base de
  développement.** `docker/entrypoint.sh` lance `config:cache` à chaque démarrage du conteneur ;
  un `bootstrap/cache/config.php` compilé **court-circuite l'environnement** — Laravel ne relit
  ni `$_SERVER` ni `$_ENV`. `database.default` reste `pgsql`, `app.env` reste `local`, et
  `RefreshDatabase` migre à zéro la base de travail : annonces, photos, propriétaires et clés
  d'accès disparaissent (les clés étant retirées à la création, les liens déjà envoyés meurent
  avec). Le même cache explique les POST de test en 419. Deux protections désormais :
  `make test` lance `config:clear` d'abord, et **`tests/TestCase::setUpTraits()` refuse de
  démarrer** si la connexion n'est pas SQLite en mémoire — ce contrôle-là ne dépend d'aucun
  cache, ne pas le retirer.

  **Et surtout ne pas le redescendre dans `setUp()`.** C'est là qu'il était écrit d'abord, après
  `parent::setUp()` — or `parent::setUp()` appelle `setUpTraits()`, donc `RefreshDatabase`, donc
  `migrate:fresh`. Le garde-fou levait bien son erreur, mais **sur une base déjà vide** : le
  11 septembre, un `php artisan test --filter=…` lancé en direct après un redémarrage du conteneur
  a effacé la base de travail une seconde fois, comptes réels compris, pendant que le message
  annonçait qu'on s'arrêtait. `setUpTraits()` s'exécute une fois la configuration chargée et
  **avant** le premier trait : c'est le seul endroit où l'on peut encore refuser. Le correctif a
  été prouvé en reproduisant le scénario exact — configuration compilée, test lancé en direct :
  même nombre d'annonces avant et après. **Lancer `make test`, jamais `php artisan test` en
  direct** ; et après tout incident de tests, compter les lignes de la base avant de conclure
  qu'elle est intacte — un message d'erreur ne prouve pas qu'on s'est arrêté à temps.
- **Un port Vite non publié fait servir les assets du projet voisin — en silence.** Une autre
  application tourne sur cette machine et publie déjà 5173. Docker laissait alors partir
  `vayla-node` **sans publier son port**, sans erreur : Vite démarrait bien à l'intérieur du
  conteneur, écrivait `public/hot` avec `http://localhost:5173`, et la page allait chercher ses
  modules **chez l'autre projet**. Symptôme : les modifications n'apparaissent pas, ou la page
  est blanche, et `curl` sur `:5173/resources/js/app.js` répond 200 en listant les pages d'une
  application étrangère. Vayla est donc sur **5174, dedans comme dehors** (`vite.config.js` +
  `docker-compose.yml`) : publier `5174:5173` aurait fait écrire `localhost:5173` dans
  `public/hot` et ramené au même problème.

  Le contrôle qui tranche, avant de soupçonner le code :

  ```bash
  cat laravel/public/hot                       # doit dire http://localhost:5174
  curl -s http://localhost:5174/resources/js/app.js | grep -c 'Pages/Owner'
  ```

  Corollaire : `public/hot` est **recréé à chaque démarrage de Vite**. Le supprimer pour forcer
  les assets compilés ne tient que jusqu'au prochain `restart node`.
- **Le conteneur `node` ne doit pas dépendre du registre npm pour démarrer.** La commande était
  `npm install && npm run dev` : quand `npm install` se fige — ça arrive, sans message —, le
  port 5174 accepte la connexion sans que rien n'écoute derrière, et le navigateur affiche
  `ERR_CONNECTION_RESET` sur `@vite/client` et `app.js`, page blanche, sans que rien n'indique
  npm. La commande n'installe plus que si `node_modules/.bin/vite` manque ; après un changement
  de `package.json`, `make npm-install`.
- **Le même piège vaut pour `APP_ENV`.** `docker-compose` l'exporte à `local` ; sans
  `<server name="APP_ENV" value="testing" force="true"/>`, `runningUnitTests()` est faux, la
  protection CSRF reste active et **tout POST de test répond 419**. C'est exactement la même
  cause que pour `DB_CONNECTION` : toute variable que `docker-compose` exporte doit être
  redéclarée en `<server>`, pas seulement en `<env>`.
- **Ajouter un dossier dans `Pages/` demande de vider le cache de Vite, pas seulement de le
  redémarrer.** `app.js` résout les pages par `import.meta.glob` ; sans quoi Inertia lève
  « Page not found: ./Pages/…/Index.vue » et la page reste blanche, alors que `npm run build`
  passe très bien. **Un simple `docker compose restart node` ne suffit pas** — pire, il peut
  laisser le glob entièrement vide (`Object.assign({})`, aucune page résolue, y compris
  l'accueil). La séquence qui marche :

  ```bash
  docker compose exec node rm -rf /var/www/html/node_modules/.vite
  docker compose restart node
  ```

  Pour vérifier sans passer par le navigateur : `curl -s http://localhost:5174/resources/js/app.js`
  doit lister les pages dans le `Object.assign({…})`.

  **Le même symptôme suit le premier import d'un nouveau module d'un paquet** — `gsap/Flip`, importé
  pour la première fois par `useRangement.js`, a fait réoptimiser les dépendances par Vite, et le
  navigateur a reçu une liste de pages sans `Office/Destinations/Edit.vue`, pourtant présente et
  compilable. Même remède : vider `node_modules/.vite`, redémarrer, vérifier le glob. Ce n'est pas
  le `vite build` lancé dans le conteneur : vérifié, il laisse le glob du serveur intact.

  **La purge est maintenant dans la commande du conteneur** (`docker-compose.yml`) : tout
  démarrage de `node` — `make npm-dev`, `make up`, un redémarrage de Docker Desktop — vide
  `node_modules/.vite` avant de lancer Vite. La purge manuelle ne tenait pas : le 11 septembre, le
  conteneur est reparti sans que personne la fasse, et le back-office entier est tombé sur « Page
  not found » avec un glob vide. Vérifié en redémarrant deux fois de suite sans purge : 81 pages à
  chaque fois. La règle vaut pour **tout** fichier ajouté sous `Pages/`, partiels compris
  (`Pages/Owner/Listings/FormSteps.vue` l'a déclenché) : le glob `./Pages/**/*.vue` les ramasse
  aussi.

  **Et `app.js` ne laisse plus la page blanche en développement** : si une page manque à la liste,
  il la charge directement par son chemin (le serveur de dev sert tout fichier) et l'écrit dans la
  console — `[vayla] « … » manque à la liste de pages de Vite`. Ce détour n'existe qu'en
  développement (`import.meta.env.DEV`) ; le build calcule sa liste une fois et ne l'embarque pas.
  Si l'avertissement revient, c'est que Vite doit être redémarré — le filet cache le symptôme, pas
  la cause.
- **Jamais de `whereBetween` sur une colonne de date — un intervalle semi-ouvert.** La colonne est
  déclarée `date`, mais **SQLite est faiblement typé** et y range ce que Laravel écrit :
  `2026-08-31 00:00:00`. La comparaison redevient alors une comparaison de **chaînes**, où cette
  valeur est *supérieure* à la borne haute `2026-08-31` parce qu'elle est plus longue. Le séjour
  qui se terminait le dernier jour du mois sortait de sa facture et n'entrait pas dans la
  suivante. PostgreSQL comparait bien des dates : le défaut ne se voyait **qu'aux tests**, et
  seulement les jours où la date calculée depuis `Carbon::today()` tombait sur un 31 — une suite
  verte hier, rouge aujourd'hui, sans qu'une ligne ait bougé. Écrire
  `->where('col', '>=', $debut)->where('col', '<', $finPlusUnJour)` : juste sur les deux moteurs,
  et toujours indexable, ce que `whereDate()` n'aurait pas été.

  **Même cause, autre symptôme : le cast `date` sur une colonne qu'on cherche par égalité.**
  `invoice_settlements.month` était casté en `date` ; Eloquent l'écrivait donc
  `2026-08-01 00:00:00`, et `where('month', '2026-08-01')` ne le retrouvait plus sous SQLite — la
  facture restait « à régler » juste après avoir été réglée, et le règlement suivant heurtait
  l'unicité. La colonne reste une chaîne `AAAA-MM-JJ`, sans cast.
- **`Rule::exists(...)->where('colonne', false)` ne trouve jamais rien.** La règle s'écrit en
  chaîne, et le booléen y devient le texte « false » : aucune photo de lieu ne passait la
  validation d'une destination. Écrire la condition dans une fermeture —
  `->where(fn ($q) => $q->where('is_ai', false))` —, qui s'applique à la requête.
- **Centrer en flex avec `overflow: hidden` rend le haut de la page inatteignable.** Un bloc en
  `align-items: center` dont le contenu dépasse la hauteur déborde **des deux côtés** ; avec
  `overflow: hidden`, le haut est rogné et il n'y a aucun moyen d'y revenir — sur `/connexion`,
  c'était le lien de retour à l'accueil qui disparaissait. `margin-block: auto` sur l'enfant fait
  le même centrage et retombe à zéro quand la place manque. Et `overflow-x: clip` plutôt que
  `overflow: hidden` quand seul le décor doit être coupé : `hidden` fait du bloc un conteneur de
  défilement et enferme aussi le débord vertical.
- **La session est sérialisée en JSON** (`session.serialization`, le réglage sûr contre les
  attaques par désérialisation). **Un objet flashé revient en tableau** : `->with('x', $data)` avec
  un objet `Data` passait les tests d'envoi et faisait échouer la page suivante sur une erreur de
  type. Flasher `->toArray()`, et retyper à la lecture (`StayRequestPrefillRequest::envoyee()`).
- `specs/` (étude business, PDF/DOCX) est ignoré par git : contexte produit non versionné.
- `make clean` supprime le volume de la base.
