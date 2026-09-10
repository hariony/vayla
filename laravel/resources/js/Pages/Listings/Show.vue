<script setup>
/**
 * La fiche d'une annonce — orchestrateur.
 *
 * Ordre assumé, et c'est le pari de la page : la galerie, puis **ce qui a
 * été vérifié**, puis les équipements, et le prix seulement dans la colonne
 * latérale. Partout ailleurs le prix vient en second ; ici ce qui se vend
 * n'est pas le tarif mais la certitude que le logement existe et correspond.
 *
 * Deux blocs n'existent sur aucune plateforme du Nord, et ce sont eux qui
 * justifient la page :
 *   — « Ce qui tient quand la ville lâche » : électricité, eau, connexion.
 *     À Madagascar, un logement peut être magnifique et invivable à 19 h.
 *   — « Ce qui a été vérifié » : les quatre barreaux, franchis ou non, avec
 *     ce qui manque écrit noir sur blanc.
 *
 * Aucun libellé de niveau ni d'équipement n'est écrit ici : tout vient du
 * serveur, comme dans l'application mobile.
 */
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

import SiteHeader from '@/Components/SiteHeader.vue'
import SiteFooter from '@/Components/SiteFooter.vue'
import ListingCard from '@/Components/ListingCard.vue'

import CapacityBar from './Partials/CapacityBar.vue'
import Gallery from './Partials/Gallery.vue'
import TrustPanel from './Partials/TrustPanel.vue'
import EnergyPanel from './Partials/EnergyPanel.vue'
import AmenityGroups from './Partials/AmenityGroups.vue'
import AvailabilitySection from './Partials/AvailabilitySection.vue'
import BookingBox from './Partials/BookingBox.vue'
import StayRules from './Partials/StayRules.vue'
import Confirmations from './Partials/Confirmations.vue'

import { useFicheMotion } from '@/Composables/useFicheMotion.js'
import { useStayDates } from '@/Composables/useStayDates.js'

const props = defineProps({
    fiche: { type: Object, required: true },
    similar: { type: Array, default: () => [] },
    trustLevels: { type: Array, default: () => [] },
    demo: { type: Boolean, default: false },
    photos: { type: Object, default: () => ({}) },
    credits: { type: Array, default: () => [] },
    /** Le séjour saisi dans le moteur de recherche, à reporter. */
    sejour: { type: Object, default: null },
})

const root = ref(null)
useFicheMotion(root)

const l = computed(() => props.fiche.listing)
const calendrier = computed(() => props.fiche.calendar)

// L'état des dates vit ici : la section calendrier et l'encart de prix le
// partagent. Deux états séparés finiraient par afficher deux séjours.
const dates = useStayDates(calendrier)

// Le séjour vient du moteur de recherche ou d'un lien partagé. `preselectionner`
// applique les deux dates ou aucune, et refuse celles qui chevauchent une nuit
// déjà prise : mieux vaut un calendrier vide qu'une pré-sélection irréservable.
if (props.sejour) {
    dates.preselectionner(props.sejour.arrival, props.sejour.departure)
}
const niveau = computed(() => props.trustLevels[l.value.trust - 1])
</script>

<template>
    <Head :title="`${l.title} — ${l.place}`" />

    <div ref="root" class="page">
        <SiteHeader :search="false" />

        <main class="fiche">
            <div class="shell">
                <nav class="fiche__crumbs" aria-label="Fil d'Ariane" data-fiche-head>
                    <Link href="/logements" class="fiche__crumb">Logements</Link>
                    <span aria-hidden="true">·</span>
                    <Link :href="`/logements?destination=${l.destination}`" class="fiche__crumb">
                        {{ l.place }}
                    </Link>
                </nav>

                <header class="fiche__head" data-fiche-head>
                    <div class="fiche__headline">
                        <h1 class="display display--md fiche__title">{{ l.title }}</h1>
                        <p class="fiche__place">
                            {{ l.kindLabel }} à {{ l.place }}<span aria-hidden="true"> · </span>
                            <span class="fiche__region">{{ l.region }}</span>
                        </p>
                    </div>

                    <CapacityBar :listing="l" />
                </header>

                <p v-if="demo" class="fiche__demo" data-fiche-head>
                    <span class="chip chip--terre fiche__demo-chip">Aperçu</span>
                    Annonce fictive, le temps que les premiers propriétaires publient.
                    Les photographies, elles, sont réelles et créditées.
                </p>

                <Gallery
                    :photos="fiche.gallery"
                    :title="l.title"
                    :trust="l.trust"
                    :trust-name="niveau?.name"
                />

                <div class="fiche__body">
                    <div class="fiche__main">
                        <div class="fiche__intro" data-anim>
                            <!-- « Logement entier · Villa » plutôt que « Villa
                                 entière » : l'accord dépend du type, et
                                 « Studio entier » / « Maison entière » aurait
                                 demandé un genre par valeur de l'enum. -->
                            <p class="fiche__kind">
                                <template v-if="l.wholePlace">Logement entier<span aria-hidden="true"> · </span></template>
                                {{ l.kindLabel }} à {{ l.place }}
                            </p>
                            <p v-if="l.summary" class="lede fiche__summary">{{ l.summary }}</p>
                        </div>

                        <section v-if="fiche.description" class="fiche__section" data-anim>
                            <h2 class="fiche__h2">Le logement</h2>
                            <p class="fiche__prose">{{ fiche.description }}</p>
                        </section>

                        <div class="fiche__section">
                            <EnergyPanel :groups="fiche.amenities" />
                        </div>

                        <div class="fiche__section fiche__section--trust">
                            <TrustPanel :level="l.trust" :trust-levels="trustLevels" />
                        </div>

                        <div class="fiche__section fiche__section--wide">
                            <Confirmations
                                :summary="fiche.confirmed"
                                :confirmations="fiche.confirmations"
                                :trust="l.trust"
                            />
                        </div>

                        <section class="fiche__section" data-anim>
                            <h2 class="fiche__h2">
                                Équipements
                                <span class="fiche__h2-n num" :data-count="l.amenityCount">{{ l.amenityCount }}</span>
                            </h2>
                            <AmenityGroups :groups="fiche.amenities" />
                        </section>

                        <div class="fiche__section fiche__section--wide">
                            <AvailabilitySection
                                :dates="dates"
                                :calendar="calendrier"
                                :place="l.place"
                            />
                        </div>

                        <div class="fiche__section">
                            <StayRules :rules="fiche.rules" />
                        </div>
                    </div>

                    <aside class="fiche__aside">
                        <BookingBox
                            :listing="l"
                            :calendar="calendrier"
                            :dates="dates"
                            :trust-name="niveau?.name"
                        />
                    </aside>
                </div>

                <section v-if="similar.length" class="fiche__similar" data-anim>
                    <h2 class="fiche__h2">Autres logements à {{ l.place }}</h2>
                    <div class="fiche__grid" data-anim-group>
                        <ListingCard
                            v-for="s in similar"
                            :key="s.slug"
                            :listing="s"
                            :photos="photos"
                            :stay="{ arrival: dates.arrivee.value, departure: dates.depart.value }"
                        />
                    </div>
                </section>
            </div>
        </main>

        <SiteFooter :credits="credits" />
    </div>
</template>

<style scoped>
.page { overflow-x: clip; }

.fiche {
    padding-top: calc(var(--header-h) + clamp(1.25rem, 3vw, 2.25rem));
    padding-bottom: clamp(4rem, 8vw, 7rem);
}

.fiche__crumbs {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
    font-size: .82rem;
    font-weight: 600;
    color: var(--text-3);
}
.fiche__crumb { color: var(--text-2); text-decoration: none; }
.fiche__crumb:hover { color: var(--terre-600); text-decoration: underline; }

.fiche__head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1.25rem 2.5rem;
    margin: .85rem 0 1.4rem;
}

.fiche__headline { max-width: 42rem; }
.fiche__title { margin: 0; }

.fiche__place {
    margin: .5rem 0 0;
    font-size: 1rem;
    font-weight: 500;
    color: var(--text-2);
}
.fiche__region { color: var(--text-3); }

.fiche__demo {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: .25rem .55rem;
    margin: 0 0 1.1rem;
    font-size: .84rem;
    line-height: 1.6;
    color: var(--text-3);
}

.fiche__demo-chip { translate: 0 -1px; }

.fiche__body {
    display: grid;
    grid-template-columns: 1fr;
    gap: clamp(2.25rem, 5vw, 3.5rem);
    margin-top: clamp(2.25rem, 5vw, 3.25rem);
}

/* ── L'introduction, en une ligne ─────────────────────────────── */
.fiche__intro { padding-bottom: 1.75rem; border-bottom: 1px solid var(--line); }

.fiche__kind {
    margin: 0;
    font-size: 1.22rem;
    font-weight: 800;
    letter-spacing: -.03em;
    color: var(--ink);
}

.fiche__summary { margin: 1.1rem 0 0; max-width: 46ch; }


.fiche__section { margin-top: clamp(2.5rem, 5vw, 3.5rem); }
.fiche__section--trust { margin-top: clamp(1.5rem, 3vw, 2rem); }

/* Le calendrier respire : plus d'espace au-dessus que les autres blocs,
   parce que c'est là qu'on décide. */
.fiche__section--wide { margin-top: clamp(3rem, 6vw, 4.5rem); }

.fiche__h2 {
    display: flex;
    align-items: baseline;
    gap: .6rem;
    margin: 0 0 1.35rem;
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.032em;
    color: var(--ink);
}

.fiche__h2-n {
    padding: .12rem .5rem;
    border-radius: var(--r-pill);
    font-size: .76rem;
    font-weight: 700;
    color: var(--text-2);
    background: var(--off-2);
}

.fiche__prose {
    margin: 0;
    max-width: 62ch;
    font-size: 1rem;
    line-height: 1.75;
    color: var(--text-2);
}


/* ── Voisins ──────────────────────────────────────────────────── */
.fiche__similar {
    margin-top: clamp(3rem, 6vw, 4.5rem);
    padding-top: clamp(2.5rem, 5vw, 3.5rem);
    border-top: 1px solid var(--line);
}

.fiche__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr));
    gap: clamp(1.6rem, 3vw, 2.4rem) clamp(1.2rem, 2vw, 1.8rem);
}

@media (min-width: 1000px) {
    .fiche__body { grid-template-columns: minmax(0, 1fr) 340px; }

    .fiche__aside {
        position: sticky;
        top: calc(var(--header-h) + 1.5rem);
        align-self: start;
    }
}
</style>
