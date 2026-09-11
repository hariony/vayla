<script setup>
/**
 * Les six pictogrammes des raccourcis de la fiche — dessinés pour elle.
 *
 * **Chacun dit ce que la section contient *sur Vayla*, pas ce qu'elle
 * contient ailleurs.** Un appareil photo, un éclair nu, une bulle de dialogue
 * et une grille de quatre carrés auraient pu venir de n'importe quelle
 * bibliothèque ; ce sont exactement les formes qu'on ne remarque plus.
 *
 * - **Photos** — un cadre, un relief, un soleil : une photographie de
 *   voyage, pas l'outil qui la prend.
 * - **Énergie** — l'éclair et ses étincelles : ce qui tient quand la ville
 *   lâche.
 * - **Vérification** — un écusson, et dedans **la coche du monogramme** :
 *   le V de Vayla est lui-même une coche, c'est la section qui en parle.
 * - **Avis** — une liste cochée, **pas une bulle ni une étoile** : Vayla n'a
 *   pas de note sur cinq, les voyageurs y cochent des faits. Le pictogramme
 *   dit ce que la section est.
 * - **Équipements** — quatre cases et un pion : un inventaire, où l'on pointe.
 * - **Calendrier** — une page de mois **traversée par une plage** : sur la
 *   fiche on ne choisit pas un jour, on pose une arrivée et un départ.
 *
 * **Bicolores, comme le monogramme et les pictogrammes de `/connexion`** :
 * le corps à l'encre (`currentColor`), l'accent à la terre
 * (`--glyph-accent`). C'est l'accent qui porte le mot distinctif, et c'est
 * lui qui bouge — `useGlyphMotion` ne touche qu'aux pièces marquées
 * `data-part`. Le lagon n'y entre jamais, pas même sur *Vérification* : un
 * pictogramme d'orientation n'est pas une vérification.
 *
 * Grille de 24, trait de 1,6 : la même que `SpaceIcon` et `AmenityIcon`, pour
 * que la fiche garde un seul vocabulaire graphique.
 */
defineProps({
    cle: { type: String, required: true },
})
</script>

<template>
    <svg
        class="gl"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.6"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        :data-glyph="cle"
    >
        <!-- Photos : le soleil se lève derrière le relief. -->
        <template v-if="cle === 'photos'">
            <rect x="3.4" y="4.8" width="17.2" height="14.4" rx="2.8" />
            <path d="M3.9 16.9 8.7 12l3.5 3.5 2.3-2.3 5.6 5.6" />
            <circle class="gl__acc gl__acc--plein" cx="15.9" cy="9.3" r="1.75" data-part="soleil" />
        </template>

        <!-- Énergie : l'éclair clignote, les étincelles jaillissent. -->
        <template v-else-if="cle === 'energie'">
            <path class="gl__acc gl__acc--plein" d="M13.4 2.9 5.9 13.1h5.3l-.8 8 7.7-10.5h-5.3z" data-part="eclair" />
            <path class="gl__acc" d="M19.4 4.6l1.5-1.2M20.3 8.4h1.9M3.1 18.4l-1.3 1" data-part="etincelles" />
        </template>

        <!-- Vérification : la coche du monogramme se trace dans l'écusson. -->
        <template v-else-if="cle === 'verification'">
            <path d="M12 3.1 5 5.8v5.5c0 4.2 2.9 7.8 7 9.5 4.1-1.7 7-5.3 7-9.5V5.8z" />
            <path class="gl__acc" stroke-width="2.1" d="M8.5 11.6l2.5 2.7 4.7-5.4" data-part="coche" />
        </template>

        <!-- Avis : les faits se cochent un à un. -->
        <template v-else-if="cle === 'avis'">
            <rect x="4.2" y="3.2" width="15.6" height="17.6" rx="2.8" />
            <path d="M11.2 8.4h5.3M11.2 12.4h5.3M11.2 16.4h3.4" />
            <path class="gl__acc" stroke-width="1.9" d="M7.1 8.3l.9.9 1.6-1.9" data-part="tic" />
            <path class="gl__acc" stroke-width="1.9" d="M7.1 12.3l.9.9 1.6-1.9" data-part="tic" />
            <circle cx="8.3" cy="16.4" r=".9" />
        </template>

        <!-- Équipements : le pion saute d'une case à l'autre. -->
        <template v-else-if="cle === 'equipements'">
            <rect x="3.8" y="3.8" width="7.2" height="7.2" rx="2" />
            <rect x="13" y="3.8" width="7.2" height="7.2" rx="2" />
            <rect x="3.8" y="13" width="7.2" height="7.2" rx="2" />
            <rect x="13" y="13" width="7.2" height="7.2" rx="2" />
            <rect class="gl__acc gl__acc--plein" x="5.4" y="5.4" width="4" height="4" rx="1.1" data-part="pion" />
        </template>

        <!-- Calendrier : la plage du séjour se pose, de l'arrivée au départ. -->
        <template v-else-if="cle === 'calendrier'">
            <rect x="3.4" y="5.2" width="17.2" height="15.4" rx="2.8" />
            <path d="M8 3.2v4M16 3.2v4M3.4 9.9h17.2" />
            <rect class="gl__acc gl__acc--plein" x="6.4" y="13.2" width="11.2" height="3.2" rx="1.6" data-part="plage" />
        </template>

        <circle v-else cx="12" cy="12" r="1.2" />
    </svg>
</template>

<style scoped>
.gl {
    flex: none;
    width: 1.4rem;
    height: 1.4rem;
    overflow: visible;
}

/* L'accent : un trait de terre, ou un aplat pour les pièces pleines. */
.gl__acc { stroke: var(--glyph-accent, var(--terre-500)); }
.gl__acc--plein { fill: var(--glyph-accent, var(--terre-500)); stroke: none; }

/* Le pion et la plage bougent par transformations SVG : leur origine doit
   être celle du dessin, pas celle d'une boîte que chaque moteur calcule à sa
   façon — la leçon de la clé de `/connexion`, qui partait hors de la maison. */
[data-part] { transform-box: fill-box; }
</style>
