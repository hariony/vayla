<?php

namespace App\Providers;

use App\Repositories\AmenityRepository;
use App\Repositories\BookingMessageRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\Contracts\AmenityRepositoryInterface;
use App\Repositories\Contracts\BookingMessageRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\DestinationRepositoryInterface;
use App\Repositories\Contracts\ListingRepositoryInterface;
use App\Repositories\Contracts\OfficeRepositoryInterface;
use App\Repositories\Contracts\OutboundMessageRepositoryInterface;
use App\Repositories\Contracts\OwnerRepositoryInterface;
use App\Repositories\Contracts\PhotoRepositoryInterface;
use App\Repositories\Contracts\UnavailabilityRepositoryInterface;
use App\Repositories\DestinationRepository;
use App\Repositories\ListingRepository;
use App\Repositories\OfficeRepository;
use App\Repositories\OutboundMessageRepository;
use App\Repositories\OwnerRepository;
use App\Repositories\PhotoRepository;
use App\Repositories\UnavailabilityRepository;
use App\Services\Currency\ExchangeRateProvider;
use App\Services\Currency\SettingExchangeRate;
use App\Services\Settings\SettingsService;
use App\Services\Verification\LogCodeSender;
use App\Services\Verification\MailCodeSender;
use App\Services\Verification\SmsCodeSender;
use App\Services\Verification\VerificationCodeService;
use App\Services\Verification\WhatsAppCodeSender;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Apple\Provider as AppleProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Inversion de dépendance : les services ne connaissent que les contrats.
     * Changer d'implémentation (cache, source externe, faux dépôt de test)
     * se fait ici, sans toucher une ligne de métier.
     */
    public function register(): void
    {
        $this->app->bind(PhotoRepositoryInterface::class, PhotoRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(DestinationRepositoryInterface::class, DestinationRepository::class);
        $this->app->bind(ListingRepositoryInterface::class, ListingRepository::class);
        $this->app->bind(AmenityRepositoryInterface::class, AmenityRepository::class);
        $this->app->bind(OwnerRepositoryInterface::class, OwnerRepository::class);
        $this->app->bind(UnavailabilityRepositoryInterface::class, UnavailabilityRepository::class);
        $this->app->bind(BookingMessageRepositoryInterface::class, BookingMessageRepository::class);
        $this->app->bind(OutboundMessageRepositoryInterface::class, OutboundMessageRepository::class);
        $this->app->bind(OfficeRepositoryInterface::class, OfficeRepository::class);

        // Le taux de change vient de la configuration. Le remplacer par une
        // API de change se fait ici, et nulle part ailleurs.
        $this->app->bind(ExchangeRateProvider::class, SettingExchangeRate::class);
        $this->app->scoped(SettingsService::class);

        /*
         * Les canaux du code à usage unique, dans l'ordre où on les
         * interroge. L'e-mail sert l'inscription — c'est le seul qui part
         * automatiquement sans entreprise enregistrée ni carte bancaire.
         *
         * Pour le téléphone, `log` reste le défaut : envoyer pour de vrai doit
         * être un choix explicite, jamais le résultat d'un oubli de
         * configuration — sinon la mise au point d'un écran part sur de vrais
         * téléphones, et se facture.
         */
        $this->app->bind(VerificationCodeService::class, fn () => new VerificationCodeService([
            new MailCodeSender,
            match (config('vayla.otp.driver')) {
                'whatsapp' => new WhatsAppCodeSender,
                'sms' => new SmsCodeSender,
                default => new LogCodeSender,
            },
        ]));
    }

    public function boot(): void
    {
        /*
         * **Apple n'a pas de pilote dans Socialite**, contrairement à Google et
         * Facebook : son flux diffère (le « secret » est un JWT signé, le nom
         * n'arrive qu'une fois) et Laravel laisse ça à un paquet communautaire.
         * L'enregistrer ici plutôt que dans un `EventServiceProvider` dédié :
         * le projet n'en a pas, et en créer un pour une ligne serait une couche
         * de plus pour rien.
         */
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('apple', AppleProvider::class);
        });

        //
    }
}
