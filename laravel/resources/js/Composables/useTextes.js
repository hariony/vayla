import { usePage } from '@inertiajs/vue3'

/**
 * Les textes du site, tenus depuis le back-office (prop partagée `textes`).
 *
 * `t(cle)` rend le texte brut — pour un attribut, un titre de page.
 * `riche(cle)` rend du HTML **sûr** pour `v-html` : le texte est d'abord
 * **échappé**, puis seules deux marques sont reconnues — `**gras**` et le
 * retour à la ligne. Rien de ce que l'équipe tape ne peut devenir une balise
 * ou un script : c'est la condition pour laisser un compte d'équipe écrire sur
 * la page d'accueil.
 *
 * Une clé inconnue rend une chaîne vide, jamais la clé elle-même : « accueil.
 * hero.titre » affiché à un voyageur serait pire qu'un blanc. Le serveur
 * fournit de toute façon l'original de chaque texte.
 */
const ECHAP = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }

export const echapper = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ECHAP[c])

export const enrichir = (s) => echapper(s)
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/\n/g, '<br>')

export function useTextes() {
    const page = usePage()

    const t = (cle) => page.props.textes?.[cle] ?? ''
    const riche = (cle) => enrichir(t(cle))

    return { t, riche }
}
