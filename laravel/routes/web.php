<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\Auth\TravellerAuthController;
use App\Http\Controllers\Auth\TravellerRegisterController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\Owner\AccessLinkController;
use App\Http\Controllers\Owner\AuthController as OwnerAuthController;
use App\Http\Controllers\Owner\BookingController as OwnerBookingController;
use App\Http\Controllers\Owner\ListingController as OwnerListingController;
use App\Http\Controllers\Owner\RegisterController as OwnerRegisterController;
use App\Http\Controllers\OwnerCalendarController;
use App\Http\Controllers\OwnerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
 * Le catalogue. Les critères passent par l'URL et non par un état de
 * composant : un filtre doit se partager, se mettre en favori et survivre au
 * retour arrière du navigateur. C'est aussi ce qui rend la page indexable.
 */
Route::get('/logements', [ListingController::class, 'index'])->name('listings.index');
Route::get('/logements/{slug}', [ListingController::class, 'show'])->name('listings.show');

/*
 * La réservation. Vayla n'encaisse rien : ces trois écrans mettent en
 * relation et bloquent des dates, l'acompte se convient entre le voyageur
 * et le propriétaire, hors plateforme.
 */
Route::get('/logements/{slug}/reserver', [BookingController::class, 'create'])->name('bookings.create');
Route::post('/logements/{slug}/reserver', [BookingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('bookings.store');
/*
 * Le suivi d'une réservation, ouvert par sa référence.
 *
 * **La référence est courte parce qu'elle se dicte au téléphone** — cinq
 * caractères sans O/0 ni I/1 — et c'est un bon choix pour l'usage. Mais cinq
 * caractères font une trentaine de millions de combinaisons, et ce nombre
 * **rétrécit à mesure que les réservations s'accumulent** : à dix mille
 * réservations, un tirage au hasard sur trois mille tombe juste. Sans limite
 * de débit, énumérer les références jusqu'à afficher le nom, le téléphone et
 * les dates d'un inconnu n'est qu'une question d'heures.
 *
 * La limite de débit est donc ce qui tient la porte, pas la longueur du code.
 */
Route::get('/reservations/{reference}', [BookingController::class, 'show'])
    ->middleware(['throttle:30,1', 'sans-index'])
    ->name('bookings.show');

/*
 * Le fil d'échange, côté voyageur. Il n'a pas de compte — c'est délibéré : la
 * référence tient lieu de droit d'accès, comme pour la page elle-même, et la
 * limite de débit est ce qui tient la porte.
 */
Route::post('/reservations/{reference}/messages', [BookingController::class, 'reply'])
    ->middleware(['throttle:20,1', 'sans-index'])
    ->name('bookings.reply');

/*
 * Les destinations. La page ne double pas le catalogue filtré : elle porte
 * ce que la grille ne porte pas — quand venir, comment y aller, et jusqu'où
 * la vérification est allée sur place.
 */
Route::get('/destinations', [DestinationController::class, 'index'])->name('destinations.index');
Route::get('/destinations/{slug}', [DestinationController::class, 'show'])->name('destinations.show');

Route::post('/ai/chat', [AiController::class, 'chat'])
    ->middleware('throttle:20,1')
    ->name('ai.chat');

/*
 * Le carrefour de connexion. Deux espaces, deux portes qui ne se ressemblent
 * pas : le propriétaire a un compte, le voyageur a une référence. La
 * recherche de référence est limitée en débit — cinq caractères se devinent à
 * force d'essais, et derrière il y a le nom et le téléphone de quelqu'un.
 */
Route::get('/connexion', [AccessController::class, 'index'])->name('access');
/*
 * La connexion sociale — Google, Facebook, Apple.
 *
 * **`/auth/{provider}` et non `/connexion/{provider}`**, malgré le vocabulaire
 * français du reste du fichier : ces deux URL ne sont pas lues par un humain,
 * elles sont **recopiées dans une console de fournisseur** (Google Cloud, Meta,
 * Apple Developer) où elles doivent correspondre au caractère près. Un accent
 * ou une variante de casse dans une URL de rappel se paie par un échec que la
 * console ne sait pas expliquer.
 *
 * Les deux routes sont bornées en débit : un point d'entrée d'authentification
 * sans limite est un point d'entrée qu'on sonde. `guest` empêche un utilisateur
 * déjà connecté d'y repasser — le rattachement d'un compte social à une session
 * ouverte est un geste différent, qui viendra depuis l'espace client.
 */
Route::middleware('guest')->group(function () {
    Route::get('/auth/{provider}', [SocialController::class, 'redirect'])
        ->middleware('throttle:10,1')->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialController::class, 'callback'])
        ->middleware('throttle:20,1')->name('social.callback');

    // Google One Tap : le navigateur envoie un jeton d'identité déjà signé,
    // vérifié côté serveur. Pas de `state` ici — la protection CSRF est celle
    // de Laravel sur ce POST, et l'authenticité vient de la signature.
    Route::post('/auth/google/one-tap', [SocialController::class, 'oneTap'])
        ->middleware('throttle:20,1')->name('social.one-tap');
});

// Le même départ depuis l'écran du propriétaire. **Une seule URL de rappel**
// pour les deux : elle est figée chez le fournisseur, et l'espace visé voyage
// en session — voir `EspaceSocial`.
Route::middleware('guest:proprietaire')->group(function () {
    Route::get('/proprietaire/auth/{provider}', [SocialController::class, 'redirectOwner'])
        ->middleware('throttle:10,1')->name('social.redirect.owner');
});

// **Même porte que `/inscription`, autre titre.** Le formulaire poste sur
// `/inscription` : s'inscrire et se connecter sont ici le même geste, et deux
// points d'entrée auraient divergé sur le renvoi de code ou l'expiration.
Route::get('/connexion/client', [TravellerRegisterController::class, 'connexion'])->name('access.client');

/*
 * L'inscription du voyageur, en deux temps : formulaire, puis code reçu par
 * e-mail. **Le compte n'existe qu'après le code** — une ligne « non vérifiée »
 * créée à la première étape permettrait de squatter l'adresse de quelqu'un
 * d'autre, qui se verrait ensuite refuser son inscription.
 *
 * Le compte reste **facultatif** : demander un séjour se fait toujours sans,
 * et une référence ouvre toujours une réservation. Il ajoute le confort de
 * retrouver ses séjours sans avoir gardé la référence.
 */
Route::middleware('guest')->group(function () {
    Route::get('/inscription', [TravellerRegisterController::class, 'form'])->name('register');
    Route::post('/inscription', [TravellerRegisterController::class, 'store'])
        ->middleware('throttle:10,1')->name('register.store');
    Route::get('/inscription/code', [TravellerRegisterController::class, 'codeForm'])->name('register.code');
    Route::post('/inscription/code', [TravellerRegisterController::class, 'confirm'])
        ->middleware('throttle:20,1')->name('register.confirm');
    Route::post('/inscription/code/renvoyer', [TravellerRegisterController::class, 'resend'])
        ->middleware('throttle:10,1')->name('register.resend');

});

Route::post('/deconnexion', [TravellerAuthController::class, 'logout'])->name('traveller.logout');

// Les réservations rattachées à l'adresse du compte : c'est tout ce que le
// compte voyageur apporte, et c'est déjà ce qui manquait le plus.
Route::get('/mes-reservations', [TravellerAuthController::class, 'bookings'])
    ->middleware(['auth', 'sans-index'])->name('traveller.bookings');

/*
 * L'espace propriétaire — un vrai compte.
 *
 * L'accès se fait par **numéro de téléphone et mot de passe**. Le numéro
 * plutôt qu'une adresse e-mail : c'est par WhatsApp qu'on joint nos
 * propriétaires, beaucoup n'ont pas de boîte qu'ils relèvent, et l'exiger
 * écarterait ceux qu'on veut servir.
 *
 * Le lien d'accès envoyé sur WhatsApp n'ouvre plus l'espace : il ouvre **le
 * compte**, à la première connexion et le jour où le mot de passe est perdu.
 * C'est ce qui rend un mot de passe tenable pour ce public — celui qui
 * l'oublie n'a ni boîte mail à relever, ni SMS à attendre : il rouvre sa
 * conversation WhatsApp.
 *
 * Tout est hors index : ces pages n'ont rien à faire dans un moteur de
 * recherche, et le lien d'accès ne doit fuiter par aucun en-tête Referer.
 */
Route::middleware('sans-index')->group(function () {
    Route::middleware('guest:proprietaire')->group(function () {
        // **Même porte que l'inscription, autre titre.** Le formulaire poste sur
        // `/proprietaire/inscription` : s'inscrire et se connecter sont ici le
        // même geste, et c'est le code qui décide lequel des deux a lieu.
        Route::get('/proprietaire/connexion', [OwnerRegisterController::class, 'connexion'])->name('owner.login');

        /*
         * L'inscription du propriétaire. **S'inscrire n'est pas publier** : le
         * compte naît au niveau 1, et c'est l'appel de vérification qui met en
         * ligne. L'écran le dit — un propriétaire qui découvre trois jours plus
         * tard que son annonce n'était pas publiée ne revient pas.
         */
        Route::get('/proprietaire/inscription', [OwnerRegisterController::class, 'form'])
            ->name('owner.register');
        Route::post('/proprietaire/inscription', [OwnerRegisterController::class, 'store'])
            ->middleware('throttle:10,1')->name('owner.register.store');
        Route::get('/proprietaire/inscription/code', [OwnerRegisterController::class, 'codeForm'])
            ->name('owner.register.code');
        Route::post('/proprietaire/inscription/code', [OwnerRegisterController::class, 'confirm'])
            ->middleware('throttle:20,1')->name('owner.register.confirm');
        // La fiche : nom et numéro WhatsApp. Le compte naît ici, pas au code —
        // rien en base tant que la fiche n'est pas remplie.
        Route::get('/proprietaire/inscription/fiche', [OwnerRegisterController::class, 'profileForm'])
            ->name('owner.register.profile');
        Route::post('/proprietaire/inscription/fiche', [OwnerRegisterController::class, 'profile'])
            ->middleware('throttle:10,1')->name('owner.register.profile.store');
        Route::post('/proprietaire/inscription/code/renvoyer', [OwnerRegisterController::class, 'resend'])
            ->middleware('throttle:10,1')->name('owner.register.resend');
    });

    // Le lien WhatsApp. Limité en débit comme tout ce qui porte une clé.
    Route::get('/proprietaire/acces/{cle}', [AccessLinkController::class, 'entrer'])
        ->middleware('throttle:30,1')->name('owner.access');

    Route::post('/proprietaire/deconnexion', [OwnerAuthController::class, 'logout'])->name('owner.logout');

    Route::middleware('auth:proprietaire')->group(function () {
        // Plus de garde « mot de passe posé » : il n'y a plus de mot de passe.
        // La connexion prouve l'adresse à chaque fois, ce qu'un mot de passe ne
        // faisait jamais.
        Route::group([], function () {
            Route::get('/proprietaire', [OwnerController::class, 'show'])->name('owner.home');

            /*
             * Les annonces, saisies par leur propriétaire. Il remplit, Vayla
             * publie : `submit` fait passer la fiche en vérification, jamais
             * en ligne. Le niveau de confiance n'entre par aucune de ces
             * routes — il est attribué, jamais déclaré.
             */
            Route::get('/proprietaire/logements', [OwnerListingController::class, 'index'])
                ->name('owner.listings');
            Route::get('/proprietaire/logements/nouveau', [OwnerListingController::class, 'create'])
                ->name('owner.listings.create');
            Route::post('/proprietaire/logements', [OwnerListingController::class, 'store'])
                ->middleware('throttle:30,1')->name('owner.listings.store');
            Route::get('/proprietaire/logements/{slug}/modifier', [OwnerListingController::class, 'edit'])
                ->name('owner.listings.edit');
            Route::post('/proprietaire/logements/{slug}', [OwnerListingController::class, 'update'])
                ->middleware('throttle:60,1')->name('owner.listings.update');
            Route::post('/proprietaire/logements/{slug}/soumettre', [OwnerListingController::class, 'submit'])
                ->middleware('throttle:20,1')->name('owner.listings.submit');

            // Le téléversement est plus lent et plus lourd : sa limite lui est propre.
            Route::post('/proprietaire/logements/{slug}/photos', [OwnerListingController::class, 'uploadPhoto'])
                ->middleware('throttle:40,1')->name('owner.photos.store');
            Route::post('/proprietaire/logements/{slug}/photos/ordre', [OwnerListingController::class, 'reorderPhotos'])
                ->middleware('throttle:60,1')->name('owner.photos.order');
            Route::post('/proprietaire/logements/{slug}/photos/{photo}/retirer', [OwnerListingController::class, 'deletePhoto'])
                ->middleware('throttle:40,1')->whereNumber('photo')->name('owner.photos.destroy');

            // L'historique complet : le tableau de bord ne montre que ce qui
            // exige une action, celui-ci répond à « qui est venu ».
            Route::get('/proprietaire/reservations', [OwnerBookingController::class, 'index'])
                ->name('owner.bookings');
            Route::get('/proprietaire/reservations/{reference}', [OwnerBookingController::class, 'show'])
                ->name('owner.bookings.show');
            Route::post('/proprietaire/reservations/{reference}/messages', [OwnerBookingController::class, 'reply'])
                ->middleware('throttle:20,1')->name('owner.bookings.reply');

            Route::post('/proprietaire/demandes/{reference}/accepter', [OwnerController::class, 'accept'])
                ->middleware('throttle:20,1')->name('owner.accept');
            Route::post('/proprietaire/demandes/{reference}/refuser', [OwnerController::class, 'decline'])
                ->middleware('throttle:20,1')->name('owner.decline');

            /*
             * Le calendrier d'un logement. Sans lui, un propriétaire qui loue
             * aussi par WhatsApp — c'est-à-dire tous — reçoit des demandes sur
             * des nuits déjà vendues et doit les refuser une par une, ce qui
             * abîme la relation avec le voyageur sans rien corriger.
             */
            Route::get('/proprietaire/logements/{slug}/calendrier', [OwnerCalendarController::class, 'show'])
                ->name('owner.calendar');
            Route::post('/proprietaire/logements/{slug}/calendrier', [OwnerCalendarController::class, 'store'])
                ->middleware('throttle:30,1')->name('owner.calendar.store');
            Route::post('/proprietaire/logements/{slug}/calendrier/{id}/liberer', [OwnerCalendarController::class, 'destroy'])
                ->middleware('throttle:30,1')->whereNumber('id')->name('owner.calendar.destroy');
        });
    });
});
