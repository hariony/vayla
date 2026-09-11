/**
 * L'adresse du portrait d'un propriétaire.
 *
 * Deux paliers seulement — 160 et 480 px — parce qu'un portrait s'affiche à
 * 2 rem dans une barre latérale et à 4 rem sur son propre écran. Produire un
 * 1600 de plus serait un fichier que personne ne télécharge, sur des
 * connexions où chaque aller-retour se paie.
 *
 * `srcset` laisse le navigateur choisir : il connaît la largeur d'affichage et
 * la densité de l'écran, ce que le composant ne sait pas.
 *
 * Ces fichiers vivent dans `images/proprietaires/`, jamais dans `lieux/` ni
 * `annonces/` : ces deux-là sont surveillés fichier par fichier par
 * `PhotoFilesTest`, qui exige un crédit — un visage n'en a pas.
 */
const PALIERS = [160, 480]

export const portraitUrl = (cle, cote) => `/images/proprietaires/${cle}-${cote}.webp`

export const portraitSrcset = (cle) =>
    (cle ? PALIERS.map((c) => `${portraitUrl(cle, c)} ${c}w`).join(', ') : undefined)

export const portraitSrc = (cle) => (cle ? portraitUrl(cle, PALIERS[1]) : undefined)
