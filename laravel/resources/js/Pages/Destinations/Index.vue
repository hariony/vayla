<script setup>
/**
 * L'atlas, en page.
 *
 * Il double la section de l'accueil, et c'est assumé : là-bas elle sert à
 * découvrir en défilant, ici elle est une adresse — partageable, indexable,
 * et la cible du lien « Destinations » de l'en-tête, qui pointait jusqu'ici
 * vers une ancre.
 *
 * **Le compteur est affiché même à zéro.** Cinq destinations sur onze n'ont
 * aucune annonce ; les masquer donnerait un atlas flatteur et faux, et un
 * voyageur qui clique découvrirait le vide sans prévenir. Une destination
 * sans logement porte donc « aucun logement » et mène quand même à sa page,
 * où la demande de séjour prend le relais.
 */
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

import SiteHeader from '@/Components/SiteHeader.vue'
import SiteFooter from '@/Components/SiteFooter.vue'
import SceneArt from '@/Components/SceneArt.vue'
import MadagascarMap from '@/Components/MadagascarMap.vue'
import { photoSrc, photoSrcset } from '@/Support/photo.js'

const props = defineProps({
    destinations: { type: Array, default: () => [] },
    demo: { type: Boolean, default: false },
    photos: { type: Object, default: () => ({}) },
    credits: { type: Array, default: () => [] },
})

const ouvertes = computed(() => props.destinations.filter((d) => d.listings).length)
const total = computed(() => props.destinations.reduce((n, d) => n + d.listings, 0))
</script>

<template>
    <Head title="Destinations couvertes à Madagascar" />

    <div class="page">
        <SiteHeader :destinations="destinations" :search="false" />

        <main class="atl">
            <div class="shell">
                <header class="atl__head">
                    <p class="eyebrow">Atlas</p>
                    <h1 class="display display--md">Où nous sommes allés</h1>
                    <p class="lede atl__lede">
                        <strong class="num">{{ destinations.length }}</strong> destinations
                        ouvertes, <strong class="num">{{ ouvertes }}</strong> avec des logements
                        vérifiés et <strong class="num">{{ total }}</strong> annonces en ligne.
                        Les autres attendent leur premier propriétaire — et votre demande
                        les fait avancer.
                    </p>
                </header>

                <div class="atl__body">
                    <div class="atl__map">
                        <MadagascarMap :destinations="destinations" />
                    </div>

                    <ul class="atl__list">
                        <li v-for="d in destinations" :key="d.slug">
                            <Link :href="`/destinations/${d.slug}`" class="atl__card">
                                <span class="atl__media">
                                    <img
                                        v-if="d.photo"
                                        :src="photoSrc(photos[d.photo], 800)"
                                        :srcset="photoSrcset(photos[d.photo])"
                                        sizes="(min-width: 1120px) 300px, (min-width: 640px) 45vw, 92vw"
                                        :alt="photos[d.photo]?.caption ?? d.name"
                                        width="560"
                                        height="420"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                    <SceneArt v-else :variant="d.scene" />
                                </span>

                                <span class="atl__body-txt">
                                    <span class="atl__name">{{ d.name }}</span>
                                    <span class="atl__region">{{ d.region }}</span>
                                    <span class="atl__tagline">{{ d.tagline }}</span>
                                    <span class="atl__count num" :class="{ 'is-zero': !d.listings }">
                                        <template v-if="d.listings">
                                            {{ d.listings }} logement{{ d.listings > 1 ? 's' : '' }}
                                        </template>
                                        <template v-else>Aucun logement</template>
                                    </span>
                                </span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </main>

        <SiteFooter :credits="credits" />
    </div>
</template>

<style scoped>
.page { overflow-x: clip; }

.atl {
    padding-top: calc(var(--header-h) + clamp(1.5rem, 4vw, 3rem));
    padding-bottom: clamp(4rem, 8vw, 7rem);
}

.atl__head { max-width: 46rem; }
.atl__head .display { margin: .4rem 0 0; }
.atl__lede { margin: .9rem 0 0; }
.atl__lede strong { color: var(--ink); font-weight: 800; }

.atl__body {
    display: grid;
    grid-template-columns: 1fr;
    gap: clamp(2rem, 5vw, 3.5rem);
    margin-top: clamp(2rem, 5vw, 3.5rem);
}

.atl__map { max-width: 22rem; margin-inline: auto; }

.atl__list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 250px), 1fr));
    gap: 1.5rem 1.25rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.atl__card {
    display: block;
    text-decoration: none;
    color: inherit;
}

.atl__media {
    display: block;
    aspect-ratio: 4 / 3;
    overflow: hidden;
    border-radius: var(--r-lg);
    background: var(--off-2);
    box-shadow: var(--sh-1);
}
.atl__media img,
.atl__media :deep(svg) {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 1.1s var(--ease);
}
.atl__card:hover .atl__media img,
.atl__card:hover .atl__media :deep(svg) { transform: scale(1.055); }
.atl__card:focus-visible .atl__media { outline: 2.5px solid var(--terre-500); outline-offset: 3px; }

.atl__body-txt { display: flex; flex-direction: column; gap: .1rem; padding: .85rem .15rem 0; }

.atl__name { font-size: 1rem; font-weight: 700; letter-spacing: -.022em; color: var(--ink); }
.atl__region { font-size: .8rem; color: var(--text-3); }
.atl__tagline { margin-top: .3rem; font-size: .84rem; line-height: 1.45; color: var(--text-2); }

.atl__count {
    margin-top: .5rem;
    font-size: .78rem;
    font-weight: 700;
    color: var(--lagon-700);
}
/* Zéro se dit, il ne se cache pas — mais en gris : ce n'est pas une
   vérification, c'est une absence. */
.atl__count.is-zero { color: var(--text-3); font-weight: 500; }

@media (min-width: 1000px) {
    .atl__body { grid-template-columns: 20rem minmax(0, 1fr); align-items: start; }
    .atl__map { margin-inline: 0; position: sticky; top: calc(var(--header-h) + 2rem); }
}
</style>
