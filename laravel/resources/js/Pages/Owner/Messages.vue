<script setup>
/**
 * La boîte du propriétaire.
 *
 * **Elle existe parce qu'un message se rate.** Les fils ne vivaient que *dans*
 * chaque réservation : pour savoir si quelqu'un attendait une réponse, il
 * fallait ouvrir les réservations une par une, ou remarquer une pastille sur
 * un onglet qui ne disait pas laquelle. Un voyageur qui pose une question
 * trois jours avant d'arriver et n'obtient rien ne revient pas — et il le dira
 * dans sa confirmation de séjour, qui est publique.
 *
 * **La liste mène au fil, elle ne le rend pas** : la conversation s'ouvre sur
 * la réservation, avec ses dates, son total et ses boutons. Un message sans
 * son séjour ne veut rien dire — « c'est possible d'arriver plus tard ? » se
 * répond en regardant la date d'arrivée.
 */
import { Head } from '@inertiajs/vue3'

import OwnerShell from './Partials/OwnerShell.vue'
import ConversationList from '@/Components/ConversationList.vue'

defineProps({
    conversations: { type: Array, default: () => [] },
})
</script>

<template>
    <Head title="Messages — Vayla" />

    <OwnerShell>
        <header class="espace__tete">
            <h1 class="espace__titre">Messages</h1>
            <p class="espace__lede">
                Les échanges ouverts autour de vos réservations. Répondre vite est
                ce qui fait accepter un séjour&nbsp;: le voyageur écrit souvent à
                plusieurs propriétaires le même jour.
            </p>
        </header>

        <ConversationList
            v-if="conversations.length"
            :conversations="conversations"
            base="/proprietaire/reservations/"
            moi="owner"
        />

        <div v-else class="ms__vide">
            <p class="ms__vide-t">Aucun message pour l'instant.</p>
            <p class="ms__vide-s">
                Le message qu'un voyageur dépose avec sa demande ouvre la
                conversation&nbsp;: elle apparaîtra ici, et Vayla vous préviendra
                sur WhatsApp.
            </p>
        </div>
    </OwnerShell>
</template>

<style scoped>
.ms__vide {
    padding: clamp(2rem, 6vw, 3.5rem);
    border: 1px dashed var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
    text-align: center;
}
.ms__vide-t { margin: 0; font-size: 1.15rem; font-weight: 800; letter-spacing: -.025em; color: var(--ink); }
.ms__vide-s { margin: .5rem auto 0; max-width: 52ch; font-size: .92rem; line-height: 1.6; color: var(--text-2); }
</style>
