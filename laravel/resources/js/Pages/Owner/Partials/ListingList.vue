<script setup>
/**
 * Les logements du propriétaire, avec leur niveau de vérification et l'accès
 * à leur calendrier.
 *
 * Le niveau n'est pas décoratif ici : c'est ce que le propriétaire peut faire
 * monter, et donc ce qui décide de sa visibilité. Chaque carte renvoie à la
 * fiche publique — voir son annonce comme un voyageur la voit est le premier
 * réflexe, et il n'existait nulle part.
 *
 * **Le calendrier est un bouton distinct, pas la carte entière.** Une carte
 * cliquable qui mène à deux endroits différents selon la zone touchée est
 * exactement le genre d'interface qui se rate au doigt. La photo, le nom et
 * le prix ouvrent l'annonce ; le bouton ouvre le calendrier, et il dit
 * combien de périodes y sont déjà fermées — un bouton dont on ne sait pas ce
 * qu'il y a derrière ne se presse pas.
 */
import { Link } from '@inertiajs/vue3'

import TrustGauge from '@/Components/TrustGauge.vue'
import { nombre } from '@/Support/format.js'
import { photoSrc, photoSrcset } from '@/Support/photo.js'

defineProps({
    listings: { type: Array, default: () => [] },
})

const periodes = (n) => (n ? `${n} période${n > 1 ? 's' : ''} fermée${n > 1 ? 's' : ''}` : 'Aucune date fermée')
</script>

<template>
    <section class="ol">
        <h2 class="espace__section ol__title">Mes logements</h2>

        <ul class="ol__list">
            <li v-for="l in listings" :key="l.slug" class="ol__card">
                <Link :href="`/logements/${l.slug}`" class="ol__link">
                    <span class="ol__media">
                        <img
                            v-if="l.photo"
                            :src="photoSrc(l.photo, 800)"
                            :srcset="photoSrcset(l.photo)"
                            sizes="88px"
                            :alt="l.title"
                            width="800"
                            height="600"
                            loading="lazy"
                            decoding="async"
                        >
                    </span>

                    <span class="ol__body">
                        <span class="ol__name">{{ l.title }}</span>
                        <span class="ol__place">{{ l.place }}</span>
                        <span class="ol__trust" :class="`ol__trust--n${l.trust}`">
                            <TrustGauge :level="l.trust" />
                            {{ l.trustName }}
                        </span>
                    </span>

                    <span class="ol__price num">{{ nombre(l.price) }} Ar<span class="ol__unit"> / nuit</span></span>
                </Link>

                <div class="ol__acts">
                    <span class="ol__count">{{ periodes(l.blocked) }}</span>
                    <Link
                        class="btn btn--outline ol__cal"
                        :href="`/proprietaire/logements/${l.slug}/calendrier`"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="16" rx="2.5" />
                            <path d="M3 10h18M8 3v4M16 3v4" />
                        </svg>
                        Gérer le calendrier
                    </Link>
                </div>
            </li>
        </ul>
    </section>
</template>

<style scoped>
.ol__title { margin: 0 0 1rem; }

.ol__list { margin: 0; padding: 0; list-style: none; display: grid; gap: .7rem; }

.ol__card { border: 1px solid var(--line-2); border-radius: var(--r-lg); background: var(--white); overflow: hidden; }

.ol__link {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 1rem;
    padding: .8rem 1.1rem .8rem .8rem;
    text-decoration: none;
    transition: background-color .25s var(--ease);
}
.ol__link:hover { background: var(--off); }

.ol__media {
    display: block;
    width: 5.5rem;
    aspect-ratio: 4 / 3;
    border-radius: var(--r-md);
    overflow: hidden;
    background: var(--off-2);
}
.ol__media img { display: block; width: 100%; height: 100%; object-fit: cover; }

.ol__body { display: flex; flex-direction: column; gap: .15rem; min-width: 0; }
.ol__name { font-size: .98rem; font-weight: 800; letter-spacing: -.022em; color: var(--ink); }
.ol__place { font-size: .82rem; color: var(--text-3); }

.ol__trust { display: inline-flex; align-items: center; gap: .4rem; margin-top: .25rem; font-size: .78rem; font-weight: 700; color: var(--text-3); }
.ol__trust--n2, .ol__trust--n3 { color: var(--lagon-600); }
.ol__trust--n4 { color: var(--lagon-700); }

.ol__price { font-size: .95rem; font-weight: 800; color: var(--ink); white-space: nowrap; }
.ol__unit { font-size: .76rem; font-weight: 500; color: var(--text-3); }

.ol__acts {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .6rem;
    padding: .7rem 1.1rem .8rem;
    border-top: 1px solid var(--line);
    background: var(--off);
}
.ol__count { font-size: .82rem; font-weight: 600; color: var(--text-2); }
.ol__cal { display: inline-flex; align-items: center; gap: .45rem; }
.ol__cal svg { width: 1.05rem; height: 1.05rem; }
</style>
