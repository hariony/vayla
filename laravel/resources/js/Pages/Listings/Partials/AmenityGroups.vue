<script setup>
/**
 * Les équipements de la fiche, par rubrique.
 *
 * Repli progressif plutôt qu'une fenêtre modale : la liste complète s'ouvre
 * **dans le flux**, sans piéger le clavier ni casser le retour arrière. Un
 * logement à soixante équipements ne doit pas noyer la fiche, mais il ne doit
 * pas non plus cacher ce qu'il a derrière une porte.
 *
 * La précision du propriétaire — « Démarrage automatique », « Deux kilomètres
 * de sable depuis la route d'Ifaty » — est ce qui distingue une annonce
 * remplie d'une annonce recopiée. Elle est donc affichée, pas résumée.
 *
 * Les rubriques vides ne sont jamais servies par le serveur : un titre suivi
 * de rien serait pire que rien.
 */
import { computed, ref } from 'vue'
import AmenityIcon from '@/Components/AmenityIcon.vue'

const props = defineProps({
    groups: { type: Array, default: () => [] },
    apercu: { type: Number, default: 12 },
})

const deplie = ref(false)

const total = computed(() =>
    props.groups.reduce((n, g) => n + g.amenities.length, 0)
)

/** En aperçu, on coupe sur le nombre d'équipements, pas sur le nombre de rubriques. */
const visibles = computed(() => {
    if (deplie.value) return props.groups

    const out = []
    let reste = props.apercu

    for (const g of props.groups) {
        if (reste <= 0) break
        out.push({ ...g, amenities: g.amenities.slice(0, reste) })
        reste -= g.amenities.length
    }

    return out
})

const caches = computed(() =>
    total.value - visibles.value.reduce((n, g) => n + g.amenities.length, 0)
)
</script>

<template>
    <div class="ag">
        <TransitionGroup name="ag" tag="div" class="ag__grid">
            <section v-for="g in visibles" :key="g.key" class="ag__group">
                <h3 class="ag__title">{{ g.label }}</h3>
                <p v-if="g.note" class="ag__note">{{ g.note }}</p>

                <ul class="ag__list">
                    <li v-for="a in g.amenities" :key="a.key" class="ag__item">
                        <AmenityIcon :name="a.icon" class="ag__icon" />
                        <span class="ag__txt">
                            <span class="ag__label">{{ a.label }}</span>
                            <span v-if="a.note" class="ag__precision">{{ a.note }}</span>
                        </span>
                    </li>
                </ul>
            </section>
        </TransitionGroup>

        <button
            v-if="caches > 0 || deplie"
            type="button"
            class="btn btn--outline ag__more"
            @click="deplie = !deplie"
        >
            <template v-if="deplie">Réduire la liste</template>
            <template v-else>Afficher les {{ total }} équipements</template>
        </button>
    </div>
</template>

<style scoped>
.ag__grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.9rem clamp(1.75rem, 4vw, 3.25rem);
}

.ag__title {
    margin: 0;
    font-size: .74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: var(--text-3);
}

.ag__note {
    margin: .5rem 0 0;
    max-width: 44ch;
    font-size: .81rem;
    line-height: 1.55;
    color: var(--text-3);
}

.ag__list {
    display: flex;
    flex-direction: column;
    gap: .5rem;
    margin: .85rem 0 0;
    padding: 0;
    list-style: none;
}

.ag__item { display: flex; align-items: flex-start; gap: .6rem; }

.ag__icon { margin-top: .1rem; color: var(--text-3); }

.ag__txt { display: flex; flex-direction: column; gap: .05rem; min-width: 0; }

.ag__label {
    font-size: .92rem;
    font-weight: 500;
    line-height: 1.35;
    color: var(--text);
}

/* La précision du propriétaire, en retrait : elle complète, elle ne titre pas. */
.ag__precision {
    font-size: .8rem;
    line-height: 1.4;
    color: var(--text-3);
}

.ag__more { margin-top: 2rem; }

/* Le dépliage entre en fondu court : la liste s'allonge, elle ne surgit pas. */
.ag-enter-active { transition: opacity .35s var(--ease), transform .35s var(--ease); }
.ag-enter-from { opacity: 0; transform: translateY(8px); }
.ag-leave-active { transition: opacity .18s var(--ease); position: absolute; }
.ag-leave-to { opacity: 0; }

@media (min-width: 700px) {
    .ag__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (min-width: 1240px) {
    .ag__grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (prefers-reduced-motion: reduce) {
    .ag-enter-active, .ag-leave-active { transition: none; }
}
</style>
