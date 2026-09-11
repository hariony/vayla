<?php

namespace App\Support;

/**
 * Les textes du site que l'équipe peut réécrire depuis le back-office — et
 * **leur version d'origine**, qui reste ici.
 *
 * **Le texte d'origine est dans le code, la modification en base.** « Rétablir
 * l'original » supprime la ligne de `site_texts` ; un texte que personne n'a
 * touché n'est écrit nulle part ailleurs qu'ici. Deux copies du même texte
 * finiraient par diverger sans que personne sache laquelle fait foi.
 *
 * **Chaque texte porte sa borne, et la raison de sa borne.** La seconde ligne du
 * titre de l'accueil est soulignée et ne se coupe jamais : au-delà de vingt
 * caractères, elle déborde sur un téléphone. Une borne sans explication serait
 * contournée à la première occasion ; celle-ci est écrite sous le champ.
 *
 * **Ce qui n'est pas ici ne se réécrit pas, et c'est délibéré** : les boutons
 * (ce sont des contrôles, pas du texte), les libellés des niveaux de confiance
 * (ils viennent de l'échelle, `TrustLevel` — les réécrire ici ferait dire à
 * l'accueil autre chose que les fiches), et tout ce qui est calculé.
 *
 * Mise en forme permise : `**gras**` et le retour à la ligne. Rien d'autre :
 * le texte est échappé avant d'être affiché, jamais injecté tel quel.
 *
 * Type : `ligne` (un champ), `texte` (un paragraphe), `titre` (quelques mots,
 * retour à la ligne permis).
 */
final class SiteTextCatalog
{
    public const GROUPES = [
        'accueil-ouverture' => [
            'titre' => 'Accueil — l’ouverture',
            'ancre' => '/',
            'textes' => [
                'accueil.hero.titre' => [
                    'label' => 'Titre, première ligne', 'type' => 'ligne', 'max' => 60,
                    'defaut' => 'Location de villas et appartements meublés.',
                    'aide' => 'Ce que le site propose, en une ligne. C’est le texte le plus lu du site.',
                ],
                'accueil.hero.titre_souligne' => [
                    'label' => 'Titre, ligne soulignée', 'type' => 'ligne', 'max' => 20,
                    'defaut' => 'Vérifiés avant vous.',
                    'aide' => 'Vingt caractères au plus : cette ligne soulignée ne se coupe jamais, et au-delà elle déborde de l’écran d’un téléphone.',
                ],
                'accueil.hero.accroche' => [
                    'label' => 'Accroche', 'type' => 'texte', 'max' => 260,
                    'defaut' => 'Chaque logement affiche jusqu’où nous sommes allés pour le vérifier. Déclaré, contact confirmé, visité en visio, séjour confirmé — c’est écrit sur l’annonce, avant le prix.',
                ],
                'accueil.hero.stat_niveaux' => [
                    'label' => 'Sous le chiffre des niveaux', 'type' => 'ligne', 'max' => 40,
                    'defaut' => 'niveaux de vérification',
                    'aide' => 'Le chiffre, lui, est compté : il vient de l’échelle de confiance.',
                ],
                'accueil.hero.stat_destinations' => [
                    'label' => 'Sous le chiffre des destinations', 'type' => 'ligne', 'max' => 40,
                    'defaut' => 'destinations ouvertes',
                ],
                'accueil.hero.stat_prix' => [
                    'label' => 'Sous « Ar »', 'type' => 'ligne', 'max' => 40,
                    'defaut' => 'prix en ariary, sans conversion',
                ],
            ],
        ],

        'accueil-envies' => [
            'titre' => 'Accueil — les envies du moment',
            'ancre' => '/#offres',
            'textes' => [
                'accueil.offres.titre' => [
                    'label' => 'Titre du rail', 'type' => 'ligne', 'max' => 40,
                    'defaut' => 'Les envies du moment',
                    'aide' => 'Éditorial, jamais statistique : « les plus populaires » deviendrait faux le jour où une place du rail sera vendue — et mesurable.',
                ],
                'accueil.offres.consigne' => [
                    'label' => 'Consigne sous le titre', 'type' => 'ligne', 'max' => 80,
                    'defaut' => 'Choisissez une envie, la sélection suit.',
                ],
                'accueil.offres.demo' => [
                    'label' => 'Mention « Aperçu »', 'type' => 'ligne', 'max' => 120,
                    'defaut' => 'Annonces fictives, le temps que les premiers propriétaires publient.',
                    'aide' => 'Ne s’affiche que tant que les annonces de démonstration sont servies. Elle doit continuer à dire qu’elles sont fictives.',
                ],
                'accueil.offres.vide_titre' => [
                    'label' => 'Titre quand rien ne correspond', 'type' => 'ligne', 'max' => 80,
                    'defaut' => 'Rien ici. C’est exactement pour ça qu’on existe.',
                ],
                'accueil.offres.vide_texte' => [
                    'label' => 'Texte quand rien ne correspond', 'type' => 'texte', 'max' => 240,
                    'defaut' => 'Décrivez ce que vous cherchez : on va le chercher auprès des propriétaires, on le vérifie, et on vous répond.',
                ],
            ],
        ],

        'accueil-confiance' => [
            'titre' => 'Accueil — la confiance',
            'ancre' => '/#confiance',
            'note' => 'Les quatre niveaux eux-mêmes ne se réécrivent pas ici : ils viennent de l’échelle de confiance, et l’accueil doit dire exactement ce que disent les fiches.',
            'textes' => [
                'accueil.confiance.surtitre' => ['label' => 'Sur-titre', 'type' => 'ligne', 'max' => 30, 'defaut' => 'La différence'],
                'accueil.confiance.titre' => [
                    'label' => 'Titre', 'type' => 'titre', 'max' => 80,
                    'defaut' => "Quatre niveaux.\nÉcrits sur chaque annonce.",
                    'aide' => 'Un retour à la ligne est permis.',
                ],
                'accueil.confiance.accroche' => [
                    'label' => 'Accroche', 'type' => 'texte', 'max' => 220,
                    'defaut' => 'Ailleurs, « vérifié » ne veut rien dire. Ici, c’est une échelle : vous savez toujours ce qui a été contrôlé, et par qui.',
                ],
            ],
        ],

        'accueil-atlas' => [
            'titre' => 'Accueil — l’atlas',
            'ancre' => '/#atlas',
            'textes' => [
                'accueil.atlas.surtitre' => ['label' => 'Sur-titre', 'type' => 'ligne', 'max' => 30, 'defaut' => 'L’atlas'],
                'accueil.atlas.titre' => ['label' => 'Titre', 'type' => 'ligne', 'max' => 60, 'defaut' => 'Où l’on est déjà passé'],
                'accueil.atlas.accroche' => [
                    'label' => 'Accroche', 'type' => 'texte', 'max' => 200,
                    'defaut' => 'On ouvre région par région, jamais avant d’avoir un correspondant sur place. Survolez la carte.',
                ],
            ],
        ],

        'accueil-demande' => [
            'titre' => 'Accueil — la demande de séjour',
            'ancre' => '/#demande',
            'textes' => [
                'accueil.demande.surtitre' => ['label' => 'Sur-titre', 'type' => 'ligne', 'max' => 30, 'defaut' => 'Le sens inverse'],
                'accueil.demande.titre' => ['label' => 'Titre', 'type' => 'ligne', 'max' => 50, 'defaut' => 'Vous ne trouvez pas ?'],
                'accueil.demande.titre_accent' => [
                    'label' => 'Titre, seconde ligne en italique', 'type' => 'ligne', 'max' => 50,
                    'defaut' => 'C’est nous qui cherchons.',
                ],
                'accueil.demande.accroche' => [
                    'label' => 'Accroche', 'type' => 'texte', 'max' => 300,
                    'defaut' => 'Dites-nous la ville, les dates, le budget et le nombre de voyageurs. On sollicite les propriétaires de la zone, on vérifie ce qui remonte, et vous recevez les réponses — sur WhatsApp, sans créer de compte.',
                ],
                'accueil.demande.etape1' => ['label' => 'Étape 1', 'type' => 'ligne', 'max' => 80, 'defaut' => 'Vous décrivez le séjour en une minute.'],
                'accueil.demande.etape2' => ['label' => 'Étape 2', 'type' => 'ligne', 'max' => 80, 'defaut' => 'On diffuse la demande aux propriétaires vérifiés.'],
                'accueil.demande.etape3' => ['label' => 'Étape 3', 'type' => 'ligne', 'max' => 80, 'defaut' => 'On contrôle chaque proposition avant de vous l’envoyer.'],
            ],
        ],

        'accueil-proprietaires' => [
            'titre' => 'Accueil — les propriétaires',
            'ancre' => '/#proprietaires',
            'textes' => [
                'accueil.proprietaires.surtitre' => ['label' => 'Sur-titre', 'type' => 'ligne', 'max' => 30, 'defaut' => 'Propriétaires'],
                'accueil.proprietaires.titre' => ['label' => 'Titre', 'type' => 'ligne', 'max' => 60, 'defaut' => 'Votre annonce, enfin crédible.'],
                'accueil.proprietaires.accroche' => [
                    'label' => 'Accroche', 'type' => 'texte', 'max' => 280,
                    'defaut' => 'Vous perdez des séjours parce que le voyageur n’ose pas envoyer l’acompte. Faites vérifier votre logement une fois : le niveau s’affiche sur votre annonce, et il parle pour vous.',
                ],
                'accueil.proprietaires.deja' => ['label' => 'Avant « Accéder à mon espace »', 'type' => 'ligne', 'max' => 60, 'defaut' => 'Déjà propriétaire sur Vayla ?'],
                'accueil.proprietaires.argument1_titre' => ['label' => 'Argument 1 — titre', 'type' => 'ligne', 'max' => 40, 'defaut' => 'Photos protégées'],
                'accueil.proprietaires.argument1_texte' => ['label' => 'Argument 1 — texte', 'type' => 'texte', 'max' => 160, 'defaut' => 'On compare vos images au reste du web : personne ne réutilisera votre logement.'],
                'accueil.proprietaires.argument2_titre' => ['label' => 'Argument 2 — titre', 'type' => 'ligne', 'max' => 40, 'defaut' => 'La visio suffit'],
                'accueil.proprietaires.argument2_texte' => ['label' => 'Argument 2 — texte', 'type' => 'texte', 'max' => 160, 'defaut' => 'Une visite guidée par appel vidéo, depuis chez vous, et le niveau 3 est acquis.'],
                'accueil.proprietaires.argument3_titre' => ['label' => 'Argument 3 — titre', 'type' => 'ligne', 'max' => 40, 'defaut' => 'Les séjours comptent'],
                'accueil.proprietaires.argument3_texte' => ['label' => 'Argument 3 — texte', 'type' => 'texte', 'max' => 160, 'defaut' => 'Chaque séjour confirmé par un voyageur consolide votre niveau.'],
            ],
        ],

        'pied' => [
            'titre' => 'Pied de page',
            'ancre' => '/',
            'note' => 'Les liens du pied de page viennent des pages publiées : on les range depuis « Pages ».',
            'textes' => [
                'pied.accroche' => [
                    'label' => 'Présentation sous la marque', 'type' => 'texte', 'max' => 200,
                    'defaut' => 'Des locations meublées vérifiées, partout à Madagascar. On contrôle avant vous, pour que vous réserviez sans retenir votre souffle.',
                ],
            ],
        ],
    ];

    /** @return array<string, array<string, mixed>> la définition de chaque texte, à plat */
    public static function definitions(): array
    {
        return collect(self::GROUPES)->flatMap(fn (array $g) => $g['textes'])->all();
    }

    /** @return array<string, string> */
    public static function defauts(): array
    {
        return collect(self::definitions())->map(fn (array $d) => $d['defaut'])->all();
    }
}
