<script setup>
/**
 * L'historique complet des réservations.
 *
 * Le tableau de bord ne montre que ce qui exige une action. Cet écran répond
 * à l'autre question, celle qu'on se pose une fois par mois : **qui est venu,
 * qu'est-ce qui a été refusé, qu'est-ce qui a expiré**. Sans lui, une demande
 * répondue disparaissait de l'espace et le propriétaire n'en gardait aucune
 * trace.
 *
 * **Chaque ligne dit si elle est facturable.** C'est la question qui vient
 * juste après « qui est venu » : seul un séjour **confirmé par le voyageur**
 * se facture, jamais une réservation acceptée. Le dire ici évite le « pourquoi
 * cette ligne n'est pas sur ma facture » de fin de mois.
 *
 * Le filtre est une **liste de pastilles avec leur compte**, pas un menu
 * déroulant : cinq états tiennent à l'écran, et un choix visible se corrige
 * sans rouvrir quoi que ce soit.
 */
import { Head, Link, router } from '@inertiajs/vue3'

import OwnerShell from '../Partials/OwnerShell.vue'
import { nombre } from '@/Support/format.js'
import { formatCompact } from '@/Composables/useStayDates.js'

defineProps({
    bookings: { type: Array, default: () => [] },
    filtre: { type: String, default: 'tous' },
    compteurs: { type: Array, default: () => [] },
})

const filtrer = (valeur) => router.get('/proprietaire/reservations',
    valeur === 'tous' ? {} : { statut: valeur },
    { preserveScroll: true, preserveState: true })
</script>

<template>
    <Head title="Mes réservations — Vayla" />

    <OwnerShell>
        <header class="rs__head">
            <h1 class="rs__title">Mes réservations</h1>
            <p class="rs__lede">
                Tout ce qui est passé par Vayla, y compris les demandes refusées et expirées.
            </p>
        </header>

        <div class="rs__filters" role="group" aria-label="Filtrer par état">
            <button
                v-for="c in compteurs"
                :key="c.value"
                type="button"
                class="chip rs__chip"
                :class="{ 'is-on': filtre === c.value }"
                @click="filtrer(c.value)"
            >
                {{ c.label }} <span class="num rs__n">{{ c.n }}</span>
            </button>
        </div>

        <p v-if="!bookings.length" class="rs__empty">
            Aucune réservation dans cette catégorie.
        </p>

        <ul v-else class="rs__list">
            <li v-for="b in bookings" :key="b.reference" class="rs__row">
                <Link :href="`/proprietaire/reservations/${b.reference}`" class="rs__main">
                    <p class="rs__when num">
                        {{ formatCompact(b.arrival) }} → {{ formatCompact(b.departure) }}
                        <span class="rs__nights">{{ b.nights }} nuit{{ b.nights > 1 ? 's' : '' }}</span>
                    </p>
                    <p class="rs__listing">{{ b.listing }}</p>
                    <p class="rs__who">
                        {{ b.traveller }} · {{ b.guests }} pers.
                        <a class="rs__tel" :href="`tel:${b.phone}`">{{ b.phone }}</a>
                    </p>
                    <p v-if="b.reason" class="rs__reason">« {{ b.reason }} »</p>

                    <!-- Le non-lu se compte en **conversations**, pas en
                         messages : « 2 » veut dire « deux échanges vous
                         attendent », pas « quatorze lignes de texte ». -->
                    <p class="rs__msg">
                        <span v-if="b.nonLus" class="rs__unread">{{ b.nonLus }} nouveau{{ b.nonLus > 1 ? 'x' : '' }} message{{ b.nonLus > 1 ? 's' : '' }}</span>
                        <span v-else-if="b.messages">{{ b.messages }} message{{ b.messages > 1 ? 's' : '' }}</span>
                        <span v-else>Ouvrir l'échange</span>
                    </p>
                </Link>

                <div class="rs__side">
                    <span class="rs__state" :class="`rs__state--${b.status}`">{{ b.statusLabel }}</span>
                    <p class="rs__total num">{{ nombre(b.total) }} Ar</p>
                    <p class="rs__fee num">
                        <template v-if="b.facturable">Commission {{ nombre(b.commission) }} Ar</template>
                        <template v-else>Non facturée</template>
                    </p>
                    <span class="rs__ref num">{{ b.reference }}</span>
                </div>
            </li>
        </ul>

        <p class="rs__note">
            Vayla ne facture que les <strong>séjours confirmés par le voyageur</strong>, jamais
            les réservations. Une réservation acceptée mais non confirmée n'apparaît sur aucune facture.
        </p>
    </OwnerShell>
</template>

<style scoped>
.rs__head { margin-bottom: 1.25rem; }
.rs__title { margin: 0; font-size: clamp(1.6rem, 4vw, 2.1rem); font-weight: 800; letter-spacing: -.045em; color: var(--ink); }
.rs__lede { margin: .35rem 0 0; max-width: 54ch; font-size: .92rem; line-height: 1.55; color: var(--text-2); }

.rs__filters { display: flex; flex-wrap: wrap; gap: .45rem; margin-bottom: 1.25rem; }
.rs__chip {
    min-height: 2.5rem;
    border: 1px solid var(--line-2);
    background: var(--white);
    color: var(--text-2);
    cursor: pointer;
    font: inherit;
    font-size: .84rem;
    font-weight: 700;
}
.rs__chip.is-on { border-color: var(--ink); background: var(--ink); color: var(--white); }
.rs__n { opacity: .65; }

.rs__empty {
    margin: 0;
    padding: 2rem;
    border: 1px dashed var(--line-2);
    border-radius: var(--r-lg);
    text-align: center;
    font-size: .92rem;
    color: var(--text-3);
}

.rs__list { display: grid; gap: .6rem; margin: 0; padding: 0; list-style: none; }

.rs__row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem 1.25rem;
    padding: 1rem 1.15rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
}

.rs__main { min-width: 0; text-decoration: none; }

.rs__msg { margin: .5rem 0 0; font-size: .8rem; font-weight: 600; color: var(--text-3); }
/* Le non-lu prend la terre : c'est le seul endroit de la ligne qui appelle une
   action. Le lagon ne dit que « vérifié », il n'entre pas ici. */
.rs__unread { color: var(--terre-600); }
.rs__when { margin: 0; font-size: 1rem; font-weight: 800; letter-spacing: -.02em; color: var(--ink); }
.rs__nights { margin-left: .5rem; font-size: .8rem; font-weight: 500; color: var(--text-3); }
.rs__listing { margin: .2rem 0 0; font-size: .9rem; font-weight: 600; color: var(--text-2); }
.rs__who { margin: .15rem 0 0; font-size: .84rem; color: var(--text-3); }
.rs__tel { margin-left: .4rem; font-weight: 600; color: var(--terre-600); }
.rs__reason {
    margin: .5rem 0 0;
    padding-left: .7rem;
    border-left: 3px solid var(--line-2);
    font-size: .84rem;
    line-height: 1.5;
    color: var(--text-2);
}

.rs__side { text-align: right; }

/* L'état prend un aplat neutre : la terre porte l'action, le lagon ne dit que
   « vérifié ». Un état de réservation n'est ni l'un ni l'autre. */
.rs__state {
    display: inline-block;
    padding: .25rem .7rem;
    border-radius: var(--r-pill);
    background: var(--off-2);
    font-size: .72rem;
    font-weight: 800;
    color: var(--text-2);
}
.rs__state--accepted, .rs__state--completed { background: var(--ink); color: var(--white); }

.rs__total { margin: .5rem 0 0; font-size: 1.05rem; font-weight: 800; color: var(--ink); }
.rs__fee { margin: .1rem 0 0; font-size: .78rem; color: var(--text-3); }
.rs__ref { display: inline-block; margin-top: .4rem; font-size: .74rem; color: var(--text-3); }

.rs__note {
    margin: 1.5rem 0 0;
    padding-top: 1.1rem;
    border-top: 1px solid var(--line);
    max-width: 62ch;
    font-size: .82rem;
    line-height: 1.55;
    color: var(--text-3);
}
.rs__note strong { color: var(--text-2); }
</style>
