<script setup>
/**
 * Moteur de recherche. Une pilule blanche posée sur le blanc : elle ne tient
 * que par son ombre et ses filets.
 *
 * **Il ne détient aucun état.** Tout vient de `useSearchQuery`, tenu par la
 * page d'accueil et partagé avec l'en-tête compact : deux exemplaires du
 * moteur, une seule vérité. Auparavant chaque exemplaire recopiait les
 * critères dans ses propres `ref` et les renvoyait par un événement — un
 * aller-retour qui rendait possible que le hero et l'en-tête affichent deux
 * recherches différentes.
 *
 * Les dates ouvrent **le calendrier de la maison**, pas deux `input[type=date]`
 * natifs : sur Android le sélecteur natif change de forme, de langue et de
 * premier jour de semaine d'un téléphone à l'autre, et il ne sait pas dire
 * « trois nuits ». C'est le même composant que la fiche logement, donc le même
 * geste — un clic pour l'arrivée, un pour le départ.
 *
 * `compact` sert la version encastrée dans l'en-tête au défilement.
 */
import { computed, ref, watch } from 'vue'

import StayCalendar from '@/Components/StayCalendar.vue'
import { formatCompact } from '@/Composables/useStayDates.js'

const props = defineProps({
    destinations: { type: Array, default: () => [] },
    compact: { type: Boolean, default: false },
    /** L'état rendu par `useSearchQuery`. */
    recherche: { type: Object, required: true },
})

const emit = defineEmits(['expand'])

const ouvert = ref(false)

const { destination, guests, dates, calendrier, libelle, compte, chercher } = props.recherche

const currentName = computed(() => {
    const found = props.destinations.find((d) => d.slug === destination.value)

    return found ? found.name : 'Partout à Madagascar'
})

/** Le résumé des dates, en une ligne, dans les deux formes du moteur. */
const resumeDates = computed(() => {
    if (dates.arrivee.value && dates.depart.value) {
        return `${formatCompact(dates.arrivee.value)} → ${formatCompact(dates.depart.value)}`
    }

    if (dates.arrivee.value) return formatCompact(dates.arrivee.value)

    return null
})

const nuits = computed(() => dates.nuits.value)

// Le calendrier se referme dès que le séjour est complet : le laisser ouvert
// obligeait à chercher où cliquer pour revenir au bouton « Chercher ».
watch(() => dates.complet.value, (fini) => {
    if (fini) ouvert.value = false
})
</script>

<template>
    <!-- Version réduite : un résumé cliquable qui renvoie au moteur complet -->
    <button
        v-if="compact"
        type="button"
        class="spc"
        @click="emit('expand')"
    >
        <span class="spc__txt">{{ currentName }}</span>
        <span class="spc__sep" aria-hidden="true"></span>
        <span class="spc__txt spc__txt--dim">{{ resumeDates ?? 'Quand ?' }}</span>
        <span class="spc__sep" aria-hidden="true"></span>
        <span class="spc__txt spc__txt--dim num">{{ guests }} voy.</span>
        <span class="spc__go" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round">
                <circle cx="11" cy="11" r="7" /><path d="m20 20-3.6-3.6" />
            </svg>
        </span>
        <span class="sr-only">Modifier la recherche</span>
    </button>

    <div v-else class="spw">
        <form class="sp" @submit.prevent="chercher()">
            <div class="sp__field sp__field--wide">
                <label class="sp__label" for="sp-dest">Où</label>
                <select id="sp-dest" v-model="destination" class="sp__input sp__select">
                    <option value="">Partout à Madagascar</option>
                    <option v-for="d in destinations" :key="d.slug" :value="d.slug">
                        {{ d.name }}
                    </option>
                </select>
            </div>

            <!-- Un seul champ pour les deux dates : c'est un séjour, pas deux
                 informations indépendantes, et le calendrier le dit mieux que
                 deux sélecteurs natifs côte à côte. -->
            <div class="sp__field sp__field--dates">
                <span class="sp__label">Dates</span>
                <button
                    type="button"
                    class="sp__input sp__dates"
                    :class="{ 'is-open': ouvert }"
                    :aria-expanded="ouvert"
                    @click="ouvert = !ouvert"
                >
                    <span :class="{ 'sp__placeholder': !resumeDates }">
                        {{ resumeDates ?? 'Choisir vos dates' }}
                    </span>
                    <span v-if="nuits" class="sp__nights num">{{ nuits }} nuit{{ nuits > 1 ? 's' : '' }}</span>
                </button>
            </div>

            <div class="sp__field sp__field--narrow">
                <label class="sp__label" for="sp-guests">Voyageurs</label>
                <select id="sp-guests" v-model.number="guests" class="sp__input sp__select">
                    <option v-for="n in 12" :key="n" :value="n">
                        {{ n }}{{ n === 12 ? '+' : '' }}
                    </option>
                </select>
            </div>

            <div class="sp__action">
                <button type="submit" class="sp__submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round">
                        <circle cx="11" cy="11" r="7" /><path d="m20 20-3.6-3.6" />
                    </svg>
                    <span class="sp__submit-txt">Chercher</span>
                </button>
            </div>
        </form>

        <Transition name="sp">
            <div v-if="ouvert" class="sp__cal">
                <StayCalendar :dates="dates" :calendar="calendrier" :months="2" />
            </div>
        </Transition>

        <!-- Le décompte vient de l'API, pas des huit annonces que la page
             tient dans ses props : il doit dire la vérité sur tout le
             catalogue, y compris sur les dates que le navigateur ne sait pas
             filtrer. -->
        <p class="sp__count" :class="{ 'is-loading': compte }" aria-live="polite">
            <span v-if="libelle">{{ libelle }}</span>
        </p>
    </div>
</template>

<style scoped>
/* ---------- Moteur complet ---------- */

.sp {
    display: grid;
    grid-template-columns: 1fr;
    gap: .25rem;
    padding: .4rem;
    background: #fff;
    border: 1px solid var(--line-2);
    border-radius: var(--r-xl);
    box-shadow: var(--sh-2);
    transition: box-shadow .45s var(--ease);
}
.sp:focus-within { box-shadow: var(--sh-3); }

.sp__field {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: .1rem;
    padding: .85rem 1.15rem;
    border-radius: var(--r-lg);
    cursor: text;
    transition: background-color .3s var(--ease);
}
.sp__field:hover { background: var(--off); }
.sp__field:focus-within { background: var(--terre-050); }

.sp__label {
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .14em;
    color: var(--text-3);
    transition: color .3s;
}
.sp__field:focus-within .sp__label { color: var(--terre-500); }

.sp__input {
    width: 100%;
    padding: 0;
    font-family: inherit;
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: -.018em;
    color: var(--ink);
    background: transparent;
    border: 0;
    outline: none;
    appearance: none;
}
.sp__input::-webkit-calendar-picker-indicator { cursor: pointer; opacity: .35; }
.sp__input::-webkit-calendar-picker-indicator:hover { opacity: .8; }

.sp__select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231A1512' stroke-width='2.4' stroke-linecap='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0 center;
    background-size: 15px;
    padding-right: 1.4rem;
    cursor: pointer;
}

.sp__action { padding: .1rem; }

.sp__submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .6rem;
    width: 100%;
    height: 100%;
    min-height: 56px;
    padding-inline: 1.5rem;
    font-family: inherit;
    font-size: .95rem;
    font-weight: 700;
    letter-spacing: -.012em;
    color: #fff;
    background: var(--terre-500);
    border: 0;
    border-radius: var(--r-lg);
    cursor: pointer;
    transition: background-color .3s, box-shadow .4s var(--ease);
}
.sp__submit:hover { background: var(--terre-600); box-shadow: var(--sh-terre); }
.sp__submit:focus-visible { outline: 2.5px solid var(--ink); outline-offset: 2px; }
.sp__submit svg { width: 19px; height: 19px; flex: none; }

@media (min-width: 900px) {
    .sp {
        grid-template-columns: 1.4fr 1fr 1fr .78fr auto;
        align-items: stretch;
        border-radius: var(--r-pill);
        padding: .35rem .35rem .35rem .5rem;
    }
    .sp__field { justify-content: center; border-radius: var(--r-pill); }

    /* Les filets ne sont pas des bordures : ils disparaissent au survol
       du champ voisin, comme sur les moteurs de réservation. */
    .sp__field + .sp__field::before,
    .sp__action::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        translate: 0 -50%;
        width: 1px;
        height: 44%;
        background: var(--line-2);
        transition: opacity .25s;
    }
    .sp__action { position: relative; }
    .sp:hover .sp__field:hover + .sp__field::before,
    .sp__field:hover::before,
    .sp__field:focus-within::before,
    .sp__field:focus-within + .sp__field::before,
    .sp__field:focus-within + .sp__action::before,
    .sp__field:hover + .sp__action::before { opacity: 0; }

    .sp__submit { border-radius: var(--r-pill); min-height: 60px; }
    .sp__submit-txt { display: inline; }
}

@media (max-width: 899px) {
    .sp__field + .sp__field { border-top: 1px solid var(--line); border-radius: 0; }
    .sp__field:last-of-type { border-radius: 0 0 var(--r-lg) var(--r-lg); }
}

/* ---------- Résumé encastré dans l'en-tête ---------- */

.spc {
    display: inline-flex;
    align-items: center;
    gap: .7rem;
    padding: .38rem .38rem .38rem 1.15rem;
    font-family: inherit;
    background: #fff;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    box-shadow: var(--sh-1);
    cursor: pointer;
    transition: box-shadow .35s var(--ease), border-color .3s;
}
.spc:hover { box-shadow: var(--sh-2); border-color: var(--line); }
.spc:focus-visible { outline: 2.5px solid var(--terre-500); outline-offset: 3px; }

.spc__txt {
    font-size: .86rem;
    font-weight: 700;
    letter-spacing: -.015em;
    color: var(--ink);
    white-space: nowrap;
}
.spc__txt--dim { font-weight: 500; color: var(--text-2); }

.spc__sep { width: 1px; height: 18px; background: var(--line-2); }

.spc__go {
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    color: #fff;
    background: var(--terre-500);
    transition: background-color .3s;
}
.spc:hover .spc__go { background: var(--terre-600); }
.spc__go svg { width: 16px; height: 16px; }

@media (max-width: 1023px) {
    .spc__sep, .spc__txt--dim { display: none; }
}
</style>

/* ---------- Enveloppe, calendrier et décompte ---------- */

.spw { position: relative; }

.sp__dates {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
    gap: .15rem .5rem;
    text-align: left;
    cursor: pointer;
}
.sp__dates.is-open { font-weight: 700; }
.sp__placeholder { color: var(--text-3); }
.sp__nights {
    font-size: .74rem;
    font-weight: 700;
    color: var(--terre-600);
    white-space: nowrap;
}

.sp__cal {
    position: absolute;
    z-index: 20;
    left: 0;
    right: 0;
    margin-top: .6rem;
    padding: clamp(1rem, 2.5vw, 1.5rem);
    border: 1px solid var(--line);
    border-radius: var(--r-lg);
    background: var(--white);
    box-shadow: var(--sh-2);
    overflow: hidden;
}

/* Réservé même vide : sans hauteur fixe, l'apparition du décompte pousse
   toute la page d'un cran au premier caractère tapé. */
.sp__count {
    min-height: 1.4rem;
    margin: .7rem 0 0;
    text-align: center;
    font-size: .84rem;
    font-weight: 600;
    color: var(--text-2);
    transition: opacity .25s var(--ease);
}
.sp__count.is-loading { opacity: .45; }

.sp-enter-active, .sp-leave-active { transition: opacity .25s var(--ease), transform .25s var(--ease); }
.sp-enter-from, .sp-leave-to { opacity: 0; transform: translateY(-8px); }

@media (prefers-reduced-motion: reduce) {
    .sp-enter-active, .sp-leave-active { transition: none; }
}
