<script setup>
/**
 * Les réglages que l'équipe tient elle-même.
 *
 * **Le taux de change** s'affiche à côté de l'ariary, sur chaque fiche et
 * chaque encart : il porte sa date, **saisie avec lui**, parce qu'un montant
 * converti sans date est invérifiable. L'ariary reste le prix ; l'euro n'est
 * qu'une aide à la lecture.
 *
 * **La commission ne vaut que pour les nouvelles demandes.** Chaque
 * réservation fige le taux du jour où elle a été faite : une facture qui
 * changerait après coup est une facture qu'on ne paie pas. L'écran le dit
 * avant le bouton, et demande une confirmation — c'est un chiffre que les
 * propriétaires lisent avant de répondre.
 *
 * Les deux valeurs viennent du `.env` tant que personne ne les a réglées ici ;
 * l'écran dit laquelle des deux sources parle.
 */
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { ilYA, nombre } from '@/Support/format.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    taux: { type: Object, required: true },
    commission: { type: Object, required: true },
    historique: { type: Array, default: () => [] },
})

const racine = ref(null)
useOfficeMotion(racine)

const aujourdhui = new Date().toISOString().slice(0, 10)

const change = useForm({ eur_rate: props.taux.valeur, eur_rate_date: aujourdhui })
const enregistrerTaux = () => change.post('/reglages/taux', { preserveScroll: true })

// Un exemple, pour que le chiffre se lise : 185 000 Ar → ≈ 37 €.
const exemple = computed(() => (change.eur_rate > 0 ? Math.round(185000 / change.eur_rate) : null))

const com = useForm({ commission: props.commission.pourcent })
const confirmer = ref(false)
const enregistrerCommission = () => com.post('/reglages/commission', { preserveScroll: true, onSuccess: () => { confirmer.value = false } })
</script>

<template>
    <Head title="Réglages — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Contenu" titre="Réglages" lede="Deux chiffres que tout le site lit. Chacun garde qui l'a changé, et quand." />

        <div class="rg">
            <section class="of-card" data-reveal aria-labelledby="tx-t">
                <header class="of-card__h">
                    <h2 id="tx-t" class="of-card__t">Taux de change</h2>
                    <span class="rg__src">{{ taux.source === 'back-office' ? `réglé ${ilYA(taux.trace?.at)}${taux.trace?.par ? ' par ' + taux.trace.par : ''}` : 'valeur du fichier de configuration' }}</span>
                </header>
                <form class="of-card__b rg__form" @submit.prevent="enregistrerTaux">
                    <p class="rg__actuel">1 € = <strong class="of-num">{{ nombre(taux.valeur) }} Ar</strong>, relevé le <span class="of-num">{{ taux.date }}</span></p>
                    <div class="rg__champs">
                        <div class="of-field">
                            <label class="of-label" for="rate">Ariary pour un euro</label>
                            <input id="rate" v-model.number="change.eur_rate" class="of-input of-num" type="number" min="1000" max="20000" step="1">
                            <p v-if="change.errors.eur_rate" class="of-err" role="alert">{{ change.errors.eur_rate }}</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="date">Relevé le</label>
                            <input id="date" v-model="change.eur_rate_date" class="of-input of-num" type="date" :max="aujourdhui">
                            <p v-if="change.errors.eur_rate_date" class="of-err" role="alert">{{ change.errors.eur_rate_date }}</p>
                        </div>
                    </div>
                    <p v-if="exemple" class="of-help">Sur une fiche : <span class="of-num">185 000 Ar</span> s'affichera <span class="of-num">≈ {{ exemple }} €</span>, sans centimes. L'ariary reste le prix.</p>
                    <button type="submit" class="btn btn--sm btn--ink rg__go" :disabled="change.processing">Enregistrer le taux</button>
                </form>
            </section>

            <section class="of-card" data-reveal aria-labelledby="co-t">
                <header class="of-card__h">
                    <h2 id="co-t" class="of-card__t">Commission Vayla</h2>
                    <span class="rg__src">{{ commission.source === 'back-office' ? `réglée ${ilYA(commission.trace?.at)}${commission.trace?.par ? ' par ' + commission.trace.par : ''}` : 'valeur du fichier de configuration' }}</span>
                </header>
                <form class="of-card__b rg__form" @submit.prevent="confirmer ? enregistrerCommission() : (confirmer = true)">
                    <p class="rg__actuel">Aujourd'hui : <strong class="of-num">{{ commission.pourcent }} %</strong> du total de chaque séjour confirmé</p>
                    <div class="of-field rg__pourcent">
                        <label class="of-label" for="com">Pourcentage des nouvelles demandes</label>
                        <div class="rg__unite">
                            <input id="com" v-model.number="com.commission" class="of-input of-num" type="number" min="0" max="30" step="0.5" @input="confirmer = false">
                            <span>%</span>
                        </div>
                        <p v-if="com.errors.commission" class="of-err" role="alert">{{ com.errors.commission }}</p>
                    </div>
                    <p class="rg__avert">
                        Il ne s'applique qu'aux <strong>demandes faites à partir de maintenant</strong>. Chaque réservation garde le taux du jour où elle a été faite — les factures déjà engagées ne bougent pas.
                    </p>
                    <div class="rg__boutons">
                        <button type="submit" class="btn btn--sm" :class="confirmer ? 'btn--terre' : 'btn--ink'" :disabled="com.processing || com.commission === commission.pourcent">
                            {{ confirmer ? `Confirmer : ${com.commission} % pour les nouvelles demandes` : 'Changer la commission' }}
                        </button>
                        <button v-if="confirmer" type="button" class="btn btn--sm btn--ghost" @click="confirmer = false">Annuler</button>
                    </div>
                </form>
            </section>
        </div>

        <section class="of-card rg__hist" data-reveal aria-labelledby="hi-t">
            <header class="of-card__h"><h2 id="hi-t" class="of-card__t">Derniers changements</h2></header>
            <ol v-if="historique.length" class="rg__liste">
                <li v-for="h in historique" :key="h.id">
                    <p class="rg__h-t">{{ h.summary }}</p>
                    <p class="rg__h-s">{{ h.admin }} · {{ ilYA(h.at) }}</p>
                </li>
            </ol>
            <p v-else class="of-vide">Aucun réglage changé depuis le back-office : les valeurs viennent du fichier de configuration.</p>
        </section>
    </div>
</template>

<style scoped>
.rg { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; align-items: start; }
.rg__src { font-size: .74rem; color: var(--text-3); }
.rg__form { display: grid; gap: .9rem; }
.rg__actuel { margin: 0; font-size: .92rem; color: var(--text-2); }
.rg__actuel strong { font-size: 1.15rem; font-weight: 800; color: var(--ink); }
.rg__champs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .9rem; }
.rg__go { justify-self: start; }
.rg__pourcent { max-width: 14rem; }
.rg__unite { position: relative; }
.rg__unite span { position: absolute; right: .85rem; top: 50%; font-weight: 700; color: var(--text-3); transform: translateY(-50%); }
.rg__avert { margin: 0; padding: .7rem .85rem; border-left: 3px solid var(--terre-500); border-radius: 0 var(--r-xs) var(--r-xs) 0; background: var(--terre-050); font-size: .82rem; line-height: 1.5; color: var(--ink); }
.rg__boutons { display: flex; flex-wrap: wrap; gap: .4rem; }
.rg__hist { margin-top: 1rem; }
.rg__liste { margin: 0; padding: .4rem 1.15rem .9rem; list-style: none; }
.rg__liste li { padding: .55rem 0; border-top: 1px solid var(--line); }
.rg__liste li:first-child { border-top: 0; }
.rg__h-t { margin: 0; font-size: .86rem; color: var(--ink); }
.rg__h-s { margin: .1rem 0 0; font-size: .74rem; color: var(--text-3); }

@media (max-width: 900px) {
    .rg, .rg__champs { grid-template-columns: minmax(0, 1fr); }
}
</style>
