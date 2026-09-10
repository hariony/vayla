<script setup>
/**
 * « Y aller » — l'information que personne ne publie.
 *
 * Quand on prépare un séjour à Madagascar, c'est ce qu'on cherche le plus
 * longtemps : on la reconstitue de forum en forum, souvent avec des chiffres
 * périmés. Elle décide pourtant du voyage — Tuléar est à 1 h 30 d'avion ou à
 * deux jours de route, et ce n'est ni le même séjour ni le même budget.
 *
 * Deux honnêtetés dans ce bloc :
 *   — les durées sont annoncées **en fourchettes** et le disent, parce que
 *     les routes malgaches varient du simple au double selon la saison ;
 *   — une destination sans vol ni route déclarés n'affiche **rien** plutôt
 *     qu'un cadre vide. Ne pas savoir se dit en se taisant.
 */
import { computed } from 'vue'
import AmenityIcon from '@/Components/AmenityIcon.vue'

const props = defineProps({
    access: { type: Object, required: true },
    place: { type: String, default: '' },
})

const voies = computed(() => {
    const a = props.access
    const out = []

    if (a.flight) {
        out.push({
            icon: 'plane',
            mode: 'En avion',
            duree: a.flight,
            detail: a.airportName
                ? `Aéroport de ${a.airportName}${a.airportCode ? ` (${a.airportCode})` : ''}`
                : null,
        })
    }

    if (a.route) {
        out.push({
            icon: 'road',
            mode: 'Par la route',
            duree: a.hours,
            detail: [a.route, a.km ? `${a.km} km` : null].filter(Boolean).join(' · '),
        })
    }

    return out
})
</script>

<template>
    <section v-if="voies.length || access.note" class="ac" data-anim>
        <h2 class="ac__title">Y aller</h2>
        <p class="ac__lede">Depuis Antananarivo.</p>

        <ul v-if="voies.length" class="ac__voies">
            <li v-for="v in voies" :key="v.mode" class="ac__voie">
                <span class="ac__badge" aria-hidden="true">
                    <AmenityIcon :name="v.icon" />
                </span>
                <div>
                    <p class="ac__mode">{{ v.mode }}</p>
                    <p v-if="v.duree" class="ac__duree num">{{ v.duree }}</p>
                    <p v-if="v.detail" class="ac__detail">{{ v.detail }}</p>
                </div>
            </li>
        </ul>

        <p v-if="access.note" class="ac__note">{{ access.note }}</p>
        <p v-if="voies.length" class="ac__caveat">{{ access.caveat }}</p>
    </section>
</template>

<style scoped>
.ac {
    padding: clamp(1.5rem, 3.5vw, 2.25rem);
    border: 1px solid var(--line);
    border-radius: var(--r-lg);
    background: var(--off);
}

.ac__title {
    margin: 0;
    font-size: 1.22rem;
    font-weight: 800;
    letter-spacing: -.032em;
    color: var(--ink);
}

.ac__lede {
    margin: .35rem 0 0;
    font-size: .88rem;
    color: var(--text-3);
}

.ac__voies {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
    margin: 1.5rem 0 0;
    padding: 0;
    list-style: none;
}

.ac__voie { display: flex; gap: .85rem; }

.ac__badge {
    display: grid;
    place-items: center;
    flex: none;
    width: 2.1rem;
    height: 2.1rem;
    border: 1px solid var(--line-2);
    border-radius: 50%;
    background: var(--white);
    color: var(--text-3);
}

.ac__mode {
    margin: .1rem 0 0;
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: var(--text-3);
}

.ac__duree {
    margin: .25rem 0 0;
    font-size: 1.08rem;
    font-weight: 800;
    letter-spacing: -.03em;
    color: var(--ink);
}

.ac__detail {
    margin: .15rem 0 0;
    font-size: .84rem;
    line-height: 1.45;
    color: var(--text-2);
}

.ac__note {
    margin: 1.25rem 0 0;
    padding-top: 1rem;
    border-top: 1px solid var(--line);
    font-size: .86rem;
    line-height: 1.6;
    color: var(--text-2);
}

.ac__caveat {
    margin: .9rem 0 0;
    font-size: .76rem;
    line-height: 1.5;
    color: var(--text-3);
}

@media (min-width: 620px) {
    .ac__voies { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>
