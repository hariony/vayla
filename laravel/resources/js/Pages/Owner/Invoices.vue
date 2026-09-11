<script setup>
/**
 * La facturation, vue par le propriétaire.
 *
 * **C'est la rubrique qui décide s'il reste.** Il paie une commission sur des
 * séjours qu'il doit pouvoir vérifier ligne à ligne : le tableau de bord n'en
 * montrait qu'une, et seulement la dernière. « Pourquoi ce montant » n'avait
 * pas de réponse trois mois plus tard.
 *
 * **Le mois en cours vient en premier, et ce n'est pas une facture.** C'est ce
 * qui s'accumule. Le cacher jusqu'au premier du mois suivant ferait découvrir
 * un montant qu'on aurait pu voir venir — et c'est exactement ce qui fait
 * qu'une commission se sent comme un piège. L'écran écrit noir sur blanc que
 * rien n'est encore dû.
 *
 * **Aucun euro sur cette page.** La règle vaut pour tout ce que le
 * propriétaire reçoit ou doit : il est réglé en ariary par mobile money, et un
 * euro à côté d'une commission serait un chiffre de plus à rapprocher, pour
 * rien.
 *
 * **Rien ne se paie ici.** Vayla n'encaisse pas : le propriétaire pousse son
 * règlement par mobile money. Un bouton « Payer » promettrait un débit qui
 * n'existe pas.
 */
import { Head } from '@inertiajs/vue3'

import OwnerShell from './Partials/OwnerShell.vue'
import InvoiceCard from './Partials/InvoiceCard.vue'
import { nombre } from '@/Support/format.js'

const props = defineProps({
    facturation: { type: Object, required: true },
    taux: { type: Number, default: 0 },
})

const pourcent = Math.round(props.taux * 1000) / 10
</script>

<template>
    <Head title="Facturation — Vayla" />

    <OwnerShell>
        <header class="espace__tete">
            <h1 class="espace__titre">Facturation</h1>
            <p class="espace__lede">
                Vayla n'encaisse rien pendant le séjour. Une commission de
                <strong class="num">{{ pourcent }}&nbsp;%</strong> est facturée en fin de
                mois, et <strong>uniquement sur les séjours confirmés par les
                voyageurs</strong> — ni les demandes, ni les annulations, ni les
                voyageurs qui ne sont pas venus.
            </p>
        </header>

        <!-- Le mois en cours : ce qui s'accumule, pas ce qui est dû. -->
        <section class="fa__cours">
            <header class="fa__cours-h">
                <p class="fa__cours-lab">Ce mois-ci</p>
                <h2 class="espace__section fa__cours-t">{{ facturation.encours.period.label }}</h2>
            </header>

            <p v-if="!facturation.encours.stays" class="fa__cours-rien">
                Aucun séjour confirmé pour l'instant&nbsp;: <strong>rien à venir</strong>.
            </p>

            <dl v-else class="fa__chiffres">
                <div class="fa__chiffre">
                    <dt>Séjours confirmés</dt>
                    <dd class="num">{{ facturation.encours.stays }}</dd>
                </div>
                <div class="fa__chiffre">
                    <dt>Nuits</dt>
                    <dd class="num">{{ facturation.encours.nights }}</dd>
                </div>
                <div class="fa__chiffre">
                    <dt>Encaissé sur place</dt>
                    <dd class="num">{{ nombre(facturation.encours.revenue) }} Ar</dd>
                </div>
                <div class="fa__chiffre fa__chiffre--du">
                    <dt>Commission à venir</dt>
                    <dd class="num">{{ nombre(facturation.encours.due) }} Ar</dd>
                </div>
            </dl>

            <p class="fa__cours-note">
                Ce n'est pas encore une facture&nbsp;: le mois n'est pas terminé, et
                un séjour ne compte qu'une fois confirmé par le voyageur.
            </p>
        </section>

        <h2 class="espace__section fa__h">Vos factures</h2>

        <div v-if="facturation.factures.length" class="fa__liste">
            <!-- La plus récente ouverte, les autres repliées : on vient
                 vérifier la dernière, pas relire six mois. Un `<details>` est
                 un vrai contrôle, bordé et pourvu d'un chevron — pas un titre
                 qu'il faudrait deviner cliquable. -->
            <details
                v-for="(facture, i) in facturation.factures"
                :key="facture.period.from"
                class="fa__pli"
                :open="i === 0"
            >
                <summary class="fa__resume">
                    <span class="fa__resume-t">{{ facture.period.label }}</span>
                    <span class="fa__resume-n num">{{ facture.stays }} séjour{{ facture.stays > 1 ? 's' : '' }}</span>
                    <span class="fa__resume-du num">{{ nombre(facture.due) }} Ar</span>
                    <svg class="fa__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m7 10 5 5 5-5" />
                    </svg>
                </summary>

                <div class="fa__detail">
                    <InvoiceCard :invoice="facture" />
                </div>
            </details>
        </div>

        <p v-else class="fa__vide">
            Aucune facture pour l'instant. La première arrivera le mois qui suit
            votre premier séjour confirmé.
        </p>
    </OwnerShell>
</template>

<style scoped>
.espace__lede strong { color: var(--ink); font-weight: 700; }

.fa__cours {
    padding: clamp(1.25rem, 3vw, 1.75rem);
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
}

.fa__cours-h { margin-bottom: 1.1rem; }
.fa__cours-lab {
    margin: 0;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: var(--text-3);
}
.fa__cours-t {
    margin: .2rem 0 0;
    text-transform: capitalize;
}

.fa__cours-rien {
    margin: 0;
    padding: 1.1rem;
    border: 1px dashed var(--line-2);
    border-radius: var(--r-md);
    font-size: .95rem;
    color: var(--text-2);
    text-align: center;
}
.fa__cours-rien strong { color: var(--ink); }

.fa__chiffres {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(9.5rem, 1fr));
    gap: .8rem;
    margin: 0;
}
.fa__chiffre {
    padding: .85rem 1rem;
    border: 1px solid var(--line);
    border-radius: var(--r-md);
    background: var(--off);
}
.fa__chiffre dt { font-size: .78rem; font-weight: 700; color: var(--text-3); }
.fa__chiffre dd {
    margin: .2rem 0 0;
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: -.03em;
    color: var(--ink);
}

/* Le montant qui compte prend l'encre pleine, pas la terre : la terre porte
   l'action, et il n'y a rien à faire ici — le mois n'est pas fini. */
.fa__chiffre--du { border-color: var(--ink); background: var(--ink); }
.fa__chiffre--du dt { color: rgba(255, 255, 255, .7); }
.fa__chiffre--du dd { color: var(--white); }

.fa__cours-note {
    margin: 1rem 0 0;
    max-width: 60ch;
    font-size: .82rem;
    line-height: 1.55;
    color: var(--text-3);
}

.fa__h {
    margin: clamp(2rem, 4vw, 2.75rem) 0 .9rem;
}

.fa__liste { display: grid; gap: .6rem; }

.fa__pli {
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
    overflow: hidden;
}

/* Un contrôle doit se voir comme un contrôle : la barre entière se clique,
   elle a sa hauteur de cible tactile et son chevron. */
.fa__resume {
    display: flex;
    align-items: center;
    gap: .75rem;
    min-height: 3.25rem;
    padding: .75rem 1.15rem;
    cursor: pointer;
    list-style: none;
    transition: background-color .2s var(--ease);
}
.fa__resume::-webkit-details-marker { display: none; }
.fa__resume:hover { background: var(--off); }
.fa__pli[open] .fa__resume { border-bottom: 1px solid var(--line); }

.fa__resume-t {
    flex: 1;
    min-width: 0;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -.025em;
    color: var(--ink);
    text-transform: capitalize;
}
.fa__resume-n { font-size: .84rem; color: var(--text-3); white-space: nowrap; }
.fa__resume-du {
    font-size: .98rem;
    font-weight: 800;
    color: var(--ink);
    white-space: nowrap;
}

.fa__chev {
    flex: none;
    width: 1.15rem;
    height: 1.15rem;
    color: var(--text-3);
    transition: transform .3s var(--ease);
}
.fa__pli[open] .fa__chev { transform: rotate(180deg); }

.fa__detail { padding: clamp(1rem, 3vw, 1.5rem); }

.fa__vide {
    margin: 0;
    padding: clamp(1.75rem, 5vw, 2.5rem);
    border: 1px dashed var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
    font-size: .95rem;
    line-height: 1.6;
    color: var(--text-2);
    text-align: center;
}

@media (max-width: 560px) {
    .fa__resume { flex-wrap: wrap; gap: .3rem .75rem; }
    .fa__resume-t { flex: 1 0 100%; }
}

@media (prefers-reduced-motion: reduce) {
    .fa__chev { transition: none; }
}
</style>
