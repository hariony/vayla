<script setup>
/**
 * Les pictogrammes des espaces — la barre latérale du client et celle du
 * propriétaire. Ceux de la fiche de logement, bicolores et animés, vivent dans
 * `SectionGlyph`.
 *
 * Même grille de 24 px, même trait et même façon de faire que
 * `AmenityIcon` : un seul vocabulaire graphique sur tout le site, et aucune
 * bibliothèque d'icônes ajoutée. Une dizaine de tracés pèsent moins qu'une
 * dépendance, et ils suivent notre grille plutôt que celle d'un autre.
 *
 * **Ils ne décorent pas, ils repèrent.** Dans une barre qu'on parcourt des
 * dizaines de fois par semaine, la forme se reconnaît avant le mot — c'est ce
 * qui fait qu'on vise « Demandes » sans relire toute la liste. Ils restent
 * donc **neutres** : la couleur est portée par l'état de la rubrique, pas par
 * le dessin.
 *
 * Une clé inconnue retombe sur le point, comme ailleurs : une rubrique
 * ajoutée sans son tracé ne doit pas casser l'écran.
 */
const props = defineProps({
    name: { type: String, default: 'dot' },
})

const ICONS = {
    // Une corbeille d'arrivée : ce qui attend une réponse, et qui repartira.
    demandes: 'M3.6 13.6h4.1l1.4 2.4h5.8l1.4-2.4h4.1M3.6 13.6 6 5.6a2 2 0 0 1 1.9-1.4h8.2A2 2 0 0 1 18 5.6l2.4 8v4.6a2 2 0 0 1-2 2H5.6a2 2 0 0 1-2-2z',
    // Un calendrier coché : des nuits engagées, pas des dates libres.
    reservations: 'M4 6.6h16v13.4H4zM4 10.8h16M8.4 3.6v4.2M15.6 3.6v4.2M9.2 14.9l2 2 3.6-3.7',
    logements: 'M3.4 10.6 12 3.8l8.6 6.8M5.9 12.4v7.8h12.2v-7.8M9.9 20.2v-5h4.2v5',
    publier: 'M12 3.7a8.3 8.3 0 1 1 0 16.6 8.3 8.3 0 0 1 0-16.6M12 8.3v7.4M8.3 12h7.4',
    calendrier: 'M4 6.6h16v13.4H4zM4 10.8h16M8.4 3.6v4.2M15.6 3.6v4.2',
    // Un reçu, dents en bas : ce qu'on doit, pas ce qu'on encaisse.
    facturation: 'M5.6 3.6h12.8v16.8l-2.1-1.5-2.2 1.5-2.1-1.5-2.2 1.5-2.1-1.5-2.1 1.5zM9 8.4h6M9 12.4h6',
    messages: 'M20.4 15.3a2 2 0 0 1-2 2H8.2l-4.6 4V5.6a2 2 0 0 1 2-2h12.8a2 2 0 0 1 2 2z',
    profil: 'M12 12.3a4.1 4.1 0 1 0 0-8.2 4.1 4.1 0 0 0 0 8.2M4.6 20.4c.9-4 3.8-6.1 7.4-6.1s6.5 2.1 7.4 6.1',
    aide: 'M12 3.7a8.3 8.3 0 1 1 0 16.6 8.3 8.3 0 0 1 0-16.6M9.6 9.5a2.5 2.5 0 0 1 4.9.7c0 1.7-2.5 2-2.5 3.6M12 17.3h.01',
    // Une valise : le séjour du voyageur, pas le logement du propriétaire.
    sejours: 'M3.6 8.6h16.8v9.8a2 2 0 0 1-2 2H5.6a2 2 0 0 1-2-2zM8.7 8.6V5.9a1.6 1.6 0 0 1 1.6-1.6h3.4a1.6 1.6 0 0 1 1.6 1.6v2.7M3.6 13.2h16.8',
    favoris: 'M12 20.1 4.9 13a4.3 4.3 0 0 1 6.1-6.1l1 1 1-1A4.3 4.3 0 0 1 19.1 13z',
    // Une porte, et la flèche qui en sort : se déconnecter, c'est quitter.
    sortir: 'M9.6 4.6H6.2a2 2 0 0 0-2 2v10.8a2 2 0 0 0 2 2h3.4M14.8 16.4 19.2 12l-4.4-4.4M19.2 12H9.4',

    dot: 'M12 12h.01',
}
</script>

<template>
    <svg
        class="si"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.55"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path :d="ICONS[props.name] ?? ICONS.dot" />
    </svg>
</template>

<style scoped>
.si {
    flex: none;
    width: 1.2rem;
    height: 1.2rem;
}
</style>
