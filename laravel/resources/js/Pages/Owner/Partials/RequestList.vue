<script setup>
/**
 * Les demandes en attente de réponse — le seul bloc de l'écran qui agit.
 *
 * Trois partis pris, et ils viennent tous du même constat : le propriétaire
 * n'est pas là pour explorer, il est là pour répondre.
 *
 * — **Le temps restant est écrit en heures, pas en date d'expiration.**
 *   « Il vous reste 41 h » se comprend sans calcul ; « expire le 5 à 14 h 12 »
 *   demande de savoir quelle heure il est et quel jour on est.
 * — **La commission est affichée avant la réponse**, à côté du total. La
 *   découvrir sur la facture de fin de mois, c'est se sentir piégé — et avoir
 *   raison. Le taux est figé à la réservation, donc connu dès maintenant.
 * — **Refuser demande une confirmation, accepter non.** Accepter est le
 *   chemin normal et reste réversible (une annulation existe). Refuser rend
 *   les nuits et ferme la demande : c'est irréversible, et une main qui
 *   glisse sur un téléphone ne doit pas coûter une réservation.
 */
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

import { nombre } from '@/Support/format.js'
import { formatCompact } from '@/Composables/useStayDates.js'

defineProps({
    requests: { type: Array, default: () => [] },
})

const refus = ref(null)     // la référence dont on confirme le refus
const motif = ref('')
const envoi = ref(null)

function accepter(reference) {
    envoi.value = reference
    router.post(`/proprietaire/demandes/${reference}/accepter`, {}, {
        preserveScroll: true,
        onFinish: () => (envoi.value = null),
    })
}

function confirmerRefus(reference) {
    envoi.value = reference
    router.post(`/proprietaire/demandes/${reference}/refuser`,
        { reason: motif.value }, {
            preserveScroll: true,
            onFinish: () => {
                envoi.value = null
                refus.value = null
                motif.value = ''
            },
        })
}

/** Sous douze heures, l'urgence change de nature : on le dit en rouge. */
const presse = (h) => h !== null && h <= 12
</script>

<template>
    <section class="rq">
        <header class="rq__head">
            <h2 class="rq__title">
                À répondre
                <span v-if="requests.length" class="rq__n num">{{ requests.length }}</span>
            </h2>
            <p v-if="requests.length" class="rq__lede">
                Sans réponse de votre part, les nuits sont rendues à votre calendrier
                et le voyageur cherche ailleurs.
            </p>
        </header>

        <p v-if="!requests.length" class="rq__empty">
            Aucune demande en attente. Vous êtes à jour.
        </p>

        <ul v-else class="rq__list">
            <li v-for="r in requests" :key="r.reference" class="rq__card">
                <div class="rq__top">
                    <div>
                        <p class="rq__listing">{{ r.listing }}</p>
                        <p class="rq__who">
                            {{ r.traveller }}
                            <a class="rq__tel" :href="`tel:${r.phone}`">{{ r.phone }}</a>
                        </p>
                    </div>

                    <p
                        v-if="r.hoursLeft !== null"
                        class="rq__clock"
                        :class="{ 'is-urgent': presse(r.hoursLeft) }"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" /><path d="M12 7v5.5l3.5 2" />
                        </svg>
                        <span><span class="num">{{ r.hoursLeft }}</span> h pour répondre</span>
                    </p>
                </div>

                <dl class="rq__facts">
                    <div>
                        <dt>Séjour</dt>
                        <dd class="num">{{ formatCompact(r.arrival) }} → {{ formatCompact(r.departure) }}</dd>
                    </div>
                    <div>
                        <dt>Nuits</dt>
                        <dd class="num">{{ r.nights }}</dd>
                    </div>
                    <div>
                        <dt>Personnes</dt>
                        <dd class="num">{{ r.guests }}</dd>
                    </div>
                    <div>
                        <dt>Vous recevez</dt>
                        <dd class="num rq__money">{{ nombre(r.total) }} Ar</dd>
                    </div>
                    <div>
                        <dt>Commission Vayla</dt>
                        <dd class="num">{{ nombre(r.commission) }} Ar</dd>
                    </div>
                </dl>

                <p v-if="r.message" class="rq__msg">« {{ r.message }} »</p>

                <!-- Un rappel, pas une note de bas de page : c'est ce qui
                     distingue Vayla d'une plateforme qui encaisse. -->
                <p class="rq__note">
                    Le voyageur vous règle sur place. La commission n'est facturée
                    qu'après le séjour, s'il est confirmé.
                </p>

                <div v-if="refus !== r.reference" class="rq__actions">
                    <button
                        type="button"
                        class="btn btn--terre btn--lg rq__accept"
                        :disabled="envoi === r.reference"
                        @click="accepter(r.reference)"
                    >
                        {{ envoi === r.reference ? 'Envoi…' : 'Accepter la demande' }}
                    </button>
                    <button type="button" class="btn btn--outline rq__decline" @click="refus = r.reference">
                        Refuser
                    </button>
                </div>

                <!-- Refuser est irréversible : on le confirme, et on laisse
                     dire pourquoi. Le motif part au voyageur — un refus sans
                     explication use la relation des deux côtés. -->
                <div v-else class="rq__confirm">
                    <p class="rq__confirm-q">Refuser cette demande ?</p>
                    <p class="rq__confirm-s">
                        Les nuits repartent dans votre calendrier et le voyageur est prévenu.
                        C'est définitif.
                    </p>
                    <label class="rq__label" :for="`motif-${r.reference}`">
                        Motif (facultatif, transmis au voyageur)
                    </label>
                    <input
                        :id="`motif-${r.reference}`"
                        v-model="motif"
                        type="text"
                        class="rq__input"
                        maxlength="140"
                        placeholder="Ex. : le logement est déjà pris ces dates-là"
                    >
                    <div class="rq__actions">
                        <button
                            type="button"
                            class="btn btn--ink btn--lg"
                            :disabled="envoi === r.reference"
                            @click="confirmerRefus(r.reference)"
                        >
                            {{ envoi === r.reference ? 'Envoi…' : 'Oui, refuser' }}
                        </button>
                        <button type="button" class="btn btn--outline" @click="refus = null; motif = ''">
                            Annuler
                        </button>
                    </div>
                </div>
            </li>
        </ul>
    </section>
</template>

<style scoped>
.rq__head { margin-bottom: 1.1rem; }

.rq__title {
    display: flex;
    align-items: center;
    gap: .6rem;
    margin: 0;
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.032em;
    color: var(--ink);
}

.rq__n {
    display: grid;
    place-items: center;
    min-width: 1.7rem;
    height: 1.7rem;
    padding: 0 .45rem;
    border-radius: var(--r-pill);
    background: var(--terre-500);
    font-size: .84rem;
    font-weight: 800;
    color: var(--white);
}

.rq__lede { margin: .5rem 0 0; max-width: 56ch; font-size: .9rem; line-height: 1.55; color: var(--text-2); }

.rq__empty {
    margin: 0;
    padding: 1.5rem;
    border: 1px dashed var(--line-2);
    border-radius: var(--r-lg);
    text-align: center;
    font-size: .92rem;
    color: var(--text-3);
}

.rq__list { display: grid; gap: 1rem; margin: 0; padding: 0; list-style: none; }

.rq__card {
    padding: clamp(1.1rem, 2.5vw, 1.5rem);
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
    box-shadow: 0 2px 14px -10px rgba(23, 20, 28, .4);
}

.rq__top {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem 1.25rem;
}

.rq__listing { margin: 0; font-size: 1.05rem; font-weight: 800; letter-spacing: -.025em; color: var(--ink); }
.rq__who { margin: .2rem 0 0; font-size: .9rem; color: var(--text-2); }
.rq__tel { margin-left: .5rem; font-weight: 600; color: var(--terre-600); }

/* Le compte à rebours est une information, pas une alarme — jusqu'à douze
   heures. En dessous, la terre : c'est le seul moment où l'urgence est réelle. */
.rq__clock {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    margin: 0;
    padding: .4rem .8rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    font-size: .82rem;
    font-weight: 700;
    color: var(--text-2);
    white-space: nowrap;
}
.rq__clock svg { width: 1rem; height: 1rem; }
.rq__clock.is-urgent { border-color: var(--terre-300); background: var(--terre-050); color: var(--terre-700); }

.rq__facts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(7.5rem, 1fr));
    gap: .9rem 1.25rem;
    margin: 1.1rem 0 0;
    padding: 1rem 0 0;
    border-top: 1px solid var(--line);
}
.rq__facts dt { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--text-3); }
.rq__facts dd { margin: .25rem 0 0; font-size: 1rem; font-weight: 700; color: var(--ink); }
.rq__money { font-size: 1.15rem; }

.rq__msg {
    margin: 1rem 0 0;
    padding: .8rem 1rem;
    border-left: 3px solid var(--line-2);
    border-radius: 0 var(--r-sm) var(--r-sm) 0;
    background: var(--off);
    font-size: .92rem;
    line-height: 1.6;
    color: var(--text-2);
}

.rq__note { margin: .9rem 0 0; font-size: .8rem; line-height: 1.5; color: var(--text-3); }

.rq__actions { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1.1rem; }
.rq__accept { flex: 1 1 14rem; }

.rq__confirm {
    margin-top: 1.1rem;
    padding: 1rem 1.1rem 1.1rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--off);
}
.rq__confirm-q { margin: 0; font-size: 1rem; font-weight: 800; color: var(--ink); }
.rq__confirm-s { margin: .35rem 0 .9rem; font-size: .88rem; line-height: 1.55; color: var(--text-2); }

.rq__label { display: block; margin-bottom: .35rem; font-size: .78rem; font-weight: 700; color: var(--text-2); }
.rq__input {
    width: 100%;
    padding: .65rem .8rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--white);
    font: inherit;
    font-size: .9rem;
    color: var(--ink);
}
.rq__input:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 1px; }
</style>
