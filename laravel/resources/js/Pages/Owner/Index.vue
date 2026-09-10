<script setup>
/**
 * L'espace propriétaire — orchestrateur.
 *
 * **Un seul écran, ordonné par urgence.** Le propriétaire n'ouvre pas Vayla
 * par curiosité : il l'ouvre parce qu'un message WhatsApp lui dit qu'une
 * demande attend. Ce qui exige une réponse vient donc en premier, et c'est le
 * seul bloc qui porte des boutons ; tout le reste se lit.
 *
 * **Aucun onglet, aucun menu.** Quatre blocs empilés se parcourent au pouce
 * sans rien apprendre. Le calendrier d'un logement est le seul écran second,
 * et on y va par un bouton posé sur le logement concerné — pas par une
 * navigation à comprendre. Le jour où un propriétaire aura trente logements
 * il faudra paginer ; en attendre le besoin coûte moins cher que de faire
 * naviguer tout le monde tout de suite.
 *
 * Le cadre (bandeau, fond, messages de retour) est dans `OwnerShell`, partagé
 * avec le calendrier.
 */
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

import OwnerShell from './Partials/OwnerShell.vue'
import RequestList from './Partials/RequestList.vue'
import UpcomingList from './Partials/UpcomingList.vue'
import ListingList from './Partials/ListingList.vue'
import InvoiceCard from './Partials/InvoiceCard.vue'

const props = defineProps({
    owner: { type: Object, required: true },
    pending: { type: Array, default: () => [] },
    upcoming: { type: Array, default: () => [] },
    listings: { type: Array, default: () => [] },
    invoice: { type: Object, required: true },
    demo: { type: Boolean, default: false },
})

/** Le prénom seul : on s'adresse à quelqu'un, pas à une ligne de base. */
const prenom = computed(() => props.owner.name.split(' ')[0])
</script>

<template>
    <Head :title="`Espace propriétaire — ${owner.name}`" />

    <OwnerShell>
        <div class="op__hello">
            <h1 class="op__title">Bonjour {{ prenom }}</h1>
            <p class="op__sub">
                {{ owner.name }}<template v-if="owner.city"> · {{ owner.city }}</template>
            </p>
        </div>

        <p v-if="demo" class="op__demo">
            <span class="chip chip--terre">Aperçu</span>
            Données de démonstration : ces demandes et ces séjours sont fictifs.
        </p>

        <div class="op__block"><RequestList :requests="pending" /></div>
        <div class="op__block"><UpcomingList :stays="upcoming" /></div>
        <div class="op__block"><ListingList :listings="listings" /></div>
        <div class="op__block"><InvoiceCard :invoice="invoice" /></div>
    </OwnerShell>
</template>

<style scoped>
.op__hello { margin-bottom: 1.5rem; }
.op__title { margin: 0; font-size: clamp(1.7rem, 4vw, 2.2rem); font-weight: 800; letter-spacing: -.045em; color: var(--ink); }
.op__sub { margin: .35rem 0 0; font-size: .95rem; color: var(--text-2); }

.op__demo {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: .25rem .6rem;
    margin: 0 0 1.25rem;
    font-size: .84rem;
    color: var(--text-3);
}

/* Des blocs séparés par du blanc, pas par des filets : quatre cadres
   emboîtés donnent un formulaire administratif, pas un outil. */
.op__block + .op__block { margin-top: clamp(2rem, 4.5vw, 3rem); }
</style>
