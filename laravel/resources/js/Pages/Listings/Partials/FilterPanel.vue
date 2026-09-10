<script setup>
/**
 * Le panneau de filtres.
 *
 * Il suit l'ordre dans lequel on cherche un logement, pas l'ordre du schéma :
 * où, pour combien de personnes, à quel prix — puis, seulement ensuite, le
 * niveau de vérification et les équipements.
 *
 * Le niveau de confiance est un filtre à part entière et non une option
 * cachée : c'est la seule chose que Vayla apporte qu'un mur de photos
 * n'apporte pas. Il est donc placé avant les équipements, et il est le seul
 * bloc à porter le lagon.
 */
import { computed } from 'vue'
import TrustGauge from '@/Components/TrustGauge.vue'
import { nombre } from '@/Support/format.js'
import { useDevise } from '@/Composables/useDevise.js'
import { formatCompact } from '@/Composables/useStayDates.js'

const props = defineProps({
    state: { type: Object, required: true },
    destinations: { type: Array, default: () => [] },
    trustLevels: { type: Array, default: () => [] },
    amenityFilters: { type: Array, default: () => [] },
    facets: { type: Object, default: () => ({}) },
    activeCount: { type: Number, default: 0 },
})

const emit = defineEmits(['apply', 'toggle-amenity', 'reset', 'clear-dates'])

const money = nombre

// Le budget est justement ce qu'un voyageur étranger ne sait pas poser en
// ariary : la borne du curseur se lit dans les deux unités.
const { eurosFourchette } = useDevise()

/** Quatre paliers construits sur les bornes réelles, jamais sur des chiffres ronds inventés. */
const pricePalier = computed(() => {
    const { priceMin = 0, priceMax = 0 } = props.facets
    if (!priceMax || priceMax <= priceMin) return []

    const pas = (priceMax - priceMin) / 4

    return [1, 2, 3].map((i) => Math.round((priceMin + pas * i) / 5000) * 5000)
})

const isAmenity = (key) => props.state.amenities.includes(key)
</script>

<template>
    <form class="fp" aria-label="Filtrer les logements" @submit.prevent>
        <header class="fp__head">
            <h2 class="fp__title">Filtrer</h2>
            <button
                v-if="activeCount"
                type="button"
                class="fp__clear"
                @click="emit('reset')"
            >
                Tout effacer<span class="fp__clear-n num">{{ activeCount }}</span>
            </button>
        </header>

        <!-- Les dates viennent du moteur de la page d'accueil ou d'un lien
             partagé. Elles s'affichent ici pour deux raisons : savoir qu'elles
             sont actives — sinon le catalogue paraît vide sans qu'on comprenne
             pourquoi — et pouvoir les retirer d'un geste. Elles ne se
             modifient pas ici : le calendrier vit sur la fiche et dans le
             moteur, en avoir un troisième donnerait un troisième état. -->
        <div v-if="state.arrival && state.departure" class="fp__group">
            <span class="fp__label">Séjour</span>
            <p class="fp__stay">
                <span class="fp__stay-txt num">
                    {{ formatCompact(state.arrival) }} → {{ formatCompact(state.departure) }}
                </span>
                <button type="button" class="fp__stay-clear" @click="emit('clear-dates')">
                    Retirer
                </button>
            </p>
        </div>

        <div class="fp__group">
            <label class="fp__label" for="fp-dest">Destination</label>
            <select
                id="fp-dest"
                v-model="state.destination"
                class="fp__field"
                @change="emit('apply')"
            >
                <option value="">Partout à Madagascar</option>
                <option v-for="d in destinations" :key="d.slug" :value="d.slug">
                    {{ d.name }}<template v-if="d.listings"> ({{ d.listings }})</template>
                </option>
            </select>
        </div>

        <div class="fp__group">
            <label class="fp__label" for="fp-guests">Voyageurs</label>
            <!-- Une liste, pas un champ de saisie. Un `type="number"` vide
                 portant « Peu importe » demande de comprendre qu'il faut y
                 taper quelque chose ; une liste déroulante se manipule sans
                 rien savoir, et elle est identique au champ « Destination »
                 juste au-dessus. Elle interdit aussi les valeurs absurdes
                 qu'un champ libre acceptait — 0, 200, ou du texte collé. -->
            <select
                id="fp-guests"
                v-model.number="state.guests"
                class="fp__field"
                @change="emit('apply', 0)"
            >
                <option :value="null">Peu importe</option>
                <option v-for="n in 10" :key="n" :value="n">
                    {{ n }} voyageur{{ n > 1 ? 's' : '' }}{{ n === 10 ? ' ou plus' : '' }}
                </option>
            </select>
        </div>

        <div v-if="facets.kinds?.length" class="fp__group">
            <span class="fp__label">Type de logement</span>
            <div class="fp__chips">
                <button
                    type="button"
                    class="chip fp__chip"
                    :class="{ 'is-on': !state.kind }"
                    @click="state.kind = ''; emit('apply')"
                >Tous</button>
                <button
                    v-for="k in facets.kinds"
                    :key="k.key"
                    type="button"
                    class="chip fp__chip"
                    :class="{ 'is-on': state.kind === k.key }"
                    @click="state.kind = state.kind === k.key ? '' : k.key; emit('apply')"
                >{{ k.label }}</button>
            </div>
        </div>

        <div v-if="pricePalier.length" class="fp__group">
            <span class="fp__label">Prix par nuit</span>
            <div class="fp__chips">
                <button
                    type="button"
                    class="chip fp__chip"
                    :class="{ 'is-on': !state.max_price }"
                    @click="state.max_price = null; emit('apply')"
                >Tous</button>
                <button
                    v-for="p in pricePalier"
                    :key="p"
                    type="button"
                    class="chip fp__chip num"
                    :class="{ 'is-on': state.max_price === p }"
                    @click="state.max_price = state.max_price === p ? null : p; emit('apply')"
                >&lt; {{ money(p) }}</button>
            </div>
            <p class="fp__hint num">
                De {{ money(facets.priceMin) }} à {{ money(facets.priceMax) }} Ar
                <span v-if="eurosFourchette(facets.priceMin, facets.priceMax)" class="eur">
                    {{ eurosFourchette(facets.priceMin, facets.priceMax) }}
                </span>
            </p>
        </div>

        <!-- Le bloc de confiance : le seul du panneau à porter le lagon. -->
        <div class="fp__group fp__group--trust">
            <span class="fp__label">Niveau de vérification, au moins</span>
            <div class="fp__ladder">
                <button
                    v-for="t in trustLevels"
                    :key="t.level"
                    type="button"
                    class="fp__rung"
                    :class="{ 'is-on': state.min_trust === t.level }"
                    :aria-pressed="state.min_trust === t.level"
                    @click="state.min_trust = state.min_trust === t.level ? null : t.level; emit('apply')"
                >
                    <TrustGauge :level="t.level" />
                    <span class="fp__rung-txt">{{ t.name }}</span>
                </button>
            </div>
            <p class="fp__hint">
                Le niveau 1 est déclaratif : la vérification commence au niveau 2.
            </p>
        </div>

        <div v-for="g in amenityFilters" :key="g.key" class="fp__group">
            <span class="fp__label">{{ g.label }}</span>
            <p v-if="g.note" class="fp__note">{{ g.note }}</p>
            <ul class="fp__list">
                <li v-for="a in g.amenities" :key="a.key">
                    <label class="fp__check" :class="{ 'is-on': isAmenity(a.key) }">
                        <input
                            type="checkbox"
                            :checked="isAmenity(a.key)"
                            @change="emit('toggle-amenity', a.key)"
                        >
                        <span class="fp__box" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m4 12.5 5 5L20 6.5" />
                            </svg>
                        </span>
                        <span class="fp__check-txt">{{ a.label }}</span>
                    </label>
                </li>
            </ul>
        </div>
    </form>
</template>

<style scoped>
/* Le séjour actif : lu, pas modifié. Le bouton « Retirer » est un vrai
   bouton — un « × » discret aurait été invisible pour qui n'a pas
   l'habitude des interfaces. */
.fp__stay {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .6rem;
    margin: 0;
    padding: .55rem .5rem .55rem .8rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--white);
}
.fp__stay-txt { font-size: .84rem; font-weight: 700; color: var(--ink); }
.fp__stay-clear {
    padding: .3rem .6rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font: inherit;
    font-size: .76rem;
    font-weight: 700;
    color: var(--terre-600);
    cursor: pointer;
}
.fp__stay-clear:hover { border-color: var(--terre-600); background: var(--terre-050); }

.fp {
    display: flex;
    flex-direction: column;
    gap: 1.6rem;
    padding-bottom: 3rem;
}

.fp__head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 1rem;
    padding-bottom: .2rem;
}

.fp__title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: -.03em;
    color: var(--ink);
}

.fp__clear {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: 0;
    border: 0;
    background: none;
    font: inherit;
    font-size: .8rem;
    font-weight: 700;
    color: var(--terre-600);
    cursor: pointer;
}
.fp__clear:hover { text-decoration: underline; }

.fp__clear-n {
    display: grid;
    place-items: center;
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0 .3rem;
    border-radius: var(--r-pill);
    font-size: .68rem;
    color: var(--white);
    background: var(--terre-500);
}

.fp__group {
    display: flex;
    flex-direction: column;
    gap: .55rem;
    padding-top: 1.4rem;
    border-top: 1px solid var(--line);
}
.fp__head + .fp__group { padding-top: 0; border-top: 0; }

.fp__label {
    font-size: .74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .11em;
    color: var(--text-3);
}

.fp__note {
    margin: -.15rem 0 .2rem;
    font-size: .78rem;
    line-height: 1.5;
    color: var(--text-3);
}

.fp__hint {
    margin: .15rem 0 0;
    font-size: .76rem;
    color: var(--text-3);
}

.fp__field {
    width: 100%;
    padding: .68rem .85rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--white);
    font: inherit;
    font-size: .9rem;
    font-weight: 600;
    color: var(--ink);
}
.fp__field:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 1px; }

.fp__chips { display: flex; flex-wrap: wrap; gap: .35rem; }

.fp__chip {
    border: 1px solid var(--line-2);
    background: var(--white);
    color: var(--text-2);
    cursor: pointer;
    transition: border-color .25s var(--ease), color .25s var(--ease), background-color .25s var(--ease);
}
.fp__chip:hover { border-color: var(--ink); color: var(--ink); }
.fp__chip.is-on {
    border-color: var(--ink);
    background: var(--ink);
    color: var(--white);
}

/* ── Échelle de confiance ─────────────────────────────────────────────
   Le seul bloc du panneau au lagon : c'est ce que Vayla vend. */
.fp__ladder { display: flex; flex-direction: column; gap: .3rem; }

.fp__rung {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .5rem .6rem;
    border: 1px solid transparent;
    border-radius: var(--r-md);
    background: none;
    font: inherit;
    text-align: left;
    cursor: pointer;
    transition: background-color .25s var(--ease), border-color .25s var(--ease);
}
.fp__rung:hover { background: var(--off-2); }
.fp__rung.is-on {
    border-color: var(--lagon-500);
    background: color-mix(in srgb, var(--lagon-500) 8%, transparent);
}

.fp__rung-txt {
    font-size: .84rem;
    font-weight: 600;
    color: var(--text-2);
}
.fp__rung.is-on .fp__rung-txt { color: var(--lagon-700); font-weight: 700; }

.fp__list {
    display: flex;
    flex-direction: column;
    gap: .1rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.fp__check {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .34rem .2rem;
    border-radius: var(--r-sm);
    cursor: pointer;
}
.fp__check:hover .fp__box { border-color: var(--ink); }
.fp__check input { position: absolute; opacity: 0; width: 0; height: 0; }

.fp__box {
    display: grid;
    place-items: center;
    flex: none;
    width: 1.15rem;
    height: 1.15rem;
    border: 1.5px solid var(--line-2);
    border-radius: 6px;
    color: transparent;
    background: var(--white);
    transition: background-color .2s var(--ease), border-color .2s var(--ease), color .2s var(--ease);
}
.fp__box svg { width: .72rem; height: .72rem; }

.fp__check.is-on .fp__box {
    border-color: var(--ink);
    background: var(--ink);
    color: var(--white);
}
.fp__check input:focus-visible + .fp__box { outline: 2px solid var(--terre-500); outline-offset: 2px; }

.fp__check-txt {
    font-size: .86rem;
    font-weight: 500;
    line-height: 1.35;
    color: var(--text-2);
}
.fp__check.is-on .fp__check-txt { color: var(--ink); font-weight: 600; }
</style>
