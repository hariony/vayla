<?php

use App\Http\Controllers\Office\AccountController;
use App\Http\Controllers\Office\AmenityController;
use App\Http\Controllers\Office\AuthController;
use App\Http\Controllers\Office\BookingController;
use App\Http\Controllers\Office\CategoryController;
use App\Http\Controllers\Office\ContentListingController;
use App\Http\Controllers\Office\DashboardController;
use App\Http\Controllers\Office\DestinationController;
use App\Http\Controllers\Office\InvoiceController;
use App\Http\Controllers\Office\JournalController;
use App\Http\Controllers\Office\ListingController;
use App\Http\Controllers\Office\NotFoundController;
use App\Http\Controllers\Office\OwnerController;
use App\Http\Controllers\Office\PageController;
use App\Http\Controllers\Office\PhotoLibraryController;
use App\Http\Controllers\Office\SettingsController;
use App\Http\Controllers\Office\SiteTextController;
use App\Http\Controllers\Office\StatsController;
use App\Http\Controllers\Office\StayRequestController;
use App\Http\Controllers\Office\TeamController;
use App\Http\Controllers\Office\TravellerController;
use App\Http\Controllers\Office\WhatsAppController;
use Illuminate\Support\Facades\Route;

/*
 * Le back-office, sur son propre hôte (`config('vayla.office.domaine')`).
 *
 * **Ce fichier est chargé AVANT `web.php`, et c'est une règle de sécurité.**
 * Les routes du site n'ont pas de domaine : elles répondent sur tous les
 * hôtes. Chargées en premier, elles serviraient l'accueil public sur
 * `office.…/`. Voir `bootstrap/app.php`.
 *
 * **La dernière route attrape tout le reste de l'hôte** et répond 404 : sans
 * elle, `office.…/logements` ouvrirait le catalogue public dans l'outil de
 * l'équipe, et `office.…/proprietaire` l'espace d'un propriétaire.
 *
 * Tout est hors index, et limité en débit sur ce qui écrit : ce sont les
 * gestes qui engagent la promesse de Vayla.
 */
Route::domain(config('vayla.office.domaine'))
    ->middleware(['sans-index', 'office'])
    ->name('office.')
    ->group(function () {
        // La limite de débit de la route est large (dix par minute et par
        // machine) : la vraie borne, par adresse, est dans `OfficeLogin`.
        Route::middleware('guest:admin')->group(function () {
            Route::get('/connexion', [AuthController::class, 'form'])->name('login');
            Route::post('/connexion', [AuthController::class, 'login'])
                ->middleware('throttle:10,1')->name('login.send');
        });

        Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout');

        // Le seul écran ouvert tant que le mot de passe est provisoire.
        Route::middleware('auth:admin')->group(function () {
            Route::get('/compte', [AccountController::class, 'edit'])->name('account');
            Route::post('/compte', [AccountController::class, 'update'])
                ->middleware('throttle:10,1')->name('account.update');
        });

        Route::middleware(['auth:admin', 'office.mot-de-passe'])->group(function () {
            Route::get('/', DashboardController::class)->name('home');
            Route::get('/statistiques', [StatsController::class, 'demandes'])->name('stats');
            Route::get('/statistiques/sejours', [StatsController::class, 'sejours'])->name('stats.stays');
            Route::get('/statistiques/catalogue', [StatsController::class, 'catalogue'])->name('stats.catalogue');

            Route::get('/annonces', [ListingController::class, 'index'])->name('listings');
            Route::get('/annonces/{listing}', [ListingController::class, 'show'])
                ->whereNumber('listing')->name('listings.show');
            Route::post('/annonces/{listing}/publier', [ListingController::class, 'publish'])
                ->whereNumber('listing')->middleware('throttle:60,1')->name('listings.publish');
            Route::post('/annonces/{listing}/renvoyer', [ListingController::class, 'sendBack'])
                ->whereNumber('listing')->middleware('throttle:60,1')->name('listings.return');
            Route::post('/annonces/{listing}/archiver', [ListingController::class, 'archive'])
                ->whereNumber('listing')->middleware('throttle:60,1')->name('listings.archive');
            Route::post('/annonces/{listing}/niveau', [ListingController::class, 'trust'])
                ->whereNumber('listing')->middleware('throttle:60,1')->name('listings.trust');

            /*
             * Le contenu d'une annonce — tout, y compris ce que le propriétaire
             * ne peut plus toucher après vérification. Le slug, lui, ne bouge
             * jamais : c'est l'adresse publique de la fiche.
             */
            Route::get('/annonces/{listing}/modifier', [ContentListingController::class, 'edit'])
                ->whereNumber('listing')->name('listings.edit');
            Route::post('/annonces/{listing}/modifier', [ContentListingController::class, 'update'])
                ->whereNumber('listing')->middleware('throttle:60,1')->name('listings.update');
            Route::post('/annonces/{listing}/photos', [ContentListingController::class, 'uploadPhoto'])
                ->whereNumber('listing')->middleware('throttle:60,1')->name('listings.photos.store');
            Route::post('/annonces/{listing}/photos/ordre', [ContentListingController::class, 'reorderPhotos'])
                ->whereNumber('listing')->middleware('throttle:60,1')->name('listings.photos.order');
            Route::post('/annonces/{listing}/photos/{photo}/retirer', [ContentListingController::class, 'deletePhoto'])
                ->whereNumber(['listing', 'photo'])->middleware('throttle:60,1')->name('listings.photos.destroy');
            Route::get('/proprietaires/{owner}/annonces/nouvelle', [ContentListingController::class, 'create'])
                ->whereNumber('owner')->name('listings.create');
            Route::post('/proprietaires/{owner}/annonces', [ContentListingController::class, 'store'])
                ->whereNumber('owner')->middleware('throttle:30,1')->name('listings.store');

            Route::get('/reservations', [BookingController::class, 'index'])->name('bookings');
            Route::get('/reservations/{booking:reference}', [BookingController::class, 'show'])->name('bookings.show');
            Route::post('/reservations/{booking:reference}/messages', [BookingController::class, 'reply'])
                ->middleware('throttle:30,1')->name('bookings.reply');
            Route::post('/reservations/{booking:reference}/annuler', [BookingController::class, 'cancel'])
                ->middleware('throttle:30,1')->name('bookings.cancel');

            Route::get('/proprietaires', [OwnerController::class, 'index'])->name('owners');
            Route::get('/proprietaires/{owner}', [OwnerController::class, 'show'])
                ->whereNumber('owner')->name('owners.show');
            Route::post('/proprietaires/{owner}/verifier', [OwnerController::class, 'verify'])
                ->whereNumber('owner')->middleware('throttle:30,1')->name('owners.verify');
            Route::post('/proprietaires/{owner}/lien', [OwnerController::class, 'link'])
                ->whereNumber('owner')->middleware('throttle:30,1')->name('owners.link');

            // Les demandes « dans l'autre sens » : le voyageur décrit, l'équipe cherche.
            Route::get('/demandes', [StayRequestController::class, 'index'])->name('stay-requests');
            Route::post('/demandes/{demande}/prendre', [StayRequestController::class, 'take'])
                ->whereNumber('demande')->middleware('throttle:60,1')->name('stay-requests.take');
            Route::post('/demandes/{demande}/clore', [StayRequestController::class, 'close'])
                ->whereNumber('demande')->middleware('throttle:60,1')->name('stay-requests.close');

            Route::get('/voyageurs', TravellerController::class)->name('travellers');

            Route::get('/whatsapp', [WhatsAppController::class, 'index'])->name('whatsapp');
            Route::post('/whatsapp/{message}/envoye', [WhatsAppController::class, 'sent'])
                ->whereNumber('message')->middleware('throttle:120,1')->name('whatsapp.sent');

            Route::get('/facturation', [InvoiceController::class, 'index'])->name('invoices');
            Route::post('/facturation/regler', [InvoiceController::class, 'settle'])
                ->middleware('throttle:60,1')->name('invoices.settle');
            Route::post('/facturation/rouvrir', [InvoiceController::class, 'reopen'])
                ->middleware('throttle:60,1')->name('invoices.reopen');

            /*
             * Les référentiels : ce qui porte les annonces. Une clé publique —
             * slug de destination, clé de catégorie ou d'équipement — ne se
             * modifie jamais : c'est un filtre d'URL et un mot de l'API mobile.
             */
            Route::get('/destinations', [DestinationController::class, 'index'])->name('destinations');
            Route::get('/destinations/nouvelle', [DestinationController::class, 'create'])->name('destinations.create');
            Route::post('/destinations', [DestinationController::class, 'store'])
                ->middleware('throttle:30,1')->name('destinations.store');
            Route::get('/destinations/{destination}', [DestinationController::class, 'edit'])
                ->whereNumber('destination')->name('destinations.edit');
            Route::post('/destinations/{destination}', [DestinationController::class, 'update'])
                ->whereNumber('destination')->middleware('throttle:60,1')->name('destinations.update');
            Route::post('/destinations/{destination}/photos', [DestinationController::class, 'uploadPhoto'])
                ->whereNumber('destination')->middleware('throttle:30,1')->name('destinations.photos.store');
            Route::post('/destinations/{destination}/photos/ajouter', [DestinationController::class, 'attachPhoto'])
                ->whereNumber('destination')->middleware('throttle:60,1')->name('destinations.photos.attach');
            Route::post('/destinations/{destination}/photos/ordre', [DestinationController::class, 'reorderPhotos'])
                ->whereNumber('destination')->middleware('throttle:120,1')->name('destinations.photos.order');
            Route::post('/destinations/{destination}/photos/{photo}/retirer', [DestinationController::class, 'detachPhoto'])
                ->whereNumber(['destination', 'photo'])->middleware('throttle:60,1')->name('destinations.photos.detach');
            Route::post('/destinations/{destination}/supprimer', [DestinationController::class, 'destroy'])
                ->whereNumber('destination')->middleware('throttle:20,1')->name('destinations.destroy');

            /*
             * La photothèque : les crédits se corrigent ici, et une photo
             * se téléverse sans passer par une destination. Les photos de
             * Commons ne se suppriment pas ; une photo de l'équipe, seulement
             * quand elle n'illustre plus rien.
             */
            Route::get('/phototheque', [PhotoLibraryController::class, 'index'])->name('photos');
            Route::post('/phototheque', [PhotoLibraryController::class, 'store'])
                ->middleware('throttle:30,1')->name('photos.store');
            Route::post('/phototheque/{photo}', [PhotoLibraryController::class, 'update'])
                ->whereNumber('photo')->middleware('throttle:60,1')->name('photos.update');
            Route::post('/phototheque/{photo}/retirer', [PhotoLibraryController::class, 'destroy'])
                ->whereNumber('photo')->middleware('throttle:30,1')->name('photos.destroy');

            Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
            Route::post('/categories', [CategoryController::class, 'store'])
                ->middleware('throttle:30,1')->name('categories.store');
            Route::post('/categories/{categorie}', [CategoryController::class, 'update'])
                ->whereNumber('categorie')->middleware('throttle:60,1')->name('categories.update');
            Route::post('/categories/{categorie}/deplacer', [CategoryController::class, 'move'])
                ->whereNumber('categorie')->middleware('throttle:120,1')->name('categories.move');
            Route::post('/categories/{categorie}/supprimer', [CategoryController::class, 'destroy'])
                ->whereNumber('categorie')->middleware('throttle:20,1')->name('categories.destroy');

            Route::get('/equipements', [AmenityController::class, 'index'])->name('amenities');
            Route::post('/equipements', [AmenityController::class, 'store'])
                ->middleware('throttle:30,1')->name('amenities.store');
            Route::post('/equipements/{equipement}', [AmenityController::class, 'update'])
                ->whereNumber('equipement')->middleware('throttle:60,1')->name('amenities.update');
            Route::post('/equipements/{equipement}/deplacer', [AmenityController::class, 'move'])
                ->whereNumber('equipement')->middleware('throttle:120,1')->name('amenities.move');
            Route::post('/equipements/{equipement}/supprimer', [AmenityController::class, 'destroy'])
                ->whereNumber('equipement')->middleware('throttle:20,1')->name('amenities.destroy');

            /*
             * Les textes du site : les pages éditoriales entières, et les
             * textes des pages construites (l'accueil, le pied de page).
             */
            Route::get('/pages', [PageController::class, 'index'])->name('pages');
            Route::get('/pages/nouvelle', [PageController::class, 'create'])->name('pages.create');
            Route::post('/pages', [PageController::class, 'store'])
                ->middleware('throttle:30,1')->name('pages.store');
            Route::post('/pages/apercu', [PageController::class, 'preview'])
                ->middleware('throttle:240,1')->name('pages.preview');
            Route::get('/pages/{page}', [PageController::class, 'edit'])
                ->whereNumber('page')->name('pages.edit');
            Route::post('/pages/{page}', [PageController::class, 'update'])
                ->whereNumber('page')->middleware('throttle:60,1')->name('pages.update');
            Route::post('/pages/{page}/publier', [PageController::class, 'publish'])
                ->whereNumber('page')->middleware('throttle:30,1')->name('pages.publish');
            Route::post('/pages/{page}/depublier', [PageController::class, 'unpublish'])
                ->whereNumber('page')->middleware('throttle:30,1')->name('pages.unpublish');
            Route::post('/pages/{page}/supprimer', [PageController::class, 'destroy'])
                ->whereNumber('page')->middleware('throttle:20,1')->name('pages.destroy');

            Route::get('/textes', [SiteTextController::class, 'index'])->name('texts');
            Route::post('/textes', [SiteTextController::class, 'update'])
                ->middleware('throttle:60,1')->name('texts.update');

            Route::get('/reglages', [SettingsController::class, 'index'])->name('settings');
            Route::post('/reglages/taux', [SettingsController::class, 'rate'])
                ->middleware('throttle:20,1')->name('settings.rate');
            Route::post('/reglages/commission', [SettingsController::class, 'commission'])
                ->middleware('throttle:20,1')->name('settings.commission');

            Route::get('/journal', JournalController::class)->name('journal');

            Route::get('/equipe', [TeamController::class, 'index'])->name('team');
            Route::post('/equipe', [TeamController::class, 'store'])
                ->middleware('throttle:10,1')->name('team.store');
            Route::post('/equipe/{membre}/retirer', [TeamController::class, 'destroy'])
                ->whereNumber('membre')->middleware('throttle:10,1')->name('team.destroy');
            Route::post('/equipe/{membre}/mot-de-passe', [TeamController::class, 'reset'])
                ->whereNumber('membre')->middleware('throttle:10,1')->name('team.reset');
        });

        Route::any('/{chemin}', NotFoundController::class)->where('chemin', '.*')->name('missing');
    });
