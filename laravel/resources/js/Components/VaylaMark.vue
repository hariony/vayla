<script setup>
/**
 * Monogramme Vayla.
 *
 * Un V qui est aussi une coche : la lettre du nom et le signe de la
 * vérification sont la même forme. Les deux bras sont assez équilibrés
 * pour qu'on lise un V, le bras droit monte plus haut pour qu'on lise
 * une coche. C'est tout le produit en un glyphe.
 *
 * Tracé calculé, pas dessiné à vue : bande à largeur variable (fine au
 * départ, épaisse au sommet, fine à la pointe), bras long légèrement
 * cambré, coupes d'extrémité inclinées comme une plume. Les bords sont
 * ajustés par deux quadratiques — 98 caractères de chemin au lieu de
 * 2 300 pour la version échantillonnée point par point.
 *
 * La pointe passe au lagon : la marque s'achève dans la couleur qui,
 * partout ailleurs sur le site, ne dit qu'une chose — « vérifié ».
 * Sur un fond coloré, redéfinir --mark-accent.
 */
</script>

<template>
    <svg viewBox="0 0 48 48" fill="none" aria-hidden="true" focusable="false">
        <path class="vm__body" d="M2.18,4.81 L19.79,45.70 Q38.12,20.37 45.82,2.30 L38.58,4.12 Q27.42,20.47 21.66,26.12 L9.01,7.47 Z" fill="currentColor" />
        <path class="vm__tip" d="M38.91,15.91 Q41.29,11.40 45.82,2.30 L38.58,4.12 Q37.07,6.03 32.88,12.19 Z" />
    </svg>
</template>

<style scoped>
.vm__tip { fill: var(--mark-accent, var(--lagon-500)); }
</style>
