<script setup>
/**
 * Les deux pictogrammes de l'aiguillage de connexion.
 *
 * **L'accent est plein, pas tracé.** À trois rem et demi, un accent au trait
 * se dilue dans le reste du dessin : on voit un pictogramme gris avec une
 * nuance, pas deux choses distinctes. Rempli, il devient le sujet — le manche
 * de la loupe *est* le geste de chercher, la clé *est* la possession. C'est le
 * même principe que le monogramme, dont la pointe pleine porte tout le sens.
 *
 * **Ils se lisent sans leur titre.** Les deux cartes ont porté des maisons :
 * à cinquante pixels, on ne distingue pas « la maison où je vais » de « la
 * maison que je loue ». La loupe dit *chercher*, la clé dit *posséder*, et
 * c'est l'accent de couleur qui porte précisément ce mot-là.
 *
 * **La terre, jamais le lagon.** Le lagon ne dit qu'une chose sur tout le
 * site — « vérifié » — et un pictogramme d'orientation n'est pas une
 * vérification.
 *
 * Grille de 40 : à cette taille d'affichage, une grille de 24 oblige à des
 * demi-pixels sur les arrondis et le trait devient mou. Les parties mobiles
 * sont dans des `<g>` nommés — `useAccessMotion` les anime au survol et au
 * focus, jamais au survol seul.
 */
defineProps({
    name: { type: String, required: true },
})
</script>

<template>
    <svg
        viewBox="0 0 40 40"
        fill="none"
        stroke="currentColor"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        focusable="false"
    >
        <!-- Chercher : une loupe posée sur un toit. Le manche, plein et en
             terre, est le geste — c'est lui qu'on anime. -->
        <template v-if="name === 'chercher'">
            <g data-ico-move>
                <circle cx="16.5" cy="16.5" r="11.2" stroke-width="2" />
                <path d="M10.6 17.9 16.5 12.5l5.9 5.4" stroke-width="1.9" />
                <path d="M12.4 17.2v5.4h8.2v-5.4" stroke-width="1.9" />
                <path class="ai__accent ai__handle" d="m24.9 24.9 8.6 8.6" />
            </g>
        </template>

        <!-- Louer : une maison dont la serrure porte une clé. La clé, pleine
             et en terre, tourne au survol — ouvrir chez soi. -->
        <template v-else>
            <path d="M4 18.4 20 6l16 12.4" stroke-width="2" />
            <path d="M7.6 15.7v16.1a2.2 2.2 0 0 0 2.2 2.2h20.4a2.2 2.2 0 0 0 2.2-2.2V15.7" stroke-width="2" />

            <!-- Le pivot est le centre de l'anneau : une clé qui tournerait
                 autour de son panneton ne tournerait pas dans une serrure.
                 GSAP l'impose par `svgOrigin` ; la règle CSS ci-dessous n'est
                 là que pour le cas où le mouvement est coupé. -->
            <g class="ai__turn" data-ico-turn>
                <circle class="ai__fill" cx="20" cy="21.4" r="3.9" />
                <circle class="ai__hole" cx="20" cy="21.4" r="1.5" />
                <path class="ai__fill" d="M18.6 24.9h2.8v8.2h-2.8z" />
                <path class="ai__fill" d="M21.4 27.4h3.4v2.1h-3.4zM21.4 30.4h2.6v2h-2.6z" />
            </g>
        </template>
    </svg>
</template>

<style scoped>
/* `transform-box: view-box` : sans lui, un `<g>` SVG pivote autour de
   l'origine du dessin et non du point demandé — la clé sortait de la maison.
   Sa valeur par défaut a changé selon les navigateurs, on la pose donc. */
.ai__turn { transform-box: view-box; transform-origin: 20px 21.4px; }

.ai__accent { stroke: var(--icon-accent, var(--terre-500)); }
/* Épais et plein : à cette taille, un manche au trait fin se lirait comme un
   détail du dessin, pas comme le geste. */
.ai__handle { stroke-width: 4.4; }
.ai__fill { fill: var(--icon-accent, var(--terre-500)); stroke: none; }
/* Le trou de l'anneau reprend le fond de la pastille, pas du blanc en dur :
   la pastille change de teinte au survol, et un blanc figé s'y verrait. */
.ai__hole { fill: var(--icon-hole, #fff); stroke: none; }
</style>
