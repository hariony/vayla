<script setup>
/**
 * Statistiques › Demandes : ce que les voyageurs demandent, et comment les
 * propriétaires répondent.
 *
 * **Chaque graphique dit ce qu'il compte et à quelle date il le range** —
 * une demande au mois où elle a été faite. **Aucune moyenne qui trompe** : le
 * délai de réponse est une médiane, le taux de réponse ne compte que les
 * demandes tranchées. Et **pas de flèche de tendance** : sur des volumes de
 * plateforme qui démarre, « +200 % » veut dire « deux de plus ».
 *
 * Les statistiques tenaient sur une seule page, qui gonflait à chaque
 * graphique : elles sont en trois écrans, rangés sous « Statistiques » dans la
 * colonne. Celui-ci ouvre la rubrique.
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
    demandes: { type: Array, required: true },
    reponse: { type: Array, required: true },
    delai: { type: Array, required: true },
    destinations: { type: Array, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

const heures = (v) => (v === null ? '—' : `${String(v).replace('.', ',')} h`)

const tuiles = computed(() => [
    { label: 'Demandes reçues', valeur: nombre(props.chiffres.demandes), compte: props.chiffres.demandes },
    { label: 'Répondues par le propriétaire', valeur: props.chiffres.tauxReponse === null ? '—' : `${props.chiffres.tauxReponse} %` },
    { label: 'Délai médian de réponse', valeur: heures(props.chiffres.delaiMedian) },
])
</script>

<template>
    <Head title="Statistiques — Demandes — Back-office" />

    <div ref="racine">
        <StatsHeader :cadre="cadre" titre="Demandes" base="/statistiques">
            Depuis {{ cadre.periode.debut }}, mois par mois : ce que les voyageurs demandent, et comment les propriétaires y répondent.
        </StatsHeader>

        <StatsFigures :tuiles="tuiles" />

        <div class="of-graphes">
            <OfficeChart
                class="of-graphes__large"
                titre="Demandes de séjour"
                definition="Au mois où la demande a été faite, par issue. « Expirées » : restées 48 h sans réponse, nuits rendues au calendrier."
                type="empile"
                :labels="cadre.mois.courts"
                :labels-longs="cadre.mois.longs"
                :series="demandes"
                :hauteur="280"
            />

            <OfficeChart
                titre="Réponse des propriétaires"
                definition="Part des demandes tranchées qui ont reçu une réponse — acceptées ou refusées. Une demande encore en attente n'est pas comptée."
                :labels="cadre.mois.courts"
                :labels-longs="cadre.mois.longs"
                :series="reponse"
                format="pourcent"
                :plafond="100"
            />

            <OfficeChart
                titre="Délai de réponse"
                definition="Médiane, en heures, entre la demande et la réponse du propriétaire. La moitié répond plus vite, l'autre moitié plus lentement."
                :labels="cadre.mois.courts"
                :labels-longs="cadre.mois.longs"
                :series="delai"
                format="heures"
            />

            <OfficeHBars
                titre="Demandes par destination"
                definition="Sur la période, les dix destinations les plus demandées."
                :lignes="destinations"
                vide="Aucune demande sur la période."
            />
        </div>
    </div>
</template>
