<script setup>
/**
 * « Ce qui tient quand la ville lâche » — le bloc que les plateformes du Nord
 * n'ont pas.
 *
 * À Madagascar, le délestage est quotidien et l'eau courante n'est pas
 * acquise. Une annonce peut être belle et invivable à 19 h. Ce panneau
 * répond à trois questions avant qu'on ait à les poser : **l'électricité,
 * l'eau, la connexion**.
 *
 * Il dit aussi la mauvaise nouvelle. Un logement branché sur le seul réseau
 * JIRAMA affiche « rien ne prend le relais » — c'est le prix à payer pour que
 * « groupe électrogène » veuille dire quelque chose sur les autres fiches.
 * Un panneau qui ne saurait que rassurer ne renseignerait rien.
 *
 * Rien n'est inventé : tout se déduit des équipements déclarés, et les
 * précisions affichées sont celles du propriétaire.
 */
import { computed } from 'vue'
import AmenityIcon from '@/Components/AmenityIcon.vue'

const props = defineProps({
    groups: { type: Array, default: () => [] },
})

const flat = computed(() => {
    const map = {}
    props.groups.forEach((g) => g.amenities.forEach((a) => { map[a.key] = a }))
    return map
})

const has = (key) => key in flat.value
const item = (key) => flat.value[key]

/** Les équipements présents parmi une liste, dans l'ordre donné. */
const pick = (keys) => keys.filter(has).map(item)

const axes = computed(() => {
    const secours = pick(['groupe-electrogene', 'panneaux-solaires', 'onduleur'])
    const reseau = pick(['electricite-jirama'])
    const reserve = pick(['puits-forage', 'reserve-eau'])
    const eau = pick(['eau-courante', 'eau-chaude', 'chauffe-eau-solaire', 'filtre-eau'])
    const net = pick(['wifi-fibre', 'wifi', 'espace-travail'])

    return [
        {
            key: 'electricite',
            icon: 'bolt',
            label: 'Électricité',
            ok: secours.length > 0,
            verdict: secours.length
                ? 'Le logement tient pendant les coupures.'
                : reseau.length
                    ? 'Réseau seul : rien ne prend le relais pendant les coupures.'
                    : 'Rien n\'est déclaré sur l\'électricité.',
            items: [...secours, ...reseau],
        },
        {
            key: 'eau',
            icon: 'water',
            label: 'Eau',
            ok: reserve.length > 0,
            verdict: reserve.length
                ? 'Une réserve indépendante du réseau.'
                : eau.length
                    ? 'Dépend du réseau de la ville.'
                    : 'Rien n\'est déclaré sur l\'eau.',
            items: [...reserve, ...eau],
        },
        {
            key: 'connexion',
            icon: 'wifi',
            label: 'Connexion',
            ok: has('wifi-fibre'),
            verdict: has('wifi-fibre')
                ? 'Fibre : tenable pour travailler.'
                : has('wifi')
                    ? 'Wifi déclaré, débit non vérifié.'
                    : 'Aucune connexion déclarée.',
            items: net,
        },
    ]
})

const utile = computed(() => axes.value.some((a) => a.items.length))
</script>

<template>
    <section v-if="utile" class="ep" data-anim>
        <header class="ep__head">
            <h2 class="ep__title">Ce qui tient quand la ville lâche</h2>
            <p class="ep__lede">
                Le délestage et l'eau courante ne vont pas de soi à Madagascar.
                Voici ce que ce logement possède — et ce qu'il ne possède pas.
            </p>
        </header>

        <ul class="ep__axes">
            <li v-for="a in axes" :key="a.key" class="ep__axis" :class="{ 'is-ok': a.ok }">
                <span class="ep__badge" aria-hidden="true">
                    <AmenityIcon :name="a.icon" />
                </span>

                <div class="ep__body">
                    <h3 class="ep__label">{{ a.label }}</h3>
                    <p class="ep__verdict">{{ a.verdict }}</p>

                    <ul v-if="a.items.length" class="ep__list">
                        <li v-for="i in a.items" :key="i.key" class="ep__item">
                            <span class="ep__item-label">{{ i.label }}</span>
                            <span v-if="i.note" class="ep__item-note">{{ i.note }}</span>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </section>
</template>

<style scoped>
.ep {
    padding: clamp(1.5rem, 3.5vw, 2.25rem);
    border: 1px solid var(--line);
    border-radius: var(--r-lg);
    background: var(--off);
}

.ep__title {
    margin: 0;
    font-size: 1.22rem;
    font-weight: 800;
    letter-spacing: -.032em;
    color: var(--ink);
    text-wrap: balance;
}

.ep__lede {
    margin: .55rem 0 0;
    max-width: 54ch;
    font-size: .9rem;
    line-height: 1.6;
    color: var(--text-2);
}

.ep__axes {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
    margin: 1.75rem 0 0;
    padding: 0;
    list-style: none;
}

.ep__axis { display: flex; gap: .85rem; }

.ep__badge {
    display: grid;
    place-items: center;
    flex: none;
    width: 2.1rem;
    height: 2.1rem;
    border-radius: 50%;
    color: var(--text-3);
    background: var(--white);
    border: 1px solid var(--line-2);
    transition: color .3s var(--ease), border-color .3s var(--ease);
}

/* Le lagon ne dit qu'une chose : « vérifié ». Ici l'axe couvert n'est donc
   PAS en lagon — il est en encre franche. Le gris reste au non-couvert. */
.ep__axis.is-ok .ep__badge {
    color: var(--white);
    background: var(--ink);
    border-color: var(--ink);
}

.ep__label {
    margin: .12rem 0 0;
    font-size: .74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: var(--text-3);
}

.ep__verdict {
    margin: .3rem 0 0;
    font-size: .96rem;
    font-weight: 600;
    line-height: 1.45;
    color: var(--text-3);
}
.ep__axis.is-ok .ep__verdict { color: var(--ink); }

.ep__list {
    display: flex;
    flex-wrap: wrap;
    gap: .3rem .4rem;
    margin: .7rem 0 0;
    padding: 0;
    list-style: none;
}

.ep__item {
    display: inline-flex;
    align-items: baseline;
    gap: .35rem;
    padding: .25rem .6rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font-size: .8rem;
}

.ep__item-label { font-weight: 600; color: var(--text-2); }
.ep__item-note { color: var(--text-3); }

@media (min-width: 700px) {
    .ep__axes { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.5rem; }
    .ep__axis { flex-direction: column; gap: .6rem; }
    .ep__item { max-width: 100%; }
}
</style>
