<?php

namespace App\Contracts\Repositories;

use App\Models\Owner;
use Illuminate\Support\Collection;

interface OwnerRepositoryInterface
{
    /** Le propriétaire porteur de cette clé, ou `null`. */
    public function findByKey(string $key): ?Owner;

    /**
     * Retrouve un propriétaire depuis le seul repère qu'un humain a sous la
     * main en situation : son numéro de téléphone, tel qu'il est écrit ou
     * non — c'est par WhatsApp qu'on le joint. Jamais par identifiant : un
     * numéro tapé de travers ouvrirait l'espace de quelqu'un d'autre.
     */
    public function trouver(string $identifiant): ?Owner;

    /** @return Collection<int, Owner> */
    public function tous(): Collection;

    /** Pose une nouvelle clé et retient le jour où elle l'a été. */
    public function poserCle(Owner $owner, string $cle): void;
}
