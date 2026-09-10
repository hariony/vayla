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
     * photos, propriétaires, clés d'accès comprises. C'est arrivé.
     *
     * Les quatre lignes `<server force="true">` de `phpunit.xml` sont censées
     * l'empêcher, mais elles ne suffisent pas : `docker/entrypoint.sh` lance
     * `config:cache` à chaque démarrage du conteneur, et un `config.php`
     * compilé **court-circuite l'environnement**. Laravel ne relit alors ni
     * `$_SERVER`, ni `$_ENV` : `database.default` reste `pgsql`, `app.env`
     * reste `local` — d'où, le même jour, des POST de test en 419 et une base
     * de développement effacée.
     *
     * D'où ce contrôle, qui ne dépend d'aucun cache : si la connexion n'est
     * pas SQLite en mémoire, on s'arrête avant la première migration.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $connexion = config('database.default');

        if ($connexion !== 'sqlite' || config("database.connections.{$connexion}.database") !== ':memory:') {
            throw new RuntimeException(
                "Les tests tournent sur la connexion « {$connexion} », pas sur SQLite en mémoire : "
                .'la suite viderait la base de développement. '
                .'Un cache de configuration compilé ignore les <server> de phpunit.xml — '
                .'lancez `make cache` (ou `php artisan config:clear`) puis relancez.'
            );
        }

        // Une connexion nommée `sqlite` peut malgré tout pointer sur un
        // fichier : on vérifie ce qui est réellement ouvert.
        if (DB::connection()->getDatabaseName() !== ':memory:') {
            throw new RuntimeException('La connexion de test n’est pas en mémoire.');
        }
    }
}
