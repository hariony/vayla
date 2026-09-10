<script setup>
/**
 * « Quand venir » — la section qui vend.
 *
 * Un calendrier de disponibilités est un composant banal : tout le monde en
 * a un, et sur Vayla il est même plus faible qu'ailleurs, puisque rien ne se
 * réserve sur la plateforme. Une case libre ne prouve rien.
 *
 * Ce qui n'existe nulle part, c'est la **saison**. À Madagascar la date ne
 * dit pas seulement « libre », elle dit si c'est une bonne idée : Nosy Be en
 * février est en saison cyclonique, Antsirabe en juillet descend sous dix
 * degrés la nuit, Sainte-Marie en août ce sont les baleines. Un voyageur
 * l'apprend aujourd'hui sur place, donc trop tard.
 *
 * L'ordre de lecture suit cette idée : **le ruban de l'année d'abord** — où
 * est la bonne période — puis les dates. Mettre le calendrier en premier
 * aurait fait de la saison une note de bas de page.
 *
 * Pas de cadre autour : la section respire sur le blanc, comme le reste du
 * site. Un panneau bordé de plus aurait mis le calendrier dans une boîte
 * alors qu'il est censé être l'argument.
 */
import { computed, ref } from 'vue'
import StayCalendar from '@/Components/StayCalendar.vue'
import SeasonRibbon from '@/Components/SeasonRibbon.vue'
import { formatLong } from '@/Composables/useStayDates.js'

const props = defineProps({
    dates: { type: Object, required: true },
    calendar: { type: Object, required: true },
    place: { type: String, default: '' },
})

const saison = computed(() => props.calendar.season ?? null)
const visibles = ref([])

const titre = computed(() =>
    props.dates.nuits.value
        ? `${props.dates.nuits.value} nuit${props.dates.nuits.value > 1 ? 's' : ''} à ${props.place}`
        : `Quand venir à ${props.place}`
)

const soustitre = computed(() => {
    const { arrivee, depart, nuitsSurvol, survol } = props.dates

    if (arrivee.value && depart.value) {
        return `${formatLong(arrivee.value)} — ${formatLong(depart.value)}`
    }
    if (arrivee.value && nuitsSurvol.value) {
        return `${formatLong(arrivee.value)} — ${formatLong(survol.value)}`
    }
    if (arrivee.value) {
        return `Arrivée le ${formatLong(arrivee.value)}.`
    }
    return 'Les nuits déjà prises sont barrées. La saison est indiquée sous chaque mois.'
})

/**
 * La consigne, en toutes lettres et à chaque étape.
 *
 * Le sous-titre **décrivait** le calendrier (« les nuits prises sont
 * barrées ») sans jamais dire quoi faire. Nos deux publics n'ont pas le même
 * bagage numérique : ce qui est évident pour un voyageur habitué aux
 * plateformes ne l'est pas pour quelqu'un qui réserve un logement en ligne
 * pour la première fois. Un calendrier qui ne dit pas « cliquez sur une
 * date » se regarde au lieu de se remplir.
 *
 * Trois états, un par étape, avec le numéro de l'étape : on sait où on en
 * est et ce qu'il reste à faire.
 */
const consigne = computed(() => {
    const { arrivee, depart, nuits } = props.dates

    if (arrivee.value && depart.value) {
        return { etape: '✓', texte: `${nuits.value} nuit${nuits.value > 1 ? 's' : ''} choisies. Vous pouvez modifier en cliquant une nouvelle date d'arrivée.`, fait: true }
    }
    if (arrivee.value) {
        return { etape: '2', texte: 'Cliquez maintenant sur votre date de départ.', fait: false }
    }
    return { etape: '1', texte: "Cliquez sur votre date d'arrivée.", fait: false }
})
</script>

<template>
    <section class="av" data-anim>
        <header class="av__head">
            <h2 class="av__title">{{ titre }}</h2>
            <p class="av__sub">{{ soustitre }}</p>

            <p class="av__hint" :class="{ 'is-done': consigne.fait }" aria-live="polite">
                <span class="av__step" aria-hidden="true">{{ consigne.etape }}</span>
                {{ consigne.texte }}
            </p>
        </header>

        <SeasonRibbon
            v-if="saison"
            class="av__ribbon"
            :year="saison.year"
            :best="saison.best"
            :zone="saison.zone"
            :caveat="saison.caveat"
            :visible="visibles"
        />

        <div class="av__cal">
            <StayCalendar
                :dates="dates"
                :calendar="calendar"
                :months="2"
                @visible="visibles = $event"
            />
        </div>
    </section>
</template>

<style scoped>
.av {
    padding-top: clamp(2.5rem, 5vw, 3.5rem);
    border-top: 1px solid var(--line);
}

.av__head { max-width: 44rem; }

.av__title {
    margin: 0;
    font-size: clamp(1.35rem, 2.4vw, 1.6rem);
    font-weight: 800;
    letter-spacing: -.035em;
    color: var(--ink);
}

.av__sub {
    margin: .45rem 0 0;
    font-size: .93rem;
    line-height: 1.55;
    color: var(--text-2);
}

/* La consigne se lit avant le calendrier, pas après : encadrée, en terre,
   avec le numéro de l'étape. Un texte gris de plus sous le titre se serait
   fondu dans la page — c'est exactement ce qui se passait. */
.av__hint {
    display: inline-flex;
    align-items: center;
    gap: .6rem;
    margin: .9rem 0 0;
    padding: .5rem .95rem .5rem .5rem;
    border: 1px solid var(--terre-200);
    border-radius: var(--r-pill);
    background: var(--terre-050);
    font-size: .88rem;
    font-weight: 600;
    line-height: 1.4;
    color: var(--terre-700);
}

.av__step {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 1.6rem;
    height: 1.6rem;
    border-radius: 50%;
    background: var(--terre-500);
    font-size: .82rem;
    font-weight: 800;
    color: var(--white);
}

/* Une fois les deux dates posées, la consigne devient une confirmation : le
   lagon ne dit que « vérifié », donc on reste en neutre. */
.av__hint.is-done {
    border-color: var(--line-2);
    background: var(--off-2);
    color: var(--text-2);
}
.av__hint.is-done .av__step { background: var(--ink); }

.av__ribbon { margin-top: clamp(1.75rem, 3.5vw, 2.5rem); }

/* Une simple respiration entre le ruban et la grille : le filet aurait
   séparé deux choses qui disent la même. */
.av__cal { margin-top: clamp(2rem, 4vw, 3rem); }
</style>
