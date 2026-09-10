import { onMounted, onUnmounted } from 'vue'
import gsap from 'gsap'

/**
 * La chorégraphie de l'aiguillage de connexion.
 *
 * **Une seule entrée orchestrée, et rien qui tourne après.** Cette page
 * n'existe que pour poser une question ; un mouvement qui continuerait ferait
 * hésiter au moment de choisir. Tout se joue en moins d'une seconde, puis la
 * page est immobile — sauf ce que l'utilisateur provoque lui-même.
 *
 * **Le V se trace, il n'apparaît pas.** C'est le même geste que le littoral de
 * Madagascar sur l'accueil, et c'est ce qui fait de cet écran une page de
 * Vayla plutôt qu'un formulaire de connexion interchangeable. Le monogramme
 * est un V à deux branches, c'est-à-dire un carrefour : la page dit avec la
 * marque ce que le titre dit avec des mots.
 *
 * **Les pictogrammes réagissent au survol *et* au focus.** La règle de la
 * maison est sans exception : rien d'interactif au seul survol — la moitié de
 * nos utilisateurs sont au doigt, et un clavier doit voir la même chose qu'une
 * souris. Ici le mouvement est décoratif, la carte reste un lien ordinaire,
 * mais la règle vaut aussi pour l'agrément.
 *
 * `gsap.context()` garantit le nettoyage au démontage ; `gsap.matchMedia()`
 * coupe sous `prefers-reduced-motion`, et **la coupure rend visible** plutôt
 * que de laisser la page à moitié transparente.
 */
export function useAccessMotion(racine) {
    let ctx

    onMounted(() => {
        ctx = gsap.context((self) => {
            const mm = gsap.matchMedia()

            const lignes = self.selector('[data-access-line]')
            const cartes = self.selector('[data-access-card]')
            const icones = self.selector('[data-access-ico]')
            const [marque] = self.selector('[data-access-mark]')

            mm.add('(prefers-reduced-motion: reduce)', () => {
                gsap.set([...lignes, ...cartes, ...icones], { opacity: 1, y: 0, scale: 1 })
                if (marque) gsap.set(marque, { opacity: .07, strokeDashoffset: 0 })
            })

            mm.add('(prefers-reduced-motion: no-preference)', () => {
                const tl = gsap.timeline({ defaults: { ease: 'power3.out' } })

                // Le titre d'abord : c'est la question, et elle doit être lue
                // avant que les réponses n'arrivent.
                tl.from(lignes, { y: 14, opacity: 0, duration: .55, stagger: .06 })

                if (marque) {
                    const len = marque.getTotalLength?.() ?? 0

                    if (len) {
                        gsap.set(marque, { strokeDasharray: len, strokeDashoffset: len, opacity: .07 })
                        // Long et lent, en fond : le tracé accompagne la page,
                        // il ne la retient pas. Il démarre avec le titre — plus
                        // tard, on le regarderait au lieu de choisir.
                        tl.to(marque, { strokeDashoffset: 0, duration: 2.4, ease: 'power2.inOut' }, 0)
                    }
                }

                // Les deux cartes montent décalées : simultanées, elles se
                // liraient comme un seul bloc et le choix disparaîtrait.
                tl.from(cartes, { y: 20, opacity: 0, duration: .6, stagger: .1 }, .25)
                    .from(icones, { scale: .84, opacity: 0, duration: .55, ease: 'back.out(1.6)', stagger: .1 }, .38)

                cartes.forEach((carte) => reveil(carte))
            })
        }, racine.value)
    })

    onUnmounted(() => ctx?.revert())
}

/**
 * Le geste propre à chaque pictogramme.
 *
 * **La clé tourne, la loupe balaie** : dans les deux cas le mouvement *dit* ce
 * que la carte fait — ouvrir chez soi, chercher un logement. Une icône qui se
 * contenterait de grossir au survol n'ajouterait qu'un effet.
 *
 * Le retour est plus court que l'aller (`.45` contre `.6`) : une animation qui
 * met autant de temps à revenir qu'à partir donne l'impression que le curseur
 * traîne derrière la souris.
 */
function reveil(carte) {
    const cle = carte.querySelector('[data-ico-turn]')
    const loupe = carte.querySelector('[data-ico-move]')

    /*
     * `svgOrigin` et non `transformOrigin` : sur un `<g>` SVG, le pivot CSS
     * dépend de `transform-box`, dont la valeur par défaut a changé selon les
     * navigateurs. La clé tournait alors autour de l'origine du dessin et
     * sortait de la maison. `svgOrigin` prend des coordonnées du `viewBox` et
     * ne dépend d'aucun réglage — ici le centre de l'anneau, parce qu'une clé
     * qui pivoterait autour de son panneton ne tournerait pas dans une serrure.
     */
    const entre = () => {
        if (cle) gsap.to(cle, { rotation: 24, svgOrigin: '20 21.4', duration: .6, ease: 'back.out(2.2)' })
        if (loupe) gsap.to(loupe, { x: 2.6, y: -2.6, duration: .5, ease: 'power2.out' })
    }

    const sort = () => {
        if (cle) gsap.to(cle, { rotation: 0, svgOrigin: '20 21.4', duration: .45, ease: 'power2.out' })
        if (loupe) gsap.to(loupe, { x: 0, y: 0, duration: .45, ease: 'power2.out' })
    }

    // Survol **et** focus : la règle de la maison ne souffre pas d'exception,
    // même pour un mouvement décoratif.
    carte.addEventListener('mouseenter', entre)
    carte.addEventListener('mouseleave', sort)
    carte.addEventListener('focus', entre)
    carte.addEventListener('blur', sort)
}
