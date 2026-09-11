<script setup>
/**
 * Accueil Vayla — orchestrateur.
 *
 * Il ne contient ni balisage de section ni règle de style : il reçoit les
 * props du HomeService, branche le filtrage et le mouvement, et assemble
 * six partiels. Chaque section vit dans son fichier, avec son style scopé.
 *
 * Parti pris de composition, tenu par l'ordre ci-dessous : le moteur de
 * recherche ferme le hero et les annonces commencent immédiatement dessous
 * — on doit voir des logements sans avoir à défiler. Tout le reste de la
 * page vient répondre aux questions que la grille pose.
 */
import { ref, toRef } from 'vue'
import { Head } from '@inertiajs/vue3'

import SiteHeader from '@/Components/SiteHeader.vue'
import SiteFooter from '@/Components/SiteFooter.vue'

import HeroSection from './Partials/HeroSection.vue'
import OffersSection from './Partials/OffersSection.vue'
import TrustSection from './Partials/TrustSection.vue'
import AtlasSection from './Partials/AtlasSection.vue'
import AskSection from './Partials/AskSection.vue'
import OwnersSection from './Partials/OwnersSection.vue'

import { usePageMotion } from '@/Composables/usePageMotion.js'
import { useListingFilters } from '@/Composables/useListingFilters.js'
import { useSearchQuery } from '@/Composables/useSearchQuery.js'
import { allerA } from '@/Support/liens.js'

const props = defineProps({
    destinations: { type: Array, default: () => [] },
    trustLevels: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    listings: { type: Array, default: () => [] },
    demo: { type: Boolean, default: false },
    photos: { type: Object, default: () => ({}) },
    credits: { type: Array, default: () => [] },
})

// Le moteur de recherche est la source unique des critères : le hero,
// l'en-tête compact, l'atlas et la grille regardent tous cet état-là.
const recherche = useSearchQuery()

const { category, results, searchLabel, pickDestination, reset } =
    useListingFilters(toRef(props, 'listings'), toRef(props, 'destinations'), recherche)

// Choisir une région — dans la liste ou sur la carte — filtre la grille, qui
// est deux sections plus haut : sans ce défilement, le filtre s'appliquait
// hors de l'écran et le clic semblait ne rien faire.
const choisirRegion = (slug) => {
    pickDestination(slug)
    allerA('offres')
}

const root = ref(null)
const hero = ref(null)

// Le composable gère lui-même montage et démontage (gsap.context).
usePageMotion(root)
</script>

<template>
    <Head title="Locations meublées vérifiées à Madagascar" />

    <div id="top" ref="root" class="page">
        <SiteHeader
            home
            :destinations="destinations"
            :recherche="recherche"
            @expand="hero?.focusSearch()"
        />

        <main>
            <HeroSection
                ref="hero"
                :destinations="destinations"
                :trust-levels="trustLevels"
                :recherche="recherche"
            />

            <OffersSection
                :categories="categories"
                :category="category"
                :results="results"
                :photos="photos"
                :stay="{ arrival: recherche.dates.arrivee.value, departure: recherche.dates.depart.value }"
                :criteres="recherche.criteres.value"
                :demo="demo"
                :search-label="searchLabel"
                @update:category="category = $event"
                @reset="reset"
            />

            <TrustSection :trust-levels="trustLevels" />

            <AtlasSection
                :destinations="destinations"
                :photos="photos"
                @pick="choisirRegion"
            />

            <AskSection :criteres="recherche.criteres.value" />

            <OwnersSection />
        </main>

        <SiteFooter home :credits="credits" />
    </div>
</template>

<style scoped>
.page { overflow-x: clip; }
</style>
