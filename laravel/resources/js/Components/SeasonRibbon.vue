<script setup>
/**
 * Le ruban de l'année — l'élément signature du calendrier.
 *
 * Douze segments, un par mois, teintés par ce que vaut la saison à cet
 * endroit. On voit **où est la bonne période avant de lire pourquoi** :
 * c'est la seule vue de la page qui répond à « quand venir ? » sans faire
 * défiler, et c'est ce qu'aucune plateforme de location ne montre.
 *
 * L'échelle est **neutre**, du plus dense au plus clair, et une seule
 * couleur intervient : la terre, sur le risque cyclonique. Le lagon reste ce
 * qu'il est partout ailleurs sur Vayla — il ne dit que « vérifié », jamais
 * « beau temps ». Un ruban arc-en-ciel aurait été plus joli et aurait cassé
 * la seule règle chromatique de la maison.
 *
 * Lecture : plus le segment est sombre, meilleure est la période.
 */
import { computed, ref } from 'vue'
import AmenityIcon from './AmenityIcon.vue'

const props = defineProps({
    year: { type: Array, default: () => [] },
    best: { type: Array, default: () => [] },
    zone: { type: String, default: '' },
    caveat: { type: String, default: '' },
    /** Mois actuellement affichés par le calendrier, en clair : ['septembre', …] */
    visible: { type: Array, default: () => [] },
})

const survol = ref(null)

/**
 * « sept », pas « S ». Les initiales seules étaient ambiguës — trois J, deux M,
 * deux A — et il fallait compter les segments pour savoir lequel on lisait.
 *
 * Les abréviations sont **écrites à la main, pas tronquées** : couper à trois
 * lettres donnait « jui » pour juin *et* pour juillet, ce qui remplaçait une
 * ambiguïté par une autre. Elles sont indexées par le numéro du mois, jamais
 * par son libellé — ainsi une traduction du site ne les casse pas.
 */
const ABREGES = ['janv', 'févr', 'mars', 'avr', 'mai', 'juin',
                 'juil', 'août', 'sept', 'oct', 'nov', 'déc']

const abrege = (n) => ABREGES[n - 1] ?? ''

const actif = computed(() =>
    survol.value ?? props.year.find((m) => m.best) ?? props.year[0] ?? null
)

/**
 * « mai → octobre » plutôt que « mai, juin, juillet, août, septembre,
 * octobre » : une plage se retient, une énumération non. On ne replie que
 * les suites réellement contiguës.
 */
const plages = computed(() => {
    const out = []
    let debut = null
    let precedent = null

    props.year.forEach((m) => {
        if (m.best) {
            if (debut === null) debut = m
            precedent = m
        } else if (debut) {
            out.push([debut, precedent])
            debut = null
        }
    })

    if (debut) out.push([debut, precedent])

    // Une saison à cheval sur le 31 décembre se lit « novembre → février ».
    if (out.length > 1 && out[0][0].n === 1 && out.at(-1)[1].n === 12) {
        const dernier = out.pop()
        out[0] = [dernier[0], out[0][1]]
    }

    return out.map(([a, b]) => (a.n === b.n ? a.month : `${a.month} → ${b.month}`))
})
</script>

<template>
    <section v-if="year.length" class="sb">
        <header class="sb__head">
            <p class="sb__eyebrow">La bonne période · {{ zone }}</p>
            <p class="sb__plages">
                <span v-for="(p, i) in plages" :key="p">
                    <span v-if="i" class="sb__et">et</span>{{ p }}
                </span>
            </p>
        </header>

        <ol class="sb__ribbon" @mouseleave="survol = null">
            <li v-for="m in year" :key="m.n">
                <button
                    type="button"
                    class="sb__seg"
                    :class="[`is-${m.kind}`, { 'is-visible': visible.includes(m.month) }]"
                    :aria-label="`${m.month} — ${m.label}`"
                    :title="`${m.month} — ${m.label}`"
                    @mouseenter="survol = m"
                    @focus="survol = m"
                    @click="survol = m"
                >
                    <span class="sb__bar"></span>
                    <span class="sb__initial">{{ abrege(m.n) }}</span>
                </button>
            </li>
        </ol>

        <p class="sb__howto">Touchez un mois pour lire sa saison.</p>

        <p v-if="actif" class="sb__legend" :class="{ 'is-warning': actif.warning }">
            <AmenityIcon :name="actif.icon ?? (actif.warning ? 'wave' : 'sun')" />
            <span class="sb__legend-month">{{ actif.month }}</span>
            <span class="sb__legend-sep" aria-hidden="true">·</span>
            <span class="sb__legend-label">{{ actif.label }}</span>
            <span class="sb__legend-note">{{ actif.note }}</span>
        </p>

        <p class="sb__caveat">{{ caveat }}</p>
    </section>
</template>

<style scoped>
.sb { --seg: 44px; }

.sb__eyebrow {
    margin: 0;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .13em;
    color: var(--text-3);
}

.sb__plages {
    margin: .3rem 0 0;
    font-size: clamp(1.25rem, 2.6vw, 1.7rem);
    font-weight: 800;
    letter-spacing: -.04em;
    line-height: 1.15;
    color: var(--ink);
}
.sb__et { color: var(--text-3); font-weight: 500; padding: 0 .35rem; }

/* ── Le ruban ─────────────────────────────────────────────────────
   Douze segments égaux, sans gouttière : c'est une année continue,
   pas douze cases indépendantes. */
.sb__ribbon {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 3px;
    margin: 1.1rem 0 0;
    padding: 0;
    list-style: none;
}

/* `cursor: pointer` et non `default` : ces segments **sont** des boutons.
   Avec un curseur de texte, rien ne disait qu'on pouvait les interroger. */
.sb__seg {
    display: block;
    width: 100%;
    padding: 0;
    border: 0;
    background: none;
    cursor: pointer;
}
.sb__seg:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 3px; border-radius: 4px; }

.sb__bar {
    display: block;
    height: .55rem;
    border-radius: 99px;
    background: var(--ink);
    transition: opacity .3s var(--ease), transform .3s var(--ease);
}

/* L'échelle : plus c'est dense, meilleure est la période.
   Le mélange se fait AVEC la teinte de fond plutôt qu'en opacité : à 13 %
   d'opacité, le segment de saison des pluies disparaissait purement et
   simplement du ruban, et l'année n'avait plus douze mois. */
.is-ideale .sb__bar { background: var(--ink); }
.is-seche  .sb__bar { background: color-mix(in srgb, var(--ink) 46%, var(--off-2)); }
.is-chaud  .sb__bar { background: color-mix(in srgb, var(--ink) 26%, var(--off-2)); }
.is-froid  .sb__bar { background: color-mix(in srgb, var(--ink) 26%, var(--off-2)); }
.is-pluies .sb__bar { background: color-mix(in srgb, var(--ink) 14%, var(--off-2)); }

/* La seule couleur du ruban : l'avertissement. */
.is-cyclones .sb__bar { background: var(--terre-500); }

.sb__seg:hover .sb__bar { transform: scaleY(1.55); }

/* La consigne, discrète mais écrite : sans elle le ruban se regarde comme une
   illustration. Un utilisateur qui ne devine pas qu'un élément est interactif
   ne le devine jamais — il faut le lui dire. */
.sb__howto {
    margin: .7rem 0 0;
    font-size: .76rem;
    font-weight: 600;
    color: var(--text-3);
}

.sb__initial {
    display: block;
    margin-top: .4rem;
    text-align: center;
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .04em;
    color: var(--text-3);
    transition: color .3s var(--ease);
}
.sb__seg:hover .sb__initial,
.is-visible .sb__initial { color: var(--ink); }

/* Les mois que le calendrier affiche en ce moment : un simple point. */
.is-visible .sb__initial { position: relative; }
.is-visible .sb__initial::after {
    content: '';
    position: absolute;
    left: 50%;
    bottom: -.32rem;
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background: var(--ink);
    translate: -50% 0;
}

.sb__legend {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: .1rem .45rem;
    margin: 1.5rem 0 0;
    min-height: 1.4rem;
    font-size: .86rem;
    color: var(--text-2);
}
.sb__legend :deep(.ai) { width: 1rem; height: 1rem; translate: 0 2px; color: var(--text-3); }
.sb__legend.is-warning :deep(.ai) { color: var(--terre-500); }

.sb__legend-month { font-weight: 700; color: var(--ink); text-transform: capitalize; }
.sb__legend-sep { color: var(--line-2); }
.sb__legend-label { font-weight: 600; }
.sb__legend.is-warning .sb__legend-label { color: var(--terre-600); }
.sb__legend-note { color: var(--text-3); }

.sb__caveat {
    margin: .55rem 0 0;
    font-size: .74rem;
    color: var(--text-3);
}
</style>
