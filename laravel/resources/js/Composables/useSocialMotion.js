import { onMounted, onUnmounted } from 'vue'
import gsap from 'gsap'

/**
 * La chorégraphie du bloc « entrer en un geste ».
 *
 * **Trois cellules, un seul objet.** Le mouvement doit dire ça : elles
 * arrivent ensemble, décalées de quelques centièmes, et les filets qui les
 * séparent se déploient **depuis le milieu** — la barre se construit, elle ne
 * s'affiche pas. Trois boutons qui apparaîtraient séparément se liraient comme
 * trois décisions ; il n'y en a qu'une, à trois issues.
 *
 * **Le survol soulève la marque, pas la cellule.** C'est le logo qu'on
 * reconnaît de loin, et le grossir de 12 % le fait ressortir sans déplacer le
 * texte sous le curseur — un libellé qui bouge au moment où on vise est un
 * libellé qu'on rate.
 *
 * **Le trait de terre se déploie sous la cellule visée**, depuis son centre.
 * C'est la seule couleur d'action de l'écran, et elle ne dit qu'une chose :
 * « c'est celle-ci qui partira ». Un fond coloré aurait concurrencé le bouton
 * principal en dessous.
 *
 * `gsap.context()` garantit le nettoyage ; `gsap.matchMedia()` coupe sous
 * `prefers-reduced-motion`, et **la coupure rend visible** — l'état de repos
 * est l'état utilisable.
 */
export function useSocialMotion(racine) {
    let ctx

    onMounted(() => {
        ctx = gsap.context((self) => {
            const cellules = self.selector('[data-soc-cell]')
            const filets = self.selector('[data-soc-filet]')
            const mm = gsap.matchMedia()

            if (! cellules.length) {
                return
            }

            mm.add('(prefers-reduced-motion: reduce)', () => {
                gsap.set([...cellules, ...filets], { opacity: 1, y: 0, scaleY: 1 })
            })

            mm.add('(prefers-reduced-motion: no-preference)', () => {
                const tl = gsap.timeline({ defaults: { ease: 'power3.out' } })

                tl.from(cellules, { y: 10, opacity: 0, duration: .45, stagger: .06 })
                    // Les filets après les cellules : ils lient ce qui vient
                    // d'arriver, ils ne le précèdent pas.
                    .from(filets, { scaleY: 0, duration: .4, ease: 'power2.out', stagger: .05 }, .2)

                cellules.forEach((cellule) => reveil(cellule))
            })
        }, racine.value)
    })

    onUnmounted(() => ctx?.revert())
}

/**
 * Le geste d'une cellule — au survol **et** au focus.
 *
 * La règle de la maison ne souffre pas d'exception : la moitié de nos
 * utilisateurs sont au doigt, et un clavier doit voir ce qu'une souris voit.
 */
function reveil(cellule) {
    const marque = cellule.querySelector('[data-soc-logo]')
    const trait = cellule.querySelector('[data-soc-trait]')

    const entre = () => {
        if (marque) gsap.to(marque, { scale: 1.12, duration: .35, ease: 'back.out(2.4)' })
        if (trait) gsap.to(trait, { scaleX: 1, duration: .35, ease: 'power3.out' })
    }

    // Le retour est plus court que l'aller : une animation qui met autant de
    // temps à revenir donne l'impression que le curseur traîne derrière la
    // souris.
    const sort = () => {
        if (marque) gsap.to(marque, { scale: 1, duration: .28, ease: 'power2.out' })
        if (trait) gsap.to(trait, { scaleX: 0, duration: .28, ease: 'power2.out' })
    }

    cellule.addEventListener('mouseenter', entre)
    cellule.addEventListener('mouseleave', sort)
    cellule.addEventListener('focus', entre)
    cellule.addEventListener('blur', sort)
}
