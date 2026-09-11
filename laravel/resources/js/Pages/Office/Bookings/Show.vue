<script setup>
/**
 * Une réservation, vue par Vayla — **l'écran de la médiation**.
 *
 * Le fil est au centre, parce que c'est ce qu'on vient relire : qui a promis
 * quoi, et quand. Vayla y écrit **dans le fil**, jamais à côté — un mot qui
 * arriverait par un autre canal serait inconnu de l'autre partie, et le fil
 * cesserait d'être la trace commune. L'écran le dit au-dessus du champ.
 *
 * **Lire ici ne marque rien comme lu** : ouvrir le fil depuis le back-office
 * ne doit pas faire croire au propriétaire qu'il a répondu.
 *
 * **Annuler est le seul geste irréversible de l'écran** : il est bordé, isolé
 * en bas de la colonne, et demande un motif — qui part dans le fil, au nom de
 * Vayla, pour que les deux parties le lisent.
 */
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

import Conversation from '@/Components/Conversation.vue'
import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { formatLong } from '@/Composables/useStayDates.js'
import { ariary, ilYA } from '@/Support/format.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    reservation: { type: Object, required: true },
    proprietaire: { type: Object, default: null },
    messages: { type: Array, default: () => [] },
    journal: { type: Array, default: () => [] },
})

const racine = ref(null)
useOfficeMotion(racine)

const annulation = ref(false)
const motif = useForm({ reason: '' })
const annuler = () => motif.post(`/reservations/${props.reservation.reference}/annuler`, {
    preserveScroll: true,
    onSuccess: () => { annulation.value = false; motif.reset() },
})

const PUCE = { pending: 'of-chip--attente', accepted: 'of-chip--actif', completed: 'of-chip--actif' }
const pourcent = (t) => Math.round(t * 1000) / 10
</script>

<template>
    <Head :title="`${reservation.reference} — Back-office`" />

    <div ref="racine">
        <OfficeHead :back="{ href: '/reservations', label: 'Réservations' }" :kicker="`Réservation ${reservation.reference}`" :titre="reservation.listing?.title ?? 'Logement retiré'">
            <template #lede>
                {{ formatLong(reservation.arrival) }} → {{ formatLong(reservation.departure) }} ·
                {{ reservation.nights }} nuit{{ reservation.nights > 1 ? 's' : '' }} · {{ reservation.guests }} pers.
            </template>
            <template #actions>
                <span class="of-chip" :class="PUCE[reservation.status] ?? 'of-chip--clos'">{{ reservation.statusLabel }}</span>
                <span v-if="reservation.heuresRestantes !== null" class="of-chip of-chip--attente of-num">reste {{ reservation.heuresRestantes }} h</span>
            </template>
        </OfficeHead>

        <div class="bs">
            <div class="bs__main">
                <p v-if="reservation.closedReason" class="bs__clos" data-reveal>
                    <span class="of-kicker">Motif de clôture</span>
                    {{ reservation.closedReason }}
                </p>

                <section class="of-card" data-reveal aria-labelledby="fil-t">
                    <header class="of-card__h"><h2 id="fil-t" class="of-card__t">Le fil</h2></header>
                    <div class="of-card__b">
                        <p class="bs__note">
                            <OfficeIcon name="alerte" />
                            Vous écrivez <strong>au nom de Vayla</strong> : le voyageur et le propriétaire lisent tous les deux ce message.
                        </p>
                        <Conversation
                            :messages="messages"
                            :action="`/reservations/${reservation.reference}/messages`"
                            destinataire="Écrire aux deux parties"
                            mediation
                        />
                    </div>
                </section>

                <section v-if="journal.length" class="of-card" data-reveal aria-labelledby="jo-t">
                    <header class="of-card__h"><h2 id="jo-t" class="of-card__t">Gestes de l'équipe</h2></header>
                    <ol class="bs__journal">
                        <li v-for="j in journal" :key="j.id">
                            <p class="bs__j-t">{{ j.summary }}</p>
                            <p class="bs__j-s">{{ j.admin }} · {{ ilYA(j.at) }}</p>
                        </li>
                    </ol>
                </section>
            </div>

            <aside class="bs__side">
                <section class="of-card" data-reveal aria-labelledby="ar-t">
                    <header class="of-card__h"><h2 id="ar-t" class="of-card__t">L'argent</h2></header>
                    <dl class="of-card__b bs__argent">
                        <div><dt>Prix figé à la réservation</dt><dd class="of-num">{{ ariary(reservation.pricePerNight) }} / nuit</dd></div>
                        <div><dt>Total du séjour</dt><dd class="of-num">{{ ariary(reservation.total) }}</dd></div>
                        <div class="bs__com">
                            <dt>Commission Vayla ({{ pourcent(reservation.rate) }} %)</dt>
                            <dd class="of-num">{{ ariary(reservation.commission) }}</dd>
                        </div>
                    </dl>
                    <p class="bs__rappel">Facturée seulement si le voyageur confirme son séjour. Rien n'est encaissé par Vayla.</p>
                </section>

                <section class="of-card" data-reveal aria-labelledby="vo-t">
                    <header class="of-card__h"><h2 id="vo-t" class="of-card__t">Voyageur</h2></header>
                    <div class="of-card__b bs__partie">
                        <p class="bs__nom">{{ reservation.voyageur.name }}</p>
                        <p v-if="reservation.voyageur.email" class="bs__s">{{ reservation.voyageur.email }}</p>
                        <template v-if="reservation.voyageur.telephone">
                            <p class="bs__tel of-num">{{ reservation.voyageur.telephone.lisible }}</p>
                            <div class="bs__btns">
                                <a :href="reservation.voyageur.telephone.tel" class="btn btn--sm btn--outline"><OfficeIcon name="appel" /> Appeler</a>
                                <a v-if="reservation.voyageur.telephone.whatsapp" :href="reservation.voyageur.telephone.whatsapp" target="_blank" rel="noopener" class="btn btn--sm btn--outline"><OfficeIcon name="whatsapp" /> WhatsApp</a>
                            </div>
                        </template>
                        <p v-if="reservation.message" class="bs__demande">« {{ reservation.message }} »</p>
                    </div>
                </section>

                <section v-if="proprietaire" class="of-card" data-reveal aria-labelledby="pr-t">
                    <header class="of-card__h">
                        <h2 id="pr-t" class="of-card__t">Propriétaire</h2>
                        <Link :href="`/proprietaires/${proprietaire.id}`" class="of-card__lien">Sa fiche</Link>
                    </header>
                    <div class="of-card__b bs__partie">
                        <p class="bs__nom">{{ proprietaire.name }}</p>
                        <template v-if="proprietaire.telephone">
                            <p class="bs__tel of-num">{{ proprietaire.telephone.lisible }}</p>
                            <div class="bs__btns">
                                <a :href="proprietaire.telephone.tel" class="btn btn--sm btn--outline"><OfficeIcon name="appel" /> Appeler</a>
                                <a v-if="proprietaire.telephone.whatsapp" :href="proprietaire.telephone.whatsapp" target="_blank" rel="noopener" class="btn btn--sm btn--outline"><OfficeIcon name="whatsapp" /> WhatsApp</a>
                            </div>
                        </template>
                        <Link v-if="reservation.listing" :href="`/annonces/${reservation.listing.id}`" class="bs__lien">Voir l'annonce dans le back-office</Link>
                    </div>
                </section>

                <section v-if="reservation.annulable" class="of-card bs__danger" data-reveal>
                    <div class="of-card__b">
                        <button v-if="!annulation" type="button" class="btn btn--sm btn--outline bs__plein" @click="annulation = true">Annuler la réservation</button>
                        <form v-else class="bs__form" @submit.prevent="annuler">
                            <div class="of-field">
                                <label class="of-label" for="motif">Motif, lu par les deux parties</label>
                                <textarea id="motif" v-model="motif.reason" class="of-input" rows="3" maxlength="250" required
                                          placeholder="Ex. : le logement est inondé depuis la tempête, le propriétaire nous a prévenus." />
                                <p class="of-help">Il est écrit dans le fil au nom de Vayla. Les nuits sont rendues au calendrier.</p>
                                <p v-if="motif.errors.reason" class="of-err" role="alert">{{ motif.errors.reason }}</p>
                            </div>
                            <div class="bs__btns">
                                <button type="submit" class="btn btn--sm btn--ink" :disabled="motif.processing">Confirmer l'annulation</button>
                                <button type="button" class="btn btn--sm btn--ghost" @click="annulation = false">Garder la réservation</button>
                            </div>
                        </form>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.bs {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(18rem, 21rem);
    align-items: start;
    gap: 1rem;
}
.bs__main, .bs__side { display: grid; gap: 1rem; min-width: 0; }

.bs__clos {
    display: grid;
    gap: .2rem;
    margin: 0;
    padding: .8rem 1rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-sm);
    background: var(--white);
    font-size: .9rem;
    color: var(--ink);
}

.bs__note {
    display: flex;
    align-items: flex-start;
    gap: .45rem;
    margin: 0 0 1rem;
    padding: .6rem .75rem;
    border-radius: var(--r-sm);
    background: var(--off-2);
    font-size: .82rem;
    line-height: 1.45;
    color: var(--text-2);
}
.bs__note .oi { width: 1rem; height: 1rem; margin-top: .1rem; color: var(--terre-600); }
.bs__note strong { color: var(--ink); }

.bs__journal { margin: 0; padding: .4rem 1.15rem .9rem; list-style: none; }
.bs__journal li { padding: .55rem 0; border-top: 1px solid var(--line); }
.bs__journal li:first-child { border-top: 0; }
.bs__j-t { margin: 0; font-size: .86rem; color: var(--ink); }
.bs__j-s { margin: .1rem 0 0; font-size: .74rem; color: var(--text-3); }

.bs__argent { display: grid; gap: .55rem; margin: 0; }
.bs__argent div { display: flex; justify-content: space-between; gap: 1rem; font-size: .84rem; }
.bs__argent dt { color: var(--text-3); }
.bs__argent dd { margin: 0; font-weight: 700; color: var(--ink); text-align: right; }
.bs__com { padding-top: .55rem; border-top: 1px solid var(--line); }
.bs__com dd { font-weight: 800; }
.bs__rappel { margin: 0; padding: 0 1.15rem 1rem; font-size: .76rem; line-height: 1.45; color: var(--text-3); }

.bs__nom { margin: 0; font-size: .95rem; font-weight: 800; letter-spacing: -.02em; color: var(--ink); }
.bs__s { margin: .1rem 0 0; font-size: .8rem; color: var(--text-3); overflow-wrap: anywhere; }
.bs__tel { margin: .6rem 0 0; font-weight: 700; color: var(--ink); }
.bs__btns { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .6rem; }
.bs__btns .btn .oi { width: 1rem; height: 1rem; }
.bs__demande { margin: .8rem 0 0; padding-left: .7rem; border-left: 2px solid var(--line-2); font-size: .84rem; line-height: 1.5; color: var(--text-2); }
.bs__lien { display: inline-block; margin-top: .8rem; font-size: .8rem; font-weight: 700; color: var(--terre-600); }

.bs__danger { border-style: dashed; }
.bs__plein { width: 100%; }
.bs__form { display: grid; gap: .4rem; }

@media (max-width: 1040px) {
    .bs { grid-template-columns: minmax(0, 1fr); }
}
</style>
