<script setup>
/**
 * « Entrer en un geste » — Google, Facebook, Apple.
 *
 * **Deux formes, selon le nombre de fournisseurs — et c'est le nombre qui
 * décide, pas nous.**
 *
 * À plusieurs : un **contrôle segmenté**. La pile de pilules pleine largeur
 * est ce que fait tout le monde, et elle dit trois décisions là où il n'y en a
 * qu'une — « avez-vous déjà un compte quelque part ? ». Une barre unique,
 * divisée par des filets, dit l'inverse : un objet, plusieurs issues, sur une
 * ligne au lieu de trois.
 *
 * **Seul : un bouton pleine largeur.** Une cellule isolée dans un contrôle
 * segmenté annonce un choix qui n'existe pas — on cherche les autres. Et à
 * un tiers de la largeur, elle se raterait au doigt là où le bouton principal
 * juste en dessous fait toute la largeur.
 *
 * **Le libellé complet revient alors** : « Continuer avec Google ». Dans la
 * barre, le nom seul suffit — les trois cellules se lisent ensemble et le
 * titre au-dessus porte le verbe. Isolé, le nom seul ne dit plus ce que le
 * clic fait.
 *
 * **« Continuer », pas « Se connecter ».** Notre porte est unique : la même
 * page inscrit et connecte, c'est le code qui décide. « Se connecter »
 * mentirait à qui n'a pas de compte, « S'inscrire » à qui en a un. C'est
 * aussi l'une des trois formulations que Google autorise.
 *
 * **Le logo au-dessus du mot, pas à côté.** À trois cellules côte à côte, un
 * logo aligné à gauche du texte réduit la marque à un détail ; empilé, il se
 * reconnaît avant qu'on ait lu. Et le mot reste — un pictogramme seul serait
 * un faux signal pour qui ne connaît pas ces marques.
 *
 * **La terre n'apparaît qu'au survol et au focus**, sous forme d'un trait qui
 * se déploie sous la cellule visée. C'est la couleur de l'action, et une
 * cellule au repos n'en est pas une. Un fond coloré aurait concurrencé le
 * bouton principal en dessous.
 *
 * **Ce sont des liens.** La destination est une vraie navigation vers le
 * fournisseur : un `<button>` piloté en JavaScript casserait le clic milieu,
 * le clavier et le retour arrière, pour rien.
 *
 * **Anti double-clic par verrouillage.** Une fois le départ engagé, les trois
 * cellules deviennent inertes et celle qu'on a choisie dit « Ouverture… ».
 * Deux allers-retours OAuth simultanés se marchent dessus sur le `state`, et
 * le second échoue.
 *
 * **Logos officiels uniquement**, aux couleurs de chaque marque — sauf Apple,
 * dont les règles imposent un logo monochrome.
 */
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'

import { useSocialMotion } from '@/Composables/useSocialMotion.js'

const props = defineProps({
    /** `voyageur` ou `proprietaire` : l'espace visé décide de la route. */
    espace: { type: String, default: 'voyageur' },
})

const page = usePage()
const racine = ref(null)
const engage = ref(null)

/*
 * **Google refuse de s'ouvrir dans le navigateur interne d'une application**
 * — Facebook, Messenger, Instagram : il affiche « disallowed_useragent » au
 * lieu de l'écran de connexion. Or c'est exactement là qu'arrive quelqu'un qui
 * clique sur une publicité. On retire donc Google de ces navigateurs ; l'adresse
 * et le code, eux, fonctionnent partout.
 */
const NAVIGATEUR_INTERNE = /FBAN|FBAV|FB_IAB|FBIOS|Messenger|Instagram/i
const dansUneApplication = typeof navigator !== 'undefined' && NAVIGATEUR_INTERNE.test(navigator.userAgent)

const fournisseurs = computed(() => (page.props.social ?? [])
    .filter((f) => ! (dansUneApplication && f.cle === 'google')))

/** Seul, il devient un vrai bouton ; à plusieurs, une barre segmentée. */
const seul = computed(() => fournisseurs.value.length === 1)

const lien = (cle) => (props.espace === 'proprietaire' ? `/proprietaire/auth/${cle}` : `/auth/${cle}`)

useSocialMotion(racine)
</script>

<template>
    <div v-if="fournisseurs.length" ref="racine" class="soc">
        <!-- Le sur-titre ne coiffe qu'un groupe : au-dessus d'un bouton
             unique qui dit déjà « Continuer avec Google », il répète. -->
        <p v-if="!seul" class="soc__titre">Entrer en un geste</p>

        <div class="soc__barre" :class="{ 'is-engage': engage, 'is-seul': seul }">
            <template v-for="(f, i) in fournisseurs" :key="f.cle">
                <span v-if="i" class="soc__filet" data-soc-filet aria-hidden="true"></span>

                <a
                    :href="lien(f.cle)"
                    class="soc__cell"
                    :class="{ 'is-choisi': engage === f.cle }"
                    :aria-disabled="engage ? 'true' : 'false'"
                    :aria-label="`Continuer avec ${f.label}`"
                    data-soc-cell
                    @click="engage = f.cle"
                >
                    <span class="soc__logo" data-soc-logo aria-hidden="true">
                        <svg v-if="f.cle === 'google'" viewBox="0 0 18 18">
                            <path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.91c1.7-1.57 2.69-3.88 2.69-6.62Z" />
                            <path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.91-2.26c-.81.54-1.84.86-3.05.86-2.35 0-4.34-1.58-5.05-3.71H.92v2.33A9 9 0 0 0 9 18Z" />
                            <path fill="#FBBC05" d="M3.95 10.71a5.4 5.4 0 0 1 0-3.42V4.96H.92a9 9 0 0 0 0 8.08l3.03-2.33Z" />
                            <path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.44 1.35l2.58-2.58C13.46.89 11.43 0 9 0A9 9 0 0 0 .92 4.96l3.03 2.33C4.66 5.16 6.65 3.58 9 3.58Z" />
                        </svg>
                        <svg v-else-if="f.cle === 'facebook'" viewBox="0 0 18 18">
                            <path fill="#1877F2" d="M18 9a9 9 0 1 0-10.41 8.89v-6.29H5.31V9h2.28V7.02c0-2.25 1.34-3.5 3.4-3.5.98 0 2.01.18 2.01.18v2.21h-1.13c-1.12 0-1.47.69-1.47 1.4V9h2.5l-.4 2.6h-2.1v6.29A9 9 0 0 0 18 9Z" />
                        </svg>
                        <svg v-else viewBox="0 0 18 18">
                            <path fill="currentColor" d="M13.02 9.55c.02 2.2 1.93 2.93 1.95 2.94-.01.05-.3 1.05-1.01 2.08-.61.89-1.25 1.78-2.25 1.8-.99.02-1.3-.59-2.43-.59-1.13 0-1.48.57-2.41.6-.97.04-1.71-.96-2.32-1.85-1.25-1.8-2.2-5.1-.92-7.33.64-1.1 1.78-1.8 3.01-1.82.95-.02 1.85.64 2.43.64.58 0 1.67-.79 2.82-.68.48.02 1.83.19 2.7 1.46-.07.04-1.61.94-1.59 2.8M11.2 3.24c.51-.62.86-1.48.76-2.34-.74.03-1.63.49-2.16 1.11-.47.55-.89 1.43-.78 2.27.82.07 1.66-.42 2.18-1.04" />
                        </svg>
                    </span>

                    <span class="soc__mot">{{
                        engage === f.cle
                            ? 'Ouverture…'
                            : (seul ? `Continuer avec ${f.label}` : f.label)
                    }}</span>
                    <span class="soc__trait" data-soc-trait aria-hidden="true"></span>
                </a>
            </template>
        </div>

        <!-- Le « ou » vit avec les boutons, pas dans chaque page : dans le
             navigateur de Facebook, où Google est retiré, il serait resté seul
             au-dessus du formulaire, séparant l'adresse de rien. -->
        <p class="acces__ou" aria-hidden="true"><span>ou</span></p>
    </div>
</template>

<style scoped>
.soc { display: grid; gap: .6rem; }
/* Le séparateur hérite de sa marge globale ; la grille ajoute déjà son écart. */
.soc .acces__ou { margin-top: .5rem; }

.soc__titre {
    margin: 0;
    font-size: .74rem;
    font-weight: 800;
    letter-spacing: .09em;
    text-transform: uppercase;
    text-align: center;
    color: var(--text-3);
}

/* Une seule barre, divisée : c'est ce qui dit « un objet, trois issues ». */
.soc__barre {
    display: flex;
    align-items: stretch;
    overflow: hidden;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--white);
    box-shadow: 0 1px 2px rgba(23, 20, 28, .04);
}

.soc__filet { width: 1px; background: var(--line-2); transform-origin: center; }

/* **Seul, c'est un bouton, pas une case.** Même hauteur et même rayon que le
   bouton principal en dessous, pour qu'ils se lisent comme deux chemins de
   même rang — mais **contour blanc, jamais terre** : la terre est la couleur
   de l'action principale, et deux boutons pleins se disputeraient le regard.
   C'est aussi le thème clair que recommande Google pour son bouton. */
.soc__barre.is-seul {
    border-radius: var(--r-pill);
}
.soc__barre.is-seul .soc__cell {
    grid-auto-flow: column;
    justify-content: center;
    align-items: center;
    gap: .7rem;
    min-height: 3.25rem;
    padding: .9rem 1.25rem;
}
.soc__barre.is-seul .soc__mot { font-size: .95rem; font-weight: 800; letter-spacing: -.01em; }
.soc__barre.is-seul .soc__logo { width: 1.25rem; height: 1.25rem; }
/* Le trait d'action épouse la pilule : d'un bord à l'autre, il déborderait
   des angles arrondis. */
.soc__barre.is-seul .soc__trait { left: 25%; right: 25%; bottom: .45rem; }

.soc__cell {
    position: relative;
    display: grid;
    justify-items: center;
    gap: .4rem;
    flex: 1 1 0;
    /* La cible tactile minimale, hauteur comprise : trois cellules côte à côte
       sur un téléphone sont déjà étroites, elles n'ont pas le droit d'être
       basses en plus. */
    min-width: 0;
    padding: .85rem .4rem .8rem;
    text-decoration: none;
    color: var(--ink);
    transition: background-color .3s var(--ease);
}
.soc__cell:hover,
.soc__cell:focus-visible { background: var(--off); }
.soc__cell:focus-visible { outline: 2px solid var(--terre-500); outline-offset: -2px; }

.soc__logo { display: grid; place-items: center; width: 1.4rem; height: 1.4rem; }
.soc__logo svg { width: 100%; height: 100%; }

.soc__mot {
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: -.01em;
    /* « Facebook » est le plus long : sur un écran étroit il se coupe plutôt
       que d'élargir sa cellule et de déséquilibrer les trois. */
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Le trait d'action, déployé par GSAP depuis le centre. Il est posé en CSS à
   l'échelle 0 : sans mouvement (préférence système), il reste simplement
   absent, et la cellule garde son fond au survol pour se signaler. */
.soc__trait {
    position: absolute;
    left: .5rem;
    right: .5rem;
    bottom: 0;
    height: 2px;
    border-radius: 2px;
    background: var(--terre-500);
    transform: scaleX(0);
}

/* Engagé : plus rien ne repart, et la cellule choisie garde son trait. */
.soc__barre.is-engage .soc__cell { pointer-events: none; color: var(--text-3); }
.soc__barre.is-engage .soc__cell.is-choisi { color: var(--ink); background: var(--terre-050); }
.soc__barre.is-engage .soc__cell.is-choisi .soc__trait { transform: scaleX(1); }
.soc__barre.is-engage .soc__logo { opacity: .45; }
.soc__barre.is-engage .soc__cell.is-choisi .soc__logo { opacity: 1; }

@media (prefers-reduced-motion: reduce) {
    .soc__cell:hover .soc__trait,
    .soc__cell:focus-visible .soc__trait { transform: scaleX(1); }
}
</style>
