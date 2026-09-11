<script setup>
/**
 * La commission, en pleine largeur : **ce qui est facturé, ce qui est reçu,
 * ce qui manque, et chez qui.**
 *
 * Une courbe à deux traits disait « on facture plus qu'on ne reçoit » sans
 * dire combien, ni à qui téléphoner. Le bloc répond maintenant aux trois
 * questions qu'on se pose en l'ouvrant, dans cet ordre : où en est-on (les
 * chiffres), comment ça évolue (le graphique), qui doit encore (la liste) —
 * puis le détail mois par mois, chaque mois menant à sa facturation.
 *
 * **Le mois en cours n'est pas une facture** : il s'accumule, rien n'y est dû,
 * et il n'entre ni dans le reste à recevoir ni dans le recouvrement. L'écran
 * le dit sur sa ligne plutôt que d'afficher un « reste » qui ferait relancer
 * un propriétaire pour une somme qu'il ne doit pas encore.
 *
 * Ce qui attend quelqu'un prend la terre — le reste à recevoir. Le lagon n'y
 * entre pas : un taux de recouvrement n'est pas une vérification.
 */
import { Link } from '@inertiajs/vue3'

import OfficeChart from '@/Components/Office/OfficeChart.vue'
import { ariary, nombre } from '@/Support/format.js'

defineProps({
    commission: { type: Object, required: true },
    labels: { type: Array, required: true },
    labelsLongs: { type: Array, required: true },
})

const pourcent = (v) => (v === null ? '—' : `${String(v).replace('.', ',')} %`)
const ouTiret = (v, f = ariary) => (v ? f(v) : '—')
const pluriel = (n, mot) => `${nombre(n)} ${mot}${n > 1 ? 's' : ''}`
</script>

<template>
    <section class="of-card com" aria-labelledby="com-titre" data-reveal>
        <header class="com__tete">
            <div class="com__intro">
                <h3 id="com-titre" class="com__titre">Commission</h3>
                <p class="com__def">
                    Facturée au mois du départ, sur les seuls séjours confirmés par le voyageur ; reçue au mois qu'elle solde.
                    Le reste à recevoir ne compte que les mois clos : le mois en cours n'est pas encore une facture.
                </p>
            </div>
            <Link href="/facturation" class="btn btn--sm btn--outline">Ouvrir la facturation</Link>
        </header>

        <dl class="com__chiffres">
            <div class="com__c">
                <dt>Volume des séjours</dt>
                <dd class="of-num">{{ ariary(commission.chiffres.volume) }}</dd>
                <p>ce sur quoi la commission est calculée</p>
            </div>
            <div class="com__c">
                <dt>Taux appliqué</dt>
                <dd class="of-num">{{ pourcent(commission.chiffres.taux) }}</dd>
                <p>chaque réservation a figé le sien</p>
            </div>
            <div class="com__c">
                <dt>Facturée</dt>
                <dd class="of-num">{{ ariary(commission.chiffres.facturee) }}</dd>
                <p>sur toute la période</p>
            </div>
            <div class="com__c">
                <dt>Reçue</dt>
                <dd class="of-num">{{ ariary(commission.chiffres.reglee) }}</dd>
                <p>règlements consignés</p>
            </div>
            <div class="com__c" :class="{ 'is-du': commission.chiffres.reste > 0 }">
                <dt>Reste à recevoir</dt>
                <dd class="of-num">{{ ariary(commission.chiffres.reste) }}</dd>
                <p>sur les mois clos</p>
            </div>
            <div class="com__c">
                <dt>Recouvrement</dt>
                <dd class="of-num">{{ pourcent(commission.chiffres.recouvrement) }}</dd>
                <p v-if="commission.chiffres.factures">{{ nombre(commission.chiffres.reglees) }} facture{{ commission.chiffres.reglees > 1 ? 's' : '' }} réglée{{ commission.chiffres.reglees > 1 ? 's' : '' }} sur {{ nombre(commission.chiffres.factures) }}</p>
                <p v-else>aucune facture sur les mois clos</p>
            </div>
            <div class="com__c">
                <dt>Mois en cours</dt>
                <dd class="of-num">{{ ariary(commission.chiffres.enCours) }}</dd>
                <p>s'accumule, pas encore dû</p>
            </div>
        </dl>

        <div class="com__corps">
            <OfficeChart
                nu
                titre="Commission facturée et reçue"
                type="barres"
                :labels="labels"
                :labels-longs="labelsLongs"
                :series="commission.series"
                format="ariary"
                :hauteur="280"
            />

            <div class="com__dus">
                <h4 class="com__sous">Qui doit encore</h4>
                <ul v-if="commission.debiteurs.length" class="com__liste">
                    <li v-for="d in commission.debiteurs" :key="d.ownerId ?? d.nom" class="com__du">
                        <div class="com__qui">
                            <Link v-if="d.ownerId" :href="`/proprietaires/${d.ownerId}`" class="com__nom">{{ d.nom }}</Link>
                            <span v-else class="com__nom">{{ d.nom }}</span>
                            <span class="com__mois">{{ d.mois.join(' · ') }}</span>
                        </div>
                        <strong class="of-num com__montant">{{ ariary(d.reste) }}</strong>
                    </li>
                </ul>
                <p v-else class="com__rien">Rien d'impayé sur les mois clos de la période.</p>
                <p v-if="commission.autresDebiteurs" class="com__note">
                    Et {{ pluriel(commission.autresDebiteurs, 'autre') }} — le détail est dans la <Link href="/facturation" class="com__lien">facturation</Link>.
                </p>
            </div>
        </div>

        <div class="com__table">
            <table>
                <caption class="com__caption">Mois par mois, le plus récent en tête</caption>
                <thead>
                    <tr>
                        <th scope="col">Mois</th>
                        <th scope="col">Séjours</th>
                        <th scope="col">Volume</th>
                        <th scope="col">Taux</th>
                        <th scope="col">Facturée</th>
                        <th scope="col">Reçue</th>
                        <th scope="col">Reste à recevoir</th>
                        <th scope="col">Factures réglées</th>
                        <th scope="col"><span class="com__sr">Facturation du mois</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="m in commission.mois" :key="m.mois" :class="{ 'is-cours': m.enCours }">
                        <th scope="row">
                            {{ m.label }}
                            <span v-if="m.enCours" class="of-chip">en cours</span>
                        </th>
                        <td class="of-num">{{ ouTiret(m.sejours, nombre) }}</td>
                        <td class="of-num">{{ ouTiret(m.volume) }}</td>
                        <td class="of-num">{{ pourcent(m.taux) }}</td>
                        <td class="of-num">{{ ouTiret(m.facturee) }}</td>
                        <td class="of-num">{{ ouTiret(m.reglee) }}</td>
                        <td class="of-num" :class="{ 'is-du': m.reste > 0 }">
                            <template v-if="m.enCours">pas encore dû</template>
                            <template v-else>{{ ouTiret(m.reste) }}</template>
                        </td>
                        <td class="of-num">{{ m.factures ? `${nombre(m.reglees)} sur ${nombre(m.factures)}` : '—' }}</td>
                        <td><Link :href="`/facturation?mois=${m.mois}`" class="com__lien">Voir le mois</Link></td>
                    </tr>
                </tbody>
            </table>
            <p v-if="commission.moisSansActivite" class="com__note">
                {{ nombre(commission.moisSansActivite) }} mois sans séjour ni règlement
                {{ commission.moisSansActivite > 1 ? 'ne sont pas listés' : "n'est pas listé" }}.
            </p>
        </div>
    </section>
</template>

<style scoped>
.com { display: grid; gap: 1.1rem; padding: 1.1rem 1.25rem 1.2rem; min-width: 0; }

.com__tete { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: .6rem 1rem; }
.com__intro { display: grid; gap: .2rem; max-width: 62rem; }
.com__titre { margin: 0; font-size: .98rem; font-weight: 800; letter-spacing: -.02em; color: var(--ink); }
.com__def { margin: 0; font-size: .78rem; line-height: 1.5; color: var(--text-3); }

.com__chiffres {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(9.5rem, 1fr));
    gap: 1px;
    margin: 0;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: var(--r-md);
    background: var(--line);
}
.com__c { display: grid; align-content: start; gap: .2rem; padding: .8rem .95rem; background: var(--white); }
.com__c dt { font-size: .72rem; font-weight: 600; line-height: 1.3; color: var(--text-3); }
.com__c dd { margin: 0; font-size: 1.15rem; font-weight: 800; letter-spacing: -.02em; line-height: 1.15; color: var(--ink); }
.com__c p { margin: 0; font-size: .72rem; line-height: 1.35; color: var(--text-3); }
.com__c.is-du { background: var(--terre-050); }
.com__c.is-du dd { color: var(--terre-700); }

/* Le graphique prend la place, la liste des dus se lit à côté ; sous
   1100 px elle passe dessous. */
.com__corps { display: grid; grid-template-columns: minmax(0, 2.2fr) minmax(16rem, 1fr); gap: 1.25rem; align-items: start; }

.com__dus { display: grid; gap: .5rem; padding: .85rem .95rem; border: 1px solid var(--line); border-radius: var(--r-md); background: var(--off); }
.com__sous { margin: 0; font-size: .74rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--text-3); }
.com__liste { display: grid; margin: 0; padding: 0; list-style: none; }
.com__du { display: flex; align-items: baseline; justify-content: space-between; gap: .75rem; padding: .55rem 0; border-top: 1px solid var(--line); }
.com__du:first-child { border-top: 0; }
.com__qui { display: grid; gap: .1rem; min-width: 0; }
.com__nom { font-size: .88rem; font-weight: 700; color: var(--ink); text-decoration: underline; text-decoration-color: var(--line-2); text-underline-offset: .2em; }
a.com__nom:hover { text-decoration-color: currentColor; }
span.com__nom { text-decoration: none; }
.com__mois { font-size: .74rem; color: var(--text-3); text-transform: capitalize; }
.com__montant { flex-shrink: 0; font-size: .92rem; font-weight: 800; color: var(--terre-700); }
.com__rien { margin: 0; font-size: .84rem; line-height: 1.45; color: var(--text-2); }

.com__note { margin: .5rem 0 0; font-size: .76rem; color: var(--text-3); }
.com__lien { font-weight: 700; color: var(--ink); text-decoration: underline; text-underline-offset: .2em; white-space: nowrap; }
.com__lien:hover { color: var(--terre-600); }

.com__table { overflow-x: auto; }
.com__table table { width: 100%; border-collapse: collapse; font-size: .82rem; }
.com__caption { padding-bottom: .5rem; caption-side: top; font-size: .74rem; font-weight: 700; letter-spacing: .08em; text-align: left; text-transform: uppercase; color: var(--text-3); }
.com__table th, .com__table td { padding: .5rem .6rem; border-bottom: 1px solid var(--line); text-align: right; white-space: nowrap; }
.com__table thead th { font-size: .7rem; font-weight: 700; color: var(--text-3); }
.com__table tbody th { text-align: left; font-weight: 700; color: var(--ink); text-transform: capitalize; }
.com__table tbody th .of-chip { margin-left: .35rem; text-transform: none; }
.com__table th:first-child { text-align: left; }
.com__table tr.is-cours td, .com__table tr.is-cours th { background: var(--off); }
.com__table td.is-du { font-weight: 800; color: var(--terre-700); }
.com__sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }

@media (max-width: 1100px) {
    .com__corps { grid-template-columns: minmax(0, 1fr); }
}
</style>
