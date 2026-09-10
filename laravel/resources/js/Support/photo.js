/**
 * L'adresse d'une photographie, en trois paliers.
 *
 * Chaque fichier existe en 800, 1600 et 3200 px — mais **seulement jusqu'à la
 * résolution que porte vraiment l'original de Commons**. `photo.width` dit
 * laquelle : un `srcset` qui promettrait un 3200 inexistant ferait télécharger
 * un 404 au navigateur, et sur une connexion malgache un aller-retour perdu se
 * paie cher.
 *
 * Le palier n'est jamais choisi ici : c'est `sizes` qui le décide côté
 * navigateur, en connaissant la largeur d'affichage réelle et la densité de
 * l'écran. `photoSrc` ne sert qu'au repli des navigateurs sans `srcset`.
 */
const PALIERS = [800, 1600, 3200]

/**
 * Deux dossiers, et ils ne se mélangent pas : `lieux` porte les
 * photographies de Wikimedia Commons, `annonces` celles qu'un propriétaire a
 * téléversées. `lieux` par défaut — c'est ce que renvoie une photo servie par
 * une version du serveur qui ne connaît pas encore la colonne.
 */
export const photoUrl = (photo, largeur) =>
    `/images/${photo?.folder ?? 'lieux'}/${photo?.key}-${largeur}.webp`

/** La plus grande taille réellement produite pour cette photo. */
const plafond = (photo) => photo?.width ?? PALIERS[0]

export function photoSrcset(photo) {
    if (!photo?.key) return undefined

    return PALIERS
        .filter((w) => w <= plafond(photo))
        .map((w) => `${photoUrl(photo, w)} ${w}w`)
        .join(', ')
}

export function photoSrc(photo, souhaitee = 1600) {
    if (!photo?.key) return undefined

    const cible = Math.min(souhaitee, plafond(photo))

    return photoUrl(photo, PALIERS.filter((w) => w <= cible).pop() ?? PALIERS[0])
}
