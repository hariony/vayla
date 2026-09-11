<script setup>
/**
 * La galerie d'une annonce : une photographie en tête, la pellicule dessous,
 * puis la visionneuse plein écran.
 *
 * **Ce n'est volontairement pas la mosaïque à cinq tuiles d'Airbnb.** Trois
 * raisons, et aucune n'est esthétique :
 *
 * 1. Ici les photographies sont **de vraies photographies créditées** — c'est
 *    l'argument du produit. Les empiler cinq par cinq les traite en vignettes ;
 *    en donner une à toute la largeur la traite en photographie.
 * 2. La mosaïque demandait **quatre dispositions** selon le nombre de photos,
 *    et affichait « +7 » au-delà de cinq. La pellicule montre les quinze si
 *    l'annonce en porte quinze, et se comporte pareil à trois.
 * 3. Le niveau de vérification a enfin **un endroit où se poser** : sur la
 *    photo, à l'instant précis où le visiteur regarde le logement. C'est la
 *    seule chose que les plateformes du Nord n'ont pas à mettre là.
 *
 * Deux règles héritées, à ne pas défaire :
 *
 * — **Le crédit suit la photo jusque dans la visionneuse.** CC BY et CC BY-SA
 *   exigent l'attribution *partout où l'image est affichée* ; un bloc au pied
 *   de page ne couvre pas un plein écran.
 * — **La tête charge sans différer.** Elle est au-dessus de la ligne de
 *   flottaison, et `loading="lazy"` y laissait un rectangle gris — jamais rien
 *   du tout dans un onglet d'arrière-plan. Seules les vignettes diffèrent.
 *
 * La visionneuse est un vrai dialogue : Échap ferme, les flèches naviguent,
 * le défilement est bloqué, le focus revient sur le bouton d'origine.
 */
import { computed, nextTick, onUnmounted, ref, watch } from 'vue'

import TrustGauge from '@/Components/TrustGauge.vue'
import { photoSrc, photoSrcset } from '@/Support/photo.js'

const props = defineProps({
    photos: { type: Array, default: () => [] },
    title: { type: String, default: '' },
    trust: { type: Number, default: 1 },
    trustName: { type: String, default: '' },
})

const open = ref(false)
const index = ref(0)
const dialog = ref(null)
const opener = ref(null)

const count = computed(() => props.photos.length)
const current = computed(() => props.photos[index.value] ?? null)
const lead = computed(() => props.photos[0] ?? null)
const strip = computed(() => props.photos.slice(1))

/**
 * Combien de vignettes tiennent dans la largeur. En dessous de cinq, elles se
 * partagent toute la ligne — trois vignettes à la taille de cinq laissaient un
 * vide à droite ; au-delà, on en montre cinq et la pellicule défile, plutôt
 * que de les rétrécir jusqu'à l'illisible.
 */
const visibles = computed(() => Math.min(strip.value.length, 5))

function show(i, event) {
    opener.value = event?.currentTarget ?? null
    index.value = i
    open.value = true
}

function close() {
    open.value = false
    opener.value?.focus()
}

const step = (delta) => {
    index.value = (index.value + delta + count.value) % count.value
}

function onKey(e) {
    if (e.key === 'Escape') return close()
    if (e.key === 'ArrowRight') return step(1)
    if (e.key === 'ArrowLeft') return step(-1)
}

watch(open, async (isOpen) => {
    // Le fond ne doit pas défiler derrière la visionneuse.
    document.documentElement.style.overflow = isOpen ? 'hidden' : ''
    if (isOpen) {
        await nextTick()
        dialog.value?.focus()
    }
})

onUnmounted(() => {
    document.documentElement.style.overflow = ''
})
</script>

<template>
    <section v-if="count" class="gal" data-gallery>
        <!-- ── La photographie de tête ────────────────────────────── -->
        <button type="button" class="gal__lead" data-gal-lead @click="show(0, $event)">
            <img
                :src="photoSrc(lead, 1600)"
                :srcset="photoSrcset(lead)"
                sizes="(min-width: 1400px) 1216px, (min-width: 1180px) calc(100vw - 11.5rem), calc(100vw - 3rem)"
                :alt="lead.caption"
                width="1600"
                height="1200"
                loading="eager"
                fetchpriority="high"
                decoding="async"
                data-gal-img
            >
            <span class="gal__veil" aria-hidden="true"></span>
            <span class="sr-only">Agrandir : {{ lead.caption }}</span>
        </button>

        <!-- Le niveau de vérification est posé sur la photo, pas à côté :
             c'est ce que Vayla a à dire au moment exact où l'on regarde. -->
        <p v-if="trustName" class="gal__seal" :class="`gal__seal--n${trust}`">
            <TrustGauge :level="trust" />
            <span>{{ trustName }}</span>
        </p>

        <!-- La surcouche épouse la photographie de tête, pas la galerie
             entière : sans elle, tout ce qu'on y pose atterrirait au bas de la
             pellicule. Elle ne capte pas le clic — la photo dessous reste
             cliquable partout où la surcouche est vide. -->
        <div v-if="$slots.surcouche" class="gal__over">
            <slot name="surcouche" />
        </div>

        <button v-if="count > 1" type="button" class="gal__all" @click="show(0, $event)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3.5 5.5h7v7h-7zM13.5 5.5h7v7h-7zM3.5 15.5h7v5h-7zM13.5 15.5h7v5h-7z" />
            </svg>
            <span class="num">{{ count }}</span> photos
        </button>

        <!-- ── La pellicule ──────────────────────────────────────── -->
        <ol v-if="strip.length" class="gal__strip no-bar" :style="{ '--n': visibles }">
            <li v-for="(p, i) in strip" :key="p.key">
                <button type="button" class="gal__tile" data-gal-tile @click="show(i + 1, $event)">
                    <img
                        :src="photoSrc(p, 800)"
                        :srcset="photoSrcset(p)"
                        sizes="(min-width: 760px) 260px, 38vw"
                        :alt="p.caption"
                        width="800"
                        height="600"
                        loading="eager"
                        decoding="async"
                    >
                    <span class="gal__veil" aria-hidden="true"></span>
                    <span class="sr-only">Agrandir : {{ p.caption }}</span>
                </button>
            </li>
        </ol>
    </section>

    <!-- ── Visionneuse ────────────────────────────────────────────── -->
    <Teleport to="body">
        <div
            v-if="open"
            ref="dialog"
            class="lb"
            role="dialog"
            aria-modal="true"
            :aria-label="`Photos — ${title}`"
            tabindex="-1"
            @keydown="onKey"
        >
            <header class="lb__bar">
                <p class="lb__pos num">{{ index + 1 }} / {{ count }}</p>
                <button type="button" class="lb__close" @click="close">
                    <span class="sr-only">Fermer</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"
                         stroke-linecap="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>
            </header>

            <div class="lb__stage" @click.self="close">
                <button
                    v-if="count > 1"
                    type="button"
                    class="lb__nav lb__nav--prev"
                    @click="step(-1)"
                >
                    <span class="sr-only">Photo précédente</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 4.5 7.5 12 15 19.5" />
                    </svg>
                </button>

                <figure class="lb__fig">
                    <img
                        :key="current.key"
                        :src="photoSrc(current, 3200)"
                        :srcset="photoSrcset(current)"
                        sizes="100vw"
                        :alt="current.caption"
                        class="lb__img"
                    >
                    <!-- L'attribution voyage avec l'image : la licence l'exige
                         ici comme au pied de page. -->
                    <figcaption class="lb__cap">
                        <span class="lb__caption">{{ current.caption }}</span>
                        <span class="lb__credit" :class="{ 'lb__credit--ia': current.generated }">
                            <template v-if="current.generated">
                                Image générée ({{ current.author }}) — ce logement de
                                démonstration n'existe pas.
                            </template>
                            <template v-else>
                                {{ current.author }} ·
                                <a v-if="current.licence_url" :href="current.licence_url" target="_blank" rel="noopener noreferrer">{{ current.licence }}</a>
                                <template v-else>{{ current.licence }}</template>
                                <template v-if="current.source">
                                    ·
                                    <a :href="current.source" target="_blank" rel="noopener noreferrer">source</a>
                                </template>
                            </template>
                        </span>
                    </figcaption>
                </figure>

                <button
                    v-if="count > 1"
                    type="button"
                    class="lb__nav lb__nav--next"
                    @click="step(1)"
                >
                    <span class="sr-only">Photo suivante</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m9 4.5 7.5 7.5L9 19.5" />
                    </svg>
                </button>
            </div>

            <ol v-if="count > 1" class="lb__strip no-bar">
                <li v-for="(p, i) in photos" :key="p.key">
                    <button
                        type="button"
                        class="lb__thumb"
                        :class="{ 'is-on': i === index }"
                        @click="index = i"
                    >
                        <img :src="photoSrc(p, 800)" :alt="p.caption" loading="lazy" decoding="async">
                    </button>
                </li>
            </ol>
        </div>
    </Teleport>
</template>

<style scoped>
/* ═══════════ TÊTE ET PELLICULE ═══════════ */
/* **La géométrie de la photographie de tête vit ici, en jetons.** Elle est
   lue à deux endroits — la photo elle-même et la surcouche qui porte les
   raccourcis — et deux copies auraient fini par diverger : la surcouche se
   serait décalée de la photo au premier ajustement du cadrage. */
.gal {
    position: relative;
    --lead-ratio: 4 / 3;
    --lead-max: min(58vh, 540px);
}

.gal__over {
    position: absolute;
    inset: 0 0 auto;
    aspect-ratio: var(--lead-ratio);
    max-height: var(--lead-max);
    pointer-events: none;
    z-index: 2;
}
.gal__over > * { pointer-events: auto; }

.gal__lead {
    position: relative;
    display: block;
    width: 100%;
    /* 4/3 sur téléphone, panoramique sur grand écran. La hauteur est bornée :
       une tête qui remplit l'écran cache le prix, les dates et le niveau de
       vérification — tout ce pour quoi on est venu. */
    aspect-ratio: var(--lead-ratio);
    max-height: var(--lead-max);
    padding: 0;
    border: 0;
    border-radius: var(--r-xl);
    overflow: hidden;
    background: var(--off-2);
    cursor: zoom-in;
}

.gal__lead img,
.gal__tile img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 1.4s var(--ease);
}
.gal__lead:hover img { transform: scale(1.028); }
.gal__lead:focus-visible,
.gal__tile:focus-visible { outline: 3px solid var(--terre-500); outline-offset: -3px; }

/* Un voile très léger au survol : la photo reste la vedette. */
.gal__veil {
    position: absolute;
    inset: 0;
    background: var(--ink);
    opacity: 0;
    transition: opacity .4s var(--ease);
}
.gal__lead:hover .gal__veil,
.gal__tile:hover .gal__veil { opacity: .07; }

/* ── Le sceau de vérification, posé sur la photo ───────────────── */
.gal__seal {
    position: absolute;
    top: .9rem;
    left: .9rem;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    margin: 0;
    padding: .45rem .85rem .45rem .7rem;
    border-radius: var(--r-pill);
    background: rgba(255, 255, 255, .93);
    backdrop-filter: saturate(180%) blur(14px);
    box-shadow: 0 4px 18px -8px rgba(23, 20, 28, .6);
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: -.012em;
    color: var(--text-2);
    pointer-events: none;
}
.gal__seal--n2 span, .gal__seal--n3 span { color: var(--lagon-600); }
.gal__seal--n4 span { color: var(--lagon-700); }

.gal__all {
    position: absolute;
    right: .9rem;
    top: .9rem;
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .5rem .85rem;
    border: 0;
    border-radius: var(--r-pill);
    background: rgba(255, 255, 255, .93);
    backdrop-filter: saturate(180%) blur(14px);
    box-shadow: 0 4px 18px -8px rgba(23, 20, 28, .6);
    font: inherit;
    font-size: .8rem;
    font-weight: 700;
    color: var(--ink);
    cursor: pointer;
    transition: transform .3s var(--ease), box-shadow .3s var(--ease);
}
.gal__all:hover { transform: translateY(-2px); box-shadow: 0 8px 24px -10px rgba(23, 20, 28, .6); }
.gal__all svg { width: 1rem; height: 1rem; }

/* ── La pellicule ─────────────────────────────────────────────── */
.gal__strip {
    display: flex;
    gap: .55rem;
    margin: .55rem 0 0;
    padding: 0;
    list-style: none;
    overflow-x: auto;
    scroll-snap-type: x proximity;
}
/* La largeur va sur l'élément de liste, jamais sur le bouton : un pourcentage
   posé sur l'enfant d'un élément flex dimensionné par son contenu se résout
   contre lui-même, et la vignette s'effondre à quelques pixels.
   Deux vignettes et demie sur téléphone : la moitié visible dit qu'on peut
   faire défiler, ce qu'une pellicule pile au bord ne dit pas. */
.gal__strip > li {
    flex: 0 0 calc((100% - 1.5 * .55rem) / 2.5);
    scroll-snap-align: start;
}

.gal__tile {
    display: block;
    width: 100%;
    aspect-ratio: 3 / 2;
    padding: 0;
    border: 0;
    border-radius: var(--r-md);
    overflow: hidden;
    background: var(--off-2);
    cursor: zoom-in;
    position: relative;
}
.gal__tile:hover img { transform: scale(1.05); }

@media (min-width: 760px) {
    .gal { --lead-ratio: 2 / 1; }

    /* `--n` vient du composant : la pellicule occupe exactement la largeur. */
    .gal__strip > li { flex-basis: calc((100% - (var(--n) - 1) * .55rem) / var(--n)); }
    .gal__seal, .gal__all { top: 1.1rem; }
    .gal__seal { left: 1.1rem; }
    .gal__all { right: 1.1rem; }
}

/* ═══════════ VISIONNEUSE ═══════════ */
.lb {
    position: fixed;
    inset: 0;
    z-index: 200;
    display: grid;
    grid-template-rows: auto minmax(0, 1fr) auto;
    background: rgba(14, 12, 17, .97);
    color: var(--white);
    animation: lb-in .28s var(--ease);
}
.lb:focus { outline: none; }

@keyframes lb-in { from { opacity: 0 } to { opacity: 1 } }

.lb__bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem clamp(1rem, 3vw, 2rem);
}

.lb__pos { margin: 0; font-size: .84rem; font-weight: 600; opacity: .7; }

.lb__close, .lb__nav {
    display: grid;
    place-items: center;
    width: 2.5rem;
    height: 2.5rem;
    border: 1px solid rgba(255, 255, 255, .18);
    border-radius: 50%;
    background: rgba(255, 255, 255, .06);
    color: inherit;
    cursor: pointer;
    transition: background-color .25s var(--ease), border-color .25s var(--ease);
}
.lb__close:hover, .lb__nav:hover { background: rgba(255, 255, 255, .16); border-color: rgba(255, 255, 255, .4); }
.lb__close svg, .lb__nav svg { width: 1.15rem; height: 1.15rem; }

.lb__stage {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: clamp(.5rem, 2vw, 1.5rem);
    padding: 0 clamp(.75rem, 3vw, 2rem);
    min-height: 0;
}

.lb__fig { display: flex; flex-direction: column; gap: .9rem; margin: 0; min-height: 0; }

.lb__img {
    display: block;
    max-width: 100%;
    max-height: calc(100vh - 15rem);
    margin: 0 auto;
    border-radius: var(--r-md);
    object-fit: contain;
    animation: lb-img .35s var(--ease);
}

@keyframes lb-img { from { opacity: 0; transform: scale(.985) } to { opacity: 1; transform: none } }

.lb__cap { text-align: center; }
.lb__caption { display: block; font-size: .92rem; font-weight: 600; }
.lb__credit { display: block; margin-top: .2rem; font-size: .76rem; opacity: .6; }
/* La mention « image générée » n'est pas une note de bas de page : elle se lit
   aussi bien que la légende, sinon elle ne sert à rien. */
.lb__credit--ia { opacity: .95; font-weight: 600; }
.lb__credit a { color: inherit; }

.lb__strip {
    display: flex;
    gap: .45rem;
    justify-content: center;
    margin: 0;
    padding: 1rem clamp(1rem, 3vw, 2rem) 1.4rem;
    list-style: none;
    overflow-x: auto;
}

.lb__thumb {
    display: block;
    width: 4.5rem;
    aspect-ratio: 4 / 3;
    padding: 0;
    border: 0;
    border-radius: var(--r-xs);
    overflow: hidden;
    opacity: .42;
    cursor: pointer;
    transition: opacity .3s var(--ease), outline-color .3s var(--ease);
    outline: 2px solid transparent;
    outline-offset: 2px;
}
.lb__thumb img { display: block; width: 100%; height: 100%; object-fit: cover; }
.lb__thumb:hover { opacity: .75; }
.lb__thumb.is-on { opacity: 1; outline-color: var(--white); }

@media (max-width: 600px) {
    .lb__nav { display: none; }
    .lb__stage { grid-template-columns: minmax(0, 1fr); }
    .lb__img { max-height: calc(100vh - 13rem); }
}

@media (prefers-reduced-motion: reduce) {
    .lb, .lb__img { animation: none; }
    .gal__lead img, .gal__tile img { transition: none; }
}
</style>
