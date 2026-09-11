<script setup>
/**
 * L'en-tête d'un écran du back-office : où l'on est, ce qu'on y fait, et les
 * gestes de l'écran à droite.
 *
 * Le titre prend l'échelle des écrans de travail (`.espace__titre`), jamais
 * celle des pages de vente : on ouvre ces écrans vingt fois par jour, un titre
 * de soixante pixels repousserait la liste hors de l'écran à chaque fois.
 */
import { Link } from '@inertiajs/vue3'

import OfficeIcon from '@/Components/OfficeIcon.vue'

defineProps({
    kicker: { type: String, default: '' },
    titre: { type: String, required: true },
    lede: { type: String, default: '' },
    /** `{ href, label }` — le retour vers la liste, pour un écran de détail. */
    back: { type: Object, default: null },
})
</script>

<template>
    <header class="oh" data-reveal>
        <Link v-if="back" :href="back.href" class="oh__back">
            <OfficeIcon name="retour" />
            {{ back.label }}
        </Link>

        <div class="oh__ligne">
            <div class="oh__txt">
                <p v-if="kicker" class="of-kicker">{{ kicker }}</p>
                <h1 class="espace__titre oh__titre">{{ titre }}</h1>
                <p v-if="lede || $slots.lede" class="espace__lede">
                    <slot name="lede">{{ lede }}</slot>
                </p>
            </div>

            <div v-if="$slots.actions" class="oh__actions">
                <slot name="actions" />
            </div>
        </div>
    </header>
</template>

<style scoped>
.oh { margin-bottom: clamp(1.25rem, 3vw, 1.75rem); }

/* Un retour qui se voit comme un bouton : bordé, plein, à hauteur de doigt. */
.oh__back {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    min-height: 2.5rem;
    margin-bottom: 1rem;
    padding: .4rem .95rem .4rem .65rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font-size: .82rem;
    font-weight: 700;
    color: var(--ink);
    text-decoration: none;
    transition: background-color .2s var(--ease), border-color .2s var(--ease);
}
.oh__back:hover { border-color: var(--ink); }
.oh__back:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }
.oh__back .oi { width: 1rem; height: 1rem; }

.oh__ligne {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem 1.5rem;
}
.oh__txt { min-width: 0; max-width: 46rem; }
.oh__titre { margin-top: .3rem; overflow-wrap: anywhere; }

.oh__actions { display: flex; flex-wrap: wrap; gap: .5rem; }
</style>
