<script setup>
/**
 * L'en-tête commun aux trois écrans de statistiques : le titre de la
 * rubrique, la période, et **la démonstration dite en toutes lettres**.
 *
 * La période et le retrait de la démonstration vivent dans l'adresse ; le
 * sous-menu de la colonne les emporte d'un écran à l'autre, et les liens
 * d'ici restent sur l'écran où l'on est (`base`).
 */
import { Link } from '@inertiajs/vue3'

import OfficeHead from '@/Components/Office/OfficeHead.vue'

const props = defineProps({
    cadre: { type: Object, required: true },
    titre: { type: String, required: true },
    /** L'adresse de l'écran — `/statistiques`, `/statistiques/sejours`… */
    base: { type: String, required: true },
})

const lien = (params) => {
    const p = new URLSearchParams({ periode: props.cadre.periode.mois, ...(props.cadre.demo.inclus ? {} : { demo: '0' }), ...params })
    if (p.get('demo') === '1') p.delete('demo')
    return `${props.base}?${p.toString()}`
}
</script>

<template>
    <OfficeHead kicker="Statistiques" :titre="titre">
        <template #lede><slot /></template>
        <template #actions>
            <!-- La période : trois durées, pas un calendrier à régler. -->
            <nav class="st__periodes" aria-label="Période">
                <Link
                    v-for="p in cadre.periode.choix"
                    :key="p"
                    :href="lien({ periode: p })"
                    class="st__p"
                    :class="{ 'is-on': p === cadre.periode.mois }"
                    :aria-current="p === cadre.periode.mois ? 'page' : undefined"
                    preserve-scroll
                >{{ p }} mois</Link>
            </nav>
        </template>
    </OfficeHead>

    <!-- La démonstration : dite en toutes lettres, et retirable d'un geste. -->
    <div v-if="cadre.demo.present" class="st__demo" :class="{ 'is-sans': ! cadre.demo.inclus }" role="note" data-reveal>
        <p class="st__demo-t">
            <template v-if="cadre.demo.inclus"><strong>Ces chiffres incluent les données de démonstration.</strong> Elles servent à voir les courbes ; ce n'est pas l'activité réelle.</template>
            <template v-else><strong>Données réelles seulement.</strong> La démonstration est retirée.</template>
        </p>
        <Link :href="lien({ demo: cadre.demo.inclus ? '0' : '1' })" class="btn btn--sm btn--outline" preserve-scroll>
            {{ cadre.demo.inclus ? 'Retirer la démonstration' : 'Inclure la démonstration' }}
        </Link>
    </div>
</template>

<style scoped>
.st__periodes { display: flex; gap: .25rem; padding: .25rem; border: 1px solid var(--line); border-radius: var(--r-pill); background: var(--white); }
.st__p {
    display: inline-flex;
    align-items: center;
    min-height: 2.4rem;
    padding: 0 .95rem;
    border-radius: var(--r-pill);
    font-size: .84rem;
    font-weight: 600;
    color: var(--text-2);
    text-decoration: none;
}
.st__p:hover { background: var(--off-2); color: var(--ink); }
.st__p:focus-visible { outline: 2px solid var(--terre-500); outline-offset: -2px; }
.st__p.is-on { background: var(--ink); color: var(--white); font-weight: 700; }

.st__demo {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .6rem 1rem;
    margin-bottom: 1rem;
    padding: .7rem .8rem .7rem 1rem;
    border: 1px dashed var(--terre-300);
    border-radius: var(--r-md);
    background: var(--terre-050);
}
.st__demo.is-sans { border-color: var(--line-2); background: var(--white); }
.st__demo-t { margin: 0; font-size: .86rem; line-height: 1.5; color: var(--ink); }
</style>
