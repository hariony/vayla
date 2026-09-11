<script setup>
/**
 * Toutes les réservations. **« En attente » s'ouvre par défaut et se trie par
 * échéance** : une demande à 3 h de l'expiration passe devant une demande
 * arrivée ce matin — c'est elle qu'il faut relancer.
 *
 * La recherche prend la référence, le nom, l'adresse ou le numéro du
 * voyageur : au téléphone, on vous dicte l'un des quatre, rarement celui qu'on
 * attend.
 */
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficePager from '@/Components/Office/OfficePager.vue'
import OfficeSearch from '@/Components/Office/OfficeSearch.vue'
import OfficeTabs from '@/Components/Office/OfficeTabs.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { formatCompact } from '@/Composables/useStayDates.js'
import { ariary } from '@/Support/format.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    reservations: { type: Object, required: true },
    onglets: { type: Array, required: true },
    filtre: { type: Object, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

const puce = (s) => ({ pending: 'of-chip--attente', accepted: 'of-chip--actif', completed: 'of-chip--actif' }[s] ?? 'of-chip--clos')

const params = props.filtre.onglet !== 'attente' ? { filtre: props.filtre.onglet } : {}
</script>

<template>
    <Head title="Réservations — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Séjours" titre="Réservations" lede="Une demande bloque les nuits 48 h. Sans réponse, elle expire et les rend au calendrier : c'est ici qu'on la voit venir." />

        <div class="bi__outils">
            <OfficeTabs :onglets="onglets" :actif="filtre.onglet" base="/reservations" param="filtre" defaut="attente" :q="filtre.q" />
            <OfficeSearch base="/reservations" :q="filtre.q" :params="params" placeholder="Référence, voyageur, e-mail, téléphone…" label="Rechercher une réservation" />
        </div>

        <section class="of-card" data-reveal>
            <ul v-if="reservations.data.length" class="of-rows">
                <li v-for="r in reservations.data" :key="r.reference">
                    <Link :href="`/reservations/${r.reference}`" class="of-row bi__row">
                        <span class="bi__ref of-num">{{ r.reference }}</span>

                        <span class="bi__txt">
                            <span class="of-row__t">{{ r.listing?.title }}</span>
                            <span class="of-row__s">{{ r.traveller }} · {{ r.guests }} pers. · chez {{ r.owner?.name }}</span>
                        </span>

                        <span class="bi__dates of-num">
                            {{ formatCompact(r.arrival) }} → {{ formatCompact(r.departure) }}
                            <span class="bi__nuits">{{ r.nights }} nuit{{ r.nights > 1 ? 's' : '' }} · {{ ariary(r.total) }}</span>
                        </span>

                        <span class="bi__etat">
                            <span class="of-chip" :class="puce(r.status)">{{ r.statusLabel }}</span>
                            <span v-if="r.heuresRestantes !== null" class="bi__h of-num" :class="{ 'is-urgent': r.heuresRestantes < 12 }">
                                reste {{ r.heuresRestantes }} h
                            </span>
                            <span v-if="r.isDemo" class="of-chip of-chip--demo">Démo</span>
                        </span>
                    </Link>
                </li>
            </ul>
            <p v-else class="of-vide">
                <strong>{{ filtre.q ? 'Aucune réservation ne correspond.' : 'Rien dans cette file.' }}</strong>
                {{ filtre.q ? 'Une référence se tape sans espace : VY-7K2QD.' : 'Changez d’onglet pour voir les autres états.' }}
            </p>
        </section>

        <OfficePager :meta="reservations.meta" unite="réservation" />
    </div>
</template>

<style scoped>
.bi__outils { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 0 1rem; }

.bi__row { grid-template-columns: 5.6rem minmax(0, 1fr) 11rem 10.5rem; }
.bi__ref { font-size: .84rem; font-weight: 800; color: var(--ink); }
.bi__txt { min-width: 0; }
.bi__dates { display: grid; font-size: .84rem; font-weight: 700; color: var(--ink); }
.bi__nuits { font-size: .74rem; font-weight: 500; color: var(--text-3); }
.bi__etat { display: flex; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: .3rem; }
.bi__h { font-size: .74rem; font-weight: 700; color: var(--text-2); }
.bi__h.is-urgent { color: var(--terre-700); }

@media (max-width: 900px) {
    .bi__row { grid-template-columns: 5rem minmax(0, 1fr); }
    .bi__dates, .bi__etat { grid-column: 2; }
    .bi__etat { justify-content: flex-start; }
}
</style>
