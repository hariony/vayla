<?php

namespace App\Services\Owners;

use App\Contracts\Repositories\PhotoRepositoryInterface;
use App\Data\Owners\OwnerLandingPageData;
use App\Data\PhotoData;
use App\Data\TrustLevelData;
use App\Enums\TrustLevel;

/**
 * Ce que montre la page où mène la publicité.
 *
 * **De vraies photographies de Madagascar, créditées** — la règle photo vaut ici
 * comme partout : une fiche d'exemple illustrée par une banque d'images ferait
 * exactement ce que Vayla reproche aux annonces volées.
 *
 * **Une villa, pas des bungalows.** Vayla loue des villas et des appartements
 * meublés entre particuliers ; des bungalows d'hôtel en exemple auraient dit le
 * contraire de la page qu'ils illustrent. La villa d'Ampefy est posée sur le lac
 * Itasy, à Ampefy aussi : une galerie reste cohérente avec sa région. **Le nom
 * de la villa n'est pas écrit sur la fiche** — ce vrai lieu n'est pas sur Vayla ;
 * il n'apparaît que dans le texte alternatif et le crédit.
 *
 * `an-villa-ampefy` est une clé de démonstration : si elle disparaît avec les
 * annonces fictives, la fiche tient sans photo, et il faudra en choisir une autre.
 */
final class OwnerLandingQuery
{
    private const LIEU = 'ampefy-itasy';

    private const FICHE = 'an-villa-ampefy';

    public function __construct(private PhotoRepositoryInterface $photos) {}

    public function page(): OwnerLandingPageData
    {
        return new OwnerLandingPageData(
            lieu: $this->photo(self::LIEU),
            fiche: $this->photo(self::FICHE),
            // Jamais le quatrième : il ne s'obtient qu'avec un séjour confirmé,
            // et une fiche d'exemple ne doit pas le laisser croire acquis.
            niveaux: [
                TrustLevelData::fromEnum(TrustLevel::Declared),
                TrustLevelData::fromEnum(TrustLevel::Contact),
                TrustLevelData::fromEnum(TrustLevel::Visited),
            ],
        );
    }

    private function photo(string $cle): ?PhotoData
    {
        $photo = $this->photos->findByKey($cle);

        return $photo ? PhotoData::fromModel($photo) : null;
    }
}
