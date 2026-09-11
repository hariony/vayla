<script setup>
/**
 * Les demandes de séjour « dans l'autre sens » : un voyageur a décrit ce qu'il
 * cherche, l'équipe va le chercher.
 *
 * **Chaque carte porte ce qu'il faut pour agir sans rien ouvrir d'autre** :
 * WhatsApp prêt à écrire (c'est la réponse promise sur l'accueil), le
 * catalogue déjà filtré sur la demande pour voir ce qui existe, et qui s'en
 * occupe. **Les nouvelles, la plus ancienne en tête** : c'est celle qui
 * attend depuis le plus longtemps.
 *
 * Prendre une demande l'écrit — deux personnes qui écrivent au même voyageur,
 * c'est un voyageur qui reçoit deux fois la même question. La clore demande une
 * note pour l'équipe : ce qui a été proposé, ou pourquoi rien.
 */
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import OfficePager from '@/Components/Office/OfficePager.vue'
import OfficeSearch from '@/Components/Office/OfficeSearch.vue'
import OfficeTabs from '@/Components/Office/OfficeTabs.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { formatCompact } from '@/Composables/useStayDates.js'
import { ariary, ilYA } from '@/Support/format.js'

defineOptions({ layout: OfficeShell })

defineProps({
    demandes: { type: Array, required: true },
    meta: { type: Object, required: true },
    onglets: { type: Array, required: true },
    filtre: { type: Object, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

const prendre = (d) => router.post(`/demandes/${d.id}/prendre`, {}, { preserveScroll: true })

const aClore = ref(null)
const cloture = useForm({ note: '' })
const ouvrirCloture = (d) => {
    aClore.value = d.id
    cloture.reset()
    cloture.clearErrors()
}
const clore = (d) => cloture.post(`/demandes/${d.id}/clore`, {
    preserveScroll: true,
    onSuccess: () => { aClore.value = null },
})
</script>

<template>
    <Head title="Demandes de séjour — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Séjours" titre="Demandes de séjour">
            <template #lede>
                Des voyageurs qui n'ont pas trouvé et décrivent ce qu'ils cherchent. On sollicite les propriétaires de la zone,
                on vérifie ce qui remonte, et on leur répond — sur WhatsApp d'abord. Rien n'est réservé par une demande.
            </template>
        </OfficeHead>

        <OfficeTabs :onglets="onglets" :actif="filtre.onglet" base="/demandes" param="onglet" defaut="nouvelles" :q="filtre.q" />
        <OfficeSearch base="/demandes" :q="filtre.q" :params="filtre.onglet !== 'nouvelles' ? { onglet: filtre.onglet } : {}" placeholder="Nom, e-mail, numéro, lieu…" label="Rechercher une demande" />

        <ul v-if="demandes.length" class="dq" data-reveal>
            <li v-for="d in demandes" :key="d.id" class="of-card dq__carte" :class="`dq__carte--${d.statut}`">
                <header class="of-card__h dq__tete">
                    <div>
                        <h2 class="of-card__t">{{ d.nom }}</h2>
                        <p class="dq__recue">Reçue {{ ilYA(d.recue) }}</p>
                    </div>
                    <span class="of-chip" :class="{ 'of-chip--attente': d.statut === 'nouvelle', 'of-chip--actif': d.statut === 'en_cours', 'of-chip--clos': d.statut === 'close' }">
                        {{ { nouvelle: 'Nouvelle', en_cours: 'En cours', close: 'Close' }[d.statut] }}
                    </span>
                </header>

                <div class="of-card__b dq__corps">
                    <dl class="dq__faits">
                        <div>
                            <dt>Où</dt>
                            <dd>{{ d.destination ?? 'Pas de destination choisie' }}<template v-if="d.lieu"> — « {{ d.lieu }} »</template></dd>
                        </div>
                        <div>
                            <dt>Quand</dt>
                            <dd v-if="d.arrivee">{{ formatCompact(d.arrivee) }} → {{ formatCompact(d.depart) }} · {{ d.nuits }} nuit{{ d.nuits > 1 ? 's' : '' }}</dd>
                            <dd v-else>Dates non fixées</dd>
                        </div>
                        <div>
                            <dt>Qui</dt>
                            <dd>{{ d.voyageurs }} voyageur{{ d.voyageurs > 1 ? 's' : '' }}</dd>
                        </div>
                        <div>
                            <dt>Budget</dt>
                            <dd>{{ d.budget ? `${ariary(d.budget)} / nuit` : 'Non précisé' }}</dd>
                        </div>
                    </dl>

                    <blockquote v-if="d.message" class="dq__message">{{ d.message }}</blockquote>

                    <div class="dq__gestes">
                        <a v-if="d.whatsapp" :href="d.whatsapp" target="_blank" rel="noopener" class="btn btn--sm btn--ink"><OfficeIcon name="whatsapp" /> Écrire sur WhatsApp</a>
                        <a v-if="d.tel" :href="d.tel" class="btn btn--sm btn--outline"><OfficeIcon name="appel" /> {{ d.telephone }}</a>
                        <a v-if="d.email" :href="`mailto:${d.email}`" class="btn btn--sm btn--outline">{{ d.email }}</a>
                        <a :href="d.catalogue" target="_blank" rel="noopener" class="btn btn--sm btn--outline"><OfficeIcon name="recherche" /> Voir ce qui existe déjà</a>
                    </div>
                </div>

                <footer class="dq__pied">
                    <template v-if="d.statut === 'nouvelle'">
                        <p class="dq__etat">Personne ne s'en occupe encore.</p>
                        <button type="button" class="btn btn--sm btn--terre" @click="prendre(d)">Je m'en occupe</button>
                    </template>

                    <template v-else-if="d.statut === 'en_cours'">
                        <p class="dq__etat"><strong>{{ d.parQui }}</strong> s'en occupe depuis {{ ilYA(d.priseLe) }}.</p>
                        <button v-if="aClore !== d.id" type="button" class="btn btn--sm btn--outline" @click="ouvrirCloture(d)">Clore la demande</button>
                    </template>

                    <template v-else>
                        <p class="dq__etat">Close {{ ilYA(d.closeLe) }}<template v-if="d.parQui"> par <strong>{{ d.parQui }}</strong></template>.</p>
                    </template>

                    <form v-if="aClore === d.id" class="dq__clore" novalidate @submit.prevent="clore(d)">
                        <label class="of-label" :for="`note-${d.id}`">Note pour l'équipe</label>
                        <textarea :id="`note-${d.id}`" v-model="cloture.note" class="of-input" rows="2" maxlength="1000" placeholder="Trois logements proposés à Ambatoloaka, elle a réservé le deuxième." />
                        <p v-if="cloture.errors.note" class="of-err">{{ cloture.errors.note }}</p>
                        <div class="dq__boutons">
                            <button type="submit" class="btn btn--sm btn--ink" :disabled="cloture.processing">Clore</button>
                            <button type="button" class="btn btn--sm btn--ghost" @click="aClore = null">Annuler</button>
                        </div>
                    </form>

                    <p v-if="d.note" class="dq__note">« {{ d.note }} »</p>
                </footer>
            </li>
        </ul>

        <div v-else class="of-vide" data-reveal>
            <strong>{{ filtre.q ? 'Aucune demande ne correspond.' : 'Rien dans cet onglet.' }}</strong>
            <template v-if="filtre.onglet === 'nouvelles' && !filtre.q">Les demandes déposées sur /demande arrivent ici.</template>
        </div>

        <OfficePager :meta="meta" unite="demande" />
    </div>
</template>

<style scoped>
.dq { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 30rem), 1fr)); gap: 1rem; margin: 1rem 0; padding: 0; list-style: none; }
.dq__carte { display: flex; flex-direction: column; }
.dq__carte--nouvelle { border-color: var(--terre-300); }
.dq__tete { align-items: flex-start; }
.dq__recue { margin: .15rem 0 0; font-size: .76rem; color: var(--text-3); }
.dq__corps { display: grid; gap: .9rem; flex: 1; }
.dq__faits { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem 1rem; margin: 0; }
.dq__faits dt { font-size: .68rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--text-3); }
.dq__faits dd { margin: .15rem 0 0; font-size: .88rem; line-height: 1.4; color: var(--ink); }
.dq__message { margin: 0; padding: .7rem .9rem; border-left: 3px solid var(--line-2); background: var(--off); font-size: .86rem; line-height: 1.5; color: var(--text-2); white-space: pre-line; }
.dq__gestes { display: flex; flex-wrap: wrap; gap: .4rem; }
.dq__gestes .oi { width: 1rem; height: 1rem; }
.dq__pied { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .8rem; padding: .7rem 1.15rem; border-top: 1px solid var(--line); background: var(--off); border-radius: 0 0 var(--r-md) var(--r-md); }
.dq__etat { flex: 1; margin: 0; font-size: .82rem; color: var(--text-2); }
.dq__etat strong { color: var(--ink); }
.dq__clore { display: grid; gap: .45rem; flex-basis: 100%; }
.dq__boutons { display: flex; gap: .4rem; }
.dq__note { flex-basis: 100%; margin: 0; font-size: .82rem; font-style: italic; color: var(--text-2); }

@media (max-width: 560px) {
    .dq__faits { grid-template-columns: minmax(0, 1fr); }
}
</style>
