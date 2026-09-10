<?php

namespace App\Enums;

/**
 * Ce qu'un voyageur confirme, point par point.
 *
 * C'est la réponse de Vayla à la note sur cinq — et c'en est le contraire.
 * Une moyenne étoilée agrège tout en un chiffre : on ne sait pas si le 4,6
 * vient d'une douche froide ou d'une adresse fausse. Pire, elle se fabrique,
 * et sur les grandes plateformes tout le monde finit à 4,8, ce qui n'informe
 * plus personne.
 *
 * Ici, chaque point est **une question fermée sur un fait vérifiable** :
 * les photos correspondaient-elles ? l'adresse était-elle la bonne ? Un
 * voyageur ne note pas son humeur, il constate.
 *
 * Et surtout : **il peut répondre non**. Un point signalé apparaît à côté du
 * point confirmé, à la même taille. Une plateforme qui n'afficherait que les
 * « oui » ne vaudrait pas mieux qu'une moyenne étoilée.
 */
enum ConfirmationPoint: string
{
    case Photos = 'photos';
    case Address = 'address';
    case Amenities = 'amenities';
    case Price = 'price';
    case Owner = 'owner';
    case Cleanliness = 'cleanliness';

    public function label(): string
    {
        return match ($this) {
            self::Photos => 'Les photos correspondent',
            self::Address => 'L\'adresse est la bonne',
            self::Amenities => 'Les équipements annoncés sont là',
            self::Price => 'Le prix est celui annoncé',
            self::Owner => 'Le propriétaire est bien celui du contact',
            self::Cleanliness => 'Le logement était propre',
        };
    }

    /** Formulation courte, pour les barres du récapitulatif. */
    public function short(): string
    {
        return match ($this) {
            self::Photos => 'Photos conformes',
            self::Address => 'Adresse exacte',
            self::Amenities => 'Équipements présents',
            self::Price => 'Prix respecté',
            self::Owner => 'Bon propriétaire',
            self::Cleanliness => 'Propreté',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Photos => 'plate',
            self::Address => 'guide',
            self::Amenities => 'kit',
            self::Price => 'safe',
            self::Owner => 'shield',
            self::Cleanliness => 'broom',
        };
    }

    /** @return array<int, self> */
    public static function ordered(): array
    {
        return self::cases();
    }
}
