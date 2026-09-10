import { computed, ref } from 'vue'

/**
 * Les dates d'un séjour, et rien d'autre.
 *
 * L'état vit ici plutôt que dans le calendrier, parce que **trois surfaces
 * le partagent** : la grille des mois, les deux champs de l'encart de prix,
 * et le total de nuits. Le laisser dans le composant de calendrier aurait
 * obligé l'encart à le remonter par événements à chaque frappe.
 *
 * Tout est manipulé en chaînes `AAAA-MM-JJ` et en dates UTC. Un `new Date()`
 * local décale d'un jour dès qu'on est à l'est de Greenwich — et Madagascar
 * est à UTC+3 : le calendrier aurait sélectionné la veille.
 *
 * Deux règles de comptage qui décident du prix affiché :
 *   — une nuit appartient à sa date d'arrivée ; du 12 au 15 fait trois nuits ;
 *   — le jour du départ est libre pour le voyageur suivant.
 */

/** @param {string} iso `AAAA-MM-JJ` */
export const toUTC = (iso) => {
    const [y, m, d] = iso.split('-').map(Number)
    return Date.UTC(y, m - 1, d)
}

export const toISO = (utc) => new Date(utc).toISOString().slice(0, 10)

export const JOUR = 86400000

export const addDays = (iso, n) => toISO(toUTC(iso) + n * JOUR)

/** Nombre de nuits entre deux dates : le départ n'est pas une nuit. */
export const nightsBetween = (from, to) => Math.round((toUTC(to) - toUTC(from)) / JOUR)

const MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin',
    'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre']

/** « 1er » et non « 1 » : le seul ordinal du français, et il se remarque. */
const quantieme = (n) => (n === 1 ? '1er' : String(n))

export const formatLong = (iso) => {
    const d = new Date(toUTC(iso))
    return `${quantieme(d.getUTCDate())} ${MOIS[d.getUTCMonth()]} ${d.getUTCFullYear()}`
}

const ABREGES = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin',
    'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.']

/**
 * « 14 sept. », jamais « 14/09 ».
 *
 * Vayla sert des voyageurs étrangers autant que des Malgaches, et `14/09` se
 * lit « 14 septembre » ici mais « 9 avril » pour un Américain. Sur un champ
 * de dates de séjour, l'ambiguïté ne se remarque qu'après la réservation.
 * Un mois écrit en lettres ne se lit que d'une façon.
 */
export const formatCompact = (iso) => {
    const d = new Date(toUTC(iso))
    return `${quantieme(d.getUTCDate())} ${ABREGES[d.getUTCMonth()]}`
}

export function useStayDates(calendar) {
    const arrivee = ref(null)
    const depart = ref(null)
    const survol = ref(null)

    /**
     * L'ensemble des nuits occupées, à plat. Le serveur envoie des
     * intervalles — trois périodes pèsent moins que trois cents dates — et
     * c'est ici qu'on les déplie, une fois, pour un test en O(1) par case.
     */
    const occupees = computed(() => {
        const set = new Set()

        for (const p of calendar.value?.blocked ?? []) {
            for (let t = toUTC(p.from); t <= toUTC(p.to); t += JOUR) {
                set.add(toISO(t))
            }
        }

        return set
    })

    const estOccupee = (iso) => occupees.value.has(iso)

    const minNights = computed(() => calendar.value?.minNights ?? 1)
    const maxNights = computed(() => calendar.value?.maxNights ?? null)

    /**
     * Une nuit occupée entre l'arrivée et le départ interdit la sélection.
     * On s'arrête à la veille du départ : la nuit de départ n'existe pas.
     */
    const chevauche = (from, to) => {
        for (let t = toUTC(from); t < toUTC(to); t += JOUR) {
            if (estOccupee(toISO(t))) return true
        }
        return false
    }

    const nuits = computed(() =>
        arrivee.value && depart.value ? nightsBetween(arrivee.value, depart.value) : 0
    )

    /** Nuits de l'aperçu au survol, avant le second clic. */
    const nuitsSurvol = computed(() => {
        if (!arrivee.value || depart.value || !survol.value) return 0
        return survol.value > arrivee.value ? nightsBetween(arrivee.value, survol.value) : 0
    })

    const total = computed(() => nuits.value * (calendar.value?.price ?? 0))

    const erreur = computed(() => {
        if (!nuits.value) return null
        if (nuits.value < minNights.value) {
            return `Ce logement se loue à partir de ${minNights.value} nuits.`
        }
        if (maxNights.value && nuits.value > maxNights.value) {
            return `Ce logement se loue au plus ${maxNights.value} nuits d'affilée.`
        }
        return null
    })

    const complet = computed(() => nuits.value > 0 && !erreur.value)

    /**
     * Un seul geste : le premier clic pose l'arrivée, le second le départ.
     * Cliquer avant l'arrivée déjà posée recommence — c'est ce que fait
     * l'utilisateur qui s'est trompé, plutôt que de chercher un bouton.
     */
    function choisir(iso) {
        if (!arrivee.value || depart.value) {
            arrivee.value = iso
            depart.value = null
            return
        }

        if (iso <= arrivee.value) {
            arrivee.value = iso
            return
        }

        if (chevauche(arrivee.value, iso)) {
            arrivee.value = iso
            return
        }

        depart.value = iso
    }

    /**
     * Pose les deux dates d'un coup, ou aucune.
     *
     * Sert au report du séjour saisi ailleurs — moteur de recherche, catalogue,
     * lien partagé. `choisir()` ne convient pas : appelé deux fois, il peut
     * poser l'arrivée puis refuser le départ, et laisser une sélection à
     * moitié faite que l'utilisateur n'a pas demandée. Ici c'est tout ou rien,
     * et les mêmes règles s'appliquent qu'à un clic : dans la fenêtre du
     * calendrier, aucune nuit occupée entre les deux, durée dans les bornes du
     * logement. Un séjour refusé ne laisse **aucune trace** : mieux vaut un
     * calendrier vide qu'une pré-sélection impossible à réserver.
     */
    function preselectionner(a, d) {
        if (!a || !d || d <= a) return false

        const min = calendar.value?.from
        const max = calendar.value?.to
        if ((min && a < min) || (max && d > max)) return false
        if (chevauche(a, d)) return false

        const n = nightsBetween(a, d)
        if (n < minNights.value) return false
        if (maxNights.value && n > maxNights.value) return false

        arrivee.value = a
        depart.value = d

        return true
    }

    function effacer() {
        arrivee.value = null
        depart.value = null
        survol.value = null
    }

    /** État d'une case, pour que le composant ne calcule rien lui-même. */
    function etat(iso, { min, max }) {
        if (iso < min || iso > max) return 'hors'
        if (estOccupee(iso)) return 'occupee'

        if (iso === arrivee.value) return 'arrivee'
        if (iso === depart.value) return 'depart'

        const fin = depart.value ?? (arrivee.value && survol.value > arrivee.value ? survol.value : null)
        if (arrivee.value && fin && iso > arrivee.value && iso < fin) return 'entre'

        return 'libre'
    }

    return {
        arrivee, depart, survol,
        nuits, nuitsSurvol, total, erreur, complet,
        minNights, maxNights,
        estOccupee, choisir, preselectionner, effacer, etat,
    }
}
