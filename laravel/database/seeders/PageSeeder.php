<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Les pages éditoriales de départ — celles que le pied de page annonçait sans
 * qu'elles existent.
 *
 * **Créer ce qui manque, ne jamais réécrire** : dès qu'une page existe,
 * l'équipe la tient depuis le back-office, et un `make seed` n'y touche plus.
 *
 * **Publiées seulement quand le texte dit des faits connus.** « Comment ça
 * marche », les tarifs, le guide : tout y découle des règles du produit. Le
 * contact et les pages légales demandent des informations que ce dépôt n'a
 * pas — une adresse, un responsable de publication — : elles naissent en
 * brouillon, portent des « [à compléter] » que la publication refuse, et une
 * note interne qui dit ce qui manque. Un texte juridique inventé serait pire
 * que pas de texte.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $position => $page) {
            Page::query()->firstOrCreate(['slug' => $page['slug']], $page + [
                'footer_position' => $position,
                'is_system' => true,
                'published_at' => ($page['is_published'] ?? false) ? now() : null,
            ]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function pages(): array
    {
        return [
            [
                'slug' => 'comment-ca-marche',
                'title' => 'Comment ça marche',
                'lede' => 'Chercher, lire le niveau de vérification, demander un séjour, régler sur place. Vayla ne prend pas votre argent : il vérifie avant vous.',
                'footer_group' => 'voyageurs',
                'is_published' => true,
                'seo_description' => 'Comment réserver un logement vérifié à Madagascar avec Vayla : l’échelle de confiance, la demande de séjour, le règlement sur place.',
                'body' => <<<'MD'
## Chercher un logement

Choisissez une destination et vos dates : le catalogue ne montre que les logements libres sur ces nuits. Chaque annonce dit sa capacité avant ses photos — combien de personnes, combien de chambres — parce que c'est la question qui écarte un logement.

## Lire le niveau de vérification

Chaque annonce affiche jusqu’où Vayla est allé pour la vérifier. Quatre niveaux, et le niveau atteint est écrit sur la photo :

1. **Annonce déclarée** — le propriétaire a créé sa fiche. Ce n’est pas encore une vérification.
2. **Contact confirmé** — nous avons eu le propriétaire au téléphone, et ses photos ont été comparées au reste du web.
3. **Logement visité** — une visite guidée en direct, par visio ou par notre correspondant sur place.
4. **Séjour confirmé** — des voyageurs y ont dormi et ont confirmé que tout correspondait.

La fiche montre les quatre barreaux, et dit noir sur blanc ce qui reste à franchir.

## Demander un séjour

Vous choisissez vos dates et vous envoyez une demande — **sans créer de compte et sans payer**. Les nuits sont bloquées pour vous pendant 48 heures, le temps que le propriétaire réponde. Sans réponse, la demande expire et les nuits repartent dans son calendrier : vous pouvez alors en demander d’autres.

Vous ne trouvez pas ce qu’il vous faut ? [Décrivez votre séjour](/demande) : nous le cherchons auprès des propriétaires de la zone, et nous vérifions ce qui remonte.

## Régler sur place

**Vayla n’encaisse rien.** Le prix affiché est en ariary, et c’est en ariary que vous réglez, directement au propriétaire. L’équivalent en euros n’est qu’une aide à la lecture, à la date du taux indiquée.

## Confirmer votre séjour

Après votre séjour, nous vous demandons de confirmer ce qui correspondait : les photos, l’adresse, les équipements, le prix, l’accueil, la propreté — et de signaler ce qui ne correspondait pas. Pas de note sur cinq : des faits. C’est ce qui fait passer un logement au niveau 4.
MD,
            ],
            [
                'slug' => 'tarifs',
                'title' => 'Tarifs',
                'lede' => 'L’inscription est gratuite. Vayla prend une commission sur les séjours effectués — et seulement sur eux.',
                'footer_group' => 'proprietaires',
                'is_published' => true,
                'seo_description' => 'Les tarifs de Vayla pour les propriétaires : inscription et vérification gratuites, une commission sur les seuls séjours confirmés.',
                'body' => <<<'MD'
## Ce qui est gratuit

- Créer votre compte et votre fiche.
- L’appel de vérification, la visite en visio, le niveau affiché sur votre annonce.
- Recevoir les demandes et y répondre.

Il n’y a **pas d’abonnement**.

## La commission

Vayla facture **{commission}** du total de chaque séjour **effectué et confirmé par le voyageur**. Rien sur une demande, rien sur une réservation annulée, rien sur un voyageur qui n’est pas venu : on facture le séjour, jamais l’intention.

Le taux est **figé le jour de la demande**. S’il change plus tard, les séjours déjà réservés gardent le leur.

## La facture

Elle arrive en début de mois et liste chaque séjour du mois précédent — voyageur, dates, total, commission — pour que vous puissiez la vérifier ligne à ligne. Vous la réglez par mobile money. Le mois en cours est visible dans votre espace avant d’être facturé : aucune surprise.

## Le règlement des voyageurs

Le voyageur vous règle directement, sur place, en ariary. Vayla n’encaisse rien et ne prélève rien sur ce que vous recevez.
MD,
            ],
            [
                'slug' => 'guide-du-proprietaire',
                'title' => 'Guide du propriétaire',
                'lede' => 'De l’inscription au premier séjour confirmé : ce que vous faites, ce que fait Vayla.',
                'footer_group' => 'proprietaires',
                'is_published' => true,
                'seo_description' => 'Publier un logement sur Vayla : l’inscription, la fiche, l’appel de vérification, les demandes, le calendrier et la facture.',
                'body' => <<<'MD'
## 1. Créez votre compte

Une adresse e-mail et un code : il n’y a pas de mot de passe. Vous donnez ensuite votre nom et votre numéro WhatsApp — c’est par là que Vayla vous rappelle.

## 2. Décrivez votre logement

Le nom, la destination, la capacité, le tarif, les équipements, les photos. Déclarez aussi ce qui tient pendant les coupures — groupe électrogène, panneaux solaires, réserve d’eau — et l’accès en 4×4 s’il le faut : c’est ce que les voyageurs regardent ici. Trois photos au moins, prises avec l’appareil du téléphone plutôt que dans une conversation, qui les réduit.

## 3. Envoyez la fiche à Vayla

Votre annonce n’est pas publiée tout de suite. Nous vous appelons sous 48 heures : c’est l’appel qui vérifie votre numéro et votre identité, et qui met l’annonce en ligne au niveau 2. Une visite en visio la fait passer au niveau 3.

## 4. Répondez aux demandes

Chaque demande vous arrive sur WhatsApp. Vous avez **48 heures** pour l’accepter ou la refuser ; sans réponse, les nuits repartent dans votre calendrier. La commission s’affiche à côté du total avant que vous répondiez.

## 5. Tenez votre calendrier

Vous louez aussi en direct ? Fermez ces nuits dans votre calendrier : vous ne recevrez plus de demandes sur des dates déjà prises.

## 6. La facture

En début de mois, une facture liste les séjours confirmés du mois précédent. Vous la réglez par mobile money. Voir la page « Tarifs ».
MD,
            ],
            [
                'slug' => 'a-propos',
                'title' => 'À propos',
                'lede' => 'Vayla propose des locations meublées vérifiées, partout à Madagascar.',
                'footer_group' => 'vayla',
                'is_published' => true,
                'seo_description' => 'Vayla : des locations meublées vérifiées à Madagascar. Pourquoi et comment nous vérifions chaque logement.',
                'body' => <<<'MD'
## Pourquoi Vayla

Réserver un logement à distance, c’est envoyer un acompte à quelqu’un qu’on ne connaît pas, pour une maison qu’on n’a vue qu’en photo. Ailleurs, « vérifié » ne veut souvent rien dire. Ici, c’est une échelle : chaque annonce dit ce qui a été contrôlé, et par qui.

## Ce que nous faisons

Nous appelons chaque propriétaire, nous comparons ses photos au reste du web, nous visitons les logements en visio ou par un correspondant sur place, et nous demandons aux voyageurs de confirmer ce qui correspondait. Nous ouvrons les destinations une par une, jamais avant d’avoir quelqu’un sur place.

## Ce que nous ne faisons pas

Nous n’encaissons pas votre argent : le séjour se règle au propriétaire, sur place, en ariary. Nous ne publions pas de note sur cinq : les voyageurs confirment des faits, et ce qui ne correspondait pas s’affiche à la même taille que le reste.
MD,
            ],
            [
                'slug' => 'contact',
                'title' => 'Nous contacter',
                'lede' => 'Une question sur un logement, une demande en cours, une facture ?',
                'footer_group' => 'vayla',
                'is_published' => false,
                'internal_note' => 'À compléter avant publication : le numéro WhatsApp de Vayla, l’adresse e-mail de contact et les horaires de réponse.',
                'body' => <<<'MD'
## Sur WhatsApp

C’est le plus rapide : [à compléter : numéro WhatsApp de Vayla].

## Par e-mail

[à compléter : adresse e-mail de contact]

## Quand nous répondons

[à compléter : jours et horaires de réponse]

Pour une réservation en cours, gardez votre référence sous la main — elle commence par VY-.
MD,
            ],
            [
                'slug' => 'mentions-legales',
                'title' => 'Mentions légales',
                'footer_group' => 'legal',
                'is_published' => false,
                'internal_note' => 'Brouillon. Identité de l’éditeur, responsable de publication et hébergeur à compléter, puis à faire relire avant publication.',
                'body' => <<<'MD'
## Éditeur du site

[à compléter : nom ou raison sociale, forme juridique, adresse, numéro d’immatriculation le cas échéant]

## Responsable de la publication

[à compléter : nom du responsable de la publication]

## Hébergement

[à compléter : nom, adresse et contact de l’hébergeur]

## Contact

[à compléter : adresse e-mail de contact]

## Photographies

Les photographies des lieux sont issues de Wikimedia Commons, sous licence libre ; leurs auteurs et licences sont cités dans le pied de chaque page, rubrique « Crédits photo ».
MD,
            ],
            [
                'slug' => 'conditions-d-utilisation',
                'title' => 'Conditions d’utilisation',
                'footer_group' => 'legal',
                'is_published' => false,
                'internal_note' => 'Brouillon rédigé à partir des règles du produit. À faire relire par un juriste ; compléter le droit applicable et l’éditeur avant publication.',
                'body' => <<<'MD'
## Objet

Vayla met en relation des voyageurs et des propriétaires de logements meublés à Madagascar, et affiche pour chaque logement le niveau de vérification atteint.

## Le rôle de Vayla

Vayla n’est ni propriétaire ni gestionnaire des logements, et **n’encaisse aucun paiement** entre voyageurs et propriétaires. Le séjour se règle directement au propriétaire.

## Les demandes de séjour

Une demande bloque les nuits choisies pendant 48 heures. Sans réponse du propriétaire dans ce délai, elle expire et les nuits sont libérées. Une demande acceptée engage le voyageur et le propriétaire selon les conditions convenues entre eux.

## Les propriétaires

Le propriétaire garantit l’exactitude de sa fiche. Vayla peut renvoyer une fiche à compléter, la retirer du site ou modifier son niveau de vérification. Une commission est facturée au propriétaire sur les séjours effectués et confirmés, au taux en vigueur le jour de la demande (voir « Tarifs »).

## Les confirmations de séjour

Les voyageurs confirment des faits vérifiables et signalent ce qui ne correspondait pas. Ce qu’ils signalent s’affiche sur l’annonce.

## Droit applicable

[à compléter : droit applicable et juridiction compétente]

## Éditeur

[à compléter : identité de l’éditeur — voir les mentions légales]
MD,
            ],
            [
                'slug' => 'confidentialite',
                'title' => 'Confidentialité',
                'footer_group' => 'legal',
                'is_published' => false,
                'internal_note' => 'Brouillon. Responsable du traitement, contact et durées de conservation à compléter ; à faire relire avant publication.',
                'body' => <<<'MD'
## Ce que nous collectons

- **Voyageurs** : l’adresse e-mail du compte, et, si vous les donnez, votre nom, votre prénom et votre numéro de téléphone ; pour une demande de séjour, le nom, le téléphone, les dates et le message transmis au propriétaire.
- **Propriétaires** : le nom, l’adresse e-mail, le numéro WhatsApp, la ville et, si vous les donnez, l’adresse exacte, le numéro de mobile money et une photo.

## Pourquoi

Pour transmettre les demandes, permettre au propriétaire et au voyageur de se joindre, vérifier les logements, établir la facture des propriétaires, et vous envoyer vos codes de connexion.

## Ce que nous ne faisons pas

Aucune carte bancaire n’est demandée ni conservée. L’adresse exacte et la photo d’un propriétaire ne sont jamais publiées.

## Combien de temps

[à compléter : durées de conservation]

## Vos droits

Vous pouvez demander l’accès à vos données, leur correction ou leur suppression en écrivant à [à compléter : adresse de contact].

## Responsable du traitement

[à compléter : identité du responsable du traitement]
MD,
            ],
        ];
    }
}
