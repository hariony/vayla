<script setup>
/**
 * Jusqu'où la vérification est allée, ici.
 *
 * C'est la seule chose qu'une page destination peut dire et qu'un guide de
 * voyage ne dira jamais. « 6 logements à Nosy Be » n'informe pas ; « 6
 * logements, dont 2 où des voyageurs ont dormi et confirmé » informe.
 *
 * **La répartition, jamais une moyenne.** Un « niveau moyen 2,8 » ne veut
 * rien dire : on ne sait pas s'il vient de six logements médiocres ou de
 * deux excellents et quatre déclarés. Les barreaux à zéro s'affichent aussi
 * — une échelle amputée de ses barreaux vides ne se lit plus comme une
 * échelle, et cacher le zéro serait précisément le maquillage que Vayla
 * refuse.
 */
import { computed } from 'vue'
import TrustGauge from '@/Components/TrustGauge.vue'

const props = defineProps({
    trust: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
})

const max = computed(() => Math.max(1, ...props.trust.map((t) => t.count)))
const verifies = computed(() =>
    props.trust.filter((t) => t.level >= 2).reduce((n, t) => n + t.count, 0)
)
</script>

<template>
    <section v-if="total" class="tb" data-anim>
        <header class="tb__head">
            <h2 class="tb__title">Jusqu'où nous sommes allés ici</h2>
            <p class="tb__lede">
                <strong class="num">{{ verifies }}</strong>
                logement{{ verifies > 1 ? 's' : '' }} sur
                <strong class="num">{{ total }}</strong>
                {{ verifies > 1 ? 'ont' : 'a' }} passé au moins une
                vérification. Le niveau 1 est déclaratif : il ne compte pas.
            </p>
        </header>

        <ol class="tb__rows" data-anim-group>
            <li v-for="t in [...trust].reverse()" :key="t.level" class="tb__row" :class="{ 'is-zero': !t.count }">
                <TrustGauge :level="t.level" />
                <span class="tb__name">{{ t.name }}</span>
                <span class="tb__bar">
                    <span class="tb__fill" :style="{ width: `${(t.count / max) * 100}%` }"></span>
                </span>
                <span class="tb__count num">{{ t.count }}</span>
            </li>
        </ol>
    </section>
</template>

<style scoped>
.tb__title {
    margin: 0;
    font-size: clamp(1.35rem, 2.4vw, 1.6rem);
    font-weight: 800;
    letter-spacing: -.035em;
    color: var(--ink);
}

.tb__lede {
    margin: .5rem 0 0;
    max-width: 48ch;
    font-size: .93rem;
    line-height: 1.6;
    color: var(--text-2);
}
.tb__lede strong { color: var(--ink); font-weight: 800; }

.tb__rows {
    display: flex;
    flex-direction: column;
    gap: .8rem;
    margin: 1.75rem 0 0;
    padding: 0;
    list-style: none;
}

.tb__row {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: .5rem .75rem;
}

.tb__name {
    font-size: .88rem;
    font-weight: 600;
    color: var(--ink);
}
.tb__row.is-zero .tb__name { color: var(--text-3); font-weight: 500; }

.tb__bar {
    grid-column: 1 / -1;
    height: .38rem;
    border-radius: 99px;
    background: var(--off-2);
    overflow: hidden;
}

.tb__fill {
    display: block;
    height: 100%;
    border-radius: 99px;
    background: var(--lagon-500);
    transition: width .8s var(--ease);
}

/* Le niveau 1 n'est pas une vérification : il garde le gris, ici comme
   partout ailleurs sur le site. */
.tb__row:last-child .tb__fill { background: var(--unverified); }

.tb__count {
    font-size: .95rem;
    font-weight: 800;
    letter-spacing: -.02em;
    color: var(--ink);
}
.tb__row.is-zero .tb__count { color: var(--line-2); }

@media (min-width: 560px) {
    .tb__row { grid-template-columns: auto 14rem minmax(0, 1fr) auto; }
    .tb__bar { grid-column: auto; }
}
</style>
