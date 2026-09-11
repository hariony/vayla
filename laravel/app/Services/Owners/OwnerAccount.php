<?php

namespace App\Services\Owners;

use App\Contracts\Repositories\OwnerRepositoryInterface;
use App\Data\Owners\OwnerAccountData;
use App\Data\Owners\OwnerAccountPageData;
use App\DTOs\Owners\OwnerAccountDto;
use App\Enums\MobileMoneyOperator;
use App\Models\Owner;

/**
 * « Mes informations » du propriétaire. **L'adresse e-mail n'a pas de champ** :
 * elle est l'identifiant de connexion, et modifiable depuis une session
 * ouverte elle offrirait le compte à qui a emprunté le téléphone.
 */
final class OwnerAccount
{
    public function __construct(
        private OwnerRepositoryInterface $proprietaires,
    ) {}

    public function page(Owner $owner): OwnerAccountPageData
    {
        return new OwnerAccountPageData(OwnerAccountData::fromModel($owner), MobileMoneyOperator::valeurs());
    }

    /**
     * **Changer de numéro annule la preuve qu'on le tenait.**
     * `phone_verified_at` n'enregistre pas une vérification maison : il
     * enregistre le fait que le lien d'accès envoyé sur ce WhatsApp a été
     * utilisé. Le numéro changé, cette preuve ne porte plus sur rien — la
     * garder ferait dire au compte une chose fausse, et c'est précisément ce
     * que Vayla reproche aux annonces qu'elle vérifie.
     */
    public function modifier(Owner $owner, OwnerAccountDto $compte): void
    {
        $this->proprietaires->modifierCompte($owner, $compte, oublierVerification: $compte->phone !== $owner->phone);
    }
}
