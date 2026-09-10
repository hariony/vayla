<script setup>
/**
 * Rail de catégories, juste au-dessus de la grille : le geste de tri le
 * plus rapide, un cran avant le moteur de recherche. Défile à l'horizontale
 * sans ascenseur visible.
 */
defineProps({
    categories: { type: Array, default: () => [] },
    active: { type: String, default: 'all' },
})

defineEmits(['select'])

// Pictogrammes au trait, dessinés dans la même grille de 24 px.
const ICONS = {
    sparkle: 'M12 3.5l1.9 5.1 5.1 1.9-5.1 1.9L12 17.5l-1.9-5.1L5 10.5l5.1-1.9z',
    wave: 'M3 9.5c2.2-2 4.3-2 6.5 0s4.3 2 6.5 0 4.3-2 5 -.6M3 15c2.2-2 4.3-2 6.5 0s4.3 2 6.5 0 4.3-2 5-.6',
    drop: 'M12 3.5c3.4 4 5.5 6.6 5.5 9.3a5.5 5.5 0 0 1-11 0c0-2.7 2.1-5.3 5.5-9.3z',
    peak: 'M2.5 19h19L14 5.5 9.8 13 7.5 9.8z',
    leaf: 'M20 4c0 8.5-4.4 13-11 13H5c0-8 4.6-13 11-13zM5 20c2.5-5 5.5-7.8 9.5-9.8',
    city: 'M3.5 20V9.5l6-3.5v4l5-2.5V20M3.5 20h17M7 20v-3.5h3V20M14.5 12.5h3v7.5',
    group: 'M3.5 19.5c0-3 2.4-4.8 5-4.8s5 1.8 5 4.8M8.5 11.6a3.3 3.3 0 1 0 0-6.6 3.3 3.3 0 0 0 0 6.6M16 15.2c2.5 0 4.5 1.7 4.5 4.3M16.4 11.4a2.9 2.9 0 1 0 0-5.8',
    check: 'M4.5 12.5l5 5 10-10',
}
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

.rail__label {
    font-size: .76rem;
    font-weight: 700;
    letter-spacing: -.008em;
    white-space: nowrap;
}
</style>
