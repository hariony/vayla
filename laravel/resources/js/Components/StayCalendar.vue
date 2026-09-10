<script setup>
/**
 * Le calendrier de séjour : deux mois côte à côte, un seul sur mobile.
 *
 * Écrit à la main, sans dépendance : une bibliothèque de dates pèse plus
 * lourd que ces cent lignes, impose sa grille et ses classes, et il faudrait
 * de toute façon la plier au design system.
 *
 * La semaine commence **lundi** — c'est la convention française, et un
 * calendrier qui commence dimanche fait mal lire les week-ends.
 *
 * Trois choses qu'un calendrier de location doit faire et que beaucoup
 * ratent :
 *   — le jour du **départ** d'un occupant reste réservable par le suivant :
 *     il n'est pas grisé, il est seulement en demi-teinte ;
 *   — l'aperçu au survol montre la traînée avant le second clic, sinon on
 *     sélectionne à l'aveugle ;
 *   — les flèches du clavier déplacent la sélection, jour par jour et
 *     semaine par semaine, comme sur n'importe quelle grille de dates.
 */
import { computed, ref, watch } from 'vue'
import AmenityIcon from './AmenityIcon.vue'
import { toUTC, toISO, JOUR } from '@/Composables/useStayDates.js'

const props = defineProps({
    dates: { type: Object, required: true },
    calendar: { type: Object, required: true },
    months: { type: Number, default: 2 },
})

const emit = defineEmits(['visible'])

const MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin',
    'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre']
const JOURS = ['L', 'M', 'M', 'J', 'V', 'S', 'D']

const min = computed(() => props.calendar.from)
const max = computed(() => props.calendar.to)

const curseur = ref(min.value.slice(0, 7))

const decale = (ym, n) => {
    const [y, m] = ym.split('-').map(Number)
    const d = new Date(Date.UTC(y, m - 1 + n, 1))
    return `${d.getUTCFullYear()}-${String(d.getUTCMonth() + 1).padStart(2, '0')}`
}

/** Une grille de mois : les cases vides d'avant le 1er, puis les jours. */
function grille(ym) {
    const [y, m] = ym.split('-').map(Number)
    const premier = new Date(Date.UTC(y, m - 1, 1))
    const jours = new Date(Date.UTC(y, m, 0)).getUTCDate()

    // getUTCDay() : 0 = dimanche. On ramène lundi à 0.
    const vides = (premier.getUTCDay() + 6) % 7

    return {
        cle: ym,
        titre: `${MOIS[m - 1]} ${y}`,
        vides,
        jours: Array.from({ length: jours }, (_, i) => {
            const iso = `${y}-${String(m).padStart(2, '0')}-${String(i + 1).padStart(2, '0')}`
            return { iso, n: i + 1, etat: props.dates.etat(iso, { min: min.value, max: max.value }) }
        }),
    }
}

const saisons = computed(() => props.calendar.season?.months ?? {})

const mois = computed(() =>
    Array.from({ length: props.months }, (_, i) => {
        const ym = decale(curseur.value, i)
        return { ...grille(ym), saison: saisons.value[ym] ?? null }
    })
)

// Le ruban de l'année marque les mois qu'on regarde : les deux vues parlent
// du même temps, autant qu'elles se répondent.
watch(mois, (m) => emit('visible', m.map((x) => x.saison?.month).filter(Boolean)),
    { immediate: true })

/** Une nuit isolée n'a pas de traînée à raccorder : pas de demi-bande. */
const aUneBande = computed(() =>
    Boolean(props.dates.depart.value) || props.dates.nuitsSurvol.value > 0
)

const peutReculer = computed(() => curseur.value > min.value.slice(0, 7))
const peutAvancer = computed(() => decale(curseur.value, props.months - 1) < max.value.slice(0, 7))

const naviguer = (n) => { curseur.value = decale(curseur.value, n) }

/** Flèches du clavier : jour à jour, semaine à semaine. */
function auClavier(e, iso) {
    const pas = { ArrowRight: 1, ArrowLeft: -1, ArrowDown: 7, ArrowUp: -7 }[e.key]
    if (!pas) return

    e.preventDefault()
    const cible = toISO(toUTC(iso) + pas * JOUR)
    if (cible < min.value || cible > max.value) return

    // Changer de mois si la cible en sort, sinon le focus part dans le vide.
    if (cible.slice(0, 7) > decale(curseur.value, props.months - 1)) naviguer(1)
    if (cible.slice(0, 7) < curseur.value) naviguer(-1)

    requestAnimationFrame(() => {
        document.querySelector(`[data-jour="${cible}"]`)?.focus()
    })
}
</script>

<template>
    <div class="cal">
        <div class="cal__bar">
            <button
                type="button"
                class="cal__step cal__step--prev"
                title="Mois précédent"
                :disabled="!peutReculer"
                @click="naviguer(-1)"
            >
                <span class="sr-only">Mois précédent</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M15 4.5 7.5 12 15 19.5" />
                </svg>
            </button>

            <div class="cal__titles">
                <div v-for="m in mois" :key="m.cle" class="cal__titleblock">
                    <p class="cal__title">{{ m.titre }}</p>
                    <p v-if="m.saison" class="cal__season" :class="{ 'is-warning': m.saison.warning }">
                        <AmenityIcon :name="m.saison.icon" />
                        {{ m.saison.label }}
                    </p>
                </div>
            </div>

            <button
                type="button"
                class="cal__step cal__step--next"
                title="Mois suivant"
                :disabled="!peutAvancer"
                @click="naviguer(1)"
            >
                <span class="sr-only">Mois suivant</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m9 4.5 7.5 7.5L9 19.5" />
                </svg>
            </button>
        </div>

        <div class="cal__months">
            <section v-for="m in mois" :key="m.cle" class="cal__month">
                <p class="cal__month-title">{{ m.titre }}</p>
                <p v-if="m.saison" class="cal__season cal__season--inline" :class="{ 'is-warning': m.saison.warning }">
                    <AmenityIcon :name="m.saison.icon" />
                    {{ m.saison.label }}
                </p>

                <div class="cal__week" aria-hidden="true">
                    <span v-for="(j, i) in JOURS" :key="i">{{ j }}</span>
                </div>

                <div class="cal__grid" role="grid">
                    <span v-for="v in m.vides" :key="`v${v}`" class="cal__pad"></span>

                    <!-- La bande de sélection vit sur la cellule, la pastille
                         sur le bouton. Les deux sur le même élément se
                         recouvraient : un fond ne peut pas être à la fois
                         rond au centre et carré sur les bords. -->
                    <div
                        v-for="j in m.jours"
                        :key="j.iso"
                        class="cal__cell"
                        :class="[`cell-${j.etat}`, { 'has-band': aUneBande }]"
                    >
                        <button
                            type="button"
                            class="cal__day"
                            :class="`is-${j.etat}`"
                            :data-jour="j.iso"
                            :disabled="j.etat === 'occupee' || j.etat === 'hors'"
                            :aria-label="j.iso"
                            @click="dates.choisir(j.iso)"
                            @mouseenter="dates.survol.value = j.iso"
                            @focus="dates.survol.value = j.iso"
                            @keydown="auClavier($event, j.iso)"
                        >{{ j.n }}</button>
                    </div>
                </div>

                <!-- La précision de la saison, sous la grille : c'est elle
                     qui transforme « libre » en « bonne idée ». -->
                <p v-if="m.saison" class="cal__note">{{ m.saison.note }}</p>
            </section>
        </div>

        <footer class="cal__foot">
            <p class="cal__legend">
                <span class="cal__dot cal__dot--busy" aria-hidden="true"></span>
                Nuits déjà prises
            </p>
            <button
                v-if="dates.arrivee.value"
                type="button"
                class="cal__clear"
                @click="dates.effacer()"
            >Effacer les dates</button>
        </footer>
    </div>
</template>

<style scoped>
.cal { --cell: 2.75rem; }

/* Les titres sont posés sur la MÊME grille que les mois, et les flèches
   sortent du flux. En flex, la tête se répartissait sur trois éléments et
   les titres tombaient à côté de leurs colonnes — un décalage de trente
   pixels qui se voit immédiatement. */
.cal__bar {
    position: relative;
    display: grid;
    grid-template-columns: 1fr;
    margin-bottom: .4rem;
}

.cal__titles { display: none; }
.cal__titleblock { text-align: center; }

.cal__title {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -.028em;
    color: var(--ink);
    text-transform: capitalize;
}

/* La saison, sous le nom du mois : c'est la ligne qui distingue ce
   calendrier de tous les autres. Neutre par défaut, terre sur
   l'avertissement — jamais le lagon, réservé à « vérifié ». */
.cal__season {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    margin: .25rem 0 0;
    font-size: .74rem;
    font-weight: 700;
    letter-spacing: .01em;
    color: var(--text-3);
}
.cal__season :deep(.ai) { width: .9rem; height: .9rem; }
.cal__season.is-warning { color: var(--terre-600); }

.cal__season--inline { display: none; }

/* **Ces flèches doivent se voir comme des boutons, pas comme des ornements.**
   Elles étaient un chevron gris de 16 px sans fond ni contour : quelqu'un qui
   n'a pas l'habitude des applications ne peut pas deviner que ça se clique.
   Contour franc, fond blanc, chevron à la couleur du texte, et 2,75 rem — la
   cible tactile minimale, celle qu'on atteint au pouce du premier coup. */
.cal__step {
    position: absolute;
    top: -.25rem;
    z-index: 1;
    display: grid;
    place-items: center;
    width: 2.75rem;
    height: 2.75rem;
    border: 1px solid var(--line-2);
    border-radius: 50%;
    background: var(--white);
    color: var(--ink);
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(23, 20, 28, .05);
    transition: background-color .25s var(--ease), border-color .25s var(--ease),
                box-shadow .25s var(--ease);
}
.cal__step--prev { left: 0; }
.cal__step--next { right: 0; }
.cal__step:hover:not(:disabled) {
    background: var(--off);
    border-color: var(--ink);
    box-shadow: 0 3px 10px -4px rgba(23, 20, 28, .3);
}
.cal__step:focus-visible { outline: 3px solid var(--terre-500); outline-offset: 2px; }

/* Désactivé : gris franc et non translucide. À 30 % d'opacité le bouton
   disparaissait au lieu de dire « il n'y a rien avant ce mois-ci ». */
.cal__step:disabled {
    color: var(--text-3);
    background: var(--off-2);
    border-color: var(--line);
    box-shadow: none;
    cursor: not-allowed;
}
.cal__step svg { width: 1.25rem; height: 1.25rem; }

.cal__months { display: grid; grid-template-columns: 1fr; gap: 2rem; }

.cal__month-title {
    margin: 0 0 .7rem;
    text-align: center;
    font-size: .95rem;
    font-weight: 700;
    letter-spacing: -.02em;
    color: var(--ink);
}

.cal__week,
.cal__grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
}

.cal__week {
    margin-bottom: .5rem;
    font-size: .66rem;
    font-weight: 700;
    text-align: center;
    letter-spacing: .09em;
    color: var(--text-3);
}

/* Aucune gouttière : la traînée de sélection doit être continue d'une case
   à l'autre. Les pastilles restent rondes, elles sont simplement inscrites
   dans une case qui touche ses voisines. */
.cal__grid { gap: 0; }

.cal__cell { position: relative; }

/* Les demi-bandes raccordent les pastilles à la traînée : sans elles,
   l'arrivée et le départ flottent à côté de la sélection au lieu de la
   fermer. Elles sont sur la cellule, jamais sur le bouton. */
.cal__cell.cell-entre { background: var(--terre-050); }
.cal__cell.has-band.cell-arrivee {
    background: linear-gradient(to right, transparent 50%, var(--terre-050) 50%);
}
.cal__cell.has-band.cell-depart {
    background: linear-gradient(to left, transparent 50%, var(--terre-050) 50%);
}

/* La bande se referme en bout de ligne. La grille a sept colonnes et les
   cases vides du début de mois en font partie : `nth-child(7n)` désigne donc
   bien la dernière colonne, pas le septième jour. Sans ça, une sélection à
   cheval sur deux semaines se coupait au carré contre le vide. */
.cal__cell:nth-child(7n + 1) { border-radius: 99px 0 0 99px; }
.cal__cell:nth-child(7n) { border-radius: 0 99px 99px 0; }
.cal__cell:last-child { border-radius: 0 99px 99px 0; }

.cal__day {
    position: relative;
    display: grid;
    place-items: center;
    width: 100%;
    aspect-ratio: 1;
    min-height: var(--cell);
    padding: 0;
    border: 0;
    border-radius: 50%;
    background: none;
    font: inherit;
    font-size: .84rem;
    font-weight: 600;
    color: var(--ink);
    cursor: pointer;
    transition: background-color .18s var(--ease), color .18s var(--ease);
}
.cal__day:hover:not(:disabled) { background: var(--off-2); }
.cal__day:focus-visible { outline: 2px solid var(--terre-500); outline-offset: -2px; }

/* Une nuit prise n'est pas cliquable, et le dit par une barre — un simple
   gris pâle se confond avec les jours hors calendrier. */
/* Une nuit prise : le chiffre s'efface et une barre fine le traverse.
   Un fond gris plein aurait fait des pâtés dans la grille. */
.cal__day.is-occupee {
    color: var(--text-3);
    cursor: not-allowed;
    opacity: .45;
}
.cal__day.is-occupee::after {
    content: '';
    position: absolute;
    left: 28%;
    right: 28%;
    top: 50%;
    height: 1px;
    background: currentColor;
}

.cal__day.is-hors { color: var(--line-2); cursor: default; }

/* La sélection est en terre : c'est une action, pas une vérification. Le
   lagon reste réservé à « vérifié ». */
.cal__day.is-arrivee,
.cal__day.is-depart {
    background: var(--terre-500);
    color: var(--white);
}
.cal__day.is-entre { color: var(--terre-700); }


.cal__note {
    margin: .9rem 0 0;
    font-size: .78rem;
    line-height: 1.5;
    color: var(--text-3);
    text-align: center;
}

.cal__foot {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .5rem 1rem;
    margin-top: 1.6rem;
    padding-top: 1rem;
    border-top: 1px solid var(--line);
}

.cal__legend {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    margin: 0;
    font-size: .78rem;
    color: var(--text-3);
}

.cal__dot {
    width: .7rem;
    height: .7rem;
    border-radius: 50%;
    background: var(--off-2);
    border: 1px solid var(--line-2);
    position: relative;
}
.cal__dot--busy::after {
    content: '';
    position: absolute;
    inset: 45% 12% auto;
    height: 1px;
    background: var(--text-3);
}

.cal__clear {
    padding: 0;
    border: 0;
    background: none;
    font: inherit;
    font-size: .8rem;
    font-weight: 700;
    color: var(--ink);
    text-decoration: underline;
    text-underline-offset: 3px;
    cursor: pointer;
}
.cal__clear:hover { color: var(--terre-600); }

@media (min-width: 560px) {
    .cal__months,
    .cal__titles { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: clamp(1.5rem, 4vw, 3rem); }
    .cal__titles { display: grid; }
    .cal__month-title, .cal__season--inline { display: none; }
    .cal__bar { margin-bottom: 1.1rem; }
}

@media (max-width: 559px) {
    .cal__titles { display: none; }
    .cal__season--inline { display: inline-flex; margin: 0 auto .8rem; }
    .cal__month-title { margin-bottom: .2rem; }
    .cal__month { text-align: center; }
    .cal__week, .cal__grid { text-align: initial; }
}
</style>
