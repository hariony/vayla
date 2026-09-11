<script setup>
/**
 * La facturation d'un mois, tous propriétaires confondus.
 *
 * **Vayla n'encaisse rien** : le propriétaire pousse son règlement par mobile
 * money, et cet écran est l'endroit où l'on consigne qu'il est arrivé. **Le
 * montant n'est pas un champ** — c'est la facture qui dit ce qui est dû, et une
 * saisie à la main ouvrirait l'écart entre ce que voit le propriétaire et ce
 * que Vayla croit avoir reçu. On ne saisit que la référence de la transaction,
 * pour la retrouver le jour où quelqu'un conteste.
 *
 * **Le mois en cours se regarde, il ne se règle pas** : ce n'est pas encore une
 * facture — l'écran du propriétaire le lui dit dans les mêmes mots.
 *
 * Aucun euro ici : tout ce que le propriétaire doit est en ariary.
 */
import { computed, reactive, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { formatCompact } from '@/Composables/useStayDates.js'
import { ariary, ilYA, nombre } from '@/Support/format.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    mois: { type: Object, required: true },
    lignes: { type: Array, default: () => [] },
    totaux: { type: Object, required: true },
    taux: { type: Number, default: 0 },
})

const racine = ref(null)
useOfficeMotion(racine)

const ouverte = ref(null)
const references = reactive({})

const regler = useForm({ owner_id: null, mois: '', reference: '' })
const consigner = (l) => {
    regler.owner_id = l.ownerId
    regler.mois = props.mois.cle
    regler.reference = references[l.ownerId] ?? ''
    regler.post('/facturation/regler', { preserveScroll: true, onSuccess: () => { ouverte.value = null } })
}

const rouvrir = useForm({ owner_id: null, mois: '' })
const annulerReglement = (l) => {
    rouvrir.owner_id = l.ownerId
    rouvrir.mois = props.mois.cle
    rouvrir.post('/facturation/rouvrir', { preserveScroll: true })
}

const pourcent = Math.round(props.taux * 1000) / 10
// Suit les props : après un règlement consigné, la barre avance sans recharger.
const part = computed(() => (props.totaux.du ? props.totaux.regle / props.totaux.du : 0))
</script>

<template>
    <Head title="Facturation — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Relais" titre="Facturation">
            <template #lede>
                Commission de <strong class="of-num">{{ pourcent }} %</strong>, sur les seuls séjours confirmés par les voyageurs.
                Le propriétaire règle par mobile money ; consignez chaque règlement reçu.
            </template>
        </OfficeHead>

        <!-- Le mois : on avance et on recule, jamais au-delà d'aujourd'hui. -->
        <nav class="fa__mois" aria-label="Changer de mois" data-reveal>
            <Link :href="`/facturation?mois=${mois.precedent}`" class="fa__fl" preserve-scroll aria-label="Mois précédent"><OfficeIcon name="retour" /></Link>
            <p class="fa__mois-l">
                <span class="fa__mois-t">{{ mois.label }}</span>
                <span v-if="mois.enCours" class="of-chip of-chip--attente">En cours — pas encore une facture</span>
            </p>
            <Link v-if="mois.suivant" :href="`/facturation?mois=${mois.suivant}`" class="fa__fl" preserve-scroll aria-label="Mois suivant"><OfficeIcon name="avant" /></Link>
            <span v-else class="fa__fl" aria-disabled="true" aria-label="Pas de mois suivant"><OfficeIcon name="avant" /></span>
        </nav>

        <!-- Les totaux : dû, reçu, reste. La barre dit la part reçue. -->
        <section class="fa__totaux of-card" data-reveal>
            <dl class="fa__tt">
                <div><dt>{{ mois.enCours ? 'Commission à venir' : 'Commission due' }}</dt><dd class="of-num">{{ ariary(totaux.du) }}</dd></div>
                <div><dt>Reçu</dt><dd class="of-num">{{ ariary(totaux.regle) }}</dd></div>
                <div class="fa__reste"><dt>Reste à recevoir</dt><dd class="of-num">{{ ariary(totaux.reste) }}</dd></div>
                <div><dt>Séjours confirmés</dt><dd class="of-num">{{ nombre(totaux.sejours) }}</dd></div>
            </dl>
            <span class="fa__jauge" aria-hidden="true"><span class="fa__jauge-in" data-bar :style="{ transform: `scaleX(${part})` }" /></span>
        </section>

        <section class="of-card" data-reveal aria-labelledby="fl-t">
            <header class="of-card__h">
                <h2 id="fl-t" class="of-card__t">Par propriétaire</h2>
                <span class="fa__n of-num">{{ lignes.length }}</span>
            </header>

            <ul v-if="lignes.length" class="of-rows">
                <li v-for="l in lignes" :key="l.ownerId" class="fa__ligne" :class="{ 'is-reglee': l.settlement }">
                    <div class="of-row fa__row">
                        <span class="fa__txt">
                            <Link :href="`/proprietaires/${l.ownerId}`" class="of-row__t fa__nom">{{ l.owner.name }}</Link>
                            <span class="of-row__s">
                                {{ l.stays }} séjour{{ l.stays > 1 ? 's' : '' }} · {{ l.nights }} nuit{{ l.nights > 1 ? 's' : '' }} ·
                                <template v-if="l.owner.mobileMoney">{{ l.owner.operator }} <span class="of-num">{{ l.owner.mobileMoney }}</span></template>
                                <template v-else>mobile money non renseigné</template>
                            </span>
                        </span>

                        <span class="fa__du of-num">{{ ariary(l.due) }}</span>

                        <span class="fa__etat">
                            <template v-if="l.settlement">
                                <span class="of-chip of-chip--actif">Réglée {{ ilYA(l.settlement.at) }}</span>
                                <span v-if="l.settlement.reference" class="fa__ref of-num">réf. {{ l.settlement.reference }}</span>
                            </template>
                            <span v-else-if="mois.enCours" class="of-chip">En cours</span>
                            <span v-else class="of-chip of-chip--attente">À régler</span>
                        </span>

                        <button type="button" class="fa__detail" :aria-expanded="ouverte === l.ownerId" @click="ouverte = ouverte === l.ownerId ? null : l.ownerId">
                            {{ ouverte === l.ownerId ? 'Fermer' : 'Détail' }}
                        </button>
                    </div>

                    <div v-if="ouverte === l.ownerId" class="fa__plus">
                        <table class="fa__table">
                            <thead>
                                <tr><th>Réf.</th><th>Logement</th><th>Séjour</th><th class="d">Total</th><th class="d">Commission</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="s in l.lines" :key="s.reference">
                                    <td class="of-num"><Link :href="`/reservations/${s.reference}`">{{ s.reference }}</Link></td>
                                    <td>{{ s.listing }}<span class="fa__voy"> · {{ s.traveller }}</span></td>
                                    <td class="of-num">{{ formatCompact(s.arrival) }} → {{ formatCompact(s.departure) }}</td>
                                    <td class="d of-num">{{ ariary(s.total) }}</td>
                                    <td class="d of-num">{{ ariary(s.commission) }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <form v-if="!mois.enCours && !l.settlement" class="fa__regler" @submit.prevent="consigner(l)">
                            <div class="of-field">
                                <label class="of-label" :for="`ref-${l.ownerId}`">Référence de la transaction (facultatif)</label>
                                <input :id="`ref-${l.ownerId}`" v-model="references[l.ownerId]" class="of-input" maxlength="60" placeholder="Ex. : MP240915.1432.A12345">
                            </div>
                            <button type="submit" class="btn btn--sm btn--terre" :disabled="regler.processing">
                                Consigner le règlement de {{ ariary(l.due) }}
                            </button>
                        </form>

                        <div v-else-if="l.settlement" class="fa__regler">
                            <p class="of-help">Consigné par erreur ? Annuler le règlement rend la facture de nouveau due. Le journal garde les deux gestes.</p>
                            <button type="button" class="btn btn--sm btn--outline" :disabled="rouvrir.processing" @click="annulerReglement(l)">Annuler le règlement</button>
                        </div>
                    </div>
                </li>
            </ul>
            <p v-else class="of-vide">
                <strong>Aucune facture pour {{ mois.label }}.</strong>
                Un propriétaire sans séjour confirmé ce mois-là ne reçoit rien : une facture à zéro n'existe pas.
            </p>
        </section>
    </div>
</template>

<style scoped>
.fa__mois {
    display: flex;
    align-items: center;
    gap: .6rem;
    margin-bottom: 1rem;
}
.fa__fl {
    display: grid;
    place-items: center;
    width: 2.75rem;
    height: 2.75rem;
    border: 1px solid var(--line-2);
    border-radius: 50%;
    background: var(--white);
    color: var(--ink);
}
a.fa__fl:hover { border-color: var(--ink); }
a.fa__fl:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }
.fa__fl[aria-disabled='true'] { background: var(--off-2); color: var(--ink-3); cursor: not-allowed; }
.fa__fl .oi { width: 1.05rem; height: 1.05rem; }
.fa__mois-l { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin: 0; min-width: 0; }
.fa__mois-t { font-size: 1.1rem; font-weight: 800; letter-spacing: -.025em; color: var(--ink); text-transform: capitalize; }

.fa__totaux { margin-bottom: 1rem; overflow: hidden; }
.fa__tt {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
    gap: .75rem 1.5rem;
    margin: 0;
    padding: 1rem 1.15rem;
}
.fa__tt dt { font-size: .74rem; font-weight: 600; color: var(--text-3); }
.fa__tt dd { margin: .15rem 0 0; font-size: 1.3rem; font-weight: 800; letter-spacing: -.035em; color: var(--ink); }
.fa__reste dd { color: var(--terre-700); }
.fa__jauge { display: block; height: 4px; background: var(--off-2); }
.fa__jauge-in { display: block; height: 100%; background: var(--ink); transform-origin: left center; }

.fa__n { font-size: .82rem; font-weight: 800; color: var(--text-3); }

.fa__ligne { border-top: 1px solid var(--line); }
.fa__ligne:first-child { border-top: 0; }
.fa__ligne .of-row { border-top: 0; }
.fa__row { grid-template-columns: minmax(0, 1fr) 8rem 12rem auto; }
.fa__txt { min-width: 0; }
.fa__nom { text-decoration: none; }
.fa__nom:hover { text-decoration: underline; text-underline-offset: .2em; }
.fa__du { font-size: .95rem; font-weight: 800; text-align: right; color: var(--ink); }
.fa__etat { display: grid; justify-items: end; gap: .15rem; }
.fa__ref { font-size: .72rem; color: var(--text-3); }

.fa__detail {
    min-height: 2.5rem;
    padding: 0 .9rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font: inherit;
    font-size: .8rem;
    font-weight: 700;
    color: var(--ink);
    cursor: pointer;
}
.fa__detail:hover { border-color: var(--ink); }
.fa__detail:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }

.fa__plus { padding: 0 1.15rem 1.1rem; overflow-x: auto; }
.fa__table { width: 100%; min-width: 34rem; border-collapse: collapse; font-size: .82rem; }
.fa__table th { padding: .45rem .5rem; border-bottom: 1px solid var(--line-2); font-size: .7rem; font-weight: 700; text-align: left; color: var(--text-3); }
.fa__table td { padding: .5rem; border-bottom: 1px solid var(--line); color: var(--ink); }
.fa__table .d { text-align: right; }
.fa__table a { font-weight: 700; color: var(--ink); }
.fa__voy { color: var(--text-3); }

.fa__regler { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .6rem; margin-top: .9rem; }
.fa__regler .of-field { flex: 1 1 16rem; }

@media (max-width: 760px) {
    .fa__row { grid-template-columns: minmax(0, 1fr) auto; }
    .fa__etat { grid-column: 1; justify-items: start; }
    .fa__detail { grid-column: 2; grid-row: 2; }
}
</style>
