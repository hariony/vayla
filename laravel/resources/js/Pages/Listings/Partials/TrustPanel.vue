<script setup>
/**
 * « Ce qui a été vérifié » — le cœur de la fiche.
 *
 * Elle n'affiche pas seulement le niveau atteint : elle montre les quatre
 * barreaux, ceux qui sont franchis **et ceux qui ne le sont pas**. Un
 * logement de niveau 2 doit dire ce qui lui manque, sinon « vérifié »
 * redevient un mot creux — exactement ce que Vayla reproche aux autres.
 *
 * Le gecko s'arrête au barreau atteint : sa position n'est pas décorative,
 * elle est la donnée. Il grimpe l'échelle de confiance, il est donc en lagon
 * et nulle part ailleurs.
 *
 * Les libellés viennent de l'enum côté serveur. Aucun n'est réécrit ici.
 */
import { computed } from 'vue'
import GeckoClimb from '@/Components/GeckoClimb.vue'
import TrustGauge from '@/Components/TrustGauge.vue'

const props = defineProps({
    level: { type: Number, default: 1 },
    trustLevels: { type: Array, default: () => [] },
})

const atteint = computed(() => props.trustLevels[props.level - 1])
const manquants = computed(() => props.trustLevels.filter((t) => t.level > props.level))

/**
 * Position du gecko sur la tige, en pourcentage depuis le bas. Les barreaux
 * sont répartis régulièrement : le niveau 1 est en bas, le 4 en haut.
 */
const perche = computed(() => {
    const n = props.trustLevels.length || 4
    return ((props.level - 1) / (n - 1)) * 100
})
</script>

<template>
    <section class="tp" data-anim>
        <div class="tp__head">
            <TrustGauge :level="level" large />
            <div>
                <p class="eyebrow eyebrow--lagon">Ce qui a été vérifié</p>
                <h2 class="tp__level">{{ atteint?.name }}</h2>
                <p class="tp__summary">{{ atteint?.summary }}</p>
            </div>
        </div>

        <div class="tp__ladder">
            <!-- La tige et son grimpeur : la position du gecko EST le niveau. -->
            <div class="tp__stem" aria-hidden="true">
                <span class="tp__stem-line"></span>
                <span class="tp__stem-fill" :style="{ height: `${perche}%` }"></span>
                <GeckoClimb class="tp__gecko" :style="{ bottom: `calc(${perche}% - 1.4rem)` }" />
            </div>

            <ol class="tp__rungs">
                <li
                    v-for="t in [...trustLevels].reverse()"
                    :key="t.level"
                    class="tp__rung"
                    :class="{ 'is-done': t.level <= level, 'is-current': t.level === level }"
                    data-rung
                >
                    <span class="tp__mark" aria-hidden="true">
                        <svg v-if="t.level <= level" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m4 12.5 5 5L20 6.5" />
                        </svg>
                    </span>
                    <span class="tp__body">
                        <span class="tp__name">{{ t.name }}</span>
                        <span class="tp__desc">{{ t.summary }}</span>
                    </span>
                </li>
            </ol>
        </div>

        <!-- Dire ce qui manque est la moitié du travail. -->
        <p v-if="manquants.length" class="tp__missing">
            <strong>Pas encore :</strong>
            {{ manquants.map((m) => m.name.toLowerCase()).join(', ') }}.
            Nous continuons de vérifier ce logement.
        </p>
        <p v-else class="tp__complete">
            <strong>L'échelle est complète.</strong>
            Des voyageurs ont dormi ici et confirmé que tout correspondait.
        </p>
    </section>
</template>

<style scoped>
.tp {
    padding: clamp(1.5rem, 3.5vw, 2.25rem);
    border: 1px solid var(--line);
    border-radius: var(--r-lg);
    background: var(--white);
}

.tp__head { display: flex; align-items: flex-start; gap: 1rem; }

.tp__level {
    margin: .3rem 0 0;
    font-size: 1.22rem;
    font-weight: 800;
    letter-spacing: -.032em;
    color: var(--ink);
}

.tp__summary {
    margin: .4rem 0 0;
    max-width: 48ch;
    font-size: .92rem;
    line-height: 1.6;
    color: var(--text-2);
}

/* ── L'échelle ─────────────────────────────────────────────────── */
.tp__ladder {
    display: grid;
    grid-template-columns: 2.4rem minmax(0, 1fr);
    gap: 0 .4rem;
    margin: 1.75rem 0 0;
    padding: 1.5rem 0 0;
    border-top: 1px solid var(--line);
}

.tp__stem { position: relative; }

.tp__stem-line,
.tp__stem-fill {
    position: absolute;
    left: 50%;
    width: 2px;
    translate: -50% 0;
    border-radius: 2px;
}
.tp__stem-line { top: .7rem; bottom: .7rem; background: var(--line-2); }
.tp__stem-fill { bottom: .7rem; background: var(--lagon-400); }

.tp__gecko {
    position: absolute;
    left: 50%;
    width: 2.4rem;
    translate: -50% 0;
    color: var(--lagon-500);
}

.tp__rungs {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 1.15rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.tp__rung { display: flex; gap: .75rem; }

.tp__mark {
    display: grid;
    place-items: center;
    flex: none;
    width: 1.4rem;
    height: 1.4rem;
    border: 1.5px solid var(--line-2);
    border-radius: 50%;
    color: transparent;
    background: var(--white);
    translate: 0 1px;
}
.tp__mark svg { width: .74rem; height: .74rem; }

/* Le lagon ne commence qu'au niveau 2 : déclarer n'est pas vérifier, et le
   niveau 1 garde donc le gris `--unverified`. */
.tp__rung.is-done .tp__mark {
    border-color: var(--lagon-500);
    background: var(--lagon-500);
    color: var(--white);
}
.tp__rung.is-done:last-child .tp__mark {
    border-color: var(--unverified);
    background: var(--unverified);
}

.tp__body { display: flex; flex-direction: column; gap: .1rem; }

.tp__name { font-size: .93rem; font-weight: 700; color: var(--text-3); }
.tp__rung.is-done .tp__name { color: var(--ink); }
.tp__rung.is-current .tp__name { color: var(--lagon-700); }
.tp__rung.is-current:last-child .tp__name { color: var(--text-2); }

.tp__desc {
    max-width: 52ch;
    font-size: .83rem;
    line-height: 1.5;
    color: var(--text-3);
}

.tp__missing,
.tp__complete {
    margin: 1.5rem 0 0;
    padding: .9rem 1.1rem;
    border-radius: var(--r-md);
    font-size: .86rem;
    line-height: 1.55;
}

.tp__missing { background: var(--off-2); color: var(--text-2); }
.tp__missing strong { color: var(--ink); }

.tp__complete {
    background: color-mix(in srgb, var(--lagon-500) 9%, transparent);
    color: var(--lagon-700);
}
.tp__complete strong { color: var(--lagon-700); }
</style>
