<script setup>
/**
 * La page d'une destination — orchestrateur.
 *
 * Elle n'existe pas pour lister des logements : `/logements?destination=…`
 * le fait déjà, mieux, avec des filtres. Elle existe pour les trois choses
 * que la grille ne dira jamais :
 *
 *   1. **Quand venir** — la saison, façade par façade. Nosy Be en février
 *      est en saison cyclonique ; on ne l'apprend nulle part ailleurs.
 *   2. **Comment y aller** — 1 h 30 d'avion ou deux jours de route, ce n'est
 *      pas le même voyage.
 *   3. **Jusqu'où la vérification est allée ici** — la répartition par
 *      barreau, pas un compteur.
 *
 * L'ordre suit la façon dont on décide : *est-ce le bon moment*, *puis-je y
 * aller*, *qu'est-ce qu'on y trouve*. Mettre la grille en premier aurait
 * fait de cette page un doublon du catalogue.
 *
 * Cinq destinations sur onze n'ont aucune annonce. **C'est le cas
 * majoritaire, pas une exception** : l'état vide occupe la place de la
 * grille et propose la demande de séjour, au lieu de s'excuser en trois
 * lignes grises.
 */
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

import SiteHeader from '@/Components/SiteHeader.vue'
import SiteFooter from '@/Components/SiteFooter.vue'
import SceneArt from '@/Components/SceneArt.vue'
import ListingCard from '@/Components/ListingCard.vue'
import MadagascarMap from '@/Components/MadagascarMap.vue'
import SeasonRibbon from '@/Components/SeasonRibbon.vue'
import { photoSrc, photoSrcset } from '@/Support/photo.js'

import AccessPanel from './Partials/AccessPanel.vue'
import TrustBreakdown from './Partials/TrustBreakdown.vue'

import { useFicheMotion } from '@/Composables/useFicheMotion.js'
import { nombre } from '@/Support/format.js'
import { useDevise } from '@/Composables/useDevise.js'

const props = defineProps({
    fiche: { type: Object, required: true },
    destinations: { type: Array, default: () => [] },
    demo: { type: Boolean, default: false },
    photos: { type: Object, default: () => ({}) },
    credits: { type: Array, default: () => [] },
})

const root = ref(null)
useFicheMotion(root)

const d = computed(() => props.fiche.destination)
const photo = computed(() => (d.value.photo ? props.photos[d.value.photo] : null))
const money = nombre

const { eurosFourchette } = useDevise()

const prix = computed(() => {
    const { min, max } = props.fiche.prices
    if (!min) return null
    return min === max ? `${money(min)} Ar` : `${money(min)} — ${money(max)} Ar`
})

/** La fourchette en euros : une seule borne si les deux se confondent. */
const prixEur = computed(() => {
    const { min, max } = props.fiche.prices

    return min ? eurosFourchette(min, max) : null
})
</script>

<template>
    <Head :title="`${d.name}, ${d.region} — locations vérifiées`" />

    <div ref="root" class="page">
        <SiteHeader :destinations="destinations" :search="false" />

        <main class="dest">
            <div class="shell">
                <nav class="dest__crumbs" aria-label="Fil d'Ariane" data-fiche-head>
                    <Link href="/destinations" class="dest__crumb">Destinations</Link>
                    <span aria-hidden="true">·</span>
                    <span>{{ d.region }}</span>
                </nav>

                <!-- ── Ouverture : le lieu, la carte, les chiffres ── -->
                <header class="dest__hero">
                    <div class="dest__intro">
                        <h1 class="display display--md dest__title" data-fiche-head>{{ d.name }}</h1>
                        <p class="lede dest__tagline" data-fiche-head>{{ d.tagline }}</p>

                        <!-- Le ruban dès l'ouverture : la page répond « où »
                             et « quand » avant qu'on ait à défiler. C'est ce
                             qu'un voyageur cherche d'abord, et c'est ce que
                             personne d'autre ne publie. -->
                        <SeasonRibbon
                            class="dest__ribbon"
                            data-fiche-head
                            :year="fiche.season.year"
                            :best="fiche.season.best"
                            :zone="fiche.season.zone"
                            :caveat="fiche.season.caveat"
                        />
                    </div>

                    <!-- La carte est la géométrie réelle du pays : le repère
                         actif dit où l'on est, sans illustration décorative. -->
                    <div class="dest__map" data-fiche-head>
                        <MadagascarMap :destinations="destinations" :active="d.slug" />
                    </div>
                </header>

                <!-- Les chiffres en bandeau plein, sous les deux colonnes :
                     dans la colonne de gauche, ils laissaient deux cents
                     pixels de vide sous eux, la carte étant plus haute. -->
                <dl class="dest__stats" data-anim-group>
                    <div>
                        <dt>Logements</dt>
                        <dd class="num">{{ d.listings }}</dd>
                    </div>
                    <div v-if="prix">
                        <dt>À la nuit</dt>
                        <dd class="num dest__stat--price">
                            {{ prix }}
                            <span v-if="prixEur" class="eur">{{ prixEur }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt>Façade climatique</dt>
                        <dd class="dest__stat--txt">{{ fiche.season.zone }}</dd>
                    </div>
                </dl>

                <figure class="dest__media" data-anim>
                    <img
                        v-if="d.photo"
                        class="dest__photo"
                        :src="photoSrc(photo, 1600)"
                        :srcset="photoSrcset(photo)"
                        sizes="(min-width: 1400px) 1304px, calc(100vw - 3rem)"
                        :alt="photo?.caption ?? d.name"
                        width="1600"
                        height="1200"
                        loading="eager"
                        fetchpriority="high"
                        decoding="async"
                        data-gal-img
                    >
                    <SceneArt v-else :variant="d.scene" class="dest__photo" />

                    <figcaption v-if="photo" class="dest__credit">
                        {{ photo.caption }} — {{ photo.author }},
                        <a v-if="photo.licence_url" :href="photo.licence_url" target="_blank" rel="noopener noreferrer">{{ photo.licence }}</a>
                        <template v-else>{{ photo.licence }}</template>
                    </figcaption>
                </figure>

                <!-- ── Y aller ── -->
                <div class="dest__section">
                    <AccessPanel :access="fiche.access" :place="d.name" />
                </div>

                <!-- ── La vérification sur place ── -->
                <div v-if="d.listings" class="dest__section">
                    <TrustBreakdown :trust="fiche.trust" :total="d.listings" />
                </div>

                <!-- ── Les logements, ou leur absence ── -->
                <section class="dest__section" data-anim>
                    <header class="dest__grid-head">
                        <h2 class="dest__h2">
                            <template v-if="d.listings">
                                {{ d.listings }} logement{{ d.listings > 1 ? 's' : '' }} à {{ d.name }}
                            </template>
                            <template v-else>Aucun logement à {{ d.name }}, pour l'instant</template>
                        </h2>
                        <Link
                            v-if="d.listings"
                            :href="`/logements?destination=${d.slug}`"
                            class="btn btn--outline btn--sm"
                        >Filtrer cette sélection</Link>
                    </header>

                    <p v-if="demo && d.listings" class="dest__demo">
                        <span class="chip chip--terre">Aperçu</span>
                        Annonces fictives, le temps que les premiers propriétaires publient.
                    </p>

                    <div v-if="fiche.listings.length" class="dest__grid" data-anim-group>
                        <ListingCard
                            v-for="l in fiche.listings"
                            :key="l.slug"
                            :listing="l"
                            :photos="photos"
                        />
                    </div>

                    <!-- L'état vide est le cas majoritaire : il occupe la
                         place de la grille au lieu de s'excuser. -->
                    <div v-else class="dest__empty">
                        <h3 class="display display--sm dest__empty-title">
                            On ira le chercher pour vous.
                        </h3>
                        <p class="lede dest__empty-lede">
                            Aucun propriétaire n'a encore publié à {{ d.name }}. Dites-nous ce
                            que vous cherchez et à quelles dates : nous démarchons les
                            propriétaires sur place, nous vérifions ce qu'ils proposent, et
                            nous vous répondons.
                        </p>
                        <div class="dest__empty-actions">
                            <a href="/#demande" class="btn btn--terre btn--lg">Déposer une demande</a>
                            <Link href="/destinations" class="btn btn--outline">Voir les autres destinations</Link>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <SiteFooter :credits="credits" />
    </div>
</template>

<style scoped>
.page { overflow-x: clip; }

.dest {
    padding-top: calc(var(--header-h) + clamp(1.25rem, 3vw, 2.25rem));
    padding-bottom: clamp(4rem, 8vw, 7rem);
}

.dest__crumbs {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
    font-size: .82rem;
    font-weight: 600;
    color: var(--text-3);
}
.dest__crumb { color: var(--text-2); text-decoration: none; }
.dest__crumb:hover { color: var(--terre-600); text-decoration: underline; }

/* ── Ouverture ─────────────────────────────────────────────────── */
.dest__hero {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
    margin-top: 1rem;
}

.dest__title { margin: 0; }
.dest__tagline { margin: .7rem 0 0; max-width: 34ch; }
.dest__ribbon { margin-top: clamp(1.75rem, 3.5vw, 2.5rem); }

.dest__stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
    gap: 1.4rem 2rem;
    margin: clamp(1.75rem, 4vw, 2.75rem) 0 0;
    padding: 1.5rem 0;
    border-top: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
}

.dest__stats dt {
    font-size: .71rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .11em;
    color: var(--text-3);
}
.dest__stats dd {
    margin: .25rem 0 0;
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.035em;
    color: var(--ink);
}
/* `capitalize` mettait une majuscule à chaque mot — « Nord Et Nosy Be »,
   « Mai → Octobre ». En français seule l'initiale se capitalise. */
.dest__stat--price,
.dest__stat--txt { font-size: 1.05rem; letter-spacing: -.02em; }
.dest__stat--txt::first-letter { text-transform: uppercase; }

.dest__map { max-width: 17rem; margin-inline: auto; }

/* ── Photo ─────────────────────────────────────────────────────── */
.dest__media { margin: clamp(2rem, 4vw, 3rem) 0 0; }

.dest__photo {
    display: block;
    width: 100%;
    height: auto;
    aspect-ratio: 21 / 9;
    max-height: 52vh;
    object-fit: cover;
    border-radius: var(--r-xl);
    background: var(--off-2);
    box-shadow: var(--sh-1);
}

.dest__credit {
    margin-top: .6rem;
    font-size: .76rem;
    color: var(--text-3);
}
.dest__credit a { color: inherit; }

/* ── Sections ──────────────────────────────────────────────────── */
.dest__section { margin-top: clamp(3rem, 6vw, 4.5rem); }

.dest__h2 {
    margin: 0 0 1.25rem;
    font-size: clamp(1.35rem, 2.4vw, 1.6rem);
    font-weight: 800;
    letter-spacing: -.035em;
    color: var(--ink);
}

.dest__grid-head {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: space-between;
    gap: .75rem 1.5rem;
}
.dest__grid-head .dest__h2 { margin-bottom: 0; }

.dest__demo {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .6rem;
    margin: 1rem 0 0;
    font-size: .82rem;
    color: var(--text-3);
}

.dest__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr));
    gap: clamp(1.6rem, 3vw, 2.4rem) clamp(1.2rem, 2vw, 1.8rem);
    margin-top: 1.75rem;
}

.dest__empty {
    max-width: 40rem;
    margin: 2.5rem 0 0;
    padding: clamp(2rem, 5vw, 3rem);
    border: 1px solid var(--line);
    border-radius: var(--r-lg);
    background: var(--off);
}
.dest__empty-title { margin: 0; }
.dest__empty-lede { margin: 1rem 0 0; }
.dest__empty-actions { display: flex; flex-wrap: wrap; gap: .7rem; margin-top: 1.9rem; }

@media (min-width: 900px) {
    .dest__hero { grid-template-columns: minmax(0, 1fr) 17rem; align-items: start; gap: clamp(2rem, 5vw, 4rem); }
    .dest__map { align-self: center; }
    .dest__map { margin-inline: 0; }
}
</style>
