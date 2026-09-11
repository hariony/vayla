<script setup>
/**
 * Un graphique du back-office — courbes, barres, ou barres empilées.
 *
 * **Dessiné ici, en SVG, sans bibliothèque.** Une bibliothèque de graphiques
 * pèse plus que tout le back-office, impose son style et son vocabulaire de
 * couleurs ; ici la terre, l'encre et le lagon gardent leur métier, et le trait
 * est celui des pictogrammes.
 *
 * **Le SVG est mesuré à sa largeur réelle**, pas étiré par un `viewBox` : un
 * graphique étiré déforme ses textes et ses traits dès qu'on change la
 * fenêtre. Un `ResizeObserver` redessine à la bonne taille.
 *
 * **Lire une valeur ne dépend pas du survol.** Un mois se sélectionne au
 * survol, au doigt **et** au clavier (le graphique prend le focus, les flèches
 * avancent) ; le relevé du mois choisi s'écrit au-dessus, en toutes lettres.
 * Et chaque graphique a son **tableau** replié dessous : c'est la version
 * lisible par un lecteur d'écran, et celle qu'on recopie dans un e-mail.
 *
 * **Un mois sans donnée n'est pas un zéro.** Une courbe de taux s'interrompt
 * là où il n'y avait rien à mesurer, au lieu de plonger à zéro et de raconter
 * une chute qui n'a pas eu lieu.
 *
 * Le mouvement (GSAP) dit l'arrivée des données : les courbes se tracent, les
 * barres montent depuis la ligne de base. Il se rejoue quand la période
 * change, jamais ailleurs, et rien sous `prefers-reduced-motion`.
 */
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import gsap from 'gsap'

import { nombre } from '@/Support/format.js'

const props = defineProps({
    titre: { type: String, required: true },
    /** Ce que la série compte, et à quelle date elle le range. */
    definition: { type: String, default: '' },
    type: { type: String, default: 'lignes' }, // lignes | barres | empile
    labels: { type: Array, required: true },
    labelsLongs: { type: Array, default: () => [] },
    series: { type: Array, required: true },
    format: { type: String, default: 'nombre' }, // nombre | ariary | pourcent | heures
    hauteur: { type: Number, default: 240 },
    /** Borne haute imposée — 100 pour un pourcentage. */
    plafond: { type: Number, default: null },
    /**
     * Posé dans un bloc qui porte déjà son titre et son tableau (la
     * commission) : ni carte, ni titre, ni tableau replié — une carte dans une
     * carte, et deux tableaux des mêmes chiffres.
     */
    nu: { type: Boolean, default: false },
})

const TEINTES = {
    encre: 'var(--ink)',
    terre: 'var(--terre-500)',
    'terre-pale': 'var(--terre-200)',
    gris: 'var(--ink-3)',
    filet: 'var(--line-2)',
    lagon: 'var(--lagon-500)',
}
const teinte = (s) => TEINTES[s.teinte] ?? 'var(--ink)'

const boite = ref(null)
const svg = ref(null)
const largeur = ref(640)

const M = { haut: 16, droite: 14, bas: 30, gauche: 48 }
const utileL = computed(() => Math.max(40, largeur.value - M.gauche - M.droite))
const utileH = computed(() => props.hauteur - M.haut - M.bas)
const n = computed(() => props.labels.length)
const bande = computed(() => utileL.value / Math.max(1, n.value))

// ── Les valeurs et l'échelle ────────────────────────────────────────────
const total = (i) => props.series.reduce((s, x) => s + (x.valeurs[i] ?? 0), 0)

const maximum = computed(() => {
    if (props.plafond) return props.plafond
    const brut = props.type === 'empile'
        ? Math.max(0, ...props.labels.map((_, i) => total(i)))
        : Math.max(0, ...props.series.flatMap((s) => s.valeurs.filter((v) => v !== null)))
    return joli(brut)
})

/** Une borne haute ronde : 1, 2, 2,5 ou 5 fois une puissance de dix. */
function joli(v) {
    if (v <= 0) return 4
    const p = 10 ** Math.floor(Math.log10(v))
    const r = v / p
    const pas = r <= 1 ? 1 : r <= 2 ? 2 : r <= 2.5 ? 2.5 : r <= 5 ? 5 : 10
    return Math.max(4, pas * p)
}

const graduations = computed(() => [0, 1, 2, 3, 4].map((k) => (maximum.value * k) / 4))

const y = (v) => M.haut + utileH.value - (v / maximum.value) * utileH.value
const xCentre = (i) => M.gauche + bande.value * i + bande.value / 2

const vide = computed(() => props.series.every((s) => s.valeurs.every((v) => ! v)))

// ── Les tracés ──────────────────────────────────────────────────────────
/** Le chemin d'une courbe, interrompu là où la valeur manque. */
const chemin = (s) => {
    let d = ''
    let leve = true
    s.valeurs.forEach((v, i) => {
        if (v === null || v === undefined) { leve = true; return }
        d += `${leve ? 'M' : 'L'}${xCentre(i).toFixed(1)},${y(v).toFixed(1)} `
        leve = false
    })
    return d.trim()
}

/** L'aire sous la courbe principale, pour l'asseoir — pas pour la colorer. */
const aire = (s) => {
    const pts = s.valeurs.map((v, i) => (v === null ? null : [xCentre(i), y(v)]))
    const plein = pts.filter(Boolean)
    if (plein.length < 2) return ''
    const base = y(0)
    return `M${plein[0][0]},${base} ${plein.map((p) => `L${p[0]},${p[1]}`).join(' ')} L${plein.at(-1)[0]},${base} Z`
}

const largeurBarre = computed(() => {
    const groupe = Math.min(bande.value * 0.7, 56)
    return props.type === 'barres' ? groupe / props.series.length : groupe
})

const barres = computed(() => {
    const sortie = []
    props.labels.forEach((_, i) => {
        if (props.type === 'empile') {
            let cumul = 0
            props.series.forEach((s) => {
                const v = s.valeurs[i] ?? 0
                if (v > 0) {
                    sortie.push({ cle: `${s.cle}-${i}`, x: xCentre(i) - largeurBarre.value / 2, y: y(cumul + v), h: y(cumul) - y(cumul + v), w: largeurBarre.value, couleur: teinte(s), i })
                }
                cumul += v
            })
        } else {
            props.series.forEach((s, k) => {
                const v = s.valeurs[i] ?? 0
                const x0 = xCentre(i) - (largeurBarre.value * props.series.length) / 2 + k * largeurBarre.value
                sortie.push({ cle: `${s.cle}-${i}`, x: x0 + 1, y: y(v), h: y(0) - y(v), w: Math.max(2, largeurBarre.value - 2), couleur: teinte(s), i })
            })
        }
    })
    return sortie
})

// Moins d'étiquettes quand la place manque : une sur deux, une sur trois…
const pasEtiquette = computed(() => Math.max(1, Math.ceil(n.value / Math.max(1, Math.floor(utileL.value / 46)))))

// ── Les formats ─────────────────────────────────────────────────────────
const texte = (v) => {
    if (v === null || v === undefined) return '—'
    switch (props.format) {
        case 'ariary': return `${nombre(Math.round(v))} Ar`
        case 'pourcent': return `${Math.round(v)} %`
        case 'heures': return `${String(v).replace('.', ',')} h`
        default: return nombre(v)
    }
}

const axe = (v) => {
    if (props.format === 'pourcent') return `${v} %`
    if (props.format === 'heures') return `${v} h`
    if (v >= 1e6) return `${String(+(v / 1e6).toFixed(1)).replace('.', ',')} M`
    if (v >= 1e3) return `${String(+(v / 1e3).toFixed(1)).replace('.', ',')} k`
    return String(+v.toFixed(1)).replace('.', ',')
}

// ── La sélection d'un mois ──────────────────────────────────────────────
const dernierAvecDonnee = () => {
    for (let i = n.value - 1; i >= 0; i--) {
        if (props.series.some((s) => s.valeurs[i])) return i
    }
    return n.value - 1
}
const choisi = ref(dernierAvecDonnee())

const viser = (e) => {
    const r = svg.value.getBoundingClientRect()
    const x = (e.touches?.[0]?.clientX ?? e.clientX) - r.left - M.gauche
    choisi.value = Math.min(n.value - 1, Math.max(0, Math.floor(x / bande.value)))
}

const clavier = (e) => {
    if (e.key === 'ArrowLeft') { choisi.value = Math.max(0, choisi.value - 1); e.preventDefault() }
    if (e.key === 'ArrowRight') { choisi.value = Math.min(n.value - 1, choisi.value + 1); e.preventDefault() }
    if (e.key === 'Home') { choisi.value = 0; e.preventDefault() }
    if (e.key === 'End') { choisi.value = n.value - 1; e.preventDefault() }
}

const releve = computed(() => ({
    mois: props.labelsLongs[choisi.value] ?? props.labels[choisi.value],
    lignes: props.series.map((s) => ({ label: s.label, couleur: teinte(s), valeur: texte(s.valeurs[choisi.value]) })),
    total: props.type === 'empile' ? texte(total(choisi.value)) : null,
}))

// ── La taille et le mouvement ───────────────────────────────────────────
let observateur
let ctx

const animer = () => {
    ctx?.revert()
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || ! svg.value) return

    ctx = gsap.context(() => {
        svg.value.querySelectorAll('[data-trace]').forEach((p) => {
            const l = p.getTotalLength?.() ?? 0
            if (! l) return
            gsap.fromTo(p, { strokeDasharray: l, strokeDashoffset: l }, { strokeDashoffset: 0, duration: .9, ease: 'power2.inOut', clearProps: 'strokeDasharray,strokeDashoffset' })
        })
        gsap.from(svg.value.querySelectorAll('[data-aire]'), { opacity: 0, duration: .8, delay: .3 })
        gsap.from(svg.value.querySelectorAll('[data-barre]'), { scaleY: 0, transformOrigin: '50% 100%', duration: .55, stagger: .012, ease: 'power3.out', clearProps: 'transform' })
        gsap.from(svg.value.querySelectorAll('[data-point]'), { scale: 0, transformOrigin: '50% 50%', duration: .3, delay: .6, stagger: .02 })
    }, svg.value)
}

onMounted(() => {
    largeur.value = boite.value?.clientWidth || 640
    observateur = new ResizeObserver(([entree]) => { largeur.value = Math.round(entree.contentRect.width) })
    observateur.observe(boite.value)
    nextTick(animer)
})

onUnmounted(() => { observateur?.disconnect(); ctx?.revert() })

// Une autre période : les données arrivent de nouveau, le geste se rejoue.
watch(() => [props.labels, props.series], () => {
    choisi.value = dernierAvecDonnee()
    nextTick(animer)
}, { deep: true })
</script>

<template>
    <figure class="oc" :class="nu ? 'oc--nu' : 'of-card'" :data-reveal="nu ? undefined : ''">
        <figcaption v-if="! nu" class="oc__tete">
            <h3 class="oc__titre">{{ titre }}</h3>
            <p v-if="definition" class="oc__def">{{ definition }}</p>
        </figcaption>

        <!-- Le relevé du mois choisi : ce que le survol montrerait, écrit pour
             tous — au doigt, au clavier, au lecteur d'écran. -->
        <div class="oc__releve" aria-live="polite">
            <span class="oc__mois">{{ releve.mois }}</span>
            <span v-for="l in releve.lignes" :key="l.label" class="oc__val">
                <span class="oc__pastille" :style="{ background: l.couleur }" aria-hidden="true" />
                {{ l.label }} <strong class="of-num">{{ l.valeur }}</strong>
            </span>
            <span v-if="releve.total" class="oc__val oc__val--total">Total <strong class="of-num">{{ releve.total }}</strong></span>
        </div>

        <div ref="boite" class="oc__boite">
            <svg
                ref="svg"
                class="oc__svg"
                :width="largeur"
                :height="hauteur"
                role="img"
                :aria-label="`${titre}. Flèches gauche et droite pour parcourir les mois.`"
                tabindex="0"
                @mousemove="viser"
                @click="viser"
                @touchstart.passive="viser"
                @touchmove.passive="viser"
                @keydown="clavier"
            >
                <!-- Les graduations : fines, chaudes, discrètes. -->
                <g class="oc__grille">
                    <g v-for="g in graduations" :key="g">
                        <line :x1="M.gauche" :x2="largeur - M.droite" :y1="y(g)" :y2="y(g)" />
                        <text :x="M.gauche - 8" :y="y(g) + 4" text-anchor="end">{{ axe(g) }}</text>
                    </g>
                </g>

                <!-- Le mois choisi : une bande claire, pas un trait qui barre. -->
                <rect class="oc__choix" :x="M.gauche + bande * choisi" :y="M.haut" :width="bande" :height="utileH" rx="4" />

                <template v-if="type !== 'lignes'">
                    <rect
                        v-for="b in barres"
                        :key="b.cle"
                        :x="b.x" :y="b.y" :width="b.w" :height="Math.max(0, b.h)"
                        :fill="b.couleur"
                        :class="{ 'is-pale': b.i !== choisi }"
                        rx="2"
                        data-barre
                    />
                </template>

                <template v-else>
                    <path v-if="series[0]" :d="aire(series[0])" :fill="teinte(series[0])" class="oc__aire" data-aire />
                    <path
                        v-for="s in series"
                        :key="s.cle"
                        :d="chemin(s)"
                        :stroke="teinte(s)"
                        class="oc__ligne"
                        data-trace
                    />
                    <template v-for="s in series" :key="`p-${s.cle}`">
                        <circle
                            v-for="(v, i) in s.valeurs"
                            v-show="v !== null && v !== undefined"
                            :key="`${s.cle}-${i}`"
                            :cx="xCentre(i)" :cy="y(v ?? 0)"
                            :r="i === choisi ? 4.5 : 2.5"
                            :fill="i === choisi ? teinte(s) : 'var(--white)'"
                            :stroke="teinte(s)"
                            stroke-width="2"
                            data-point
                        />
                    </template>
                </template>

                <line class="oc__base" :x1="M.gauche" :x2="largeur - M.droite" :y1="y(0)" :y2="y(0)" />

                <g class="oc__mois-axe">
                    <template v-for="(l, i) in labels" :key="l">
                        <text v-if="i % pasEtiquette === 0 || i === choisi" :x="xCentre(i)" :y="hauteur - 8" text-anchor="middle" :class="{ 'is-on': i === choisi }">{{ l }}</text>
                    </template>
                </g>
            </svg>

            <p v-if="vide" class="oc__vide">Rien à tracer sur la période.</p>
        </div>

        <div class="oc__pied">
            <ul class="oc__legende">
                <li v-for="s in series" :key="s.cle">
                    <span class="oc__pastille" :class="{ 'oc__pastille--ligne': type === 'lignes' }" :style="{ background: teinte(s) }" aria-hidden="true" />
                    {{ s.label }}
                </li>
            </ul>

            <details v-if="! nu" class="oc__table">
                <summary>Voir les chiffres</summary>
                <div class="oc__table-in">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Mois</th>
                                <th v-for="s in series" :key="s.cle" scope="col">{{ s.label }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(l, i) in labels" :key="l">
                                <th scope="row">{{ labelsLongs[i] ?? l }}</th>
                                <td v-for="s in series" :key="s.cle" class="of-num">{{ texte(s.valeurs[i]) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>
        </div>
    </figure>
</template>

<style scoped>
.oc { display: grid; gap: .6rem; margin: 0; padding: 1rem 1.15rem 1rem; min-width: 0; }
.oc--nu { padding: 0; }

.oc__tete { display: grid; gap: .15rem; }
.oc__titre { margin: 0; font-size: .98rem; font-weight: 800; letter-spacing: -.02em; color: var(--ink); }
.oc__def { margin: 0; font-size: .76rem; line-height: 1.45; color: var(--text-3); }

.oc__releve {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: .25rem .9rem;
    min-height: 1.6rem;
    font-size: .8rem;
    color: var(--text-2);
}
.oc__mois { font-weight: 800; color: var(--ink); text-transform: capitalize; }
.oc__val { display: inline-flex; align-items: center; gap: .35rem; }
.oc__val strong { color: var(--ink); font-weight: 800; }
.oc__val--total { padding-left: .6rem; border-left: 1px solid var(--line-2); }

.oc__boite { position: relative; min-width: 0; }
.oc__svg { display: block; max-width: 100%; overflow: visible; cursor: crosshair; touch-action: pan-y; }
.oc__svg:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 4px; border-radius: 4px; }

.oc__grille line { stroke: var(--line); stroke-width: 1; }
.oc__grille text, .oc__mois-axe text { font-size: 10.5px; font-weight: 600; fill: var(--text-3); font-variant-numeric: tabular-nums; }
.oc__mois-axe text { text-transform: capitalize; }
.oc__mois-axe text.is-on { fill: var(--ink); font-weight: 800; }
.oc__base { stroke: var(--line-2); stroke-width: 1; }
.oc__choix { fill: var(--off-2); }

.oc__ligne { fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
.oc__aire { opacity: .07; }
rect.is-pale { opacity: .78; }

.oc__vide {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    margin: 0;
    font-size: .84rem;
    color: var(--text-3);
    pointer-events: none;
}

.oc__pied { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: .5rem 1rem; }
.oc__legende { display: flex; flex-wrap: wrap; gap: .3rem .9rem; margin: 0; padding: 0; list-style: none; font-size: .76rem; color: var(--text-2); }
.oc__legende li { display: inline-flex; align-items: center; gap: .35rem; }
.oc__pastille { display: inline-block; width: .7rem; height: .7rem; border-radius: 3px; }
.oc__pastille--ligne { height: .2rem; border-radius: 2px; }

.oc__table summary {
    display: inline-flex;
    align-items: center;
    min-height: 2.2rem;
    padding: 0 .75rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    font-size: .76rem;
    font-weight: 700;
    color: var(--ink);
    cursor: pointer;
    list-style: none;
}
.oc__table summary::-webkit-details-marker { display: none; }
.oc__table[open] { flex-basis: 100%; }
.oc__table-in { margin-top: .6rem; overflow-x: auto; }
.oc__table table { width: 100%; border-collapse: collapse; font-size: .8rem; }
.oc__table th, .oc__table td { padding: .4rem .55rem; border-bottom: 1px solid var(--line); text-align: right; white-space: nowrap; }
.oc__table th:first-child { text-align: left; text-transform: capitalize; }
.oc__table thead th { font-size: .7rem; font-weight: 700; color: var(--text-3); }
</style>
