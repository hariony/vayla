<script setup>
/**
 * Les raccourcis flottants de la fiche — six pictogrammes légendés, à gauche.
 *
 * **Une fiche de logement fait trois mille pixels**, et on n'y lit pas tout
 * dans l'ordre. Celui qui cherche « y a-t-il un groupe électrogène » ou
 * « quelqu'un y a-t-il vraiment dormi » n'a aucune raison de traverser la
 * description pour y arriver.
 *
 * **Les libellés sont écrits, toujours, sous chaque pictogramme.** Une
 * première version ne les déployait qu'au survol : c'était la faute des
 * chevrons gris de 16 px du calendrier, sous une forme plus jolie — un bouclier
 * ou un éclair ne disent pas « Vérification » ou « Énergie » à quelqu'un qui
 * découvre, et au doigt il n'y a pas de survol. Le pictogramme se reconnaît,
 * le mot s'assure.
 *
 * **Un seul objet, deux ancrages** — c'est la seule chose que `variante`
 * décide :
 *
 * - `flottant` : **collé au défilement et toujours visible**, dans un
 *   **couloir réservé** à gauche du contenu (`--couloir`, posé par la fiche).
 *   Il ne remonte pas hors de l'écran avec la page — un raccourci qui
 *   disparaît au premier écran n'est utile qu'une fois — mais **il s'arrête
 *   avec la fiche**, au-dessus du pied de page ; et il ne recouvre jamais une
 *   ligne : la fiche lui laisse sa place.
 * - `photo` : une rangée posée **au bas de la photographie**, sous 1180 px,
 *   où la fenêtre n'a plus de marge à offrir. Horizontale, parce que six
 *   pictogrammes légendés empilés feraient 290 px — la hauteur de la photo
 *   entière sur un téléphone.
 *
 * **Chaque pictogramme a son geste** (`useGlyphMotion`), joué au survol, au
 * focus **et à l'arrivée dans la section** : c'est l'arrivée qui le montre au
 * doigt, et c'est ce qui apprend l'icône — on la voit bouger au moment où l'on
 * atteint ce qu'elle désigne.
 *
 * **La terre dit « vous êtes là »**, sans remplir la pastille : fond de terre
 * très pâle, filet de terre, légende en terre foncée. Une pastille entièrement
 * terre aurait avalé le pictogramme bicolore — et son geste avec.
 */
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import gsap from 'gsap'

import SectionGlyph from '@/Components/SectionGlyph.vue'
import { jouerGlyphe } from '@/Composables/useGlyphMotion.js'

const props = defineProps({
    /** `[{ id, label, icone }]`, dans l'ordre de la page. */
    sections: { type: Array, required: true },
    /** L'état partagé du repérage : `{ actif, progression, aller }`. */
    pilote: { type: Object, required: true },
    /** `flottant` (couloir fixe) ou `photo` (rangée sur l'image). */
    variante: { type: String, default: 'flottant' },
})

const surLaPhoto = computed(() => props.variante === 'photo')

/**
 * Sur la photographie, la rubrique « Photos » n'y figure pas : un raccourci
 * vers l'endroit où l'on se trouve déjà est un bouton qui ne fait rien — le
 * pire des faux signaux.
 */
const raccourcis = computed(() => (surLaPhoto.value
    ? props.sections.filter((s) => s.id !== 'photos')
    : props.sections))

const racine = ref(null)
let ctx

/** Le bouton d'une rubrique, pour y jouer son geste. */
const bouton = (id) => racine.value?.querySelector(`[data-dock="${id}"]`)

/**
 * **L'entrée présente les pictogrammes un par un** : les pastilles arrivent
 * en cascade, puis chaque geste se joue à la suite. C'est le seul moment où
 * tous bougent — il apprend en une seconde ce que chacun fait, et rien ne
 * bouge plus ensuite tant qu'on ne le demande pas.
 */
onMounted(() => {
    ctx = gsap.context((self) => {
        const tuiles = self.selector('[data-dock]')
        const mm = gsap.matchMedia()

        mm.add('(prefers-reduced-motion: reduce)', () => {
            gsap.set(tuiles, { opacity: 1, x: 0, y: 0 })
        })

        mm.add('(prefers-reduced-motion: no-preference)', () => {
            gsap.from(tuiles, {
                ...(surLaPhoto.value ? { y: 10 } : { x: -14 }),
                opacity: 0,
                duration: .45,
                ease: 'power3.out',
                stagger: .05,
                delay: .25,
                onComplete: () => tuiles.forEach((t, i) => gsap.delayedCall(i * .09, () => jouerGlyphe(t))),
            })
        })
    }, racine.value)
})

onUnmounted(() => ctx?.revert())

// L'arrivée dans une section joue son geste : c'est ce qui le montre au doigt.
watch(() => props.pilote.actif.value, (id, avant) => {
    if (id && id !== avant) {
        jouerGlyphe(bouton(id))
    }
})
</script>

<template>
    <nav
        ref="racine"
        class="dock"
        :class="surLaPhoto ? 'dock--photo' : 'dock--flottant'"
        aria-label="Aller à une section de la fiche"
    >
        <!-- Le fil : il se remplit à mesure qu'on descend. Il longe la colonne
             au lieu de la traverser — passé au centre des pastilles, il aurait
             barré les légendes. Sur la photographie il n'apparaît pas : un
             trait posé sur une image n'est plus un repère, c'est une rayure. -->
        <span v-if="!surLaPhoto" class="dock__fil" aria-hidden="true">
            <span
                class="dock__fil-plein"
                :style="{ transform: `scaleY(${pilote.progression.value})` }"
            ></span>
        </span>

        <button
            v-for="s in raccourcis"
            :key="s.id"
            type="button"
            class="dock__tuile"
            :class="{ 'is-on': s.id === pilote.actif.value }"
            :aria-current="s.id === pilote.actif.value ? 'true' : undefined"
            :data-dock="s.id"
            @click="pilote.aller(s.id)"
            @mouseenter="jouerGlyphe($event.currentTarget)"
            @focus="jouerGlyphe($event.currentTarget)"
        >
            <span class="dock__pastille">
                <SectionGlyph :cle="s.icone" />
            </span>
            <span class="dock__legende">{{ s.label }}</span>
        </button>
    </nav>
</template>

<style scoped>
.dock { z-index: 30; }

/* ── Le couloir ──────────────────────────────────────────────────────
   **Collé sous l'en-tête, arrêté au-dessus du pied de page.** `sticky` dans
   la bande `.fiche__couloir`, qui a la hauteur de la fiche : il suit le
   défilement tant qu'il y a de la fiche à côté de lui, et remonte avec elle
   quand elle se termine. En `position: fixed`, il finissait posé sur le pied
   de page — une butée qu'un élément fixé à la fenêtre ne peut pas connaître.
   Il colle à la même hauteur que l'encart de réservation, de l'autre côté :
   les deux colonnes qui suivent le lecteur partent de la même ligne. */
.dock--flottant {
    position: sticky;
    top: calc(var(--header-h) + 1.5rem);
    /* 6 rem : la légende la plus longue, « Vérification », tient en 82 px
       sans déborder sur le fil. */
    width: 6rem;
    display: grid;
    gap: .75rem;
    justify-items: center;
    padding-left: .9rem;
}

@media (max-width: 1179px) {
    .dock--flottant { display: none; }
}

/* Sur un écran bas, la colonne se resserre au lieu de déborder : six
   pastilles légendées tiennent dans 560 px à ce pas. */
@media (min-width: 1180px) and (max-height: 700px) {
    .dock--flottant { gap: .35rem; }
    .dock--flottant .dock__pastille { width: 2.4rem; height: 2.4rem; }
}

/* Le fil longe le bord gauche de la colonne. */
.dock__fil {
    position: absolute;
    top: 1rem;
    bottom: 1rem;
    left: 0;
    width: 2px;
    border-radius: 2px;
    background: var(--line-2);
    overflow: hidden;
}

.dock__fil-plein {
    display: block;
    width: 100%;
    height: 100%;
    transform-origin: top;
    background: var(--terre-500);
    transition: transform .25s linear;
}

/* ── La tuile : une pastille, et sa légende dessous ─────────────────── */
.dock__tuile {
    display: grid;
    justify-items: center;
    gap: .32rem;
    width: 100%;
    padding: 0;
    border: 0;
    background: none;
    font: inherit;
    color: var(--ink);
    cursor: pointer;
}
.dock__tuile:focus-visible { outline: none; }

/* **Un vrai bouton** : 2,75 rem, la cible tactile minimale de la maison,
   contour franc et fond blanc. Le flou d'arrière-plan le garde lisible quand
   la page défile dessous. */
.dock__pastille {
    display: grid;
    place-items: center;
    width: 2.75rem;
    height: 2.75rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: rgba(255, 255, 255, .9);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 20px -14px rgba(23, 20, 28, .55);
    transition:
        transform .3s var(--ease),
        border-color .25s var(--ease),
        background-color .25s var(--ease),
        box-shadow .3s var(--ease);
}

.dock__tuile:hover .dock__pastille,
.dock__tuile:focus-visible .dock__pastille {
    transform: translateY(-2px);
    border-color: var(--terre-300);
    box-shadow: 0 12px 24px -14px rgba(201, 69, 42, .45);
}
.dock__tuile:focus-visible .dock__pastille { outline: 2px solid var(--terre-500); outline-offset: 3px; }

/* La légende : écrite, toujours. Petite mais pleine — à 700, pas en gris
   clair : c'est elle qui dit ce que fait le bouton. */
.dock__legende {
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: -.01em;
    line-height: 1.1;
    color: var(--text-2);
    white-space: nowrap;
    transition: color .25s var(--ease);
}
.dock__tuile:hover .dock__legende,
.dock__tuile:focus-visible .dock__legende { color: var(--ink); }

/* « Vous êtes là » : de la terre autour du pictogramme, pas à sa place. */
.dock__tuile.is-on .dock__pastille {
    border-color: var(--terre-300);
    background: var(--terre-050);
    box-shadow: 0 0 0 4px rgba(201, 69, 42, .12);
}
.dock__tuile.is-on .dock__legende { color: var(--terre-700); font-weight: 800; }

/* ── La rangée posée sur la photographie ────────────────────────────
   En bas, jamais en haut : le coin haut-gauche porte le sceau de
   vérification et le haut-droit « voir les photos ». La rangée défile si
   l'écran est trop étroit pour les cinq — elle ne coupe jamais une légende. */
.dock--photo {
    position: absolute;
    left: clamp(.6rem, 3vw, 1rem);
    right: clamp(.6rem, 3vw, 1rem);
    bottom: clamp(.7rem, 3vw, 1rem);
    display: flex;
    gap: .4rem;
    overflow-x: auto;
    scrollbar-width: none;
}
.dock--photo::-webkit-scrollbar { display: none; }

@media (min-width: 1180px) {
    .dock--photo { display: none; }
}

/* Sur l'image, c'est la tuile entière qui porte le fond : une légende posée
   nue sur une photographie se perd dans la première zone claire. */
.dock--photo .dock__tuile {
    flex: none;
    width: auto;
    min-width: 3.9rem;
    padding: .45rem .6rem .4rem;
    border: 1px solid rgba(255, 255, 255, .7);
    border-radius: var(--r-md);
    background: rgba(255, 255, 255, .86);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 20px -12px rgba(23, 20, 28, .5);
}
.dock--photo .dock__pastille {
    width: auto;
    height: auto;
    border: 0;
    background: none;
    backdrop-filter: none;
    box-shadow: none;
}
.dock--photo .dock__legende { font-size: .64rem; }

/* Ici la pastille n'a plus de fond : l'état et le focus passent sur la tuile
   entière, sinon ils ne dessineraient qu'un anneau autour du pictogramme. */
.dock--photo .dock__tuile.is-on {
    border-color: var(--terre-300);
    background: var(--terre-050);
}
.dock--photo .dock__tuile.is-on .dock__pastille { box-shadow: none; background: none; }
.dock--photo .dock__tuile:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }
.dock--photo .dock__tuile:focus-visible .dock__pastille { outline: none; }

@media (prefers-reduced-motion: reduce) {
    .dock__pastille,
    .dock__legende,
    .dock__fil-plein { transition: none; }
}
</style>
