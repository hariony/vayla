<script setup>
/**
 * La facture du mois écoulé.
 *
 * **Elle se vérifie ligne à ligne, sinon elle ne se paie pas.** Chaque ligne
 * nomme le voyageur, ses dates et le montant : le propriétaire sait qui a
 * dormi chez lui et peut recouper. Un total sans détail se conteste ; un
 * détail se règle.
 *
 * Un mois sans séjour confirmé n'affiche pas une facture à zéro : il affiche
 * qu'il n'y a rien à payer. Ce n'est pas la même phrase.
 */
import { nombre } from '@/Support/format.js'
import { formatCompact } from '@/Composables/useStayDates.js'

defineProps({
    invoice: { type: Object, required: true },
})
</script>

<template>
    <section class="iv">
        <header class="iv__head">
            <h2 class="iv__title">Facture · {{ invoice.period.label }}</h2>
            <p class="iv__basis">{{ invoice.basis }}</p>
        </header>

        <p v-if="!invoice.stays" class="iv__none">
            Aucun séjour confirmé sur cette période : <strong>rien à régler</strong>.
        </p>

        <template v-else>
            <table class="iv__table">
                <thead>
                    <tr>
                        <th scope="col">Séjour</th>
                        <th scope="col">Voyageur</th>
                        <th scope="col" class="iv__r">Nuits</th>
                        <th scope="col" class="iv__r">Encaissé</th>
                        <th scope="col" class="iv__r">Commission</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="line in invoice.lines" :key="line.reference">
                        <td>
                            <span class="num">{{ formatCompact(line.arrival) }} → {{ formatCompact(line.departure) }}</span>
                            <span class="iv__listing">{{ line.listing }}</span>
                        </td>
                        <td>{{ line.traveller }}</td>
                        <td class="iv__r num">{{ line.nights }}</td>
                        <td class="iv__r num">{{ nombre(line.total) }}</td>
                        <td class="iv__r num iv__due">{{ nombre(line.commission) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="iv__total">
                <p class="iv__total-lab">À régler par mobile money</p>
                <p class="iv__total-n num">{{ nombre(invoice.due) }} Ar</p>
            </div>

            <p class="iv__pay">
                Vers <strong class="num">{{ invoice.owner.mobileMoney }}</strong>
                <template v-if="invoice.owner.operator"> · {{ invoice.owner.operator }}</template>
            </p>
        </template>
    </section>
</template>

<style scoped>
.iv__head { margin-bottom: 1rem; }
.iv__title { margin: 0; font-size: 1.3rem; font-weight: 800; letter-spacing: -.032em; color: var(--ink); text-transform: capitalize; }
.iv__basis { margin: .4rem 0 0; max-width: 58ch; font-size: .84rem; line-height: 1.55; color: var(--text-3); }

.iv__none {
    margin: 0;
    padding: 1.25rem;
    border: 1px dashed var(--line-2);
    border-radius: var(--r-lg);
    text-align: center;
    font-size: .92rem;
    color: var(--text-2);
}

/* La table déborde plutôt que de se comprimer : cinq colonnes serrées sur un
   téléphone deviennent illisibles, et une facture illisible ne se paie pas. */
.iv__table {
    display: block;
    width: 100%;
    overflow-x: auto;
    border-collapse: collapse;
    font-size: .88rem;
    white-space: nowrap;
}
.iv__table th, .iv__table td { padding: .7rem .9rem; text-align: left; border-bottom: 1px solid var(--line); }
.iv__table th { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--text-3); }
.iv__r { text-align: right !important; }
.iv__due { font-weight: 800; color: var(--ink); }
.iv__listing { display: block; margin-top: .15rem; font-size: .76rem; color: var(--text-3); }

.iv__total {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 1.1rem;
    padding: 1rem 1.2rem;
    border-radius: var(--r-lg);
    background: var(--ink);
    color: var(--white);
}
.iv__total-lab { margin: 0; font-size: .88rem; font-weight: 600; opacity: .82; }
.iv__total-n { margin: 0; font-size: 1.6rem; font-weight: 800; letter-spacing: -.04em; }

.iv__pay { margin: .7rem 0 0; font-size: .86rem; color: var(--text-2); }
</style>
