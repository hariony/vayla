<script setup>
/**
 * Pagination.
 *
 * Des numéros, pas un « charger plus » : l'URL doit rester adressable, et
 * une page 3 partagée doit rouvrir la page 3. La fenêtre glissante évite la
 * file de vingt boutons le jour où le catalogue grandit.
 */
import { computed } from 'vue'

const props = defineProps({
    page: { type: Number, default: 1 },
    pages: { type: Number, default: 1 },
})

defineEmits(['go'])

const window = computed(() => {
    const { page, pages } = props
    const from = Math.max(1, Math.min(page - 2, pages - 4))
    const to = Math.min(pages, Math.max(page + 2, 5))

    return Array.from({ length: to - from + 1 }, (_, i) => from + i)
})
</script>

<template>
    <nav v-if="pages > 1" class="pg" aria-label="Pages de résultats">
        <button
            type="button"
            class="pg__step"
            :disabled="page <= 1"
            @click="$emit('go', page - 1)"
        >
            <span aria-hidden="true">←</span> Précédent
        </button>

        <ol class="pg__list">
            <li v-for="p in window" :key="p">
                <button
                    type="button"
                    class="pg__num num"
                    :class="{ 'is-on': p === page }"
                    :aria-current="p === page ? 'page' : undefined"
                    @click="$emit('go', p)"
                >{{ p }}</button>
            </li>
        </ol>

        <button
            type="button"
            class="pg__step"
            :disabled="page >= pages"
            @click="$emit('go', page + 1)"
        >
            Suivant <span aria-hidden="true">→</span>
        </button>
    </nav>
</template>

<style scoped>
.pg {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: .4rem 1rem;
    margin-top: 3rem;
}

.pg__list { display: flex; gap: .25rem; margin: 0; padding: 0; list-style: none; }

.pg__step, .pg__num {
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font: inherit;
    font-size: .84rem;
    font-weight: 600;
    color: var(--text-2);
    cursor: pointer;
    transition: border-color .25s var(--ease), color .25s var(--ease);
}
.pg__step { padding: .5rem 1rem; }
.pg__num { min-width: 2.25rem; padding: .5rem .55rem; }

.pg__step:hover:not(:disabled), .pg__num:hover { border-color: var(--ink); color: var(--ink); }
.pg__step:disabled { opacity: .4; cursor: not-allowed; }

.pg__num.is-on {
    border-color: var(--ink);
    background: var(--ink);
    color: var(--white);
}
</style>
