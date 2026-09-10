# Graph Report - vayla  (2026-09-09)

## Corpus Check
- Large corpus: 469 files · ~1,362,135 words. Semantic extraction will be expensive (many Claude tokens). Consider running on a subfolder.

## Summary
- 2126 nodes · 4274 edges · 187 communities (99 shown, 36 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 45 edges (avg confidence: 0.85)
- Token cost: 145,176 input · 0 output

## Community Hubs (Navigation)
- Photo et relations Eloquent
- Commandes artisan et file WhatsApp
- Contrôleurs API v1
- Propriétaire — compte et dépôt
- Tests API et authentification
- Objets Data des annonces
- Partiels de la fiche logement
- Utilisateur et fabrique de test
- Filtres et tri du catalogue
- Statut de réservation et dates
- Contrôleurs d'accès et Inertia
- Climat, saisons et destinations
- Orchestrateurs de pages Vue
- Carte et filtres côté client
- Relations des modèles
- Équipements — data et dépôt
- Providers et canaux d'envoi
- Fil de messages d'une réservation
- Téléphone — normalisation E.164
- Validation des requêtes propriétaire
- SejourData et occupation
- Composants d'affichage
- Codes de vérification — modèle
- Tests du code de vérification
- Réservation — modèle et exceptions
- Contrôleurs voyageur et IA
- Devise et taux de change
- Data d'accès et de destination
- Formulaire d'annonce propriétaire
- Photos — règles et crédits
- Catégories — data et modèle
- Échelle de confiance
- Tests du calendrier propriétaire
- Inscription voyageur — contrôleurs
- Annonces côté propriétaire
- Motifs de fermeture
- Rubriques d'équipements
- Tests d'authentification propriétaire
- Tests de saisie d'annonce
- Confirmations de séjour
- Statut d'annonce et destinations
- Lien d'accès WhatsApp
- Périodes d'indisponibilité
- Carte d'annonce
- Moteur de recherche et en-tête
- Design system et règles d'écran
- Modèle économique et facturation
- Diagnostic WhatsApp
- Type de logement et validation
- Calendrier propriétaire (Vue)
- Dates du séjour
- Catalogue et panneau de filtres
- Tests de réservation
- Panneaux d'accès et de confiance
- Galerie de la fiche
- Tests du fil de conversation
- Seeders de la base
- Configuration Vite et npm
- Tableau de bord propriétaire
- Tests de l'aiguillage
- Rotation de la clé d'accès
- Recherche — source unique
- Pièges Docker, cache et Vite
- Middlewares et amorçage
- Rappels et notifications
- Réservation — contrôleur public
- Écrans propriétaire
- Tests de l'espace propriétaire
- API v1 et contrat d'annonce
- Courriel du code
- Migrations — messages et motifs
- Commande de rotation de clé
- Réservations côté propriétaire
- Service d'annonce propriétaire
- Chorégraphie GSAP de l'accueil
- Tests équipements et photos
- Limitation d'énumération
- Envoi du code et exceptions
- Tests du catalogue et de la fiche
- Tests des pages destination
- Migrations — tables initiales
- Migrations — photos et identifiants
- Tests de l'API annonces
- Tests du formulaire de réservation
- Tests de facturation
- Code à usage unique et accès
- La règle de la nuit
- Espace propriétaire et notifications
- Photos — data et crédits
- Métadonnées Composer
- Scripts Composer
- Écran de saisie du code
- Tests des confirmations
- Service de disponibilité
- Dépendances PHP de développement
- Dépendances front de développement
- Pied de page et monogramme
- Chorégraphie GSAP de la fiche
- Demande de séjour
- Panneau énergie et eau
- Tests du taux de change
- Import des photos générées
- Configuration Composer
- Dépendances PHP
- Composant de conversation
- Inscription voyageur (Vue)
- Section disponibilité et saison
- Inscription propriétaire (Vue)
- L'euro à côté de l'ariary
- Dépendances front
- Sources d'images responsives
- Équipements — vocabulaire et icônes
- Sanctum et middlewares de session
- Autoload PSR-4
- Configuration des journaux
- Espace client (Vue)
- Règle des couches et service IA
- Formatage des nombres
- Console et tâches planifiées
- Mouvement de l'aiguillage
- Source unique du moteur
- Barre de capacité
- Idempotence des seeders
- Autoload des tests
- Découverte de paquets Laravel
- Seeder des réservations
- Scripts npm
- Client HTTP de l'API
- Formatage ariary et nombres
- Divergences assumées avec pdp
- Point d'entrée du conteneur
- Rail de catégories
- Service d'équipements (front)
- Service de destinations (front)
- Service d'annonces (front)

## God Nodes (most connected - your core abstractions)
1. `Listing` - 136 edges
2. `Booking` - 98 edges
3. `Owner` - 96 edges
4. `TestCase` - 61 edges
5. `vue` - 58 edges
6. `Telephone` - 53 edges
7. `@inertiajs/vue3` - 40 edges
8. `Destination` - 36 edges
9. `Controller` - 35 edges
10. `Photo` - 35 edges

## Surprising Connections (you probably didn't know these)
- `meta.demo — le client mobile doit dire que les annonces sont fictives` --semantically_similar_to--> `config('vayla.demo') — annonces fictives et bandeau « Aperçu » liés`  [INFERRED] [semantically similar]
  docs/architecture/api-v1.md → CLAUDE.md
- `scripts/import-photos-ia.py — recadrage 4/3, trois résolutions, blocs de seeder` --semantically_similar_to--> `PhotoUploadService — GD, WebP 800/1600/3200, jamais d'agrandissement`  [INFERRED] [semantically similar]
  docs/photos-annonces-ia.md → CLAUDE.md
- `Les images générées sont marquées partout où elles s'affichent` --semantically_similar_to--> `Images générées `ia-` / colonne is_ai, créditées à part`  [INFERRED] [semantically similar]
  docs/photos-annonces-ia.md → CLAUDE.md
- `calendar.blocked porte la dernière nuit occupée, pas le départ` --semantically_similar_to--> `Une nuit appartient à sa date d'arrivée (ends_on = dernière nuit occupée)`  [INFERRED] [semantically similar]
  docs/architecture/api-v1.md → CLAUDE.md
- `Ne jamais calculer de note à partir des confirmations` --semantically_similar_to--> `Il n'y a pas de note sur cinq, et il n'y en aura pas`  [INFERRED] [semantically similar]
  docs/architecture/api-v1.md → CLAUDE.md

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **La chaîne complète de la recherche par dates, couche par couche** — claude_listingindexrequest, claude_sejourdata, claude_listingfiltredata, claude_listingrepository, claude_listingservice, claude_searchdatestest [EXTRACTED 1.00]
- **Les surfaces qui portent l'échelle de confiance** — claude_trustlevel, claude_trustgauge, claude_echelle_confiance, claude_stayconfirmation, docs_architecture_api_v1_libelles_publies [EXTRACTED 1.00]
- **Le flux de vérification par code (inscription et OTP)** — claude_pendingregistration, claude_verificationcodeservice, claude_codesender, claude_otp_service, claude_otpsender, claude_auth_code_vue [EXTRACTED 1.00]

## Communities (187 total, 36 thin omitted)

### Community 0 - "Photo et relations Eloquent"
Cohesion: 0.06
Nodes (13): GdImage, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Http\UploadedFile, Illuminate\Testing\TestResponse, Photo, PhotoRepositoryInterface, PhotoRepository, PhotoUploadService (+5 more)

### Community 1 - "Commandes artisan et file WhatsApp"
Cohesion: 0.08
Nodes (10): Illuminate\Console\Command, Illuminate\Support\Str, ReleaseExpiredBookings, SendWhatsAppMessages, NotificationKind, OutboundMessage, OutboundMessageRepositoryInterface, OutboundMessageRepository (+2 more)

### Community 2 - "Contrôleurs API v1"
Cohesion: 0.08
Nodes (13): Illuminate\Http\JsonResponse, Illuminate\Support\Facades\Route, CategoryController, DestinationController, ListingController, TrustLevelController, HomeController, ListingIndexRequest (+5 more)

### Community 3 - "Propriétaire — compte et dépôt"
Cohesion: 0.09
Nodes (9): Illuminate\Auth\Authenticatable, Illuminate\Contracts\Auth\Authenticatable, Illuminate\Database\Eloquent\Relations\HasManyThrough, Owner, OwnerRepositoryInterface, OwnerRepository, InvoiceService, Carbon (+1 more)

### Community 4 - "Tests API et authentification"
Cohesion: 0.09
Nodes (9): Illuminate\Database\QueryException, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Illuminate\Support\Facades\Hash, Inertia\Testing\AssertableInertia, AmenityApiTest, ReferenceApiTest, HomeControllerTest (+1 more)

### Community 5 - "Objets Data des annonces"
Cohesion: 0.08
Nodes (11): BookingData, self, ConfirmationSummaryData, ListingData, self, ListingDetailData, self, self (+3 more)

### Community 6 - "Partiels de la fiche logement"
Cohesion: 0.06
Nodes (28): caches, deplie, props, total, visibles, { euros, mention }, lienReserver, ouvert (+20 more)

### Community 7 - "Utilisateur et fabrique de test"
Cohesion: 0.09
Nodes (10): Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, User, static (+2 more)

### Community 8 - "Filtres et tri du catalogue"
Cohesion: 0.11
Nodes (7): Illuminate\Contracts\Pagination\LengthAwarePaginator, Illuminate\Database\Eloquent\Builder, ListingFiltreData, ListingSort, self, ListingRepositoryInterface, ListingRepository

### Community 9 - "Statut de réservation et dates"
Cohesion: 0.14
Nodes (3): BookingStatus, Carbon, SearchDatesTest

### Community 10 - "Contrôleurs d'accès et Inertia"
Cohesion: 0.13
Nodes (9): Illuminate\Support\Facades\Auth, Inertia\Inertia, Inertia\Response, AccessController, Controller, DestinationController, ListingController, AuthController (+1 more)

### Community 11 - "Climat, saisons et destinations"
Cohesion: 0.12
Nodes (5): ClimateZone, SeasonKind, Destination, SeasonService, SeasonTest

### Community 12 - "Orchestrateurs de pages Vue"
Cohesion: 0.07
Nodes (14): ESPACES, racine, ouvertes, props, total, { category, results, searchLabel, pickDestination, reset }, hero, props (+6 more)

### Community 13 - "Carte et filtres côté client"
Cohesion: 0.07
Nodes (15): PINS, plotted, props, flash, page, b, { euros, mention }, props (+7 more)

### Community 14 - "Relations des modèles"
Cohesion: 0.10
Nodes (5): Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo, Illuminate\Database\Eloquent\Relations\BelongsToMany, StayConfirmation, StayConfirmationSeeder

### Community 15 - "Équipements — data et dépôt"
Cohesion: 0.14
Nodes (8): Illuminate\Support\Collection, AmenityData, self, Amenity, AmenityRepository, Collection, AmenityRepositoryInterface, ListingSeeder

### Community 16 - "Providers et canaux d'envoi"
Cohesion: 0.10
Nodes (7): Illuminate\Support\ServiceProvider, AppServiceProvider, CodeSender, LogCodeSender, MailCodeSender, SmsCodeSender, WhatsAppCodeSender

### Community 17 - "Fil de messages d'une réservation"
Cohesion: 0.13
Nodes (6): MessageAuthor, self, BookingMessage, BookingMessageRepository, BookingMessageRepositoryInterface, ConversationService

### Community 18 - "Téléphone — normalisation E.164"
Cohesion: 0.13
Nodes (4): self, Telephone, TelephoneTest, Stringable

### Community 19 - "Validation des requêtes propriétaire"
Cohesion: 0.10
Nodes (8): Illuminate\Contracts\Validation\ValidationRule, Illuminate\Foundation\Http\FormRequest, Illuminate\Validation\Rules\Password, OwnerLoginRequest, RegisterOwnerRequest, RegisterTravellerRequest, self, TelephoneValide

### Community 20 - "SejourData et occupation"
Cohesion: 0.13
Nodes (6): Carbon, self, SejourData, CalendarRefusedException, UnavailabilityRepositoryInterface, OwnerCalendarService

### Community 21 - "Composants d'affichage"
Cohesion: 0.10
Nodes (22): ICONS, props, ABREGES, actif, plages, props, survol, auClavier() (+14 more)

### Community 22 - "Codes de vérification — modèle"
Cohesion: 0.16
Nodes (4): Illuminate\Support\Facades\Session, VerificationKind, VerificationCode, VerificationCodeService

### Community 24 - "Réservation — modèle et exceptions"
Cohesion: 0.16
Nodes (5): Illuminate\Database\Eloquent\Relations\HasOne, BookingRefusedException, Booking, BookingService, Carbon

### Community 25 - "Contrôleurs voyageur et IA"
Cohesion: 0.16
Nodes (6): Illuminate\Http\Request, AiController, TravellerAuthController, OwnerCalendarController, OwnerController, AiService

### Community 26 - "Devise et taux de change"
Cohesion: 0.12
Nodes (7): Inertia\Middleware, DeviseData, self, ExchangeRateController, HandleInertiaRequests, ConfigExchangeRate, ExchangeRateProvider

### Community 27 - "Data d'accès et de destination"
Cohesion: 0.13
Nodes (7): AccessData, self, DestinationData, self, DestinationDetailData, DestinationNotFoundException, Spatie\LaravelData\Data

### Community 28 - "Formulaire d'annonce propriétaire"
Cohesion: 0.09
Nodes (13): choisis, creation, envoiVerif, { euros }, form, modifiable, nbVedettes, ouverte (+5 more)

### Community 29 - "Photos — règles et crédits"
Cohesion: 0.11
Nodes (22): config('vayla.demo') — annonces fictives et bandeau « Aperçu » liés, PhotoFilesTest — aucun orphelin, aucune clé sans fichier, photos.folder — `lieux` (Commons) vs `annonces` (propriétaire), Images générées `ia-` / colonne is_ai, créditées à part, PhotoSeeder — crédits, largeurs, suppression des clés retirées, PhotoUploadService — GD, WebP 800/1600/3200, jamais d'agrandissement, Règle photo : uniquement de vraies photographies de Madagascar (Commons), SceneArt.vue — illustrations SVG de repli (+14 more)

### Community 30 - "Catégories — data et modèle"
Cohesion: 0.13
Nodes (6): CategoryData, self, Category, CategoryRepository, CategoryRepositoryInterface, CategorySeeder

### Community 31 - "Échelle de confiance"
Cohesion: 0.12
Nodes (5): self, TrustLevelData, TrustLevel, TrustLevelTest, PHPUnit\Framework\TestCase

### Community 33 - "Inscription voyageur — contrôleurs"
Cohesion: 0.15
Nodes (4): Illuminate\Http\RedirectResponse, TravellerRegisterController, RegisterController, PendingRegistration

### Community 34 - "Annonces côté propriétaire"
Cohesion: 0.14
Nodes (3): ListingController, OwnerListingRequest, OwnerPhotoRequest

### Community 35 - "Motifs de fermeture"
Cohesion: 0.14
Nodes (4): Illuminate\Support\Carbon, BlockReason, OwnerBlockRequest, UnavailabilitySeeder

### Community 36 - "Rubriques d'équipements"
Cohesion: 0.14
Nodes (5): AmenityGroupData, self, AmenityGroup, AmenityController, AmenityService

### Community 39 - "Confirmations de séjour"
Cohesion: 0.15
Nodes (4): ConfirmationData, self, ConfirmationPoint, ConfirmationService

### Community 40 - "Statut d'annonce et destinations"
Cohesion: 0.12
Nodes (3): ListingStatus, DestinationRepositoryInterface, DestinationRepository

### Community 41 - "Lien d'accès WhatsApp"
Cohesion: 0.14
Nodes (3): AccessLinkController, OwnerPasswordRequest, OwnerAuthService

### Community 42 - "Périodes d'indisponibilité"
Cohesion: 0.19
Nodes (3): Unavailability, UnavailabilityRepository, AvailabilityTest

### Community 43 - "Carte d'annonce"
Cohesion: 0.11
Nodes (14): { euros }, href, price, priceEur, props, TRUST_LABELS, trustLabel, p (+6 more)

### Community 44 - "Moteur de recherche et en-tête"
Cohesion: 0.13
Nodes (15): currentName, emit, nuits, ouvert, props, resumeDates, anchor(), compact (+7 more)

### Community 45 - "Design system et règles d'écran"
Cohesion: 0.15
Nodes (17): AccessIcon.vue — la loupe et la clé de l'aiguillage, L'aiguillage /connexion — deux cartes, aucun formulaire, ClimateZone — six façades climatiques en enum, Un contrôle doit se voir comme un contrôle, Design system maison (resources/css/app.scss), Deux publics au bagage numérique inégal, MadagascarMap.vue — littoral et repères projetés, SeasonKind — ce que vaut un mois, y compris les mauvais (+9 more)

### Community 46 - "Modèle économique et facturation"
Cohesion: 0.13
Nodes (17): BookingService — demande, blocage des nuits, expiration, BookingStatus — seul Completed est facturable, ConfirmationPoint — les six points vérifiables d'un séjour, Séjour confirmé jamais stocké : déduit de trust_level = 4, ListingStatus — Draft → Submitted → Published, Le Makefile et docker-compose.yml font foi, pas le README du starter, Modèle économique — Vayla n'encaisse rien, commission en fin de mois, OwnerListingRequest — trust_level n'entre par aucun formulaire (+9 more)

### Community 47 - "Diagnostic WhatsApp"
Cohesion: 0.16
Nodes (7): Illuminate\Http\Client\ConnectionException, Illuminate\Http\Client\RequestException, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log, Illuminate\Support\Facades\Mail, CheckWhatsApp, Throwable

### Community 48 - "Type de logement et validation"
Cohesion: 0.15
Nodes (4): Illuminate\Validation\Rule, Illuminate\Validation\Validator, PropertyType, CatalogueService

### Community 49 - "Calendrier propriétaire (Vue)"
Cohesion: 0.12
Nodes (12): base, props, consigne, dates, envoi, erreurs, motif, page (+4 more)

### Community 50 - "Dates du séjour"
Cohesion: 0.22
Nodes (12): ABREGES, addDays(), formatCompact(), formatLong(), JOUR, MOIS, nightsBetween(), quantieme() (+4 more)

### Community 51 - "Catalogue et panneau de filtres"
Cohesion: 0.12
Nodes (11): destinationName, panelOpen, props, { state, loading, activeCount, apply, toggleAmenity, setCategory, goToPage, reset, clearDates }, title, emit, { eurosFourchette }, pricePalier (+3 more)

### Community 53 - "Panneaux d'accès et de confiance"
Cohesion: 0.13
Nodes (12): props, voies, max, props, verifies, d, { eurosFourchette }, photo (+4 more)

### Community 54 - "Galerie de la fiche"
Cohesion: 0.15
Nodes (13): close(), count, current, dialog, index, lead, onKey(), open (+5 more)

### Community 56 - "Seeders de la base"
Cohesion: 0.20
Nodes (5): Illuminate\Database\Seeder, AmenitySeeder, DatabaseSeeder, DestinationSeeder, OwnerSeeder

### Community 57 - "Configuration Vite et npm"
Cohesion: 0.16
Nodes (12): private, $schema, type, bootstrap, concurrently, laravel-vite-plugin, @popperjs/core, sass (+4 more)

### Community 58 - "Tableau de bord propriétaire"
Cohesion: 0.14
Nodes (5): prenom, props, envoi, motif, refus

### Community 61 - "Recherche — source unique"
Cohesion: 0.18
Nodes (13): ListingFiltreData — les critères de recherche, ListingIndexRequest — validation partagée site + API, SearchDatesTest — les deux bornes de la recherche par dates, SearchPanel.vue — le moteur, complet et compact, SiteHeader — forme compacte conditionnée par un moteur à encastrer, useCatalogueFilters — filtrage serveur par l'URL, useListingFilters — filtrage en mémoire de l'accueil, useSearchQuery — source unique du moteur de recherche (+5 more)

### Community 62 - "Pièges Docker, cache et Vite"
Cohesion: 0.18
Nodes (13): Piège cache de configuration : les tests migrent la base de développement, docker/entrypoint.sh met en cache config, routes et vues à chaque démarrage, Ajouter un dossier dans Pages/ exige de vider le cache Vite, Piège port Vite 5174 — un port non publié sert les assets du voisin, Schéma PostgreSQL dédié `vayla` (search_path), Service app — PHP-FPM 8.5 + Nginx + Supervisor (vayla-app), Service node:20 — serveur Vite publié sur 5174, Installation npm conditionnelle au démarrage du conteneur node (+5 more)

### Community 63 - "Middlewares et amorçage"
Cohesion: 0.23
Nodes (7): Closure, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, EnsureOwnerHasPassword, PreventIndexing, Symfony\Component\HttpFoundation\Response

### Community 66 - "Écrans propriétaire"
Cohesion: 0.15
Nodes (5): props, flash, page, RUBRIQUES, url

### Community 68 - "API v1 et contrat d'annonce"
Cohesion: 0.18
Nodes (12): Inertia pour le site, API v1 pour le mobile, ListingData — le contrat public d'une annonce, ListingRepository — le seul endroit qui parle Eloquent pour les annonces, ListingService — service d'annonces, site et API, amenities[] est conjonctif, API v1 — la surface consommée par l'application mobile, GET /listings — paramètres et bornes, Forme Listing — slug, trust, perks, price en ariary (+4 more)

### Community 69 - "Courriel du code"
Cohesion: 0.24
Nodes (8): Content, Envelope, Illuminate\Bus\Queueable, Illuminate\Mail\Mailable, Illuminate\Mail\Mailables\Content, Illuminate\Mail\Mailables\Envelope, Illuminate\Queue\SerializesModels, CodeMail

### Community 70 - "Migrations — messages et motifs"
Cohesion: 0.20
Nodes (3): Illuminate\Database\Migrations\Migration, Illuminate\Support\Facades\DB, up()

### Community 74 - "Chorégraphie GSAP de l'accueil"
Cohesion: 0.32
Nodes (11): canopyLife(), counters(), drawCoastline(), geckoClimb(), growLadder(), heroEntrance(), makiWalk(), parallax() (+3 more)

### Community 77 - "Envoi du code et exceptions"
Cohesion: 0.24
Nodes (4): SendCode, CodeSendingFailed, CodeThrottled, RuntimeException

### Community 85 - "Code à usage unique et accès"
Cohesion: 0.25
Nodes (9): AccessTest — tient la règle de famille des cinq écrans d'accès, Auth/Code.vue — un écran de code pour les deux inscriptions, CodeSender — interface d'envoi du code d'inscription, vayla:whatsapp-check — diagnostic des échecs Meta, Les écrans de connexion ne portent ni en-tête ni pied de page, App\Services\Otp — cinq bornes du code à usage unique, OtpSender — trois canaux (log, whatsapp, sms) derrière une interface, Référence VY- à cinq caractères, sans O/0 ni I/1 (+1 more)

### Community 86 - "La règle de la nuit"
Cohesion: 0.25
Nodes (9): AvailabilityService — le calendrier d'un logement, BlockReason — cinq motifs de fermeture, pas de champ libre, Une nuit appartient à sa date d'arrivée (ends_on = dernière nuit occupée), SejourData — la règle de la nuit, écrite une seule fois, UnavailabilityRepository — le Listing en paramètre est la portée de sécurité, useStayDates — les dates du séjour, un seul état, calendar.blocked porte la dernière nuit occupée, pas le départ, Pourquoi SejourData et pas une méthode privée dans le dépôt (+1 more)

### Community 87 - "Espace propriétaire et notifications"
Cohesion: 0.25
Nodes (9): vayla:whatsapp — envoi manuel par lien wa.me, Conversation.vue — le fil rattaché à une réservation, EnsureOwnerHasPassword — le lien WhatsApp ouvre le compte, pas l'espace, L'espace propriétaire — ordonné par urgence, pas par catégorie, NotificationKind — quatre motifs de notification, pas un de plus, outbound_messages — la file WhatsApp écrite, jamais envoyée directement, Owner — authentifiable sur la garde `proprietaire`, PendingRegistration — l'inscription vit en session jusqu'au code (+1 more)

### Community 88 - "Photos — data et crédits"
Cohesion: 0.28
Nodes (3): PhotoData, self, PhotoService

### Community 89 - "Métadonnées Composer"
Cohesion: 0.22
Nodes (8): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type

### Community 90 - "Scripts Composer"
Cohesion: 0.22
Nodes (9): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+1 more)

### Community 91 - "Écran de saisie du code"
Cohesion: 0.22
Nodes (6): complet, flash, form, page, props, reste

### Community 94 - "Dépendances PHP de développement"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pao, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 95 - "Dépendances front de développement"
Cohesion: 0.25
Nodes (8): devDependencies, concurrently, laravel-vite-plugin, sass, tailwindcss, @tailwindcss/vite, vite, @vitejs/plugin-vue

### Community 96 - "Pied de page et monogramme"
Cohesion: 0.29
Nodes (6): anchor(), COLUMNS, generees, photographies, props, year

### Community 97 - "Chorégraphie GSAP de la fiche"
Cohesion: 0.46
Nodes (7): compteurs(), echelle(), entree(), ombreEncart(), parallaxeGalerie(), revelations(), useFicheMotion()

### Community 98 - "Demande de séjour"
Cohesion: 0.25
Nodes (6): calendrier, dates, { euros, mention }, form, props, total

### Community 99 - "Panneau énergie et eau"
Cohesion: 0.39
Nodes (7): axes, flat, has(), item(), pick(), props, utile

### Community 101 - "Import des photos générées"
Cohesion: 0.39
Nodes (7): Path, convertir(), main(), php(), Recadre en 4/3 et écrit les paliers. Renvoie la plus grande largeur produite., Importe les intérieurs générés des annonces de démonstration. Usage : python3…, trouver()

### Community 102 - "Configuration Composer"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 103 - "Dépendances PHP"
Cohesion: 0.29
Nodes (7): require, inertiajs/inertia-laravel, laravel/framework, laravel/sanctum, laravel/tinker, php, spatie/laravel-data

### Community 104 - "Composant de conversation"
Cohesion: 0.29
Nodes (4): form, MOIS, props, zone

### Community 105 - "Inscription voyageur (Vue)"
Cohesion: 0.29
Nodes (5): assezLong, complet, form, identiques, visible

### Community 106 - "Section disponibilité et saison"
Cohesion: 0.29
Nodes (6): consigne, props, saison, soustitre, titre, visibles

### Community 107 - "Inscription propriétaire (Vue)"
Cohesion: 0.29
Nodes (5): assezLong, complet, form, identiques, visible

### Community 108 - "L'euro à côté de l'ariary"
Cohesion: 0.33
Nodes (6): ConfigExchangeRate — implémentation par configuration, L'ariary est le prix, l'euro est une aide à la lecture, ExchangeRateProvider — le taux vient d'une interface, ExchangeRateTest — vérifie statiquement les écrans d'argent, useDevise — euros(), eurosFourchette(), mention, AppServiceProvider — binding Interface → implémentation

### Community 109 - "Dépendances front"
Cohesion: 0.33
Nodes (6): dependencies, bootstrap, gsap, @inertiajs/vue3, @popperjs/core, vue

### Community 110 - "Sources d'images responsives"
Cohesion: 0.60
Nodes (5): PALIERS, photoSrc(), photoSrcset(), photoUrl(), plafond()

### Community 111 - "Équipements — vocabulaire et icônes"
Cohesion: 0.40
Nodes (5): AmenityGroup — onze rubriques figées en enum, AmenityIcon.vue — 64 pictogrammes d'équipements, perks dérivé du pivot highlight, jamais saisi, Forme AmenityGroup — rubrique, équipements, highlight, Ne jamais recopier les libellés de trust ni d'équipement

### Community 112 - "Sanctum et middlewares de session"
Cohesion: 0.40
Nodes (4): Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Foundation\Http\Middleware\ValidateCsrfToken, Laravel\Sanctum\Http\Middleware\AuthenticateSession, Laravel\Sanctum\Sanctum

### Community 113 - "Autoload PSR-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 114 - "Configuration des journaux"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 115 - "Espace client (Vue)"
Cohesion: 0.40
Nodes (4): complete, connexion, corps, reservation

### Community 116 - "Règle des couches et service IA"
Cohesion: 0.50
Nodes (4): AiService — n'importe quelle API compatible OpenAI, Règle des couches Http → Data → Services → Repositories → Models, Cinq couches, une direction de dépendance, Le flux d'une requête — FormRequest → Controller → Data → Service → Repository → Model

### Community 117 - "Formatage des nombres"
Cohesion: 0.50
Nodes (4): Piège U+202F : Plus Jakarta Sans ne dessine pas l'espace fine insécable, Support/format.js — jamais Intl.NumberFormat directement, formatCompact écrit le mois en lettres — « 14 sept. », jamais « 14/09 », Nombres : Support/format.js, jamais Intl directement

### Community 118 - "Console et tâches planifiées"
Cohesion: 0.50
Nodes (3): Illuminate\Foundation\Inspiring, Illuminate\Support\Facades\Artisan, Illuminate\Support\Facades\Schedule

### Community 119 - "Mouvement de l'aiguillage"
Cohesion: 0.67
Nodes (3): reveil(), useAccessMotion(), gsap

### Community 120 - "Source unique du moteur"
Cohesion: 0.67
Nodes (3): calendrierLibre(), useSearchQuery(), recompter()

### Community 121 - "Barre de capacité"
Cohesion: 0.67
Nodes (3): accord(), details, props

### Community 123 - "Autoload des tests"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 124 - "Découverte de paquets Laravel"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 147 - "Scripts npm"
Cohesion: 0.67
Nodes (3): scripts, build, dev

## Ambiguous Edges - Review These
- `Filtrage : deux chemins, un contrat (mémoire vs URL)` → `robots.txt — indexation entièrement ouverte`  [AMBIGUOUS]
  laravel/public/robots.txt · relation: conceptually_related_to

## Knowledge Gaps
- **300 isolated node(s):** `$schema`, `name`, `type`, `description`, `keywords` (+295 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 761 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **36 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Filtrage : deux chemins, un contrat (mémoire vs URL)` and `robots.txt — indexation entièrement ouverte`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **Why does `recompter()` connect `Source unique du moteur` to `Filtres et tri du catalogue`?**
  _High betweenness centrality (0.246) - this node is a cross-community bridge._
- **Why does `vue` connect `Carte et filtres côté client` to `Partiels de la fiche logement`, `Orchestrateurs de pages Vue`, `Composants d'affichage`, `Formulaire d'annonce propriétaire`, `Carte d'annonce`, `Moteur de recherche et en-tête`, `Calendrier propriétaire (Vue)`, `Dates du séjour`, `Catalogue et panneau de filtres`, `Panneaux d'accès et de confiance`, `Galerie de la fiche`, `Configuration Vite et npm`, `Tableau de bord propriétaire`, `Écrans propriétaire`, `Chorégraphie GSAP de l'accueil`, `Écran de saisie du code`, `Pied de page et monogramme`, `Chorégraphie GSAP de la fiche`, `Demande de séjour`, `Panneau énergie et eau`, `Composant de conversation`, `Inscription voyageur (Vue)`, `Section disponibilité et saison`, `Inscription propriétaire (Vue)`, `Espace client (Vue)`, `Mouvement de l'aiguillage`, `Source unique du moteur`, `Barre de capacité`?**
  _High betweenness centrality (0.204) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _300 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Photo et relations Eloquent` be split into smaller, more focused modules?**
  _Cohesion score 0.055944055944055944 - nodes in this community are weakly interconnected._
- **Should `Commandes artisan et file WhatsApp` be split into smaller, more focused modules?**
  _Cohesion score 0.0792156862745098 - nodes in this community are weakly interconnected._
- **Should `Contrôleurs API v1` be split into smaller, more focused modules?**
  _Cohesion score 0.08246225319396051 - nodes in this community are weakly interconnected._