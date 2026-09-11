/**
 * Les rubriques du back-office, écrites une fois — même règle que
 * `espaces.js` pour les deux espaces publics. La colonne les lit, les tests
 * aussi : chaque `href` doit répondre sous la garde `admin`.
 *
 * **Rangées par ce qu'on y fait, pas par table de la base.** « Catalogue »
 * regroupe les annonces et ceux qui les possèdent parce qu'on vérifie les
 * deux ensemble — l'appel au propriétaire ouvre le niveau 2 de ses annonces.
 * « Relais » regroupe ce que Vayla fait passer à la main tant qu'il n'y a pas
 * d'entreprise enregistrée : les messages WhatsApp et l'argent.
 *
 * `compteur` nomme une clé de `officeCompteurs` (partagé par `OfficeContext`) :
 * ce sont les trois files qui attendent quelqu'un.
 */
export const RUBRIQUES_OFFICE = [
    {
        items: [
            { href: '/', label: 'Tableau de bord', icone: 'tableau' },
            { href: '/statistiques', label: 'Statistiques', icone: 'courbes' },
        ],
    },
    {
        titre: 'Catalogue',
        items: [
            { href: '/annonces', label: 'Annonces', icone: 'annonces', compteur: 'annonces' },
            { href: '/proprietaires', label: 'Propriétaires', icone: 'proprietaires' },
        ],
    },
    {
        titre: 'Séjours',
        items: [
            { href: '/reservations', label: 'Réservations', icone: 'reservations', compteur: 'reservations' },
            { href: '/demandes', label: 'Demandes de séjour', icone: 'recherche', compteur: 'demandes' },
            { href: '/voyageurs', label: 'Voyageurs', icone: 'voyageurs' },
        ],
    },
    {
        titre: 'Relais',
        items: [
            { href: '/whatsapp', label: 'WhatsApp', icone: 'whatsapp', compteur: 'whatsapp' },
            { href: '/facturation', label: 'Facturation', icone: 'facturation' },
        ],
    },
    {
        // Ce qui porte les annonces : on les corrige ici, pas dans le code.
        titre: 'Contenu',
        items: [
            { href: '/textes', label: 'Textes du site', icone: 'textes' },
            { href: '/pages', label: 'Pages', icone: 'pages' },
            { href: '/destinations', label: 'Destinations', icone: 'destinations' },
            { href: '/phototheque', label: 'Photothèque', icone: 'photo' },
            { href: '/categories', label: 'Catégories', icone: 'categories' },
            { href: '/equipements', label: 'Équipements', icone: 'equipements' },
            { href: '/reglages', label: 'Réglages', icone: 'reglages' },
        ],
    },
    {
        titre: 'Équipe',
        items: [
            { href: '/journal', label: 'Journal', icone: 'journal' },
            { href: '/equipe', label: 'Membres', icone: 'equipe' },
        ],
    },
]
