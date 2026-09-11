<script setup>
/**
 * La liste d'une boîte — celle du propriétaire comme celle du voyageur.
 *
 * **Un seul composant pour les deux côtés**, comme `Conversation.vue` pour le
 * fil lui-même : deux listes qui rendraient la même chose avec deux mises en
 * page finiraient par ne plus dire la même chose. La seule différence est
 * `base`, le chemin vers lequel la ligne mène — la réservation côté
 * propriétaire, le suivi de réservation côté voyageur.
 *
 * **Ce qu'une ligne doit répondre, dans l'ordre où on se le demande** :
 * est-ce que ça m'attend (la pastille), de quoi ça parle (le sujet), qu'est-ce
 * qui a été dit en dernier (l'extrait), et quand (l'ancienneté). Le séjour
 * vient après — il situe, il ne décide pas.
 *
 * **L'extrait montre le dernier message, même s'il est de moi.** Ne montrer
 * que ce qu'on a reçu ferait disparaître sa propre réponse : on ne saurait
 * plus si on a répondu, ce qui est justement la question qu'on se pose en
 * ouvrant une boîte. « Vous : » préfixe alors la ligne.
 *
 * **Le non-lu est une pastille pleine, pas un gras.** Un texte en gras se
 * confond avec un titre ; un point de terre à gauche de la ligne se compte
 * d'un coup d'œil, et c'est exactement ce qu'on fait devant une boîte.
 */
import { Link } from '@inertiajs/vue3'

import { ilYA } from '@/Support/format.js'
import { formatCompact } from '@/Composables/useStayDates.js'

const props = defineProps({
    conversations: { type: Array, default: () => [] },
    /** Le chemin d'une conversation, auquel la référence est ajoutée. */
    base: { type: String, required: true },
    /** Le rôle de celui qui lit : sert à écrire « Vous : » au bon endroit. */
    moi: { type: String, required: true },
})

const lien = (c) => `${props.base}${c.reference}`

const prefixe = (c) => (c.auteur === props.moi ? 'Vous : ' : '')
</script>

<template>
    <ul class="cv">
        <li v-for="c in conversations" :key="c.reference" class="cv__item">
            <Link :href="lien(c)" class="cv__row" :class="{ 'is-neuf': c.nonLu }">
                <span class="cv__puce" :class="{ 'is-on': c.nonLu }" aria-hidden="true"></span>

                <span class="cv__corps">
                    <span class="cv__tete">
                        <span class="cv__sujet">{{ c.sujet }}</span>
                        <span class="cv__quand">{{ ilYA(c.quand) }}</span>
                    </span>

                    <span class="cv__extrait">
                        <span v-if="prefixe(c)" class="cv__moi">{{ prefixe(c) }}</span>{{ c.extrait }}
                    </span>

                    <span class="cv__sejour">
                        {{ c.listing }}<template v-if="c.place"> · {{ c.place }}</template>
                        · {{ formatCompact(c.arrival) }} → {{ formatCompact(c.departure) }}
                        · <span class="num">{{ c.reference }}</span>
                    </span>
                </span>

                <span class="cv__etat" :class="`cv__etat--${c.statut}`">{{ c.statutLabel }}</span>
            </Link>

            <!-- Annoncé aux lecteurs d'écran, qui ne voient pas la pastille. -->
            <span v-if="c.nonLu" class="sr-only">Message non lu</span>
        </li>
    </ul>
</template>

<style scoped>
.cv { display: grid; gap: .55rem; margin: 0; padding: 0; list-style: none; }

.cv__row {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: start;
    gap: .25rem .8rem;
    padding: 1rem 1.15rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
    text-decoration: none;
    transition:
        border-color .25s var(--ease),
        box-shadow .25s var(--ease),
        transform .25s var(--ease);
}
.cv__row:hover {
    border-color: var(--line);
    box-shadow: var(--sh-1);
    transform: translateY(-1px);
}
.cv__row:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }

/* La ligne qui attend se distingue par sa bordure, pas par un fond coloré :
   un aplat sur une liste entière fait un damier illisible dès que deux
   conversations se suivent. */
.cv__row.is-neuf { border-color: var(--terre-200); }

.cv__puce {
    width: .55rem;
    height: .55rem;
    margin-top: .45rem;
    border-radius: var(--r-pill);
    background: var(--line-2);
}
.cv__puce.is-on { background: var(--terre-500); }

.cv__corps { min-width: 0; }

.cv__tete { display: flex; align-items: baseline; justify-content: space-between; gap: .75rem; }

.cv__sujet {
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -.025em;
    color: var(--ink);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.cv__quand { flex: none; font-size: .78rem; color: var(--text-3); }

/* Une ligne, jamais trois : une boîte se parcourt du regard, et un extrait
   qui déborde fait scruter le mauvais message. */
.cv__extrait {
    display: block;
    margin-top: .2rem;
    font-size: .92rem;
    line-height: 1.45;
    color: var(--text-2);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.cv__moi { color: var(--text-3); }

.cv__sejour {
    display: block;
    margin-top: .3rem;
    font-size: .78rem;
    color: var(--text-3);
}

/* Neutre : la terre porte l'action, le lagon ne dit que « vérifié ». L'état
   d'une réservation n'est ni l'un ni l'autre. */
.cv__etat {
    flex: none;
    padding: .26rem .7rem;
    border-radius: var(--r-pill);
    background: var(--off-2);
    font-size: .72rem;
    font-weight: 800;
    color: var(--text-2);
    white-space: nowrap;
}
.cv__etat--accepted, .cv__etat--completed { background: var(--ink); color: var(--white); }

/* Sous 620 px l'état passe à la ligne : le comprimer sur la même rangée
   coupait le sujet à deux mots. */
@media (max-width: 620px) {
    .cv__row { grid-template-columns: auto minmax(0, 1fr); }
    .cv__etat { grid-column: 2; justify-self: start; margin-top: .5rem; }
    .cv__tete { flex-wrap: wrap; }
}
</style>
