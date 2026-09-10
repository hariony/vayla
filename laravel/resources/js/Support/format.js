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
export const nombre = (n) => new Intl.NumberFormat('fr-FR').format(n).replaceAll('\u202f', '\u00a0')

/** Un montant en ariary, unité comprise. */
export const ariary = (n) => `${nombre(n)} Ar`
