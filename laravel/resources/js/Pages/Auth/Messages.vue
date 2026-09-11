<script setup>
/**
 * La boîte du voyageur.
 *
 * **Le pendant exact de celle du propriétaire**, et pour la même raison : le
 * fil ne s'atteignait que par la page d'une réservation, elle-même atteinte
 * par une référence reçue dans un message. Celui qui avait perdu ce message
 * avait perdu la conversation — et c'est précisément le trou que le compte est
 * venu boucher.
 *
 * **Le sujet, ici, c'est le logement, pas une personne.** Le voyageur a écrit
 * à propos d'une maison ; le propriétaire n'a pas à apparaître nommément dans
 * une liste avant d'avoir accepté quoi que ce soit.
 */
import { Head, Link } from '@inertiajs/vue3'

import SiteHeader from '@/Components/SiteHeader.vue'
import SiteFooter from '@/Components/SiteFooter.vue'
import SpaceShell from '@/Components/SpaceShell.vue'
import ConversationList from '@/Components/ConversationList.vue'
import { RUBRIQUES_CLIENT } from '@/Support/espaces.js'

defineProps({
    conversations: { type: Array, default: () => [] },
})
</script>

<template>
    <Head title="Messages — Vayla" />

    <SiteHeader :search="false" />

    <SpaceShell espace="Espace client" :groupes="RUBRIQUES_CLIENT">
        <header class="espace__tete">
            <h1 class="espace__titre">Messages</h1>
            <p class="espace__lede">
                Vos échanges avec les propriétaires, séjour par séjour. Tout ce
                qui s'écrit ici reste attaché à la réservation&nbsp;: c'est la
                trace qu'un message WhatsApp ne laisse pas.
            </p>
        </header>

        <ConversationList
            v-if="conversations.length"
            :conversations="conversations"
            base="/reservations/"
            moi="traveller"
        />

        <div v-else class="ms__vide">
            <p class="ms__vide-t">Aucun message pour l'instant.</p>
            <p class="ms__vide-s">
                Le message que vous déposez en demandant un séjour ouvre la
                conversation avec le propriétaire. Elle apparaîtra ici.
            </p>
            <Link href="/logements" class="btn btn--terre btn--lg ms__vide-go">Chercher un logement</Link>
        </div>
    </SpaceShell>

    <SiteFooter />
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
.ms__vide-go { margin-top: 1.5rem; }
</style>
