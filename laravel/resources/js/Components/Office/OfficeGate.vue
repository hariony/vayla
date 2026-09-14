<script setup>
/**
 * Le cadre des écrans d'entrée du back-office — connexion, page introuvable.
 *
 * **Délibérément différent du site.** Les écrans d'accès du site sont blancs,
 * baignés d'une lueur de latérite, centrés sur une question posée à quelqu'un
 * qui découvre. Celui-ci est une **salle des cartes** : fond d'encre, papier
 * quadrillé à peine visible, le V du monogramme tracé en grand, et le
 * formulaire à droite comme une fiche posée sur la table. Personne ne doit
 * pouvoir le prendre pour la connexion d'un voyageur — ni un voyageur tomber
 * dessus et croire qu'il est au bon endroit.
 *
 * Ce qui ne change pas : la famille de caractères, la terre pour l'action, des
 * contrôles de 2,75 rem bordés et lisibles. Un outil interne n'a pas le droit
 * d'être moins lisible que le site.
 *
 * **Une seule entrée, et rien après** (`gsap`) : le V se trace, le titre monte,
 * la fiche arrive. Sous `prefers-reduced-motion`, tout est déjà en place.
 */
import { onMounted, onUnmounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import gsap from 'gsap'

import VaylaMark from '@/Components/VaylaMark.vue'

defineProps({
    titrePage: { type: String, required: true },
    titre: { type: String, required: true },
    lede: { type: String, default: '' },
})

const racine = ref(null)

const MARQUE = 'M2.18,4.81 L19.79,45.70 Q38.12,20.37 45.82,2.30 L38.58,4.12 '
    + 'Q27.42,20.47 21.66,26.12 L9.01,7.47 Z'

let ctx
onMounted(() => {
    ctx = gsap.context((self) => {
        const mm = gsap.matchMedia()

        mm.add('(prefers-reduced-motion: no-preference)', () => {
            gsap.timeline({ defaults: { ease: 'power3.out' } })
                .from(self.selector('.og__trace'), { strokeDashoffset: 300, duration: 1.6, ease: 'power2.inOut' })
                .from(self.selector('[data-og-rise]'), { y: 14, opacity: 0, duration: .55, stagger: .07, clearProps: 'transform,opacity' }, .25)
                .from(self.selector('.og__fiche'), { x: 24, opacity: 0, duration: .6, clearProps: 'transform,opacity' }, .35)
                .from(self.selector('[data-og-champ]'), { y: 8, opacity: 0, duration: .4, stagger: .06, clearProps: 'transform,opacity' }, .55)
        })
    }, racine.value)
})
onUnmounted(() => ctx?.revert())
</script>

<template>
    <Head :title="titrePage" />

    <div ref="racine" class="og">
        <section class="og__salle">
            <p class="og__marque" data-og-rise>
                <VaylaMark class="og__v" />
                <span class="og__nom">Vayla</span>
                <span class="og__sep" aria-hidden="true" />
                <span class="og__office">Back-office</span>
            </p>

            <div class="og__texte">
                <h1 class="og__titre" data-og-rise>{{ titre }}</h1>
                <p v-if="lede" class="og__lede" data-og-rise>{{ lede }}</p>
            </div>

            <p class="og__pied" data-og-rise>Accès réservé à l'équipe Vayla</p>

            <svg class="og__grand" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                <path class="og__trace" :d="MARQUE" stroke="currentColor" stroke-width=".35" stroke-linejoin="round" stroke-dasharray="300" />
            </svg>
        </section>

        <section class="og__table">
            <div class="og__fiche">
                <slot />
            </div>
        </section>
    </div>
</template>

<style scoped>
.og {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(22rem, 1fr);
    min-height: 100vh;
    background: var(--ink);
}

/* ── La salle ─────────────────────────────────────────────────────────── */
.og__salle {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 2rem;
    overflow: hidden;
    padding: clamp(1.5rem, 4vw, 3rem);
    color: var(--white);
    /* Le papier quadrillé d'un registre, à peine visible : de la matière,
       pas un motif. Et une lueur de latérite au pied, là où la terre tient. */
    background:
        radial-gradient(90% 60% at 10% 110%, rgba(201, 69, 42, .28), transparent 65%),
        linear-gradient(rgba(255, 255, 255, .035) 1px, transparent 1px) 0 0 / 2.25rem 2.25rem,
        linear-gradient(90deg, rgba(255, 255, 255, .035) 1px, transparent 1px) 0 0 / 2.25rem 2.25rem,
        var(--ink);
}

.og__marque {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: .6rem;
    margin: 0;
}
.og__v { width: 1.9rem; height: 1.9rem; color: var(--white); }
.og__nom { font-size: 1.05rem; font-weight: 800; letter-spacing: -.035em; }
.og__sep { width: 1px; height: 1.1rem; background: rgba(255, 255, 255, .25); }
.og__office {
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
    color: var(--terre-300);
}

.og__texte { position: relative; z-index: 1; max-width: 28rem; }
.og__titre {
    margin: 0;
    font-size: clamp(2rem, 4.2vw, 3.1rem);
    font-weight: 800;
    letter-spacing: -.045em;
    line-height: 1.02;
}
.og__lede {
    margin: 1rem 0 0;
    max-width: 34ch;
    font-size: .98rem;
    line-height: 1.6;
    color: rgba(255, 255, 255, .66);
}

.og__pied {
    position: relative;
    z-index: 1;
    margin: 0;
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, .56);
}

/* Le V, tracé en grand : un trait, pas une image. */
.og__grand {
    position: absolute;
    right: -8%;
    bottom: -14%;
    width: min(78vh, 42rem);
    height: min(78vh, 42rem);
    color: var(--terre-400);
    opacity: .55;
    pointer-events: none;
}

/* ── La table ─────────────────────────────────────────────────────────── */
.og__table {
    display: grid;
    place-items: center;
    padding: clamp(1.25rem, 4vw, 3rem);
    background: var(--off-2);
}

.og__fiche {
    width: 100%;
    max-width: 25rem;
    margin-block: auto;
    padding: clamp(1.5rem, 3.5vw, 2.25rem);
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--white);
    box-shadow: 0 30px 60px -40px rgba(26, 21, 18, .45);
}

/* ── Téléphone : la salle devient un bandeau, la fiche reste lisible ───── */
@media (max-width: 860px) {
    .og { grid-template-columns: minmax(0, 1fr); }
    .og__salle { gap: 1.5rem; padding-bottom: 2.25rem; }
    .og__titre { font-size: 2rem; }
    .og__pied { display: none; }
    .og__grand { width: 20rem; height: 20rem; right: -4rem; bottom: -6rem; opacity: .4; }
    .og__table { place-items: start center; }
}
</style>
