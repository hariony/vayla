<?php

namespace App\Contracts\Repositories;

use App\Models\Booking;
use App\Models\InvoiceSettlement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ce que comptent les statistiques. `$avecDemo = false` écarte tout ce qui
 * est de démonstration : réservations, propriétaires, annonces, et les
 * voyageurs en `@demo.vayla.test`.
 */
interface OfficeStatsRepositoryInterface
{
    /** @return Collection<int, Booking> faites depuis `$debut`, avec `listing.destination` */
    public function demandesDepuis(Carbon $debut, bool $avecDemo): Collection;

    /** @return Collection<int, Booking> effectuées, parties depuis `$debut`, avec `listing.owner` */
    public function sejoursDepuis(Carbon $debut, bool $avecDemo): Collection;

    /** @return Collection<int, InvoiceSettlement> qui soldent un mois depuis `$debut` */
    public function reglementsDepuis(Carbon $debut, bool $avecDemo): Collection;

    /** @return Collection<int, Carbon> dates d'ouverture des comptes voyageurs */
    public function inscriptionsVoyageurs(Carbon $debut, bool $avecDemo): Collection;

    /** @return Collection<int, Carbon> */
    public function inscriptionsProprietaires(Carbon $debut, bool $avecDemo): Collection;

    /** @return Collection<int, Carbon> */
    public function annoncesCreees(Carbon $debut, bool $avecDemo): Collection;

    /** @return array<string, int> statut → nombre d'annonces, à cet instant */
    public function annoncesParStatut(bool $avecDemo): array;

    /** @return array<int, int> niveau → nombre d'annonces en ligne */
    public function publieesParNiveau(bool $avecDemo): array;

    public function demoPresente(): bool;
}
