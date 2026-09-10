<script setup>
/**
 * Les séjours acceptés à venir. Bloc de lecture : aucun bouton.
 *
 * Le téléphone du voyageur est cliquable — c'est le seul geste utile ici, et
 * il se fait depuis le téléphone du propriétaire, jamais depuis Vayla : la
 * plateforme met en relation, elle ne s'interpose pas.
 */
import { nombre } from '@/Support/format.js'
import { formatCompact } from '@/Composables/useStayDates.js'

defineProps({
    stays: { type: Array, default: () => [] },
})
</script>

<template>
    <section class="up">
        <h2 class="up__title">Séjours à venir</h2>

        <p v-if="!stays.length" class="up__empty">Aucun séjour accepté pour l'instant.</p>

        <ul v-else class="up__list">
            <li v-for="s in stays" :key="s.reference" class="up__row">
                <div class="up__when">
                    <span class="up__dates num">{{ formatCompact(s.arrival) }} → {{ formatCompact(s.departure) }}</span>
                    <span class="up__nights num">{{ s.nights }} nuit{{ s.nights > 1 ? 's' : '' }}</span>
                </div>
                <div class="up__who">
                    <span class="up__name">{{ s.traveller }}</span>
                    <a class="up__tel" :href="`tel:${s.phone}`">{{ s.phone }}</a>
                </div>
                <p class="up__listing">{{ s.listing }}</p>
                <p class="up__total num">{{ nombre(s.total) }} Ar</p>
            </li>
        </ul>
    </section>
</template>

<style scoped>
.up__title { margin: 0 0 1rem; font-size: 1.3rem; font-weight: 800; letter-spacing: -.032em; color: var(--ink); }

.up__empty {
    margin: 0;
    padding: 1.25rem;
    border: 1px dashed var(--line-2);
    border-radius: var(--r-lg);
    text-align: center;
    font-size: .9rem;
    color: var(--text-3);
}

.up__list { margin: 0; padding: 0; list-style: none; border: 1px solid var(--line-2); border-radius: var(--r-lg); overflow: hidden; }
.up__row {
    display: grid;
    gap: .2rem 1.25rem;
    padding: .95rem 1.15rem;
    background: var(--white);
}
.up__row + .up__row { border-top: 1px solid var(--line); }

.up__when { display: flex; align-items: baseline; gap: .6rem; }
.up__dates { font-size: .95rem; font-weight: 800; color: var(--ink); }
.up__nights { font-size: .78rem; font-weight: 600; color: var(--text-3); }

.up__who { display: flex; align-items: baseline; gap: .6rem; flex-wrap: wrap; }
.up__name { font-size: .9rem; font-weight: 600; color: var(--text-2); }
.up__tel { font-size: .86rem; font-weight: 600; color: var(--terre-600); }

.up__listing { margin: 0; font-size: .82rem; color: var(--text-3); }
.up__total { margin: 0; font-size: .9rem; font-weight: 700; color: var(--ink); }

@media (min-width: 720px) {
    .up__row { grid-template-columns: 15rem minmax(0, 1fr) auto; align-items: center; }
    .up__when { grid-row: 1; }
    .up__who { grid-row: 1; grid-column: 2; }
    .up__listing { grid-row: 2; grid-column: 1 / 3; }
    .up__total { grid-row: 1 / 3; grid-column: 3; text-align: right; font-size: 1rem; }
}
</style>
