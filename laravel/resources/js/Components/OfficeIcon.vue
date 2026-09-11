<script setup>
/**
 * Les pictogrammes du back-office. Même grille de 24 px et même trait que
 * `SpaceIcon` — un seul vocabulaire graphique pour tout Vayla — mais un jeu
 * à part : ici on nomme des files de travail, pas les rubriques d'un compte.
 *
 * Neutres, toujours : la couleur vient de l'état de la rubrique. Une clé
 * inconnue retombe sur le point.
 */
const props = defineProps({
    name: { type: String, default: 'dot' },
})

const ICONS = {
    // Quatre cases inégales : un tableau de bord n'est pas une grille régulière.
    tableau: 'M4 4h7v9H4zM13 4h7v5h-7zM13 11h7v9h-7zM4 15h7v5H4z',
    // Une maison et sa coche : ce qu'on vérifie.
    annonces: 'M3.4 10.6 12 3.8l8.6 6.8M5.9 12.4v7.8h12.2v-7.8M9.4 15.6l1.9 1.9 3.4-3.6',
    proprietaires: 'M9.5 11.4a3.6 3.6 0 1 0 0-7.2 3.6 3.6 0 0 0 0 7.2M3.4 20c.8-3.5 3.2-5.4 6.1-5.4s5.3 1.9 6.1 5.4M16.2 4.6a3.4 3.4 0 0 1 0 6.4M18.3 14.9c1.3.8 2.1 2.4 2.4 4.6',
    reservations: 'M4 6.6h16v13.4H4zM4 10.8h16M8.4 3.6v4.2M15.6 3.6v4.2M9.2 14.9l2 2 3.6-3.7',
    voyageurs: 'M3.6 8.6h16.8v9.8a2 2 0 0 1-2 2H5.6a2 2 0 0 1-2-2zM8.7 8.6V5.9a1.6 1.6 0 0 1 1.6-1.6h3.4a1.6 1.6 0 0 1 1.6 1.6v2.7M3.6 13.2h16.8',
    // Une bulle et son combiné : le canal par lequel on joint les propriétaires.
    whatsapp: 'M4.2 19.8l1.2-4a8 8 0 1 1 3 2.9zM9.3 8.6c.3 2.6 3.4 5.8 6.1 6.1l1.2-1.4-2-1-1 .8a4.6 4.6 0 0 1-2.3-2.3l.8-1-1-2z',
    facturation: 'M5.6 3.6h12.8v16.8l-2.1-1.5-2.2 1.5-2.1-1.5-2.2 1.5-2.1-1.5-2.1 1.5zM9 8.4h6M9 12.4h6M9 16h3',
    // Un carnet à spirale : ce qui a été fait, écrit une fois.
    journal: 'M6.4 3.6h11.2a1.6 1.6 0 0 1 1.6 1.6v13.6a1.6 1.6 0 0 1-1.6 1.6H6.4zM4.2 7.4h3.4M4.2 12h3.4M4.2 16.6h3.4M11 8.2h5M11 11.8h5',
    equipe: 'M12 11a3.4 3.4 0 1 0 0-6.8 3.4 3.4 0 0 0 0 6.8M5.6 20c.7-3.3 3.2-5.2 6.4-5.2s5.7 1.9 6.4 5.2',
    sortir: 'M9.6 4.6H6.2a2 2 0 0 0-2 2v10.8a2 2 0 0 0 2 2h3.4M14.8 16.4 19.2 12l-4.4-4.4M19.2 12H9.4',
    recherche: 'M10.8 17.6a6.8 6.8 0 1 0 0-13.6 6.8 6.8 0 0 0 0 13.6M15.8 15.8l4.4 4.4',
    // Une flèche qui sort d'un cadre : ça s'ouvre ailleurs, sur le site public.
    externe: 'M13.6 4.4h6v6M20 4 11.4 12.6M17.6 13.8v4.6a1.6 1.6 0 0 1-1.6 1.6H5.6A1.6 1.6 0 0 1 4 18.4V8a1.6 1.6 0 0 1 1.6-1.6h4.6',
    appel: 'M5 4.2h3.4l1.7 4.2-2.2 1.4a10.4 10.4 0 0 0 6.3 6.3l1.4-2.2 4.2 1.7V19a1.6 1.6 0 0 1-1.6 1.6A15.6 15.6 0 0 1 3.4 5.8 1.6 1.6 0 0 1 5 4.2',
    coche: 'M5.6 12.6l4.1 4.1 8.7-9.2',
    fleche: 'M5 12h14M13.4 6.4 19 12l-5.6 5.6',
    retour: 'M15 5.5 8.5 12l6.5 6.5',
    avant: 'M9 5.5l6.5 6.5L9 18.5',
    horloge: 'M12 3.7a8.3 8.3 0 1 1 0 16.6 8.3 8.3 0 0 1 0-16.6M12 7.6V12l2.9 1.9',
    alerte: 'M12 4.2 21 19.6H3zM12 10v4.2M12 16.9h.01',
    // Des axes et une courbe qui monte : ce qui se mesure dans le temps.
    courbes: 'M4 4v16h16M7.5 15.5l3.6-4.3 3 2.6 5.2-6.3',
    // Un « A » et ses lignes : le texte qu'on réécrit.
    textes: 'M4 19l4.2-12h1.6L14 19M5.6 14.6h6.8M16 9.5h4M16 13.5h4M16 17.5h4',
    // Une page cornée : un document entier.
    pages: 'M6 3.5h8.5L19 8v12.5H6zM14.5 3.5V8H19M9 12.5h7M9 16h7',
    // Une épingle sur la carte : un lieu.
    destinations: 'M12 21s-6.5-5.8-6.5-11a6.5 6.5 0 0 1 13 0c0 5.2-6.5 11-6.5 11zM12 12.4a2.4 2.4 0 1 0 0-4.8 2.4 2.4 0 0 0 0 4.8',
    // Une étiquette : ce qui range une annonce dans le rail.
    categories: 'M3.8 12.2V4.6a.8.8 0 0 1 .8-.8h7.6l8 8a1.6 1.6 0 0 1 0 2.3l-5.3 5.3a1.6 1.6 0 0 1-2.3 0zM8.2 8.6h.01',
    // Une liste cochée : ce que le logement déclare.
    equipements: 'M4 6.5l1.6 1.6L8.4 5.3M4 12.5l1.6 1.6 2.8-2.8M4 18.5l1.6 1.6 2.8-2.8M11.5 6.8H20M11.5 12.8H20M11.5 18.8H20',
    // Deux curseurs : ce qui se règle.
    reglages: 'M4 7h9M17 7h3M4 17h3M11 17h9M15 9.2a2.2 2.2 0 1 0 0-4.4 2.2 2.2 0 0 0 0 4.4M9 19.2a2.2 2.2 0 1 0 0-4.4 2.2 2.2 0 0 0 0 4.4',
    photo: 'M3.5 7.5h3.2l1.6-2.3h7.4l1.6 2.3h3.2v11.5H3.5zM12 16.4a3.4 3.4 0 1 0 0-6.8 3.4 3.4 0 0 0 0 6.8',
    haut: 'M12 19V5M6 11l6-6 6 6',
    bas: 'M12 5v14M6 13l6 6 6-6',
    plus: 'M12 5v14M5 12h14',
    crayon: 'M4 20l1-4.2L16.2 4.6a1.9 1.9 0 0 1 2.7 0l.5.5a1.9 1.9 0 0 1 0 2.7L8.2 19zM14.2 6.6l3.2 3.2',
    dot: 'M12 12h.01',
}
</script>

<template>
    <svg
        class="oi"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.6"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path :d="ICONS[props.name] ?? ICONS.dot" />
    </svg>
</template>

<style scoped>
.oi { flex: none; width: 1.2rem; height: 1.2rem; }
</style>
