<script setup>
/**
 * L'encart de demande.
 *
 * Il reprend la mécanique d'Airbnb — total pour N nuits, deux champs de
 * dates, un bouton — et s'en écarte sur le point qui compte : **rien n'est
 * encaissé ici**. Pas de « 0 € aujourd'hui » qui laisse croire qu'un
 * paiement viendra plus tard : le voyageur règle sur place, avec le
 * propriétaire.
 *
 * Le prix affiché en tête est le **total du séjour** dès que des dates sont
 * choisies, pas le prix à la nuit. Un tarif nuitée seul oblige le voyageur à
 * faire la multiplication, et c'est exactement là qu'on perd les gens.
 */
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import StayCalendar from '@/Components/StayCalendar.vue'
import TrustGauge from '@/Components/TrustGauge.vue'
import AmenityIcon from '@/Components/AmenityIcon.vue'
import { formatCompact } from '@/Composables/useStayDates.js'
import { nombre } from '@/Support/format.js'
import { useDevise } from '@/Composables/useDevise.js'

const props = defineProps({
    listing: { type: Object, required: true },
    calendar: { type: Object, required: true },
    dates: { type: Object, required: true },
    trustName: { type: String, default: '' },
})

const ouvert = ref(false)
const money = nombre

// L'euro en second, jamais seul : le voyageur règle en ariary sur place, et
// c'est ici — l'endroit où le prix se décide — que la mention doit se lire.
const { euros, mention } = useDevise()

const titre = computed(() =>
    props.dates.complet.value
        ? `${money(props.dates.total.value)} Ar`
        : `${money(props.listing.price)} Ar`
)

const soustitre = computed(() =>
    props.dates.complet.value
        ? `pour ${props.dates.nuits.value} nuit${props.dates.nuits.value > 1 ? 's' : ''}`
        : 'par nuit'
)

const titreEur = computed(() => euros(
    props.dates.complet.value ? props.dates.total.value : props.listing.price
))

/** Les dates partent dans l'URL : le formulaire de réservation les reprend. */
const lienReserver = computed(() => {
    const p = new URLSearchParams()
    if (props.dates.complet.value) {
        p.set('arrivee', props.dates.arrivee.value)
        p.set('depart', props.dates.depart.value)
    }
    const q = p.toString()
    return `/logements/${props.listing.slug}/reserver${q ? `?${q}` : ''}`
})
</script>

<template>
    <div class="bb" data-sticky>
        <p class="bb__price">
            <span class="num bb__amount">{{ titre }}</span>
            <span class="bb__unit">{{ soustitre }}</span>
            <span v-if="titreEur" class="eur bb__eur">{{ titreEur }}</span>
        </p>

        <!-- Les deux champs ouvrent le même calendrier : deux calendriers
             distincts pour une même plage se désynchronisent toujours.

             Le pictogramme et le chevron ne sont pas décoratifs : sans eux,
             deux cases bordées portant « Choisir » se lisaient comme un
             affichage, pas comme un bouton. Nos deux publics n'ont pas le
             même bagage numérique, et ce qui va de soi pour un habitué des
             plateformes ne va pas de soi pour tout le monde. -->
        <div class="bb__dates" :class="{ 'is-open': ouvert }">
            <button type="button" class="bb__field" @click="ouvert = !ouvert">
                <span class="bb__label">
                    <svg class="bb__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <rect x="3.5" y="5" width="17" height="15.5" rx="2.5" />
                        <path d="M3.5 9.5h17M8 3v4M16 3v4" />
                    </svg>
                    Arrivée
                </span>
                <span class="bb__value num" :class="{ 'is-empty': !dates.arrivee.value }">
                    {{ dates.arrivee.value ? formatCompact(dates.arrivee.value) : 'Choisir' }}
                </span>
            </button>
            <button type="button" class="bb__field" @click="ouvert = !ouvert">
                <span class="bb__label">
                    <svg class="bb__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <rect x="3.5" y="5" width="17" height="15.5" rx="2.5" />
                        <path d="M3.5 9.5h17M8 3v4M16 3v4" />
                    </svg>
                    Départ
                </span>
                <span class="bb__value num" :class="{ 'is-empty': !dates.depart.value }">
                    {{ dates.depart.value ? formatCompact(dates.depart.value) : 'Choisir' }}
                </span>
            </button>
            <span class="bb__chevron" :class="{ 'is-open': ouvert }" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="m5 9 7 7 7-7" />
                </svg>
            </span>
        </div>

        <p v-if="!dates.complet.value" class="bb__howto">
            Cliquez sur les dates ci-dessus pour ouvrir le calendrier.
        </p>

        <Transition name="bb">
            <div v-if="ouvert" class="bb__cal">
                <StayCalendar :dates="dates" :calendar="calendar" :months="1" />
            </div>
        </Transition>

        <p v-if="dates.erreur.value" class="bb__error">{{ dates.erreur.value }}</p>

        <!-- Le détail n'apparaît qu'une fois les dates posées : un tableau de
             calcul sur un séjour vide n'informe personne. -->
        <dl v-if="dates.complet.value" class="bb__detail">
            <div>
                <dt>{{ money(listing.price) }} Ar × {{ dates.nuits.value }} nuits</dt>
                <dd class="num">{{ money(dates.total.value) }} Ar</dd>
            </div>
            <div class="bb__detail-total">
                <dt>Total</dt>
                <dd class="num">{{ money(dates.total.value) }} Ar</dd>
            </div>
        </dl>

        <Link :href="lienReserver" class="btn btn--terre btn--lg bb__cta">
            {{ dates.complet.value ? 'Réserver ces dates' : 'Voir les disponibilités' }}
        </Link>

        <p class="bb__nofee">Ni carte, ni acompte, ni compte à créer.</p>

        <!-- Un montant converti sans sa date est invérifiable, et c'est ici
             que l'argent se décide : la mention est visible, pas au survol. -->
        <p v-if="titreEur && mention" class="bb__rate">{{ mention }}</p>

        <p class="bb__trust">
            <TrustGauge :level="listing.trust" />
            <span>{{ trustName }}</span>
        </p>

        <ul class="bb__reassure">
            <li>
                <AmenityIcon name="shield" />
                Aucun paiement sur Vayla
            </li>
            <li>
                <AmenityIcon name="guide" />
                Mise en relation directe avec le propriétaire
            </li>
            <li>
                <AmenityIcon name="key" />
                Vous réglez sur place, comme vous voulez
            </li>
        </ul>
    </div>
</template>

<style scoped>
.bb {
    padding: clamp(1.4rem, 3vw, 1.75rem);
    border: 1px solid var(--line);
    border-radius: var(--r-lg);
    background: var(--white);
    box-shadow: 0 1px 2px rgba(23, 20, 28, .04);
}

.bb__price { display: flex; align-items: baseline; flex-wrap: wrap; gap: .35rem; margin: 0; }
.bb__amount { font-size: 1.6rem; font-weight: 800; letter-spacing: -.042em; color: var(--ink); }
.bb__unit { font-size: .88rem; font-weight: 500; color: var(--text-3); }

/* Deux champs dans un même cadre, comme une paire : ils décrivent une seule
   plage, pas deux valeurs indépendantes. */
.bb__dates {
    position: relative;
    display: grid;
    grid-template-columns: 1fr 1fr;
    margin-top: 1.1rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    overflow: hidden;
    transition: border-color .25s var(--ease), box-shadow .25s var(--ease);
}
.bb__dates:hover { border-color: var(--text-3); }
.bb__dates.is-open { border-color: var(--ink); box-shadow: 0 0 0 3px var(--off-2); }
.bb__dates .bb__field + .bb__field { border-left: 1px solid var(--line-2); }

.bb__field {
    display: flex;
    flex-direction: column;
    gap: .18rem;
    /* Réserve à droite pour le chevron, sinon il chevauche la date. */
    padding: .65rem 1.9rem .7rem .8rem;
    border: 0;
    background: var(--white);
    font: inherit;
    text-align: left;
    cursor: pointer;
}
.bb__field:hover { background: var(--off); }
.bb__field:focus-visible { outline: 2px solid var(--terre-500); outline-offset: -2px; }

.bb__label {
    display: flex;
    align-items: center;
    gap: .3rem;
    font-size: .64rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--text-3);
}
.bb__ico { width: .78rem; height: .78rem; flex: 0 0 auto; }

.bb__value { font-size: .88rem; font-weight: 700; color: var(--ink); }

/* Tant qu'aucune date n'est posée, « Choisir » est une action, pas une
   valeur : il porte la couleur de la marque comme les boutons. */
.bb__value.is-empty { color: var(--terre-600); text-decoration: underline; text-underline-offset: 3px; }

.bb__chevron {
    position: absolute;
    right: .6rem;
    top: 50%;
    translate: 0 -50%;
    display: grid;
    place-items: center;
    pointer-events: none;
    color: var(--text-3);
    transition: rotate .3s var(--ease);
}
.bb__chevron.is-open { rotate: 180deg; color: var(--ink); }
.bb__chevron svg { width: .95rem; height: .95rem; }

.bb__howto {
    margin: .5rem 0 0;
    font-size: .78rem;
    line-height: 1.45;
    color: var(--text-3);
}

.bb__cal {
    margin-top: .9rem;
    padding: .9rem;
    border: 1px solid var(--line);
    border-radius: var(--r-md);
    background: var(--off);
    overflow: hidden;
}

.bb-enter-active, .bb-leave-active { transition: opacity .28s var(--ease), transform .28s var(--ease); }
.bb-enter-from, .bb-leave-to { opacity: 0; transform: translateY(-6px); }

.bb__error {
    margin: .8rem 0 0;
    padding: .55rem .75rem;
    border-radius: var(--r-sm);
    background: var(--terre-050);
    font-size: .8rem;
    font-weight: 600;
    color: var(--terre-700);
}

.bb__detail {
    margin: 1.1rem 0 0;
    padding-top: .9rem;
    border-top: 1px solid var(--line);
    font-size: .86rem;
}
.bb__detail > div { display: flex; justify-content: space-between; gap: 1rem; }
/* Pas de soulignement : cette ligne n'est pas cliquable, et la souligner
   promettait une action qui n'existe pas. */
.bb__detail dt { color: var(--text-2); }
.bb__detail dd { margin: 0; color: var(--text-2); }

.bb__detail-total {
    margin-top: .7rem;
    padding-top: .7rem;
    border-top: 1px solid var(--line);
    font-weight: 800;
}
.bb__detail-total dt { color: var(--ink); text-decoration: none; }
.bb__detail-total dd { color: var(--ink); }

.bb__cta { width: 100%; justify-content: center; margin-top: 1.1rem; }

/* L'euro passe à la ligne : `.eur` est en `display: block`, mais un enfant de
   conteneur flex ne crée pas de rupture. Une base à 100 % la force, sans
   toucher à la règle partagée. */
.bb__eur { flex-basis: 100%; margin-top: .1rem; font-size: .95rem; }

.bb__rate { margin: .6rem 0 0; font-size: .74rem; line-height: 1.5; color: var(--text-3); }

.bb__nofee {
    margin: .65rem 0 0;
    text-align: center;
    font-size: .78rem;
    color: var(--text-3);
}

.bb__trust {
    display: flex;
    align-items: center;
    gap: .55rem;
    margin: 1.1rem 0 0;
    padding-top: .9rem;
    border-top: 1px solid var(--line);
    font-size: .84rem;
    font-weight: 600;
    color: var(--text-2);
}

.bb__reassure {
    display: flex;
    flex-direction: column;
    gap: .5rem;
    margin: .9rem 0 0;
    padding: 0;
    list-style: none;
    font-size: .79rem;
    line-height: 1.4;
    color: var(--text-3);
}
.bb__reassure li { display: flex; align-items: flex-start; gap: .5rem; }
.bb__reassure :deep(.ai) { margin-top: .05rem; width: 1rem; height: 1rem; }

@media (prefers-reduced-motion: reduce) {
    .bb-enter-active, .bb-leave-active { transition: none; }
}
</style>
