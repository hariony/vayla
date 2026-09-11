<script setup>
/**
 * Rail de catégories, juste au-dessus de la grille : le geste de tri le
 * plus rapide, un cran avant le moteur de recherche. Défile à l'horizontale
 * sans ascenseur visible.
 */
import { ICONES_CATEGORIES as ICONS } from '@/Support/categoryIcons.js'

defineProps({
    categories: { type: Array, default: () => [] },
    active: { type: String, default: 'all' },
})

defineEmits(['select'])
</script>

<template>
    <div class="rail no-bar" role="tablist" aria-label="Catégories de logements">
        <button
            v-for="c in categories"
            :key="c.key"
            type="button"
            role="tab"
            class="rail__item"
            :class="{ 'is-active': active === c.key }"
            :aria-selected="active === c.key"
            @click="$emit('select', c.key)"
        >
            <svg class="rail__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path :d="ICONS[c.icon] ?? ICONS.sparkle" />
            </svg>
            <span class="rail__label">{{ c.label }}</span>
            <!-- **Une place achetée se dit.** Le titre du rail est éditorial ;
                 si une position est vendue — à un propriétaire, à un office de
                 tourisme —, la taire ferait du rail un classement déguisé, sur
                 un site dont toute la promesse est la vérification. -->
            <span v-if="c.sponsored" class="rail__sponso">Sponsorisé</span>
        </button>
    </div>
</template>

<style scoped>
.rail {
    display: flex;
    gap: .35rem;
    overflow-x: auto;
    padding-bottom: .35rem;
    margin-inline: calc(var(--gutter) * -1);
    padding-inline: var(--gutter);
    scroll-snap-type: x proximity;
}

.rail__item {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: .38rem;
    flex: none;
    padding: .7rem 1rem .6rem;
    font-family: inherit;
    background: transparent;
    border: 0;
    border-bottom: 2px solid transparent;
    border-radius: var(--r-sm) var(--r-sm) 0 0;
    color: var(--text-3);
    cursor: pointer;
    scroll-snap-align: start;
    transition: color .25s, border-color .3s, background-color .25s;
}

.rail__item:hover { color: var(--ink); background: var(--off); }
.rail__item:focus-visible { outline: 2px solid var(--terre-500); outline-offset: -2px; }

.rail__item.is-active {
    color: var(--terre-600);
    border-bottom-color: var(--terre-500);
}

.rail__icon { width: 22px; height: 22px; }

.rail__sponso {
    margin-top: -.2rem;
    font-size: .6rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--text-3);
}

.rail__label {
    font-size: .76rem;
    font-weight: 700;
    letter-spacing: -.008em;
    white-space: nowrap;
}
</style>
