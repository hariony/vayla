/**
 * Les pictogrammes du rail de catégories, au trait, dans la grille de 24 px.
 *
 * **Écrits une fois, lus par deux surfaces** : le rail de l'accueil et l'écran
 * « Catégories » du back-office, où l'on choisit le dessin d'une catégorie.
 * Côté serveur, `OfficeContentReadService::ICONES_CATEGORIES` borne le choix ;
 * un test vérifie que les deux listes portent les mêmes clés — sinon une
 * catégorie choisirait un dessin qui n'existe pas et retomberait sur
 * l'étincelle.
 */
export const ICONES_CATEGORIES = {
    sparkle: 'M12 3.5l1.9 5.1 5.1 1.9-5.1 1.9L12 17.5l-1.9-5.1L5 10.5l5.1-1.9z',
    wave: 'M3 9.5c2.2-2 4.3-2 6.5 0s4.3 2 6.5 0 4.3-2 5 -.6M3 15c2.2-2 4.3-2 6.5 0s4.3 2 6.5 0 4.3-2 5-.6',
    drop: 'M12 3.5c3.4 4 5.5 6.6 5.5 9.3a5.5 5.5 0 0 1-11 0c0-2.7 2.1-5.3 5.5-9.3z',
    peak: 'M2.5 19h19L14 5.5 9.8 13 7.5 9.8z',
    leaf: 'M20 4c0 8.5-4.4 13-11 13H5c0-8 4.6-13 11-13zM5 20c2.5-5 5.5-7.8 9.5-9.8',
    city: 'M3.5 20V9.5l6-3.5v4l5-2.5V20M3.5 20h17M7 20v-3.5h3V20M14.5 12.5h3v7.5',
    group: 'M3.5 19.5c0-3 2.4-4.8 5-4.8s5 1.8 5 4.8M8.5 11.6a3.3 3.3 0 1 0 0-6.6 3.3 3.3 0 0 0 0 6.6M16 15.2c2.5 0 4.5 1.7 4.5 4.3M16.4 11.4a2.9 2.9 0 1 0 0-5.8',
    check: 'M4.5 12.5l5 5 10-10',
}
