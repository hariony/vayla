import { onMounted, onUnmounted } from 'vue'
import gsap from 'gsap'

/**
 * L'entrée des espaces — la barre latérale, puis le contenu.
 *
 * **Discrète parce que c'est un outil, pas une vitrine.** Le propriétaire
 * n'ouvre pas Vayla par curiosité : il l'ouvre parce qu'une demande expire
 * dans quarante et une heures. Un mouvement qui se remarque met du temps entre
 * lui et sa réponse ; rien ne dépasse donc 10 px de course ni 0,45 s, et
 * **rien ne continue après l'entrée**.
 *
 * Ce que le mouvement dit, et c'est sa seule justification : la barre est
 * **une** liste, pas huit éléments indépendants — d'où le décalage court qui
 * la fait se lire de haut en bas, dans l'ordre d'urgence des rubriques. Le
 * contenu arrive après elle, parce qu'on décide *où* on est avant de lire.
 *
 * Mêmes règles que partout : `gsap.context()` pour que le nettoyage au
 * démontage soit garanti, `gsap.matchMedia()` pour couper sous
 * `prefers-reduced-motion`, et **la coupure rend visible** — un écran de
 * travail à moitié transparent serait pire que pas d'animation du tout.
 */
export function useSpaceMotion(racine) {
    let ctx

    onMounted(() => {
        ctx = gsap.context((self) => {
            const rubriques = self.selector('[data-espace-item]')
            const corps = self.selector('[data-espace-corps]')
            const mm = gsap.matchMedia()

            mm.add('(prefers-reduced-motion: reduce)', () => {
                gsap.set([...rubriques, ...corps], { opacity: 1, x: 0, y: 0 })
            })

            mm.add('(prefers-reduced-motion: no-preference)', () => {
                gsap.timeline({ defaults: { ease: 'power3.out' } })
                    .from(rubriques, { x: -8, opacity: 0, duration: .38, stagger: .028 })
                    .from(corps, { y: 10, opacity: 0, duration: .45 }, .1)
            })
        }, racine.value)
    })

    onUnmounted(() => ctx?.revert())
}
