/**
 * Les rubriques des deux espaces — client et propriétaire.
 *
 * **Écrites une fois, lues par trois surfaces** : la barre latérale de
 * l'espace, le menu du compte dans l'en-tête, et le tiroir mobile. Elles
 * vivaient auparavant dans les composants qui les affichent, et le menu
 * déroulant avait déjà divergé — il ne connaissait ni « Messages » ni « Mes
 * informations » côté client, si bien que la même personne voyait deux menus
 * différents à trente pixels d'écart selon l'endroit où elle cliquait.
 *
 * **Aucune rubrique décorative.** Chacune pointe sur une route qui existe, et
 * `AccessTest` relit ce fichier pour les demander vraiment, sous la bonne
 * garde. C'est la règle qui a fait retirer « Demander un séjour » de
 * l'en-tête : un menu qui promet un écran absent coûte plus qu'un menu court.
 *
 * **Une rubrique sans `href` est une rubrique à venir** : la barre la rend
 * inerte sous un intitulé « Bientôt », et le menu du compte l'ignore — un
 * menu déroulant n'a pas de place pour ce qui ne mène nulle part.
 */

/**
 * L'espace client.
 *
 * L'ordre suit ce qu'on vient faire : ses séjours d'abord, les échanges qui
 * s'y rattachent ensuite, ses informations en dernier — on ne se connecte pas
 * à Vayla pour corriger son nom.
 */
export const RUBRIQUES_CLIENT = [
    {
        items: [
            { href: '/mes-reservations', label: 'Mes réservations', icone: 'sejours' },
            {
                href: '/mes-messages',
                label: 'Messages',
                icone: 'messages',
                // On compte les conversations, pas les messages : « 2 » veut
                // dire deux échanges vous attendent, pas quatorze lignes.
                compteur: 'travellerUnread',
            },
            { href: '/mon-compte', label: 'Mes informations', icone: 'profil' },
        ],
    },
]

/**
 * L'espace propriétaire, **dans l'ordre de l'urgence**.
 *
 * Une demande expire en 48 h, un message sans réponse fait perdre un séjour ;
 * l'historique et les logements attendent. On n'ouvre pas Vayla par curiosité.
 *
 * **La pastille est sur « Messages », pas sur « Réservations ».** Elle compte
 * des conversations qui attendent une réponse — posée sur l'historique, elle
 * disait « deux réservations », ce qui n'a jamais été son sens et envoyait
 * chercher au mauvais endroit.
 */
export const RUBRIQUES_PROPRIETAIRE = [
    {
        titre: 'Mon activité',
        items: [
            { href: '/proprietaire', label: 'Demandes', icone: 'demandes' },
            { href: '/proprietaire/messages', label: 'Messages', icone: 'messages', compteur: 'ownerUnread' },
            { href: '/proprietaire/reservations', label: 'Réservations', icone: 'reservations' },
            { href: '/proprietaire/logements', label: 'Mes logements', icone: 'logements', lancement: true },
        ],
    },
    {
        titre: 'Mon compte',
        items: [
            { href: '/proprietaire/facturation', label: 'Facturation', icone: 'facturation' },
            { href: '/proprietaire/compte', label: 'Mes informations', icone: 'profil', lancement: true },
        ],
    },
]

/**
 * **Pendant la collecte des logements, l'espace n'a que deux rubriques** —
 * celles marquées `lancement` : sa fiche, et ses informations. Les demandes,
 * les messages, les réservations et la facturation n'ont rien à montrer tant
 * qu'aucun voyageur ne peut réserver, et `LaunchGate` les ferme côté serveur ;
 * ici on ne fait que retirer les liens qui y mèneraient. Les intitulés de
 * groupe tombent avec : deux lignes n'ont pas besoin d'être rangées.
 */
export const rubriquesProprietaire = (lancement = false) => {
    if (! lancement) {
        return RUBRIQUES_PROPRIETAIRE
    }

    return [{ items: RUBRIQUES_PROPRIETAIRE.flatMap((groupe) => groupe.items.filter((item) => item.lancement)) }]
}

/**
 * **« Publier un logement » n'est pas une rubrique, c'est un geste.** Il est
 * en tête de barre, dans le seul bouton plein de l'espace ; rangé dans la
 * liste, il serait devenu un endroit où l'on va alors que c'est ce qu'on vient
 * faire.
 */
export const ACTION_PROPRIETAIRE = {
    href: '/proprietaire/logements/nouveau',
    label: 'Publier un logement',
    icone: 'publier',
}

/**
 * Les rubriques d'un espace, à plat et sans celles qui n'ouvrent rien.
 *
 * Le menu du compte est une liste de liens : une entrée « Bientôt » y serait
 * une ligne morte dans un panneau qu'on ouvre pour aller quelque part.
 */
export const liensDe = (groupes) => groupes.flatMap((groupe) => groupe.items.filter((item) => item.href))
