import { onMounted, onUnmounted, ref } from 'vue'
import gsap from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'

gsap.registerPlugin(ScrollTrigger)

/**
 * Le repérage du rail de la fiche : où l'on est, et combien il reste.
 *
 * **ScrollTrigger, pas un IntersectionObserver maison.** C'est la règle de la
 * maison, et elle a une raison : l'observateur seul rate le défilement rapide,
 * l'arrivée par une ancre et l'onglet en arrière-plan — exactement les trois
 * moments où un repère de position doit être juste, sinon il désigne la
 * mauvaise section et on cesse de lui faire confiance.
 *
 * **Deux mesures, pas une.** La rubrique **active** dit où l'on est ; la
 * **progression** dit combien de fiche il reste — c'est elle qui remplit le
 * fil. Une fiche de logement fait trois mille pixels, et savoir qu'on est aux
 * deux tiers change la façon dont on lit la suite.
 *
 * **Aller à une section n'est pas un saut d'ancre.** L'en-tête du site est
 * fixe : `href="#photos"` poserait le titre *sous* la barre. On défile donc à
 * la main, décalé de la hauteur d'en-tête — et **d'un coup sous
 * `prefers-reduced-motion`**, où un défilement animé de trois mille pixels est
 * précisément ce qu'on a demandé d'éviter.
 */
export function useSectionRail(sections) {
    const actif = ref(sections[0]?.id ?? '')
    const progression = ref(0)

    let ctx

    /** La hauteur de l'en-tête fixe, lue dans le jeton — jamais devinée. */
    const decalage = () => {
        const jeton = getComputedStyle(document.documentElement).getPropertyValue('--header-h')

        return (parseFloat(jeton) || 78) + 16
    }

    const aller = (id) => {
        const cible = document.getElementById(id)

        if (! cible) {
            return
        }

        const y = cible.getBoundingClientRect().top + window.scrollY - decalage()
        const doux = ! window.matchMedia('(prefers-reduced-motion: reduce)').matches

        window.scrollTo({ top: y, behavior: doux ? 'smooth' : 'auto' })

        // Le repère suit tout de suite : attendre que le défilement arrive
        // laisserait une seconde pendant laquelle le rail désigne encore la
        // section qu'on vient de quitter.
        actif.value = id
    }

    onMounted(() => {
        ctx = gsap.context(() => {
            sections.forEach(({ id }) => {
                const element = document.getElementById(id)

                if (! element) {
                    return
                }

                ScrollTrigger.create({
                    trigger: element,
                    // Une section est « active » dès que son haut passe sous
                    // l'en-tête, et jusqu'à ce que son bas y arrive : c'est ce
                    // qu'on a sous les yeux, pas ce qui traverse le milieu de
                    // l'écran.
                    start: `top ${decalage() + 24}px`,
                    end: `bottom ${decalage() + 24}px`,
                    onToggle: (self) => self.isActive && (actif.value = id),
                })
            })

            const page = document.querySelector('[data-rail-portee]')

            if (page) {
                ScrollTrigger.create({
                    trigger: page,
                    start: `top ${decalage()}px`,
                    end: 'bottom bottom',
                    onUpdate: (self) => (progression.value = self.progress),
                })
            }
        })
    })

    onUnmounted(() => ctx?.revert())

    return { actif, progression, aller }
}
