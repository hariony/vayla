<?php

namespace Tests\Feature;

use App\Contracts\Ai\AiChat;
use App\Contracts\Destinations\DestinationGallery;
use App\Contracts\Office\ActionJournal;
use App\Contracts\Office\AdminPasswords;
use App\Contracts\Photos\PhotoProcessor;
use App\Contracts\Photos\PhotoStorage;
use App\Contracts\Repositories\BookingRepositoryInterface;
use App\Contracts\Repositories\DestinationGalleryRepositoryInterface;
use App\Contracts\Repositories\ListingDraftRepositoryInterface;
use App\Contracts\Repositories\ListingGalleryRepositoryInterface;
use App\Contracts\Repositories\OfficeStatsRepositoryInterface;
use App\Contracts\Repositories\OwnerSpaceRepositoryInterface;
use App\Contracts\Repositories\PageRepositoryInterface;
use App\Contracts\Repositories\PhotoLibraryRepositoryInterface;
use App\Contracts\Repositories\SiteTextRepositoryInterface;
use App\Contracts\Repositories\SocialAccountRepositoryInterface;
use App\Contracts\Repositories\StayRequestRepositoryInterface;
use App\Contracts\Repositories\TravellerRepositoryInterface;
use App\Contracts\Repositories\VerificationCodeRepositoryInterface;
use App\Repositories\BookingRepository;
use App\Repositories\DestinationGalleryRepository;
use App\Repositories\ListingDraftRepository;
use App\Repositories\ListingGalleryRepository;
use App\Repositories\OfficeStatsRepository;
use App\Repositories\OwnerSpaceRepository;
use App\Repositories\PageRepository;
use App\Repositories\PhotoLibraryRepository;
use App\Repositories\SiteTextRepository;
use App\Repositories\SocialAccountRepository;
use App\Repositories\StayRequestRepository;
use App\Repositories\TravellerRepository;
use App\Repositories\VerificationCodeRepository;
use App\Services\Ai\AiService;
use App\Services\Destinations\DestinationGalleryService;
use App\Services\Office\AdminJournal;
use App\Services\Office\Auth\AdminPasswordService;
use App\Services\Photos\GdPhotoProcessor;
use App\Services\Photos\LocalPhotoStorage;
use Tests\TestCase;

/**
 * Les règles d'architecture, **tenues par des tests plutôt que par la
 * mémoire** : sans eux, la prochaine urgence remettrait une requête dans un
 * contrôleur.
 *
 * La mise en conformité s'est faite par lots, fichier par fichier ; tout y
 * est désormais, et **les listes sont devenues des dossiers** : un contrôleur,
 * un middleware, un service ou une commande qu'on ajoute est tenu d'emblée.
 */
class ArchitectureTest extends TestCase
{
    /**
     * Les commandes qui fabriquent un jeu de démonstration, **comme les
     * seeders** : écrire en base est leur métier, et elles refusent de tourner
     * ailleurs qu'en local.
     */
    private const COMMANDES_DE_DONNEES = [
        'Console/Commands/SeedDemoHistory.php',
        'Console/Commands/SeedTestAccounts.php',
    ];

    public function test_chaque_contrat_se_resout_vers_son_implementation(): void
    {
        foreach ([
            PhotoLibraryRepositoryInterface::class => PhotoLibraryRepository::class,
            DestinationGalleryRepositoryInterface::class => DestinationGalleryRepository::class,
            StayRequestRepositoryInterface::class => StayRequestRepository::class,
            PhotoProcessor::class => GdPhotoProcessor::class,
            PhotoStorage::class => LocalPhotoStorage::class,
            DestinationGallery::class => DestinationGalleryService::class,
            ActionJournal::class => AdminJournal::class,
            AdminPasswords::class => AdminPasswordService::class,
            AiChat::class => AiService::class,
            OfficeStatsRepositoryInterface::class => OfficeStatsRepository::class,
            PageRepositoryInterface::class => PageRepository::class,
            SiteTextRepositoryInterface::class => SiteTextRepository::class,
            BookingRepositoryInterface::class => BookingRepository::class,
            OwnerSpaceRepositoryInterface::class => OwnerSpaceRepository::class,
            ListingDraftRepositoryInterface::class => ListingDraftRepository::class,
            ListingGalleryRepositoryInterface::class => ListingGalleryRepository::class,
            TravellerRepositoryInterface::class => TravellerRepository::class,
            SocialAccountRepositoryInterface::class => SocialAccountRepository::class,
            VerificationCodeRepositoryInterface::class => VerificationCodeRepository::class,
        ] as $contrat => $implementation) {
            $this->assertInstanceOf($implementation, app($contrat), $contrat);
        }
    }

    /** Recevoir, appeler un service, répondre : ni requête, ni validation en ligne. */
    public function test_les_controleurs_ne_font_ni_requete_ni_validation(): void
    {
        foreach ([...$this->fichiers('Http/Controllers'), ...$this->fichiers('Http/Middleware')] as $fichier) {
            $code = $this->code($fichier);

            $this->assertDoesNotMatchRegularExpression('/[A-Z]\w+::(query|where|find|create|firstOrCreate|updateOrCreate)\(/', $code, "{$fichier} interroge un modèle.");
            $this->assertStringNotContainsString('DB::', $code, $fichier);
            $this->assertStringNotContainsString('->validate(', $code, "{$fichier} valide en ligne : c'est le rôle d'un FormRequest.");
            $this->assertStringNotContainsString('Validator::make', $code, $fichier);
        }
    }

    /** Les services passent par les repositories — et ne reçoivent jamais la Request. */
    public function test_les_services_ne_parlent_qu_aux_contrats(): void
    {
        foreach ($this->fichiers('Services') as $fichier) {
            $code = $this->code($fichier);

            $this->assertDoesNotMatchRegularExpression('/[A-Z]\w+::(query|where|find|create)\(/', $code, "{$fichier} interroge Eloquent directement.");
            $this->assertDoesNotMatchRegularExpression('/DB::(table|select|raw)/', $code, "{$fichier} écrit du SQL : c'est le rôle d'un repository.");
            $this->assertDoesNotMatchRegularExpression('/->(save|forceFill)\(/', $code, "{$fichier} écrit un modèle : c'est le rôle d'un repository.");
            $this->assertStringNotContainsString('Illuminate\\Http\\Request', $code, "{$fichier} reçoit la Request : il doit recevoir un DTO.");
        }
    }

    /**
     * Une commande est une porte de plus, comme un contrôleur : elle appelle un
     * service ou un repository, elle n'écrit pas d'Eloquent. La validation d'un
     * argument de ligne de commande, elle, n'a pas de FormRequest.
     */
    public function test_les_commandes_passent_par_les_services(): void
    {
        $commandes = array_diff($this->fichiers('Console/Commands'), self::COMMANDES_DE_DONNEES);

        foreach ($commandes as $fichier) {
            $code = $this->code($fichier);

            $this->assertDoesNotMatchRegularExpression('/[A-Z]\w+::(query|where|find|create|firstOrCreate|updateOrCreate)\(/', $code, "{$fichier} interroge un modèle.");
            $this->assertDoesNotMatchRegularExpression('/->(save|forceFill|delete)\(/', $code, "{$fichier} écrit un modèle.");
        }
    }

    /** @return list<string> les fichiers PHP d'un dossier de `app/`, récursivement, en chemins relatifs */
    private function fichiers(string $dossier): array
    {
        $fichiers = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path($dossier), \FilesystemIterator::SKIP_DOTS)) as $f) {
            if ($f->getExtension() === 'php') {
                $fichiers[] = str_replace(app_path().'/', '', $f->getPathname());
            }
        }

        sort($fichiers);

        return $fichiers;
    }

    /** Le code, sans les commentaires : un test qui échoue parce qu'on a documenté la règle n'apprend rien. */
    private function code(string $fichier): string
    {
        $tokens = token_get_all((string) file_get_contents(app_path($fichier)));

        return implode('', array_map(
            fn ($t) => is_array($t) ? (in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true) ? '' : $t[1]) : $t,
            $tokens,
        ));
    }
}
