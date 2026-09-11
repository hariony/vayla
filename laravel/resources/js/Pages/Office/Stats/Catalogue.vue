<script setup>
/**
 * Statistiques › Catalogue et inscriptions : ce qui s'ouvre au fil des mois —
 * comptes voyageurs, propriétaires, annonces saisies — et le catalogue **à
 * cet instant**, par état et par barreau. La période ne s'applique pas à ce
 * dernier, et ses deux blocs le disent.
 *
 * Le lagon n'apparaît que sur l'échelle de confiance : c'est une
 * vérification ; le niveau 1, qui n'en est pas une, reste gris.
 */
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeChart from '@/Components/Office/OfficeChart.vue'
import OfficeHBars from '@/Components/Office/OfficeHBars.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { nombre } from '@/Support/format.js'
import StatsFigures from './Partials/StatsFigures.vue'
import StatsHeader from './Partials/StatsHeader.vue'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    cadre: { type: Object, required: true },
    chiffres: { type: Object, required: true },
    inscriptions: { type: Array, required: true },
    statuts: { type: Array, required: true },
    niveaux: { type: Array, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

const tuiles = computed(() => [
    { label: 'Comptes voyageurs ouverts', valeur: nombre(props.chiffres.voyageurs), compte: props.chiffres.voyageurs },
    { label: 'Propriétaires inscrits', valeur: nombre(props.chiffres.proprietaires), compte: props.chiffres.proprietaires },
    { label: 'Annonces saisies', valeur: nombre(props.chiffres.annonces), compte: props.chiffres.annonces },
])
</script>

<template>
    <Head title="Statistiques — Catalogue et inscriptions — Back-office" />

    <div ref="racine">
        <StatsHeader :cadre="cadre" titre="Catalogue et inscriptions" base="/statistiques/catalogue">
            Depuis {{ cadre.periode.debut }}, au mois de la création : qui s'est inscrit, quelles annonces ont été saisies.
            Le catalogue, lui, se lit à cet instant.
        </StatsHeader>

        <StatsFigures :tuiles="tuiles" />

        <div class="of-graphes">
            <OfficeChart
                class="of-graphes__large"
                titre="Inscriptions et catalogue"
                definition="Au mois de la création : comptes voyageurs ouverts, propriétaires inscrits, annonces saisies (brouillons compris)."
                :labels="cadre.mois.courts"
                :labels-longs="cadre.mois.longs"
                :series="inscriptions"
            />

            <OfficeHBars
                titre="Le catalogue aujourd'hui"
                definition="Les annonces par état, à cet instant — la période ne s'y applique pas."
                :lignes="statuts"
            />

            <OfficeHBars
                titre="Annonces en ligne, par niveau"
                definition="La répartition par barreau, jamais une moyenne. Le vert ne commence qu'au niveau 2 : « déclarée » n'est pas une vérification."
                :lignes="niveaux"
            />
        </div>
    </div>
</template>
