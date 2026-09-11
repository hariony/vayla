<script setup>
/**
 * Les statistiques : ce qui se mesure dans le temps.
 *
 * **Chaque graphique dit ce qu'il compte et à quelle date il le range**, sous
 * son titre — une demande au mois où elle a été faite, un séjour et sa
 * commission au mois du départ, comme la facture. Sans cette ligne, deux
 * écrans qui ne tombent pas sur le même chiffre feraient douter des deux.
 *
 * **Aucune moyenne qui trompe** : le délai de réponse est une médiane, le taux
 * de réponse ne compte que les demandes tranchées. Et **pas de flèche de
 * tendance** : sur des volumes de plateforme qui démarre, « +200 % » veut dire
 * « deux de plus ».
 *
 * **La démonstration se retire d'un bouton**, et l'écran dit quand elle est
 * incluse : des courbes nourries de réservations fictives ne doivent jamais
 * passer pour l'activité réelle.
 *
 * La période et ce réglage vivent dans l'adresse : une vue se partage.
 */
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeChart from '@/Components/Office/OfficeChart.vue'
import OfficeHBars from '@/Components/Office/OfficeHBars.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import CommissionPanel from './Partials/CommissionPanel.vue'
import { ariary, nombre } from '@/Support/format.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    periode: { type: Object, required: true },
    demo: { type: Object, required: true },
    mois: { type: Object, required: true },
    chiffres: { type: Object, required: true },
    demandes: { type: Array, required: true },
    reponse: { type: Array, required: true },
    delai: { type: Array, required: true },
    sejours: { type: Array, required: true },
    commission: { type: Object, required: true },
    inscriptions: { type: Array, required: true },
    destinations: { type: Array, required: true },
    statuts: { type: Array, required: true },
    niveaux: { type: Array, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

const lien = (params) => {
    const p = new URLSearchParams({ periode: props.periode.mois, ...(props.demo.inclus ? {} : { demo: '0' }), ...params })
    if (p.get('demo') === '1') p.delete('demo')
    return `/statistiques?${p.toString()}`
}

const heures = (v) => (v === null ? '—' : `${String(v).replace('.', ',')} h`)
</script>

<template>
    <Head title="Statistiques — Back-office" />

    <div ref="racine" class="st">
        <OfficeHead kicker="Aujourd'hui" titre="Statistiques">
            <template #lede>Depuis {{ periode.debut }}, mois par mois. Des comptes définis sous chaque titre — pas de moyenne qui trompe, pas de flèche qui promet.</template>
            <template #actions>
                <!-- La période : trois durées, pas un calendrier à régler. -->
                <nav class="st__periodes" aria-label="Période">
                    <Link
                        v-for="p in periode.choix"
                        :key="p"
                        :href="lien({ periode: p })"
                        class="st__p"
                        :class="{ 'is-on': p === periode.mois }"
                        :aria-current="p === periode.mois ? 'page' : undefined"
                        preserve-scroll
                    >{{ p }} mois</Link>
                </nav>
            </template>
        </OfficeHead>

        <!-- La démonstration : dite en toutes lettres, et retirable d'un geste. -->
        <div v-if="demo.present" class="st__demo" :class="{ 'is-sans': !demo.inclus }" role="note" data-reveal>
            <p class="st__demo-t">
                <template v-if="demo.inclus"><strong>Ces chiffres incluent les données de démonstration.</strong> Elles servent à voir les courbes ; ce n'est pas l'activité réelle.</template>
                <template v-else><strong>Données réelles seulement.</strong> La démonstration est retirée.</template>
            </p>
            <Link :href="lien({ demo: demo.inclus ? '0' : '1' })" class="btn btn--sm btn--outline" preserve-scroll>
                {{ demo.inclus ? 'Retirer la démonstration' : 'Inclure la démonstration' }}
            </Link>
        </div>

        <!-- Les chiffres de la période. -->
        <dl class="st__chiffres" data-reveal>
            <div class="st__c"><dt>Demandes reçues</dt><dd class="of-num" :data-count="chiffres.demandes">{{ nombre(chiffres.demandes) }}</dd></div>
            <div class="st__c"><dt>Répondues par le propriétaire</dt><dd class="of-num">{{ chiffres.tauxReponse === null ? '—' : `${chiffres.tauxReponse} %` }}</dd></div>
            <div class="st__c"><dt>Délai médian de réponse</dt><dd class="of-num">{{ heures(chiffres.delaiMedian) }}</dd></div>
            <div class="st__c"><dt>Séjours effectués</dt><dd class="of-num" :data-count="chiffres.sejours">{{ nombre(chiffres.sejours) }}</dd></div>
            <div class="st__c"><dt>Nuits</dt><dd class="of-num" :data-count="chiffres.nuits">{{ nombre(chiffres.nuits) }}</dd></div>
            <div class="st__c st__c--argent"><dt>Commission facturée</dt><dd class="of-num">{{ ariary(chiffres.commission) }}</dd></div>
            <div class="st__c st__c--argent"><dt>Commission reçue</dt><dd class="of-num">{{ ariary(chiffres.reglee) }}</dd></div>
        </dl>

        <div class="st__grille">
            <OfficeChart
                class="st__large"
                titre="Demandes de séjour"
                definition="Au mois où la demande a été faite, par issue. « Expirées » : restées 48 h sans réponse, nuits rendues au calendrier."
                type="empile"
                :labels="mois.courts"
                :labels-longs="mois.longs"
                :series="demandes"
                :hauteur="280"
            />

            <OfficeChart
                titre="Réponse des propriétaires"
                definition="Part des demandes tranchées qui ont reçu une réponse — acceptées ou refusées. Une demande encore en attente n'est pas comptée."
                :labels="mois.courts"
                :labels-longs="mois.longs"
                :series="reponse"
                format="pourcent"
                :plafond="100"
            />

            <OfficeChart
                titre="Délai de réponse"
                definition="Médiane, en heures, entre la demande et la réponse du propriétaire. La moitié répond plus vite, l'autre moitié plus lentement."
                :labels="mois.courts"
                :labels-longs="mois.longs"
                :series="delai"
                format="heures"
            />

            <OfficeChart
                titre="Séjours effectués"
                definition="Au mois du départ, seulement les séjours confirmés par le voyageur — les seuls qui se facturent."
                type="barres"
                :labels="mois.courts"
                :labels-longs="mois.longs"
                :series="sejours"
            />

            <CommissionPanel
                class="st__large"
                :commission="commission"
                :labels="mois.courts"
                :labels-longs="mois.longs"
            />

            <OfficeChart
                class="st__large"
                titre="Inscriptions et catalogue"
                definition="Au mois de la création : comptes voyageurs ouverts, propriétaires inscrits, annonces saisies (brouillons compris)."
                :labels="mois.courts"
                :labels-longs="mois.longs"
                :series="inscriptions"
            />

            <OfficeHBars
                titre="Demandes par destination"
                definition="Sur la période, les dix destinations les plus demandées."
                :lignes="destinations.map((d) => ({ ...d, teinte: 'encre' }))"
                vide="Aucune demande sur la période."
            />

            <OfficeHBars
                titre="Le catalogue aujourd'hui"
                definition="Les annonces par état, à cet instant — la période ne s'y applique pas."
                :lignes="statuts"
            />

            <OfficeHBars
                titre="Annonces en ligne, par niveau"
                definition="La répartition par barreau, jamais une moyenne. Le vert ne commence qu'au niveau 2 : « déclarée » n'est pas une vérification."
                :lignes="niveaux"
            />
        </div>
    </div>
</template>

<style scoped>
.st__periodes { display: flex; gap: .25rem; padding: .25rem; border: 1px solid var(--line); border-radius: var(--r-pill); background: var(--white); }
.st__p {
    display: inline-flex;
    align-items: center;
    min-height: 2.4rem;
    padding: 0 .95rem;
    border-radius: var(--r-pill);
    font-size: .84rem;
    font-weight: 600;
    color: var(--text-2);
    text-decoration: none;
}
.st__p:hover { background: var(--off-2); color: var(--ink); }
.st__p:focus-visible { outline: 2px solid var(--terre-500); outline-offset: -2px; }
.st__p.is-on { background: var(--ink); color: var(--white); font-weight: 700; }

.st__demo {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .6rem 1rem;
    margin-bottom: 1rem;
    padding: .7rem .8rem .7rem 1rem;
    border: 1px dashed var(--terre-300);
    border-radius: var(--r-md);
    background: var(--terre-050);
}
.st__demo.is-sans { border-color: var(--line-2); background: var(--white); }
.st__demo-t { margin: 0; font-size: .86rem; line-height: 1.5; color: var(--ink); }

.st__chiffres {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
    gap: 1px;
    margin: 0 0 1rem;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: var(--r-md);
    background: var(--line);
}
.st__c { padding: .9rem 1rem; background: var(--white); }
.st__c dt { font-size: .74rem; font-weight: 600; line-height: 1.3; color: var(--text-3); }
.st__c dd { margin: .25rem 0 0; font-size: 1.55rem; font-weight: 800; letter-spacing: -.04em; line-height: 1.1; color: var(--ink); }
.st__c--argent dd { font-size: 1.15rem; letter-spacing: -.02em; }

/* Deux colonnes sur un écran large, les graphiques qui racontent le plus en
   pleine largeur. Le contenu du back-office va jusqu'aux bords : les courbes
   en profitent. `dense` : un bloc pleine largeur ne laisse pas de case vide
   derrière lui — le suivant qui tient vient la combler, à deux colonnes
   comme à trois. */
.st__grille { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); grid-auto-flow: row dense; gap: 1rem; }
.st__large { grid-column: 1 / -1; }

@media (min-width: 1600px) {
    .st__grille { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 1000px) {
    .st__grille { grid-template-columns: minmax(0, 1fr); }
}
</style>
