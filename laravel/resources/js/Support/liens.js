/**
 * Les adresses qui emportent la recherche en cours.
 *
 * Un bouton « Déposer une demande » ou « Tous les logements » posé sous une
 * recherche doit **garder ce qu'on a déjà choisi** — destination, dates,
 * voyageurs. Le reprendre à zéro obligerait à tout ressaisir, sur la page même
 * où l'on vient de dire qu'on ne trouvait pas.
 */
const CRITERES = ['destination', 'arrival', 'departure', 'guests']

const avec = (base, valeurs) => {
    const p = new URLSearchParams()
    for (const [k, v] of Object.entries(valeurs)) {
        if (v !== undefined && v !== null && v !== '' && v !== 'all') p.set(k, v)
    }
    const q = p.toString()

    return q ? `${base}?${q}` : base
}

const garder = (criteres = {}) => Object.fromEntries(CRITERES.map((k) => [k, criteres?.[k]]))

/** La demande de séjour, pré-remplie. */
export const lienDemande = (criteres = {}) => avec('/demande', garder(criteres))

/** Le catalogue, filtré comme la grille de l'accueil — catégorie comprise. */
export const lienCatalogue = (criteres = {}, categorie = null) => avec('/logements', { ...garder(criteres), category: categorie })

/**
 * Défiler jusqu'à une section **sous l'en-tête fixe** : un saut d'ancre
 * poserait son titre dessous. D'un coup sous `prefers-reduced-motion`.
 */
export function allerA(id) {
    const cible = document.getElementById(id)
    if (!cible) return

    const entete = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--header-h')) || 78
    const y = cible.getBoundingClientRect().top + window.scrollY - entete - 16
    const doux = !window.matchMedia('(prefers-reduced-motion: reduce)').matches

    window.scrollTo({ top: y, behavior: doux ? 'smooth' : 'auto' })
}
