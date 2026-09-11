<script setup>
/**
 * Une réservation et son fil d'échange.
 *
 * L'écran existe parce qu'une conversation a besoin de place : la glisser
 * dans la liste aurait donné des lignes de hauteurs très inégales, et un fil
 * de dix messages aurait enterré les neuf réservations suivantes.
 *
 * **Les faits d'abord, l'échange ensuite.** Ce qu'on vient vérifier en
 * ouvrant, c'est qui vient, quand, et combien — la conversation se lit après.
 */
import { Head, Link } from '@inertiajs/vue3'

import OwnerShell from '../Partials/OwnerShell.vue'
import Conversation from '@/Components/Conversation.vue'
import { nombre } from '@/Support/format.js'
import { formatLong } from '@/Composables/useStayDates.js'

const props = defineProps({
    booking: { type: Object, required: true },
    messages: { type: Array, default: () => [] },
})
</script>

<template>
    <Head :title="`${booking.reference} — Vayla`" />

    <OwnerShell :back="{ href: '/proprietaire/reservations', label: 'Mes réservations' }">
        <header class="bk__head">
            <p class="bk__eyebrow num">{{ booking.reference }}</p>
            <h1 class="espace__titre">{{ booking.traveller }}</h1>
            <p class="bk__listing">{{ booking.listing }}</p>
            <span class="bk__state" :class="`bk__state--${booking.status}`">{{ booking.statusLabel }}</span>
        </header>

        <dl class="bk__facts">
            <div>
                <dt>Arrivée</dt>
                <dd>{{ formatLong(booking.arrival) }}</dd>
            </div>
            <div>
                <dt>Départ</dt>
                <dd>{{ formatLong(booking.departure) }}</dd>
            </div>
            <div>
                <dt>Nuits</dt>
                <dd class="num">{{ booking.nights }}</dd>
            </div>
            <div>
                <dt>Personnes</dt>
                <dd class="num">{{ booking.guests }}</dd>
            </div>
            <div>
                <dt>Vous recevez</dt>
                <dd class="num bk__money">{{ nombre(booking.total) }} Ar</dd>
            </div>
            <div>
                <dt>Commission Vayla</dt>
                <dd class="num">
                    {{ nombre(booking.commission) }} Ar
                    <span v-if="!booking.facturable" class="bk__note">non facturée</span>
                </dd>
            </div>
        </dl>

        <!-- Le téléphone reste en évidence : Vayla ne cache pas les numéros,
             et l'appel reste le canal le plus rapide. -->
        <p class="bk__call">
            <a :href="`tel:${booking.phone}`" class="btn btn--outline">Appeler {{ booking.traveller.split(' ')[0] }}</a>
            <span class="bk__tel num">{{ booking.phone }}</span>
        </p>

        <p v-if="booking.reason" class="bk__reason">Motif enregistré : « {{ booking.reason }} »</p>

        <div class="bk__conv">
            <Conversation
                :messages="messages"
                :action="`/proprietaire/reservations/${booking.reference}/messages`"
                destinataire="Répondre au voyageur"
            />
        </div>

        <p v-if="booking.listingSlug" class="bk__cal">
            <Link :href="`/proprietaire/logements/${booking.listingSlug}/calendrier`">
                Voir le calendrier de ce logement
            </Link>
        </p>
    </OwnerShell>
</template>

<style scoped>
.bk__head { margin-bottom: 1.5rem; }
.bk__eyebrow { margin: 0 0 .3rem; font-size: .78rem; font-weight: 700; letter-spacing: .06em; color: var(--text-3); }
.bk__listing { margin: .2rem 0 .6rem; font-size: .95rem; color: var(--text-2); }

.bk__state {
    display: inline-block;
    padding: .28rem .8rem;
    border-radius: var(--r-pill);
    background: var(--off-2);
    font-size: .76rem;
    font-weight: 800;
    color: var(--text-2);
}
.bk__state--accepted, .bk__state--completed { background: var(--ink); color: var(--white); }

.bk__facts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(8.5rem, 1fr));
    gap: 1rem 1.25rem;
    margin: 0 0 1.25rem;
    padding: 1.15rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
}
.bk__facts dt { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--text-3); }
.bk__facts dd { margin: .25rem 0 0; font-size: 1rem; font-weight: 700; color: var(--ink); }
.bk__money { font-size: 1.15rem; }
.bk__note { display: block; margin-top: .1rem; font-size: .72rem; font-weight: 500; color: var(--text-3); }

.bk__call { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; margin: 0 0 1.25rem; }
.bk__tel { font-size: .9rem; font-weight: 600; color: var(--text-2); }

.bk__reason {
    margin: 0 0 1.25rem;
    padding: .8rem 1rem;
    border-left: 3px solid var(--line-2);
    background: var(--off);
    font-size: .9rem;
    line-height: 1.55;
    color: var(--text-2);
}

.bk__conv {
    padding: clamp(1.1rem, 3vw, 1.6rem);
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
}

.bk__cal { margin: 1.25rem 0 0; font-size: .86rem; font-weight: 600; color: var(--text-2); }
</style>
