<script setup>
/**
 * Statistiques › Séjours et commission : les séjours effectués — les seuls
 * qui se facturent — et la commission qu'ils portent.
 *
 * Les deux se rangent **au mois du départ**, la règle de la facture : ils
 * vivent donc sur le même écran, et les chiffres de l'un expliquent ceux de
 * l'autre. La commission y a toute la largeur (`CommissionPanel`) : ce qui
 * est facturé, reçu, ce qui manque, et chez qui.
 */
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeChart from '@/Components/Office/OfficeChart.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { nombre } from '@/Support/format.js'
import CommissionPanel from './Partials/CommissionPanel.vue'
import StatsFigures from './Partials/StatsFigures.vue'
import StatsHeader from './Partials/StatsHeader.vue'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    cadre: { type: Object, required: true },
    chiffres: { type: Object, required: true },
    sejours: { type: Array, required: true },
    commission: { type: Object, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

const tuiles = computed(() => [
    { label: 'Séjours effectués', valeur: nombre(props.chiffres.sejours), compte: props.chiffres.sejours },
    { label: 'Nuits', valeur: nombre(props.chiffres.nuits), compte: props.chiffres.nuits },
])
</script>

<template>
    <Head title="Statistiques — Séjours et commission — Back-office" />

    <div ref="racine">
        <StatsHeader :cadre="cadre" titre="Séjours et commission" base="/statistiques/sejours">
            Depuis {{ cadre.periode.debut }}, au mois du départ — la règle de la facture. Seuls comptent les séjours confirmés
            par le voyageur : ce sont les seuls qui se facturent.
        </StatsHeader>

        <StatsFigures :tuiles="tuiles" />

        <div class="of-graphes">
            <CommissionPanel
                class="of-graphes__large"
                :commission="commission"
                :labels="cadre.mois.courts"
                :labels-longs="cadre.mois.longs"
            />

            <OfficeChart
                class="of-graphes__large"
                titre="Séjours effectués"
                definition="Au mois du départ, seulement les séjours confirmés par le voyageur — les seuls qui se facturent."
                type="barres"
                :labels="cadre.mois.courts"
                :labels-longs="cadre.mois.longs"
                :series="sejours"
                :hauteur="220"
            />
        </div>
    </div>
</template>
