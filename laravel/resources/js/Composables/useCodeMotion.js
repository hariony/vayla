import { onMounted, onUnmounted, watch } from 'vue'
import gsap from 'gsap'

/**
 * La chorégraphie des six cases du code.
 *
 * **C'est le seul écran du parcours où l'on attend quelque chose**, et le seul
 * où le mouvement porte une information plutôt qu'un agrément : chaque chiffre
 * posé se voit, un refus se voit, et la case qui attend la frappe se voit. Sur
 * un téléphone, entre la notification et le formulaire, on perd le fil — le
 * mouvement le rattrape.
 *
 * Trois gestes, trois messages :
 *
 * - **Le chiffre tombe dans sa case.** Une pression courte (`back.out`) au
 *   moment où il apparaît : c'est l'accusé de réception de la frappe, celui
 *   que le clavier virtuel ne donne pas.
 * - **Le refus secoue la rangée.** Un code faux ne se lit pas dans un message
 *   qu'on ne regarde pas ; la rangée qui bouge ramène l'œil sur les cases,
 *   c'est-à-dire là où il faut agir. Le geste est court et ne se répète pas.
 * - **Le code complet se referme.** Les six cases s'affaissent d'un demi-pixel
 *   à la suite : la saisie est finie, on ne cherche plus une septième case.
 *
 * `gsap.context()` garantit le nettoyage au démontage ; `gsap.matchMedia()`
 * coupe tout sous `prefers-reduced-motion`, et **la coupure rend visible** —
 * l'écran doit rester utilisable, pas rester à moitié transparent.
 */
export function useCodeMotion(racine, code, erreur) {
    let ctx
    let anime = false

    onMounted(() => {
        ctx = gsap.context((self) => {
            const cases = self.selector('[data-code-case]')
            const rangee = self.selector('[data-code-row]')[0]
            const mm = gsap.matchMedia()

            mm.add('(prefers-reduced-motion: reduce)', () => {
                gsap.set(cases, { opacity: 1, y: 0, scale: 1 })
            })

            mm.add('(prefers-reduced-motion: no-preference)', () => {
                anime = true

                // L'entrée : les cases arrivent en cascade, de gauche à
                // droite, dans le sens où on va les remplir.
                gsap.from(cases, {
                    y: 12,
                    opacity: 0,
                    scale: .88,
                    duration: .45,
                    ease: 'back.out(1.8)',
                    stagger: .045,
                })
            })

            /**
             * Un chiffre de plus : la case qui vient d'être remplie encaisse.
             *
             * On ne réagit qu'à l'**allongement** — effacer ne doit pas
             * déclencher la même pression, sinon le geste dit « c'est bon »
             * au moment où l'on corrige.
             */
            watch(code, (maintenant, avant) => {
                if (! anime || maintenant.length <= (avant?.length ?? 0)) {
                    return
                }

                const cible = cases[maintenant.length - 1]

                if (cible) {
                    gsap.fromTo(cible,
                        { scale: 1.14 },
                        { scale: 1, duration: .34, ease: 'back.out(3)' }
                    )
                }

                if (maintenant.length === cases.length) {
                    // Le code est complet : les six cases se referment dans
                    // l'ordre, et la saisie se sait terminée sans le lire.
                    gsap.fromTo(cases,
                        { y: -3 },
                        { y: 0, duration: .3, ease: 'power2.out', stagger: .035 }
                    )
                }
            })

            /**
             * Le refus : la rangée entière, jamais une case seule.
             *
             * On ne sait pas **quel** chiffre est faux — le serveur ne le dit
             * pas, et il aurait tort de le dire. Secouer une case désignerait
             * un coupable au hasard.
             */
            watch(erreur, (message) => {
                if (! anime || ! message || ! rangee) {
                    return
                }

                gsap.fromTo(rangee,
                    { x: -9 },
                    { x: 0, duration: .55, ease: 'elastic.out(1, .32)' }
                )
            })
        }, racine.value)
    })

    onUnmounted(() => ctx?.revert())
}
