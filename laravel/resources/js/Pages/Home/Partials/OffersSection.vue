<script setup>
/**
 * La grille. Elle vient immediatement apres le moteur : c'est le pari de
 * composition de la page.
 *
 * Le titre du rail est editorial, jamais statistique — cette position a
 * vocation a etre vendue, et « populaire » deviendrait alors un mensonge
 * mesurable sur un site dont toute la promesse est la verification.
 */
import { Link } from '@inertiajs/vue3'
import CategoryRail from '@/Components/CategoryRail.vue'
import ListingCard from '@/Components/ListingCard.vue'

defineProps({
    categories: { type: Array, default: () => [] },
    category: { type: String, default: 'all' },
    results: { type: Array, default: () => [] },
    photos: { type: Object, default: () => ({}) },
    /** Le séjour du moteur, reporté sur les fiches ouvertes depuis la grille. */
    stay: { type: Object, default: null },
    demo: { type: Boolean, default: false },
    searchLabel: { type: String, default: '' },
})

defineEmits(['update:category', 'reset'])
</script>

<template>
    <!-- ═══════════ OFFRES ═══════════ -->
    <section id="offres" class="offers section section--flush">
        <div class="shell">
            <!--
                Le titre du rail est éditorial, pas statistique : « en ce
                moment » reste vrai le jour où une place du rail sera
                vendue, là où « populaire » ou « les plus visités »
                deviendraient un mensonge mesurable — insoutenable sur un
                site dont toute la promesse est la vérification.
            -->
            <header class="offers__head">
                <h2 class="display display--md offers__title" data-anim>
                    Les envies du moment
                </h2>
                <p class="offers__hint" data-anim>Choisissez une envie, la sélection suit.</p>
            </header>

            <CategoryRail
                :categories="categories"
                :active="category"
                @select="$emit('update:category', $event)"
            />

            <div class="offers__meta">
                <p class="offers__count">
                    <strong class="num">{{ results.length }}</strong>
                    {{ results.length > 1 ? 'logements' : 'logement' }}
                    <span class="offers__where">{{ searchLabel }}</span>
                </p>

                <p v-if="demo" class="offers__demo">
                    <span class="chip chip--terre">Aperçu</span>
                    Annonces fictives, le temps que les premiers propriétaires publient.
                </p>
            </div>

            <div v-if="results.length" class="offers__grid" data-anim-group>
                <ListingCard
                    v-for="(l, i) in results"
                    :key="l.slug"
                    :listing="l"
                    :photos="photos"
                    :stay="stay"
                    :wide="i === 0 && l.featured"
                    class="offers__cell"
                    :class="{ 'offers__cell--wide': i === 0 && l.featured }"
                />
            </div>

            <!-- L'accueil montre une sélection ; le catalogue porte les
                 filtres et la pagination. -->
            <p v-if="results.length" class="offers__more">
                <Link href="/logements" class="btn btn--outline">
                    Tous les logements, avec les filtres
                </Link>
            </p>

            <div v-else class="offers__empty">
                <h3 class="display display--md">Rien ici. C'est exactement pour ça qu'on existe.</h3>
                <p class="lede offers__empty-lede">
                    Décrivez ce que vous cherchez : on va le chercher auprès des
                    propriétaires, on le vérifie, et on vous répond.
                </p>
                <div class="offers__empty-actions">
                    <a href="#demande" class="btn btn--terre">Déposer une demande</a>
                    <button type="button" class="btn btn--outline" @click="$emit('reset')">
                        Voir tous les logements
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* ═══════════ OFFRES ═══════════ */

.offers__more { margin: 2.5rem 0 0; text-align: center; }

.offers { padding-top: clamp(1.5rem, 3vw, 2.5rem); }

.offers__head {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: .35rem 1rem;
    margin-bottom: clamp(1rem, 2vw, 1.5rem);
}

.offers__title { margin: 0; }

.offers__hint {
    margin: 0;
    font-size: .9rem;
    font-weight: 500;
    color: var(--text-3);
}

.offers__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem 2rem;
    padding: 1.25rem 0 1.5rem;
    border-bottom: 1px solid var(--line);
    margin-bottom: clamp(1.5rem, 3vw, 2.25rem);
}

.offers__count { margin: 0; font-size: .95rem; font-weight: 600; color: var(--text-2); }
.offers__count strong { font-size: 1.1rem; font-weight: 800; color: var(--ink); }
.offers__where { color: var(--terre-600); }

.offers__demo {
    display: inline-flex;
    align-items: center;
    gap: .6rem;
    margin: 0;
    font-size: .82rem;
    font-weight: 500;
    color: var(--text-3);
}

.offers__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(255px, 1fr));
    gap: clamp(1.5rem, 2.4vw, 2.25rem) clamp(1rem, 1.8vw, 1.75rem);
}

@media (min-width: 900px) {
    .offers__cell--wide { grid-column: span 2; }
}

.offers__empty {
    max-width: 46ch;
    padding: clamp(2.5rem, 6vw, 4.5rem) 0;
}
.offers__empty-lede { margin: 1rem 0 2rem; }
.offers__empty-actions { display: flex; flex-wrap: wrap; gap: .75rem; }
</style>
