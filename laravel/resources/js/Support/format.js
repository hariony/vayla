/**
 * Les nombres, en français.
 *
 * `Intl.NumberFormat('fr-FR')` sépare les milliers par une **espace fine
 * insécable** (U+202F). Plus Jakarta Sans ne dessine pas ce caractère : le
 * navigateur lui donne une chasse nulle et « 185 000 Ar » s'affiche
 * « 185000 Ar ». Sur une fiche dont le prix est l'information la plus lue,
 * ça se voyait partout — cartes, filtres, encart, facture.
 *
 * On repasse donc en espace insécable ordinaire (U+00A0), que la fonte porte.
 * Insécable et non ordinaire : un montant ne doit jamais se couper en fin de
 * ligne.
 */
import { formatCompact } from '@/Composables/useStayDates.js'

export const nombre = (n) => new Intl.NumberFormat('fr-FR').format(n).replaceAll('\u202f', '\u00a0')

/** Un poids de fichier : « 850 Ko », « 12,4 Mo » — ce qui part, ce que pèse le disque. */
export const poids = (octets) => {
    if (octets == null) return ''
    if (octets < 1024 * 1024) return `${nombre(Math.max(1, Math.round(octets / 1024)))} Ko`

    return `${nombre(Math.round(octets / 1024 / 1024 * 10) / 10)} Mo`
}

/** Un montant en ariary, unité comprise. */
export const ariary = (n) => `${nombre(n)} Ar`

/**
 * « il y a 2 h », « hier », « le 14 sept. ».
 *
 * **Une boîte se lit à l'ancienneté, pas à la date.** La question qu'on se
 * pose devant une liste de conversations est « est-ce que ça vient
 * d'arriver ? », et une date absolue oblige à savoir quel jour on est pour y
 * répondre. Au-delà d'une semaine la question s'inverse — on cherche alors
 * *quand* — et la date reprend la main.
 *
 * Les minutes s'arrêtent à « à l'instant » sous la minute : une conversation
 * qui affiche « il y a 0 min » a l'air cassée.
 */
export const ilYA = (iso) => {
    if (! iso) {
        return ''
    }

    const secondes = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000)

    if (secondes < 60) return 'à l\u2019instant'
    if (secondes < 3600) return `il y a ${Math.floor(secondes / 60)} min`
    if (secondes < 86400) return `il y a ${Math.floor(secondes / 3600)} h`
    if (secondes < 172800) return 'hier'
    if (secondes < 604800) return `il y a ${Math.floor(secondes / 86400)} jours`

    // `formatCompact` attend un jour, pas un horodatage : la partie heure
    // ferait échouer la conversion UTC.
    return formatCompact(String(iso).slice(0, 10))
}
