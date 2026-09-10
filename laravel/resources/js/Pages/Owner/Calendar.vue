<script setup>
/**
 * Le calendrier d'un logement — orchestrateur.
 *
 * **Pourquoi cet écran existe.** Les propriétaires de Vayla louaient déjà par
 * WhatsApp et par le bouche-à-oreille avant d'arriver ici, et continueront :
 * sans un endroit où déclarer leurs nuits vendues ailleurs, ils reçoivent des
 * demandes sur des dates déjà prises et n'ont d'autre choix que de les
 * refuser une par une. Le refus abîme la relation avec le voyageur, fait
 * baisser la crédibilité de Vayla, et ne corrige rien : la semaine suivante,
 * une autre demande arrive sur les mêmes nuits.
 *
 * **Une page à part, pas un cinquième bloc du tableau de bord.** Ce qui
 * compte à l'ouverture de l'espace, c'est la demande qui expire dans
 * quarante-huit heures ; douze mois de calendrier par logement l'auraient
 * noyée. On y arrive par un bouton posé sur le logement concerné — une
 * navigation qui ne s'apprend pas.
 *
 * Il ne porte ni balisage de section ni règle de style : le cadre est dans
 * `OwnerShell`, le reste dans deux partiels.
 */
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

import OwnerShell from './Partials/OwnerShell.vue'
import BlockForm from './Partials/BlockForm.vue'
import PeriodList from './Partials/PeriodList.vue'

const props = defineProps({
    listing: { type: Object, required: true },
    calendar: { type: Object, required: true },
    declared: { type: Array, default: () => [] },
    booked: { type: Array, default: () => [] },
    reasons: { type: Array, default: () => [] },
})

const base = computed(() => `/proprietaire/logements/${props.listing.slug}/calendrier`)
</script>

<template>
    <Head :title="`Calendrier — ${listing.title}`" />

    <OwnerShell :back="{ href: '/proprietaire', label: 'Retour à mon espace' }">
        <div class="oc__hello">
            <p class="oc__eyebrow">Calendrier</p>
            <h1 class="oc__title">{{ listing.title }}</h1>
            <p v-if="listing.place" class="oc__sub">{{ listing.place }}</p>
        </div>

        <div class="oc__block"><BlockForm :calendar="calendar" :reasons="reasons" :action="base" /></div>
        <div class="oc__block"><PeriodList :declared="declared" :booked="booked" :base="base" /></div>
    </OwnerShell>
</template>

<style scoped>
.oc__hello { margin-bottom: 1.75rem; }
.oc__eyebrow { margin: 0 0 .3rem; font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--terre-600); }
.oc__title { margin: 0; font-size: clamp(1.6rem, 4vw, 2.1rem); font-weight: 800; letter-spacing: -.045em; color: var(--ink); }
.oc__sub { margin: .3rem 0 0; font-size: .95rem; color: var(--text-2); }

.oc__block + .oc__block { margin-top: clamp(2.25rem, 5vw, 3.25rem); }
</style>
