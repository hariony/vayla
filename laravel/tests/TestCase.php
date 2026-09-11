<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * **Le garde-fou qui protège la base de développement.**
     *
     * `RefreshDatabase` migre à zéro la connexion par défaut. Si elle pointe
     * sur PostgreSQL, la suite **vide la base de travail** — annonces,
     * photos, propriétaires, clés d'accès, comptes réels compris. C'est arrivé
     * deux fois.
     *
     * Les quatre lignes `<server force="true">` de `phpunit.xml` sont censées
     * l'empêcher, mais elles ne suffisent pas : `docker/entrypoint.sh` lance
     * `config:cache` à chaque démarrage du conteneur, et un `config.php`
     * compilé **court-circuite l'environnement**. Laravel ne relit alors ni
     * `$_SERVER`, ni `$_ENV` : `database.default` reste `pgsql`.
     *
     * **Le contrôle vit dans `setUpTraits()`, pas dans `setUp()`.** Sa
     * première version était écrite après `parent::setUp()` — or c'est
     * `parent::setUp()` qui appelle `setUpTraits()`, donc `RefreshDatabase`,
     * donc `migrate:fresh`. Le garde-fou levait bien son erreur, mais **sur une
     * base déjà effacée** : il annonçait une protection qu'il ne donnait pas.
     * `setUpTraits()` s'exécute une fois l'application chargée — la
     * configuration est lisible — et **avant** le premier trait : c'est le seul
     * endroit où l'on peut encore refuser.
     */
    protected function setUpTraits()
    {
        $this->refuserUneAutreBaseQueLaMemoire();

        return parent::setUpTraits();
    }

    private function refuserUneAutreBaseQueLaMemoire(): void
    {
        $connexion = config('database.default');

        if ($connexion !== 'sqlite' || config("database.connections.{$connexion}.database") !== ':memory:') {
            throw new RuntimeException(
                "Les tests tournent sur la connexion « {$connexion} », pas sur SQLite en mémoire : "
                .'la suite viderait la base de développement. Rien n\'a été touché. '
                .'Un cache de configuration compilé ignore les <server> de phpunit.xml — '
                .'lancez `make test` (qui le vide d\'abord) plutôt que `php artisan test`.'
            );
        }

        // Une connexion nommée `sqlite` peut malgré tout pointer sur un
        // fichier : on vérifie ce qui est réellement ouvert.
        if (DB::connection()->getDatabaseName() !== ':memory:') {
            throw new RuntimeException('La connexion de test n’est pas en mémoire. Rien n\'a été touché.');
        }
    }
}
