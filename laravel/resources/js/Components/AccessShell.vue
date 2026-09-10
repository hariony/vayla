<script setup>
/**
 * Le cadre commun à tous les écrans d'accès — connexion, inscription, code.
 *
 * **C'est l'architecture de `/connexion`, et elle vaut pour toute la famille.**
 * Chaque écran portait auparavant sa propre carte blanche posée sur un aplat
 * gris : sept fois le même fond, sept fois la même barre de marque, sept jeux
 * de classes aux préfixes différents (`.rg__`, `.lg__`, `.cd__`…). Ils avaient
 * déjà commencé à diverger — deux rayons de bordure, trois tailles de titre —
 * et chaque correction demandait sept passages. Le cadre est ici, une fois.
 *
 * Ce qu'il porte, et pourquoi :
 *
 * - **Le fond n'est pas un aplat** : une lueur de latérite et un grain très
 *   fin. La règle « une seule dalle de couleur pleine par page » tient parce
 *   qu'il n'y a pas de dalle — il y a de l'air. Sans le grain, les deux
 *   dégradés se voient comme des taches sur un écran de bureau.
 * - **Le V du monogramme se trace en fond.** Le même geste que le littoral de
 *   Madagascar sur l'accueil : c'est ce qui fait de ces écrans des pages de
 *   Vayla plutôt que des formulaires de connexion interchangeables.
 * - **Une sortie, et elle revient d'un pas.** Elle ramenait à l'accueil ; sur
 *   un écran atteint depuis l'aiguillage, c'était deux pas en arrière au lieu
 *   d'un, et il fallait refaire le choix qu'on venait de faire.
 * - **C'est le parent déclaré (`retour`), pas `history.back()`.** La version
 *   par l'historique a été essayée et jetée : `history.length > 1` compte la
 *   page « nouvel onglet », si bien qu'un onglet ouvert sur un lien WhatsApp
 *   — le cas majoritaire chez nos propriétaires — sortait du site au premier
 *   clic. On ne peut pas lire les entrées d'historique pour savoir si la
 *   précédente est chez nous ; un parent déclaré, lui, est toujours juste.
 *   Chaque écran d'accès n'a de toute façon qu'un seul pas en amont.
 * - **Elle ne dit donc plus « vayla ».** Un monogramme qui ne ramène pas à
 *   l'accueil est un faux signal — la convention est trop installée pour
 *   qu'on la retourne en silence, et notre public est précisément celui qui
 *   la subit. Le mot est « Retour », la marque reste comme ancrage.
 * - **Ni en-tête ni pied de page du site.** Une page qui demande « qui
 *   êtes-vous ? » ou un mot de passe ne peut pas rouvrir au même instant le
 *   catalogue et « Devenir hôte » : ce sont les chemins qu'elle vient de
 *   refermer pour poser sa question.
 * - **Tout est sur un axe.** Un alignement à gauche crée un ordre de lecture,
 *   donc une hiérarchie — inutile là où il n'y a qu'une chose à faire.
 *
 * **Le cadre ne porte pas l'invite Google.** Elle connecte en un clic, et en
 * tant que **voyageur** : sur l'aiguillage, elle trancherait la question que
 * la page pose — client ou propriétaire — avant qu'on ait choisi ; sur les
 * écrans du propriétaire, elle ouvrirait carrément la mauvaise garde. Elle est
 * donc montée par les deux portes voyageur, et par elles seules.
 *
 * `sortie: false` retire le lien d'accueil : sur l'écran du code, quitter coûte
 * l'inscription entière, qui vit en session. Sa sortie à lui est « Corriger »,
 * qui revient au formulaire sans rien perdre.
 */
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

import VaylaMark from '@/Components/VaylaMark.vue'
import { useAccessMotion } from '@/Composables/useAccessMotion.js'

const props = defineProps({
    titrePage: { type: String, required: true },
    eyebrow: { type: String, default: '' },
    titre: { type: String, required: true },
    lede: { type: String, default: '' },
    /** Largeur de la colonne : `etroit` pour un formulaire, `large` pour l'aiguillage. */
    largeur: { type: String, default: 'etroit' },
    /** L'écran du code n'offre pas de sortie — voir plus haut. */
    sortie: { type: Boolean, default: true },
    /** Le pas en amont de cet écran. L'aiguillage, lui, remonte à l'accueil. */
    retour: { type: String, default: '/' },
})

const racine = ref(null)
useAccessMotion(racine)

const colonne = computed(() => (props.largeur === 'large' ? '58rem' : '30rem'))

/**
 * Le contour du monogramme, tracé et non rempli : c'est le même chemin que
 * `VaylaMark`, emprunté ici comme dessin de fond. Le dupliquer plutôt que
 * d'importer le composant permet de le tracer — un `<path>` rempli n'a pas de
 * longueur à parcourir.
 */
const MARQUE = 'M2.18,4.81 L19.79,45.70 Q38.12,20.37 45.82,2.30 L38.58,4.12 '
    + 'Q27.42,20.47 21.66,26.12 L9.01,7.47 Z'
</script>

<template>
    <Head :title="titrePage" />

    <main ref="racine" class="as" :style="{ '--as-col': colonne }">
        <!-- Le décor est hors du flux et masqué aux lecteurs d'écran : c'est de
             la matière, pas du contenu. -->
        <div class="as__decor" aria-hidden="true">
            <svg class="as__mark" viewBox="0 0 48 48" fill="none" preserveAspectRatio="xMidYMid meet">
                <path
                    :d="MARQUE"
                    data-access-mark
                    stroke="currentColor"
                    stroke-width=".85"
                    stroke-linejoin="round"
                />
            </svg>
        </div>

        <div class="shell as__in">
            <Link
                v-if="sortie"
                :href="retour"
                class="as__home"
                aria-label="Revenir à l'étape précédente"
                data-access-line
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M15 5.5 8.5 12l6.5 6.5" />
                </svg>
                <VaylaMark class="as__home-mark" />
                <span class="as__home-word">Retour</span>
            </Link>

            <!-- Sans sortie, la marque reste — mais inerte : elle situe la page
                 sans proposer de la quitter. -->
            <span v-else class="as__home as__home--inerte" data-access-line>
                <VaylaMark class="as__home-mark" />
                <span class="as__home-word">vayla</span>
            </span>

            <header class="as__head">
                <p v-if="eyebrow" class="eyebrow" data-access-line>{{ eyebrow }}</p>
                <h1 class="as__title" data-access-line>{{ titre }}</h1>
                <p v-if="lede" class="as__lede" data-access-line>{{ lede }}</p>
            </header>

            <slot />

            <p v-if="$slots.pied" class="as__foot" data-access-line>
                <slot name="pied" />
            </p>
        </div>
    </main>
</template>

<style scoped>
/* **Centré tant que ça tient, poussé en haut sinon.** `align-items: center`
   avec `overflow: hidden` rognait le haut de la page dès que le contenu
   dépassait la hauteur — et ce qui disparaissait en premier était la sortie
   vers l'accueil, sans aucun moyen de la faire revenir. Une marge automatique
   fait le même centrage et retombe à zéro quand la place manque.

   `overflow-x: clip` et non `overflow: hidden` : `hidden` fait du bloc un
   conteneur de défilement et enferme aussi le débord vertical, alors que seul
   le décor a besoin d'être coupé, et seulement sur les côtés. */
.as {
    position: relative;
    display: flex;
    min-height: 100vh;
    overflow-x: clip;
    padding-block: clamp(2rem, 6vw, 4rem) clamp(3rem, 8vw, 5rem);
    background:
        radial-gradient(120% 80% at 8% 0%, var(--terre-050) 0%, transparent 55%),
        radial-gradient(90% 70% at 100% 100%, var(--off-2) 0%, transparent 60%),
        var(--white);
}

/* Un grain très fin : sans lui, les deux dégradés se voient comme des taches
   sur un écran de bureau. Avec, ils deviennent de la matière. */
.as::after {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    opacity: .5;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3'/%3E%3CfeColorMatrix type='saturate' values='0'/%3E%3C/filter%3E%3Crect width='140' height='140' filter='url(%23n)' opacity='.055'/%3E%3C/svg%3E");
}

.as__decor {
    position: absolute;
    inset: 0;
    pointer-events: none;
    color: var(--terre-500);
}

.as__mark {
    position: absolute;
    top: 50%;
    left: 50%;
    width: min(74vh, 46rem);
    height: min(74vh, 46rem);
    transform: translate(-50%, -54%);
    opacity: .07;
}

/* Sous 900 px le contenu occupe toute la hauteur : la marque passerait
   derrière le texte, où elle le brouillerait au lieu de le porter. */
@media (max-width: 900px) {
    .as__mark { display: none; }
}

.as__in {
    position: relative;
    width: 100%;
    margin-block: auto;
}

/* La sortie est sur l'axe, en haut de la pile. Posée dans un angle, elle
   serait le seul élément hors du centre et prendrait l'œil avant la question.
   Bordée et pleine parce que c'est un contrôle : la règle « pas de bouton sans
   contour ni fond » vaut d'autant plus que c'est le seul de la page. */
.as__home {
    display: flex;
    align-items: center;
    gap: .45rem;
    width: max-content;
    margin: 0 auto clamp(2rem, 4.5vw, 3rem);
    padding: .5rem 1.1rem .5rem .8rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: rgba(255, 255, 255, .78);
    backdrop-filter: blur(14px);
    color: var(--ink);
    text-decoration: none;
    box-shadow: 0 2px 18px -14px rgba(23, 20, 28, .5);
    transition:
        border-color .35s var(--ease),
        background-color .35s var(--ease),
        box-shadow .35s var(--ease);
}
.as__home:hover,
.as__home:focus-visible {
    border-color: var(--terre-300);
    background: var(--white);
    box-shadow: 0 14px 34px -24px rgba(201, 69, 42, .4);
}
.as__home:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 3px; }

/* Inerte : ni bordure ni fond, pour ne pas annoncer une action qui n'existe
   pas. Un faux signal serait pire que pas de signal. */
.as__home--inerte {
    padding-inline: 0;
    border-color: transparent;
    background: none;
    backdrop-filter: none;
    box-shadow: none;
}

.as__home svg {
    width: 1.05rem;
    height: 1.05rem;
    color: var(--text-3);
    transition: transform .35s var(--ease), color .35s var(--ease);
}
.as__home:hover svg,
.as__home:focus-visible svg { transform: translateX(-3px); color: var(--terre-600); }

.as__home-mark { width: 1.35rem; height: 1.35rem; color: var(--terre-500); }
.as__home-word { font-size: 1.05rem; font-weight: 800; letter-spacing: -.05em; }

.as__head {
    max-width: 44rem;
    margin: 0 auto clamp(1.75rem, 4vw, 2.5rem);
    text-align: center;
}
.as__title {
    margin: .35rem 0 0;
    font-size: clamp(1.75rem, 4vw, 2.6rem);
    font-weight: 800;
    letter-spacing: -.045em;
    line-height: 1.06;
    color: var(--ink);
}
.as__lede {
    margin: .9rem auto 0;
    max-width: 34rem;
    font-size: 1rem;
    line-height: 1.6;
    color: var(--text-2);
}

.as__foot {
    margin: clamp(1.75rem, 3.5vw, 2.5rem) auto 0;
    max-width: var(--as-col);
    font-size: .92rem;
    text-align: center;
    color: var(--text-3);
}

/* Le décor coûte du calcul pour rien quand le mouvement est refusé. */
@media (prefers-reduced-motion: reduce) {
    .as::after { display: none; }
}
</style>
