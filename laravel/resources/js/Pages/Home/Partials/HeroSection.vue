<script setup>
/**
 * Le hero : l'accroche, les trois chiffres, et le moteur qui ferme la
 * section en débordant sur la grille — on doit voir des logements sans
 * avoir à défiler.
 *
 * Les deux premiers chiffres sont dérivés des données reçues : ils ne
 * peuvent pas mentir le jour où l'on ouvre une destination de plus.
 */
import { ref } from 'vue'

import HeroBackdrop from '@/Components/HeroBackdrop.vue'
import CanopyLayer from '@/Components/CanopyLayer.vue'
import SearchPanel from '@/Components/SearchPanel.vue'

defineProps({
    destinations: { type: Array, default: () => [] },
    trustLevels: { type: Array, default: () => [] },
    recherche: { type: Object, required: true },
})

const searchAnchor = ref(null)

// L'en-tete compact renvoie ici : le moteur du hero reste la source unique.
const focusSearch = () => {
    searchAnchor.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    searchAnchor.value?.querySelector('select')?.focus({ preventScroll: true })
}

defineExpose({ focusSearch })
</script>

<template>
    <!-- ═══════════ HERO ═══════════ -->
    <section class="hero" data-hero>
        <HeroBackdrop />
        <CanopyLayer class="hero__canopy" />

        <div class="shell hero__inner">
            <!-- Le titre dit désormais le produit et la promesse ; le
                 sur-titre sert donc à dire où. Construit depuis les
                 destinations : il s'allonge tout seul quand on ouvre
                 une région, aucun risque qu'il mente. -->
            <p class="eyebrow hero__brow" data-hero-item>
                {{ destinations.map((d) => d.name).join(' · ') }}
            </p>

            <h1 class="display display--xl hero__title">
                <span class="hero__line" data-hero-line>Location de villas et appartements meublés.</span>
                <span class="hero__line hero__line--minor" data-hero-line><span class="uline"><i class="uline__bar" data-underline aria-hidden="true"></i><span class="uline__txt">Vérifiés avant vous.</span></span></span>
            </h1>

            <p class="lede hero__lede" data-hero-item>
                Chaque logement affiche jusqu'où nous sommes allés pour le vérifier.
                Déclaré, contact confirmé, visité en visio, séjour confirmé — c'est
                écrit sur l'annonce, avant le prix.
            </p>

            <ul class="hero__stats" data-hero-item>
                <li class="hero__stat">
                    <span class="hero__stat-n num" :data-count="trustLevels.length">{{ trustLevels.length }}</span>
                    <span class="hero__stat-l">niveaux de vérification</span>
                </li>
                <li class="hero__stat">
                    <span class="hero__stat-n num" :data-count="destinations.length">{{ destinations.length }}</span>
                    <span class="hero__stat-l">destinations ouvertes</span>
                </li>
                <li class="hero__stat">
                    <span class="hero__stat-n">Ar</span>
                    <span class="hero__stat-l">prix en ariary, sans conversion</span>
                </li>
            </ul>
        </div>

        <!-- Le moteur ferme le hero et déborde sur la grille -->
        <div ref="searchAnchor" class="shell hero__search" data-hero-search>
            <SearchPanel
                :destinations="destinations"
                :recherche="recherche"
            />
        </div>
    </section>
</template>

<style scoped>
/* ═══════════ HERO ═══════════ */

.hero {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    gap: clamp(2rem, 5vw, 3.5rem);
    min-height: clamp(600px, 86vh, 820px);
    padding-top: calc(var(--header-h) + clamp(2.5rem, 7vw, 5rem));
    padding-bottom: clamp(1.5rem, 3vw, 2.5rem);
    isolation: isolate;
}

/* Sur blanc, la canopée hurlait : on la recule et on l'efface du côté
   du texte. Elle n'existe plus que comme atmosphère en haut à droite. */
.hero__canopy {
    z-index: 0;
    opacity: .5;
    mask-image: linear-gradient(102deg, transparent 0%, transparent 34%, #000 68%);
    -webkit-mask-image: linear-gradient(102deg, transparent 0%, transparent 34%, #000 68%);
}

.hero__inner {
    position: relative;
    z-index: 2;
    max-width: 1400px;
}

.hero__brow { margin: 0 0 1.5rem; }

/* Onze destinations débordaient sur trois lignes en petit écran :
   on resserre le corps et l'interlettrage plutôt que de tronquer
   la liste, qui est précisément l'information. */
@media (max-width: 620px) {
    .hero__brow {
        font-size: .62rem;
        letter-spacing: .08em;
        line-height: 1.5;
        margin-bottom: 1.1rem;
    }
}

/* Le titre se casse volontairement en deux rangées (text-wrap: balance
   les équilibre) : la largeur cadre la coupe, la troisième rangée reste
   la promesse surlignée. */
.hero__title {
    max-width: 24ch;
    margin: 0;
}

/* Chaque ligne monte de son propre masque : l'entrée est typographique */
.hero__line {
    display: block;
    overflow: hidden;
    padding-block: .04em;
}

/* La promesse passe deux crans sous le nom du produit : plus petite et
   nettement plus fine (500 contre 800). En em, donc elle suit le clamp du
   titre à toutes les largeurs. Le crénage se relâche un peu : le -.045em
   du titre est calé pour du gras, il étouffe un caractère plus léger. */
.hero__line--minor {
    font-size: .72em;
    font-weight: 500;
    letter-spacing: -.028em;
    margin-top: .1em;
}

.hero__lede {
    max-width: 46ch;
    margin: 1.6rem 0 0;
}

.hero__stats {
    display: flex;
    flex-wrap: wrap;
    gap: 1.25rem 2.5rem;
    margin: 2.2rem 0 0;
    padding: 0;
    list-style: none;
}

.hero__stat {
    display: flex;
    align-items: baseline;
    gap: .55rem;
    max-width: 20ch;
}

.hero__stat-n {
    font-size: 1.75rem;
    font-weight: 800;
    letter-spacing: -.05em;
    line-height: 1;
    color: var(--terre-500);
}

.hero__stat-l {
    font-size: .84rem;
    font-weight: 500;
    line-height: 1.35;
    color: var(--text-2);
}

.hero__search {
    position: relative;
    z-index: 3;
}

/* Le titre traverse toute la largeur ; le texte de soutien se range
   dessous, en deux colonnes, comme le chapô d'une double page. */
@media (min-width: 1100px) {
    .hero__inner {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, .74fr);
        gap: 0 clamp(3rem, 6vw, 6rem);
        align-items: start;
    }
    .hero__brow  { grid-column: 1 / -1; }
    .hero__title { grid-column: 1 / -1; }
    .hero__lede  { grid-column: 1; margin-top: 2.4rem; max-width: 42ch; }
    .hero__stats { grid-column: 2; margin-top: 2.4rem; gap: 1.6rem 2.5rem; }
    /* 17ch : « destinations ouvertes » tient sur une ligne même quand le
       compteur passe à deux chiffres. */
    .hero__stat  { max-width: 17ch; }
}
</style>
