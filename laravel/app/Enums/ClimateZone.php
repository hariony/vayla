<?php

namespace App\Enums;

/**
 * Les cinq façades climatiques de Madagascar.
 *
 * C'est l'atout du calendrier, et il n'existe nulle part ailleurs : une date
 * ne dit pas seulement « libre », elle dit **« bonne idée ou pas »**. Nosy Be
 * en février est en saison cyclonique ; Antsirabe en juillet descend sous dix
 * degrés la nuit ; Sainte-Marie en août, ce sont les baleines. Un voyageur
 * l'apprend aujourd'hui sur place — donc trop tard.
 *
 * Enum et non table, pour la même raison que l'échelle de confiance : ce sont
 * des **normales climatiques**, pas des données éditables. Les rendre
 * modifiables permettrait qu'un mois change de sens sous les séjours déjà
 * planifiés, et surtout que quelqu'un efface un avertissement gênant.
 *
 * Deux limites à dire clairement, et l'écran les dit :
 *   — ce sont des tendances de saison, **pas une prévision météo** ;
 *   — une bonne saison ne garantit pas une bonne semaine.
 */
enum ClimateZone: string
{
    case Nord = 'nord';
    case Est = 'est';
    case EstForet = 'est-foret';
    case Ouest = 'ouest';
    case HautesTerres = 'hautes-terres';
    case Sud = 'sud';

    public function label(): string
    {
        return match ($this) {
            self::Nord => 'Nord et Nosy Be',
            self::Est => 'Côte est',
            self::EstForet => 'Corridor forestier',
            self::Ouest => 'Côte ouest',
            self::HautesTerres => 'Hautes terres',
            self::Sud => 'Grand Sud',
        };
    }

    /**
     * Le profil des douze mois. Index 0 = janvier.
     *
     * @return array<int, array{0: string, 1: string}> [état, précision]
     */
    public function months(): array
    {
        return match ($this) {
            self::Nord => [
                ['cyclones', 'Pluies fortes et risque cyclonique'],
                ['cyclones', 'Le cœur de la saison cyclonique'],
                ['cyclones', 'Pluies fortes, risque encore présent'],
                ['pluies', 'Les pluies s\'espacent, chaleur humide'],
                ['ideale', 'Début de la saison sèche, lagon au calme'],
                ['ideale', 'Sec et lumineux'],
                ['ideale', 'Sec, alizé soutenu'],
                ['ideale', 'Sec, la haute saison'],
                ['ideale', 'Sec et chaud, mer d\'huile'],
                ['ideale', 'Sec, avant le retour des pluies'],
                ['chaud', 'Chaleur lourde, premières averses'],
                ['pluies', 'Retour des pluies'],
            ],
            self::Est => [
                ['cyclones', 'La façade la plus exposée aux cyclones'],
                ['cyclones', 'Le cœur de la saison cyclonique'],
                ['cyclones', 'Pluies intenses, risque encore présent'],
                ['pluies', 'Pluies fréquentes, mer agitée'],
                ['pluies', 'Averses régulières, températures douces'],
                ['seche', 'Plus sec, frais le matin'],
                ['ideale', 'Sec et frais — baleines à bosse au large'],
                ['ideale', 'Sec — le pic de la saison des baleines'],
                ['ideale', 'Sec, baleines encore présentes'],
                ['ideale', 'La meilleure période sur l\'est'],
                ['ideale', 'Chaud et encore sec'],
                ['pluies', 'Retour des pluies, chaleur montante'],
            ],
            // Andasibe est à trois heures de la côte : il pleut presque toute
            // l'année et on n'y voit pas de baleines. Confondre les deux
            // façades ferait dire au calendrier une chose fausse.
            self::EstForet => [
                ['pluies', 'Pluies quotidiennes, sentiers glissants'],
                ['pluies', 'Pluies fortes, forêt saturée'],
                ['pluies', 'Pluies fréquentes'],
                ['pluies', 'Averses régulières, tout est vert'],
                ['seche', 'Averses plus courtes, air frais'],
                ['froid', 'Frais et humide, nuits piquantes'],
                ['froid', 'Le mois le plus froid, faune discrète'],
                ['seche', 'Frais, la forêt se réveille'],
                ['ideale', 'Sec et doux — les indris donnent de la voix'],
                ['ideale', 'La meilleure période, faune très active'],
                ['ideale', 'Chaud et encore praticable'],
                ['pluies', 'Retour des pluies fortes'],
            ],
            self::Ouest => [
                ['cyclones', 'Pluies fortes, risque cyclonique'],
                ['cyclones', 'Pluies fortes, pistes souvent coupées'],
                ['pluies', 'Fin des pluies, pistes encore difficiles'],
                ['seche', 'Assèchement, chaleur qui retombe'],
                ['ideale', 'Sec, la bonne période commence'],
                ['ideale', 'Sec et tempéré'],
                ['ideale', 'Sec, ciel dégagé'],
                ['ideale', 'Sec, haute saison'],
                ['ideale', 'Sec, couchers de soleil au plus net'],
                ['chaud', 'Sec mais très chaud'],
                ['chaud', 'Chaleur forte, premières pluies'],
                ['pluies', 'Retour des pluies'],
            ],
            self::HautesTerres => [
                ['pluies', 'Averses d\'après-midi, vert intense'],
                ['pluies', 'Averses d\'après-midi'],
                ['pluies', 'Fin des pluies'],
                ['ideale', 'Sec et clément, rizières dorées'],
                ['ideale', 'Sec, journées douces'],
                ['froid', 'Sec — nuits sous 10 °C, chauffage utile'],
                ['froid', 'Le mois le plus froid, nuits piquantes'],
                ['froid', 'Sec et froid la nuit, lumineux le jour'],
                ['ideale', 'Sec, les températures remontent'],
                ['ideale', 'Sec et doux — jacarandas en fleur'],
                ['ideale', 'Doux, premières averses'],
                ['pluies', 'Retour des pluies'],
            ],
            self::Sud => [
                ['chaud', 'Chaud et sec, quelques orages'],
                ['chaud', 'Le mois le plus chaud'],
                ['chaud', 'Chaud, rares averses'],
                ['ideale', 'Sec et tempéré'],
                ['ideale', 'Sec, la bonne période commence'],
                ['ideale', 'Sec — vent fort possible sur la côte'],
                ['ideale', 'Sec, vent soutenu, eau claire'],
                ['ideale', 'Sec, haute saison sur le récif'],
                ['ideale', 'Sec et lumineux'],
                ['ideale', 'Sec, chaleur qui remonte'],
                ['chaud', 'Chaleur forte'],
                ['chaud', 'Chaud, quelques averses'],
            ],
        };
    }

    /** @return array<int, self> */
    public static function ordered(): array
    {
        return self::cases();
    }
}
