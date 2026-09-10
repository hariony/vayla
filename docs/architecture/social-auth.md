# La connexion sociale — Google, Facebook, Apple

Ce document dit **ce qu'il faut configurer chez chaque fournisseur** et
**pourquoi le code fait ce qu'il fait**. Il ne contient aucun identifiant :
ceux-ci vivent dans `laravel/.env`, jamais dans un fichier versionné.

## Ce que ça vient compléter

Vayla n'a **pas d'authentification par mot de passe**. Le voyageur comme le
propriétaire entrent par **adresse e-mail + code à six chiffres**. La connexion
sociale ne remplace rien : elle ajoute un chemin plus court pour qui a déjà un
compte Google, Facebook ou Apple — sans l'aller-retour vers la boîte mail.

Elle sert **les deux gardes** — voyageur (`web` / `users`) et propriétaire
(`proprietaire` / `owners`). La table `social_accounts` est donc **polymorphe**,
et la clé unique porte le type du compte : `(compte_type, provider,
provider_user_id)`. La nuance compte — **une même personne peut être voyageuse
et propriétaire** avec le même compte Google, ce qui est le principe même des
deux sessions distinctes.

**Un propriétaire inconnu n'est pas créé au retour du fournisseur.**
`owners.phone` est obligatoire — c'est par là que Vayla appelle pour la
vérification, et une annonce sans numéro joignable ne dépasse jamais le
niveau 1 — et aucun fournisseur ne le donne. Le retour dépose donc l'adresse
vérifiée **et l'identité sociale** en session, puis envoie sur
`/proprietaire/inscription/fiche` : le compte et le lien y naissent ensemble,
exactement comme pour celui qui arrive par un code.

**Une seule URL de rappel pour les deux espaces.** Elle est déclarée une fois
chez le fournisseur et ne peut pas varier selon le bouton cliqué ; l'espace visé
voyage donc en **session** (`EspaceSocial`), posé au départ — `/auth/{provider}`
ou `/proprietaire/auth/{provider}` — et relu au retour. Sans marqueur, on
retombe sur le voyageur : c'est la porte publique, et une session expirée ne
doit pas ouvrir l'espace propriétaire.

## La règle de sécurité qui commande tout le reste

**Une chaîne d'e-mail n'est jamais une preuve d'identité.**

Une identité sociale ne se rattache à un compte Vayla existant que si le
fournisseur **atteste** que l'adresse lui appartient (`email_verified` dans le
jeton OpenID Connect). Sans cette barrière, ouvrir un compte chez un
fournisseur laxiste avec l'adresse d'un tiers suffirait à entrer chez lui.

| Fournisseur | Atteste l'adresse ? | Conséquence |
|---|---|---|
| Google | oui (`email_verified` dans l'ID token) | rattachement direct |
| Apple | oui — il ne transmet que des adresses vérifiées | rattachement direct |
| **Facebook** | **non**, rien d'équivalent | **ne rattache jamais** à un compte existant |

Quand Facebook présente l'adresse d'un compte déjà connu, le rattachement est
**refusé** et l'écran renvoie vers l'entrée par code — qui, elle, prouve la
boîte. C'est `App\Services\Auth\LiaisonRefusee`.

## Les quatre cas de liaison

Tout est dans `App\Services\Auth\SocialAuthService`, écrit **une fois** pour les
trois fournisseurs, dans une transaction.

1. **`(provider, provider_user_id)` connu** → on connecte. On rafraîchit le nom
   et l'avatar ; **jamais l'adresse du compte**, qui est notre identifiant.
2. **Adresse garantie désignant un compte existant** → on rattache.
3. **Adresse non garantie désignant un compte existant** → on refuse (voir
   ci-dessus).
4. **Rien ne correspond** → on crée le compte et l'identité.

Un même compte peut porter Google **et** Apple **et** Facebook : c'est le sens
de la table dédiée.

## Ce qui n'est pas stocké

**Aucun jeton.** Ni `access_token`, ni `refresh_token`. Nous n'appelons aucune
API du fournisseur après la connexion — nous voulons savoir qui entre, pas agir
en son nom. Les garder ferait d'un vol de base un vol de comptes Google.

Les jetons ne sont pas non plus journalisés : en cas d'échec OAuth, seul le
message d'exception est écrit, jamais la requête, qui porte le code d'échange.

## Les URL de rappel à déclarer

Elles doivent correspondre **au caractère près**. En production, HTTPS est
obligatoire chez les trois fournisseurs.

| Environnement | URL de rappel |
|---|---|
| local | `http://localhost:8070/auth/google/callback` (idem `facebook`, `apple`) |
| recette | `https://<domaine-de-recette>/auth/{provider}/callback` |
| production | `https://<domaine>/auth/{provider}/callback` |

Le départ se fait sur `/auth/{provider}` ; il n'a pas à être déclaré.

> Ces deux URL sont en anglais alors que tout le reste du site est en français.
> C'est délibéré : elles ne sont pas lues par un humain, elles sont **recopiées
> dans une console de fournisseur**, où un accent ou une casse inattendue se
> paie par un échec que la console ne sait pas expliquer.

## Configuration manuelle, fournisseur par fournisseur

### Google — gratuit, immédiat

1. [Google Cloud Console](https://console.cloud.google.com/) → créer un projet.
2. **APIs & Services → OAuth consent screen** : type *External*, renseigner nom,
   e-mail de support, domaine. Ajouter les scopes `email` et `profile`.
   Tant que l'écran est en *Testing*, seuls les comptes ajoutés en *Test users*
   peuvent se connecter — c'est suffisant pour la mise au point.
3. **Credentials → Create credentials → OAuth client ID**, type *Web
   application*. Déclarer l'URL de rappel ci-dessus dans *Authorized redirect
   URIs*.
4. Reporter dans `.env` : `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`.

### Facebook — demande une revue avant la production

1. [Meta for Developers](https://developers.facebook.com/) → créer une app de
   type *Consumer*, ajouter le produit **Facebook Login**.
2. *Facebook Login → Settings* : déclarer l'URL de rappel dans *Valid OAuth
   Redirect URIs*.
3. Reporter `FACEBOOK_CLIENT_ID` (App ID) et `FACEBOOK_CLIENT_SECRET`.

> **À savoir avant de compter dessus** : en mode *Development*, seuls les
> comptes ayant un rôle dans l'app peuvent se connecter. Pour ouvrir au public,
> Meta impose une **vérification d'entreprise** et une revue de l'app pour la
> permission `email`. Sans société enregistrée, cette étape est bloquante.

### Apple — demande un compte développeur payant

1. **Apple Developer Program** (~99 USD/an) — obligatoire, il n'y a pas de
   palier gratuit pour *Sign in with Apple*.
2. *Certificates, Identifiers & Profiles* → créer un **App ID**, puis un
   **Services ID** : c'est lui qui devient `APPLE_CLIENT_ID`.
3. Sur le Services ID, activer *Sign in with Apple* et déclarer le domaine ainsi
   que l'URL de rappel. **Apple refuse `localhost`** : pour la mise au point, il
   faut un domaine public en HTTPS (un tunnel suffit).
4. Créer une **clé `.p8`** avec *Sign in with Apple* activé. Noter le *Key ID*
   et le *Team ID* — la clé ne se télécharge qu'une fois.
5. `APPLE_CLIENT_SECRET` n'est **pas** une chaîne fixe : c'est un **JWT signé**
   avec cette clé, valable **six mois au maximum**. Il faut le régénérer, sans
   quoi la connexion Apple s'arrête sans prévenir.

> **Deux règles Apple qui engagent au-delà du web** : si l'application iOS
> propose Google ou Facebook, *Sign in with Apple* devient **obligatoire** ; et
> Apple renvoie souvent une adresse **relais** (`@privaterelay.appleid.com`),
> qui cesse de fonctionner si l'utilisateur révoque l'accès. C'est une raison de
> plus pour que l'identité soit `provider_user_id`, jamais l'adresse.

## Les cas limites, et ce que le code en fait

- **Pas d'adresse** (Facebook, compte ouvert par téléphone) → le compte s'ouvre
  quand même. `users.email` est nullable. Le compte est **diminué** : il ne
  rattachera aucune réservation (`bookings.traveller_email` est la clé de
  `/mes-reservations`) et ne pourra se reconnecter que par le même fournisseur.
- **Pas de nom** (Apple, dès la deuxième connexion) → on ne l'écrase jamais avec
  du vide ; le nom appris à la première autorisation est conservé.
- **Pas d'avatar** → aucun écran n'en dépend.
- **Fournisseur inconnu ou non configuré** → 404, jamais 500.
- **Refus de l'utilisateur ou erreur OAuth** → message générique, aucune trace
  détaillée côté utilisateur.

## Le dessin des boutons

**Un contrôle segmenté, pas trois pilules empilées.** La pile pleine largeur
est ce que fait tout le monde, et elle dit trois décisions là où il n'y en a
qu'une : « avez-vous déjà un compte quelque part ? ». Une barre unique, divisée
par deux filets, dit l'inverse — un objet, trois issues — et tient sur une ligne
au lieu de trois, ce qui garde le champ e-mail au-dessus de la ligne de
flottaison sur un téléphone.

Le logo est **au-dessus** du mot, pas à côté : à trois cellules serrées, un logo
aligné à gauche réduit la marque à un détail. Le mot reste — un pictogramme seul
serait un faux signal pour qui ne connaît pas ces marques.

`useSocialMotion` porte trois gestes : les cellules arrivent décalées, les
filets se déploient **depuis leur milieu** (la barre se construit, elle
n'apparaît pas), et au survol comme au focus la marque grossit de 12 % pendant
qu'un trait de terre se déploie sous la cellule visée. La terre est la couleur
d'action et n'apparaît qu'à ce moment ; un fond coloré aurait concurrencé le
bouton principal en dessous.

**Une fois le départ engagé, la barre se verrouille** : les autres cellules
s'éteignent, celle qu'on a choisie garde son trait et dit « Ouverture… ». Deux
allers-retours OAuth simultanés se marchent dessus sur le `state`.

## Ce qui reste à faire

- **Lier un compte social depuis une session déjà ouverte.** Aujourd'hui la
  liaison ne se fait qu'à la connexion. Le cas « Facebook refusé faute
  d'attestation » se résout donc en entrant par code, mais l'écran de liaison
  depuis l'espace client n'existe pas encore.
- **Délier**, avec la garde qui empêche de retirer son dernier moyen d'entrée.
  Tant que l'écran de liaison n'existe pas, celui de déliaison n'a pas d'objet —
  et un compte sans adresse qui délierait son seul fournisseur deviendrait
  définitivement inaccessible.
