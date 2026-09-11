<?php

namespace App\Contracts\Repositories;

use App\DTOs\Owners\OwnerAccountDto;
use App\DTOs\Owners\OwnerProfileDto;
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

    public function parEmail(string $email): ?Owner;

    /**
     * Le compte né de l'inscription : l'adresse est vérifiée (le code l'a
     * prouvée), la clé d'accès posée dès maintenant — c'est le lien WhatsApp
     * qui ouvre l'espace en un geste.
     */
    public function creerDepuisInscription(OwnerProfileDto $fiche, string $email): Owner;

    public function marquerConnexion(Owner $owner): void;

    /** Le lien WhatsApp a servi : il prouve qu'on tient cette ligne. */
    public function marquerTelephoneVerifie(Owner $owner): void;

    /**
     * Enregistre « Mes informations ». `$oublierVerification` : le numéro a
     * changé, la preuve qu'on le tenait ne porte plus sur rien.
     */
    public function modifierCompte(Owner $owner, OwnerAccountDto $compte, bool $oublierVerification): void;

    /** La clé du portrait, ou `null` quand il est retiré. */
    public function poserPortrait(Owner $owner, ?string $cle): void;

    /** Pose une nouvelle clé et retient le jour où elle l'a été. */
    public function poserCle(Owner $owner, string $cle): void;
}
