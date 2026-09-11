<script setup>
/**
 * La pagination d'une liste : où l'on en est, combien il y en a, et deux
 * boutons bordés. Pas de rangée de numéros — dans une file de travail on
 * avance, on ne saute pas à la page 7.
 */
import { Link } from '@inertiajs/vue3'

import OfficeIcon from '@/Components/OfficeIcon.vue'
import { nombre } from '@/Support/format.js'

defineProps({
    meta: { type: Object, required: true },
    /** « annonce », « réservation »… au singulier. */
    unite: { type: String, default: 'résultat' },
})

const pluriel = (n, mot) => `${nombre(n)} ${mot}${n > 1 ? 's' : ''}`
</script>

<template>
    <nav v-if="meta.total" class="op" aria-label="Pagination">
        <p class="op__ou">
            {{ pluriel(meta.total, unite) }}<template v-if="meta.pages > 1"> · page {{ meta.page }} sur {{ meta.pages }}</template>
        </p>

        <div v-if="meta.pages > 1" class="op__btns">
            <Link v-if="meta.precedente" :href="meta.precedente" class="op__b" preserve-scroll>
                <OfficeIcon name="retour" /> Précédente
            </Link>
            <span v-else class="op__b" aria-disabled="true"><OfficeIcon name="retour" /> Précédente</span>

            <Link v-if="meta.suivante" :href="meta.suivante" class="op__b" preserve-scroll>
                Suivante <OfficeIcon name="avant" />
            </Link>
            <span v-else class="op__b" aria-disabled="true">Suivante <OfficeIcon name="avant" /></span>
        </div>
    </nav>
</template>

<style scoped>
.op {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    margin-top: 1rem;
}
.op__ou { margin: 0; font-size: .82rem; color: var(--text-3); }
.op__btns { display: flex; gap: .4rem; }

.op__b {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    min-height: 2.75rem;
    padding: .4rem 1rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font-size: .84rem;
    font-weight: 700;
    color: var(--ink);
    text-decoration: none;
}
.op__b .oi { width: 1rem; height: 1rem; }
a.op__b:hover { border-color: var(--ink); }
a.op__b:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }

/* Désactivé : il se lit, il ne s'efface pas — neutre, sans curseur de main. */
.op__b[aria-disabled='true'] { background: var(--off-2); color: var(--ink-3); cursor: not-allowed; }
</style>
