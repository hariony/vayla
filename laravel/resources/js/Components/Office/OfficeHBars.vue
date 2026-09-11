<script setup>
/**
 * Des barres horizontales, pour ce qui se compare sans se ranger dans le
 * temps : les demandes par destination, le catalogue par état ou par niveau.
 *
 * **Le libellé et le chiffre sont écrits**, la barre ne fait que les montrer :
 * on ne demande à personne d'estimer une longueur. Les barres montent depuis
 * la gauche à l'arrivée des données, et de nouveau quand la période change.
 */
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import gsap from 'gsap'

import { nombre } from '@/Support/format.js'

const props = defineProps({
    titre: { type: String, required: true },
    definition: { type: String, default: '' },
    lignes: { type: Array, required: true }, // [{ label, valeur, teinte }]
    vide: { type: String, default: 'Rien sur la période.' },
})

const TEINTES = { encre: 'var(--ink)', terre: 'var(--terre-500)', gris: 'var(--unverified)', lagon: 'var(--lagon-500)' }

const racine = ref(null)
const max = () => Math.max(1, ...props.lignes.map((l) => l.valeur))

let ctx
const animer = () => {
    ctx?.revert()
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || ! racine.value) return
    ctx = gsap.context(() => {
        gsap.from(racine.value.querySelectorAll('.ob__plein'), { scaleX: 0, transformOrigin: 'left center', duration: .6, stagger: .05, ease: 'power3.out', clearProps: 'transform' })
    }, racine.value)
}

onMounted(() => nextTick(animer))
onUnmounted(() => ctx?.revert())
watch(() => props.lignes, () => nextTick(animer), { deep: true })
</script>

<template>
    <figure ref="racine" class="ob of-card" data-reveal>
        <figcaption class="ob__tete">
            <h3 class="ob__titre">{{ titre }}</h3>
            <p v-if="definition" class="ob__def">{{ definition }}</p>
        </figcaption>

        <ol v-if="lignes.some((l) => l.valeur)" class="ob__liste">
            <li v-for="l in lignes" :key="l.label" class="ob__ligne">
                <span class="ob__label">{{ l.label }}</span>
                <span class="ob__piste" aria-hidden="true">
                    <span class="ob__plein" :style="{ width: `${(l.valeur / max()) * 100}%`, background: TEINTES[l.teinte] ?? 'var(--ink)' }" />
                </span>
                <span class="ob__val of-num">{{ nombre(l.valeur) }}</span>
            </li>
        </ol>
        <p v-else class="ob__vide">{{ vide }}</p>
    </figure>
</template>

<style scoped>
.ob { display: grid; gap: .8rem; margin: 0; padding: 1rem 1.15rem; min-width: 0; align-content: start; }
.ob__tete { display: grid; gap: .15rem; }
.ob__titre { margin: 0; font-size: .98rem; font-weight: 800; letter-spacing: -.02em; color: var(--ink); }
.ob__def { margin: 0; font-size: .76rem; line-height: 1.45; color: var(--text-3); }

.ob__liste { display: grid; gap: .55rem; margin: 0; padding: 0; list-style: none; }
.ob__ligne { display: grid; grid-template-columns: minmax(6rem, 11rem) minmax(0, 1fr) 2.6rem; align-items: center; gap: .7rem; }
.ob__label { overflow: hidden; font-size: .82rem; font-weight: 600; color: var(--text-2); text-overflow: ellipsis; white-space: nowrap; }
.ob__piste { display: block; height: .6rem; overflow: hidden; border-radius: .6rem; background: var(--off-2); }
.ob__plein { display: block; height: 100%; min-width: 3px; border-radius: inherit; }
.ob__val { font-size: .88rem; font-weight: 800; text-align: right; color: var(--ink); }
.ob__vide { margin: 0; padding: 1.5rem 0; font-size: .84rem; text-align: center; color: var(--text-3); }

@media (max-width: 480px) {
    .ob__ligne { grid-template-columns: minmax(0, 1fr) 2.4rem; }
    .ob__piste { grid-column: 1 / -1; grid-row: 2; }
}
</style>
