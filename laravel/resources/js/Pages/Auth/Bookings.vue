<script setup>
/**
 * L'espace client — ses réservations.
 *
 * **C'est tout ce que le compte apporte aujourd'hui, et c'est déjà ce qui
 * manquait le plus** : sans lui, un voyageur qui avait perdu sa référence
 * n'avait aucun chemin vers son séjour. Le compte ne conditionne toujours
 * rien — la demande de séjour reste ouverte sans, et la référence continue
 * d'ouvrir une réservation.
 *
 * **Les séjours se rattachent par l'adresse e-mail.** Celui qui a réservé
 * avant de créer son compte les retrouve dès l'inscription, sans qu'on recolle
 * quoi que ce soit à la main. C'est aussi pourquoi l'écran dit à quelle
 * adresse il regarde : un séjour réservé avec une autre adresse n'apparaîtra
 * pas, et il faut pouvoir le comprendre sans appeler.
 *
 * **L'écran est passé sous `SpaceShell`**, le même cadre que l'espace
 * propriétaire : une barre à gauche, et le contenu à droite. Une seule
 * rubrique existe pour l'instant — c'est justement pour ça qu'elle a besoin
 * d'être posée dans une structure qui montre ce qui vient : messages, profil,
 * favoris. Un écran unique sans barre n'aurait rien annoncé, et il aurait
 * fallu tout redessiner à la première rubrique ajoutée.
 *
 * **L'en-tête du site reste.** Contrairement au propriétaire, le voyageur
 * n'est pas dans un outil : il est sur le site, et il en repartira vers le
 * catalogue. Lui retirer le catalogue et les destinations pour lui montrer ses
 * deux réservations serait l'enfermer dans une pièce vide.
 *
 * **Plus de bouton « Se déconnecter » sur l'écran.** Il est dans le menu du
 * compte, en haut à droite, sur **toutes** les pages du site : le laisser ici
 * aussi faisait deux sorties à quinze centimètres d'écart, et donnait à croire
 * que celle-ci était particulière.
 *
 * **Le titre nomme l'écran, il ne salue plus.** « Bonjour X » était pris sur le
 * premier mot du nom, et ce mot est le **nom de famille** dès qu'il est écrit à
 * la malgache — « RAKOTOBE Jean ». On saluait donc quelqu'un par son patronyme,
 * en capitales, et le nom du compte ne dit de toute façon rien de plus que le
 * menu qui le porte déjà en haut à droite. Un écran de travail se nomme :
 * « Mes réservations », le même mot que la rubrique, pour confirmer où l'on est.
 *
 * **Et il prend `.espace__titre`, pas `.display--lg`.** L'échelle des pages de
 * vente monte à 3,9 rem : soixante-deux pixels au-dessus de deux réservations,
 * dans une colonne posée à côté d'une barre latérale. Elle hurlait là où il
 * fallait informer.
 */
import { Head, Link } from '@inertiajs/vue3'

import SiteHeader from '@/Components/SiteHeader.vue'
import SiteFooter from '@/Components/SiteFooter.vue'
import SpaceShell from '@/Components/SpaceShell.vue'
import { RUBRIQUES_CLIENT } from '@/Support/espaces.js'
import { nombre } from '@/Support/format.js'
import { formatLong } from '@/Composables/useStayDates.js'

defineProps({
    traveller: { type: Object, required: true },
    bookings: { type: Array, default: () => [] },
})

</script>

<template>
    <Head title="Mes réservations — Vayla" />

    <SiteHeader :search="false" />

    <SpaceShell espace="Espace client" :groupes="RUBRIQUES_CLIENT">
        <header class="espace__tete">
            <h1 class="espace__titre">Mes réservations</h1>
            <p class="espace__lede">
                Les séjours demandés avec <strong>{{ traveller.email }}</strong>.
                Un séjour réservé avec une autre adresse n'apparaît pas ici.
            </p>
        </header>

        <div v-if="!bookings.length" class="mb__empty">
            <p class="mb__empty-t">Aucune réservation pour l'instant.</p>
            <p class="mb__empty-s">
                Vos séjours apparaîtront ici. Si vous avez déjà réservé avec une
                autre adresse, ouvrez cette réservation avec sa référence — elle
                est dans le message reçu au moment de la demande.
            </p>
            <Link href="/logements" class="btn btn--terre btn--lg">Chercher un logement</Link>
        </div>

        <ul v-else class="mb__list">
            <li v-for="b in bookings" :key="b.reference" class="mb__row">
                <div class="mb__main">
                    <p class="mb__when">
                        {{ formatLong(b.arrival) }} → {{ formatLong(b.departure) }}
                    </p>
                    <p class="mb__listing">{{ b.listing }}<template v-if="b.place"> · {{ b.place }}</template></p>
                    <p class="mb__facts num">
                        {{ b.nights }} nuit{{ b.nights > 1 ? 's' : '' }} ·
                        {{ b.guests }} pers. · {{ nombre(b.total) }} Ar
                    </p>
                </div>

                <div class="mb__side">
                    <span class="mb__state" :class="`mb__state--${b.status}`">{{ b.statusLabel }}</span>
                    <Link :href="`/reservations/${b.reference}`" class="btn btn--outline mb__open">
                        Ouvrir
                    </Link>
                    <span class="mb__ref num">{{ b.reference }}</span>
                </div>
            </li>
        </ul>
    </SpaceShell>

    <SiteFooter />
</template>

<style scoped>
.espace__lede strong { color: var(--ink); font-weight: 700; }

.mb__empty {
    padding: clamp(2rem, 6vw, 3.5rem);
    border: 1px dashed var(--line-2);
    border-radius: var(--r-lg);
    text-align: center;
    background: var(--white);
}
.mb__empty-t { margin: 0; font-size: 1.15rem; font-weight: 800; letter-spacing: -.025em; color: var(--ink); }
.mb__empty-s { margin: .5rem auto 1.5rem; max-width: 48ch; font-size: .92rem; line-height: 1.6; color: var(--text-2); }
.mb__empty-s strong { color: var(--ink); }

.mb__list { display: grid; gap: .7rem; margin: 0; padding: 0; list-style: none; }

.mb__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .9rem 1.25rem;
    padding: 1.1rem 1.25rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
    transition: border-color .25s var(--ease), box-shadow .25s var(--ease);
}
.mb__row:hover { border-color: var(--line); box-shadow: var(--sh-1); }

.mb__main { min-width: 0; }
.mb__when { margin: 0; font-size: 1.02rem; font-weight: 800; letter-spacing: -.025em; color: var(--ink); }
.mb__listing { margin: .2rem 0 0; font-size: .92rem; color: var(--text-2); }
.mb__facts { margin: .25rem 0 0; font-size: .82rem; color: var(--text-3); }

.mb__side { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; }

/* Neutre : la terre porte l'action, le lagon ne dit que « vérifié ». Un état
   de réservation n'est ni l'un ni l'autre. */
.mb__state {
    padding: .28rem .8rem;
    border-radius: var(--r-pill);
    background: var(--off-2);
    font-size: .74rem;
    font-weight: 800;
    color: var(--text-2);
}
.mb__state--accepted, .mb__state--completed { background: var(--ink); color: var(--white); }

.mb__ref { font-size: .74rem; color: var(--text-3); }
</style>
