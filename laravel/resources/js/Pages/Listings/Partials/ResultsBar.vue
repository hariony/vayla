<script setup>
/**
 * La barre au-dessus de la grille : ce qu'on regarde, et dans quel ordre.
 *
 * Le compte est le premier élément — sur un catalogue filtrable, la question
 * qui précède toutes les autres est « combien ». Le bandeau « Aperçu » y est
 * solidaire du drapeau serveur, exactement comme sur l'accueil.
 */
defineProps({
    total: { type: Number, default: 0 },
    catalogue: { type: Number, default: 0 },
    sorts: { type: Array, default: () => [] },
    sort: { type: String, default: 'confiance' },
    demo: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
})

defineEmits(['update:sort'])
</script>

<template>
    <div class="rb">
        <div class="rb__left">
            <p class="rb__count" :class="{ 'is-loading': loading }">
                <strong class="num">{{ total }}</strong>
                {{ total > 1 ? 'logements' : 'logement' }}
                <span v-if="total < catalogue" class="rb__of num">sur {{ catalogue }}</span>
            </p>

            <p v-if="demo" class="rb__demo">
                <span class="chip chip--terre">Aperçu</span>
                Annonces fictives, le temps que les premiers propriétaires publient.
            </p>
        </div>

        <label class="rb__sort">
            <span class="sr-only">Trier les résultats</span>
            <select
                class="rb__select"
                :value="sort"
                @change="$emit('update:sort', $event.target.value)"
            >
                <option v-for="s in sorts" :key="s.key" :value="s.key">{{ s.label }}</option>
            </select>
        </label>
    </div>
</template>

<style scoped>
.rb {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem 1.5rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid var(--line);
}

.rb__left { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1rem; }

.rb__count {
    margin: 0;
    font-size: .92rem;
    font-weight: 500;
    color: var(--text-2);
    transition: opacity .25s var(--ease);
}
.rb__count.is-loading { opacity: .45; }
.rb__count strong { font-size: 1.06rem; font-weight: 800; letter-spacing: -.03em; color: var(--ink); }
.rb__of { font-size: .84rem; color: var(--text-3); }

.rb__demo {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    margin: 0;
    font-size: .8rem;
    color: var(--text-3);
}

.rb__select {
    padding: .5rem 2rem .5rem .8rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font: inherit;
    font-size: .84rem;
    font-weight: 600;
    color: var(--ink);
    cursor: pointer;
}
.rb__select:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 1px; }
</style>
