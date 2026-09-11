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
 *
 * **L'écran se nomme, il ne salue pas.** « Bonjour X » prenait le premier mot
 * du nom — c'est-à-dire le **nom de famille** dès qu'il est écrit à la
 * malgache, « RAKOTOBE Hariony » — et le criait en capitales à quelqu'un qu'on
 * voulait accueillir. Le nom du compte est de toute façon dans le menu de
 * l'en-tête et au pied de la colonne.
 */
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

</script>

<template>
    <Head :title="`Espace propriétaire — ${owner.name}`" />

    <OwnerShell>
        <header class="espace__tete">
            <h1 class="espace__titre">Demandes</h1>
            <p class="espace__lede">
                Ce qui attend votre réponse, puis vos séjours à venir. Une demande
                sans réponse sous 48&nbsp;h se ferme, et les nuits repartent dans
                votre calendrier.
            </p>
        </header>

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
