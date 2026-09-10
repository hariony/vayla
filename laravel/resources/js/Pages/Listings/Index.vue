<script setup>
/**
 * Le catalogue — orchestrateur.
 *
 * Différence de fond avec l'accueil : là-bas le filtrage tient en mémoire,
 * parce que la grille est petite et que le rail doit répondre à l'instant.
 * Ici il est **côté serveur**, parce que la grille pagine et que l'URL doit
 * porter les critères — un filtre se partage, se met en favori et s'indexe.
 *
 * Même service et même validation que `/api/v1/listings` : le site et
 * l'application Flutter ne peuvent pas diverger sur ce qu'un filtre veut dire.
 */
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'

import SiteHeader from '@/Components/SiteHeader.vue'
import SiteFooter from '@/Components/SiteFooter.vue'
import CategoryRail from '@/Components/CategoryRail.vue'
import ListingCard from '@/Components/ListingCard.vue'

import FilterPanel from './Partials/FilterPanel.vue'
import ResultsBar from './Partials/ResultsBar.vue'
import Pagination from './Partials/Pagination.vue'

import { useCatalogueFilters } from '@/Composables/useCatalogueFilters.js'

const props = defineProps({
    listings: { type: Array, default: () => [] },
    meta: { type: Object, default: () => ({}) },
    filtre: { type: Object, default: () => ({}) },
    facets: { type: Object, default: () => ({}) },
    amenityFilters: { type: Array, default: () => [] },
    sorts: { type: Array, default: () => [] },
    destinations: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    trustLevels: { type: Array, default: () => [] },
    demo: { type: Boolean, default: false },
    photos: { type: Object, default: () => ({}) },
    credits: { type: Array, default: () => [] },
})

const { state, loading, activeCount, apply, toggleAmenity, setCategory, goToPage, reset, clearDates } =
    useCatalogueFilters(props.filtre)

// Le panneau est un tiroir sous 1000 px : il occuperait tout l'écran.
const panelOpen = ref(false)

const destinationName = computed(
    () => props.destinations.find((d) => d.slug === state.destination)?.name
)

// Pas de « · Vayla » ici : app.js suffixe déjà le nom du site.
const title = computed(() =>
    destinationName.value
        ? `Logements à ${destinationName.value}`
        : 'Tous les logements vérifiés'
)
</script>

<template>
    <Head :title="title" />

    <div class="page">
        <SiteHeader :destinations="destinations" :search="false" />

        <main class="cat">
            <div class="shell">
                <header class="cat__head">
                    <p class="eyebrow">Catalogue</p>
                    <h1 class="display display--md">
                        <template v-if="destinationName">Logements à {{ destinationName }}</template>
                        <template v-else>Tous les logements</template>
                    </h1>
                    <p class="lede cat__lede">
                        Chaque annonce affiche jusqu'où nous sommes allés pour la
                        vérifier. Filtrez par ce niveau comme vous filtrez par le prix.
                    </p>
                </header>

                <CategoryRail
                    :categories="categories"
                    :active="state.category || 'all'"
                    @select="setCategory"
                />

                <button
                    type="button"
                    class="btn btn--outline cat__toggle"
                    :aria-expanded="panelOpen"
                    @click="panelOpen = !panelOpen"
                >
                    Filtres<span v-if="activeCount" class="cat__toggle-n num">{{ activeCount }}</span>
                </button>

                <div class="cat__body">
                    <aside class="cat__aside" :class="{ 'is-open': panelOpen }">
                        <FilterPanel
                            :state="state"
                            :destinations="destinations"
                            :trust-levels="trustLevels"
                            :amenity-filters="amenityFilters"
                            :facets="facets"
                            :active-count="activeCount"
                            @apply="apply"
                            @toggle-amenity="toggleAmenity"
                            @reset="reset"
                            @clear-dates="clearDates"
                        />
                    </aside>

                    <section class="cat__results" aria-live="polite">
                        <ResultsBar
                            :total="meta.total ?? 0"
                            :catalogue="facets.catalogue ?? 0"
                            :sorts="sorts"
                            :sort="state.sort"
                            :demo="demo"
                            :loading="loading"
                            @update:sort="state.sort = $event; apply()"
                        />

                        <div v-if="listings.length" class="cat__grid" :class="{ 'is-loading': loading }">
                            <ListingCard
                                v-for="l in listings"
                                :key="l.slug"
                                :listing="l"
                                :photos="photos"
                                :stay="{ arrival: state.arrival, departure: state.departure }"
                            />
                        </div>

                        <div v-else class="cat__empty">
                            <h2 class="display display--sm">Aucun logement ne coche toutes ces cases.</h2>
                            <p class="lede cat__empty-lede">
                                C'est le moment de nous le dire : on va le chercher
                                auprès des propriétaires, on le vérifie, et on vous
                                répond.
                            </p>
                            <div class="cat__empty-actions">
                                <a href="/#demande" class="btn btn--terre">Déposer une demande</a>
                                <button type="button" class="btn btn--outline" @click="reset">
                                    Retirer les filtres
                                </button>
                            </div>
                        </div>

                        <Pagination
                            :page="meta.page ?? 1"
                            :pages="meta.pages ?? 1"
                            @go="goToPage"
                        />
                    </section>
                </div>
            </div>
        </main>

        <SiteFooter :credits="credits" />
    </div>
</template>

<style scoped>
.page { overflow-x: clip; }

.cat { padding-top: calc(var(--header-h) + clamp(1.5rem, 4vw, 3rem)); }

.cat__head { max-width: 46rem; margin-bottom: clamp(1.5rem, 3vw, 2.25rem); }
.cat__head .display { margin: .4rem 0 0; }
.cat__lede { margin: .9rem 0 0; }

.cat__toggle {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    margin-top: 1.5rem;
}

.cat__toggle-n {
    display: grid;
    place-items: center;
    min-width: 1.3rem;
    height: 1.3rem;
    padding: 0 .32rem;
    border-radius: var(--r-pill);
    font-size: .7rem;
    color: var(--white);
    background: var(--terre-500);
}

.cat__body {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
    margin-top: 1.5rem;
    padding-bottom: clamp(4rem, 8vw, 7rem);
}

/* Sous 1000 px le panneau est un tiroir : déplié, il mangerait l'écran. */
.cat__aside { display: none; }
.cat__aside.is-open {
    display: block;
    padding: 1.5rem 0 0;
    border-top: 1px solid var(--line);
}

.cat__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr));
    gap: clamp(1.6rem, 3vw, 2.4rem) clamp(1.2rem, 2vw, 1.8rem);
    margin-top: 1.75rem;
    transition: opacity .25s var(--ease);
}
/* Pendant la visite, la grille pâlit sans disparaître : la page ne saute pas. */
.cat__grid.is-loading { opacity: .45; }

.cat__empty {
    max-width: 34rem;
    margin: 3.5rem auto;
    text-align: center;
}
.cat__empty-lede { margin: 1rem 0 0; }
.cat__empty-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .7rem;
    justify-content: center;
    margin-top: 1.8rem;
}

@media (min-width: 1000px) {
    .cat__toggle { display: none; }

    .cat__body { grid-template-columns: 260px minmax(0, 1fr); gap: clamp(2rem, 4vw, 3.5rem); }

    .cat__aside {
        display: block;
        position: sticky;
        top: calc(var(--header-h) + 1.5rem);
        align-self: start;
        max-height: calc(100vh - var(--header-h) - 3rem);
        padding-right: .75rem;
        overflow-y: auto;
        overscroll-behavior: contain;
    }
    .cat__aside.is-open { padding-top: 0; border-top: 0; }
}
</style>
