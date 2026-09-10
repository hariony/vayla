<?php

namespace App\Repositories;

use App\Models\Owner;
use App\Repositories\Contracts\OwnerRepositoryInterface;
use App\Support\Telephone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OwnerRepository implements OwnerRepositoryInterface
{
    public function findByKey(string $key): ?Owner
    {
        // Tout est chargé d'un coup : le tableau de bord montre les demandes,
        // les séjours à venir et les logements sur un seul écran, et les
        // charger séparément ferait une requête par annonce.
        return Owner::query()
            ->with([
                'listings' => fn ($q) => $q->orderBy('title'),
                'listings.destination',
                'listings.photos',
                // Le calendrier du propriétaire lit les deux sources
                // d'occupation sur le logement : ses périodes déclarées et
                // les réservations en cours.
                'listings.unavailabilities',
                'listings.bookings',
                'bookings' => fn ($q) => $q->orderBy('arrival'),
            ])
            ->where('access_key', $key)
            ->first();
    }

    /**
     * Le numéro est **le** repère : c'est par WhatsApp qu'on joint les
     * propriétaires, et c'est le numéro qu'on a sous les yeux au moment où
     * l'un d'eux signale que son lien a fuité ou n'arrive pas à se connecter.
     *
     * **On compare des numéros, plus des fins de chaîne.** La version
     * précédente rapprochait les **neuf derniers chiffres** : ça marchait pour
     * Madagascar, mais ça reposait sur une coïncidence de longueur — deux
     * numéros de pays différents finissant pareil auraient ouvert le même
     * compte. `Telephone` ramène les deux côtés en E.164 et l'égalité redevient
     * une égalité.
     *
     * **Aucune recherche par identifiant.** Elle avait l'air pratique et
     * c'était un piège : `0001` devenait la clé primaire 1, et un numéro tapé
     * de travers ouvrait l'espace de quelqu'un d'autre. Le seul repère est le
     * numéro, et une saisie qui n'en désigne aucun ne désigne personne.
     */
    public function trouver(string $identifiant): ?Owner
    {
        $cherche = Telephone::depuis($identifiant);

        if (! $cherche) {
            return null;
        }

        if ($exact = Owner::query()->where('phone', $cherche->e164())->first()) {
            return $exact;
        }

        // Le repli couvre les lignes écrites avant la normalisation : la
        // comparaison se fait en PHP parce que normaliser un numéro en SQL
        // n'a pas la même syntaxe sous SQLite et sous PostgreSQL, et que le
        // nombre de propriétaires se compte en dizaines.
        return Owner::query()->get()
            ->first(fn (Owner $o) => $cherche->equivaut(Telephone::depuis((string) $o->phone)));
    }

    public function tous(): Collection
    {
        return Owner::query()->orderBy('name')->get();
    }

    public function poserCle(Owner $owner, string $cle): void
    {
        $owner->forceFill([
            'access_key' => $cle,
            'access_key_set_at' => Carbon::now(),
        ])->save();
    }
}
