import { onMounted, onUnmounted } from 'vue'
import gsap from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'

gsap.registerPlugin(ScrollTrigger)

/**
 * Chorégraphie de la fiche d'annonce.
 *
 * Discrète, et c'est une contrainte de fond : sur une page qui promet de la
 * vérification, un mouvement démonstratif décrédibilise. Rien ne dépasse
 * 18 px de course, rien ne dure plus de 0,8 s, et la parallaxe de la grande
 * photo reste sous le seuil de perception consciente — on la remarque quand
 * elle est absente, pas quand elle est là.
 *
 * Tout passe par gsap.context() : nettoyage garanti au démontage.
 * `gsap.matchMedia()` coupe tout sous `prefers-reduced-motion` — et coupe
 * *en rendant visible*, jamais en laissant la page à moitié transparente.
 *
 * Conventions dans le template :
 *   data-gallery       → la galerie ; la tête puis la pellicule en cascade
 *   data-gal-lead      → la photographie de tête
 *   data-gal-img       → l'image de tête, cible de la parallaxe
 *   data-fiche-head    → titre et lieu, entrée sous masque
 *   data-anim          → révélation à l'entrée dans l'écran
 *   data-anim-group    → révélation en cascade des enfants directs
 *   data-count         → nombre compté depuis zéro
 *   data-rung          → barreau de l'échelle de confiance
 *   data-sticky        → l'encart latéral, ombre à l'accroche
 */
export function useFicheMotion(root) {
    let ctx

    onMounted(() => {
        ctx = gsap.context(() => {
            const mm = gsap.matchMedia()

            const CIBLES = '[data-fiche-head], [data-anim], [data-anim-group] > *, [data-gal-lead], [data-gal-tile], .gal__seal, .gal__all, [data-rung]'

            mm.add('(prefers-reduced-motion: reduce)', () => {
                gsap.set(CIBLES, { opacity: 1, clearProps: 'transform,clipPath' })
            })

            mm.add('(prefers-reduced-motion: no-preference)', () => {
                entree()
                revelations()
                parallaxeGalerie()
                compteurs()
                echelle()
                ombreEncart()
            })
        }, root.value)
    })

    onUnmounted(() => ctx?.revert())
}

/**
 * L'ouverture : le titre, puis la photographie de tête, puis la pellicule.
 * La tête part la première — c'est elle qu'on regarde, le reste la suit.
 */
function entree() {
    const tl = gsap.timeline({ defaults: { ease: 'power3.out' } })

    tl.from('[data-fiche-head]', {
        y: 16,
        opacity: 0,
        duration: .7,
        stagger: .07,
    })

    tl.from('[data-gal-lead]', {
        y: 18,
        opacity: 0,
        duration: .8,
    }, '-=.45')

    tl.from('[data-gal-tile]', {
        y: 14,
        opacity: 0,
        duration: .6,
        stagger: .05,
    }, '-=.5')

    // Le sceau arrive après la photo : il commente ce qu'on vient de voir.
    tl.from('.gal__seal, .gal__all', { opacity: 0, y: 8, duration: .5, stagger: .06 }, '-=.35')
}

/** Chaque bloc monte de 14 px en entrant dans l'écran. Une fois, pas à chaque passage. */
function revelations() {
    gsap.utils.toArray('[data-anim]').forEach((el) => {
        gsap.from(el, {
            y: 14,
            opacity: 0,
            duration: .65,
            ease: 'power2.out',
            scrollTrigger: { trigger: el, start: 'top 88%', once: true },
        })
    })

    gsap.utils.toArray('[data-anim-group]').forEach((group) => {
        gsap.from(group.children, {
            y: 12,
            opacity: 0,
            duration: .55,
            ease: 'power2.out',
            stagger: .04,
            scrollTrigger: { trigger: group, start: 'top 88%', once: true },
        })
    })
}

/**
 * La photo dérive de 5 % dans son cadre pendant le défilement. Le cadre ne
 * bouge pas : c'est l'image qui glisse dessous, donc aucune secousse de mise
 * en page. `scale` compense la course, sinon un bord se découvrirait.
 */
function parallaxeGalerie() {
    const grande = document.querySelector('[data-gal-lead] [data-gal-img]')
    if (!grande) return

    gsap.fromTo(grande,
        { yPercent: -2.5, scale: 1.06 },
        {
            yPercent: 2.5,
            ease: 'none',
            scrollTrigger: {
                trigger: grande.closest('[data-gallery]'),
                start: 'top top',
                end: 'bottom top',
                scrub: .6,
            },
        }
    )
}

/** Les nombres se comptent depuis zéro : le chiffre devient un fait, pas un décor. */
function compteurs() {
    gsap.utils.toArray('[data-count]').forEach((el) => {
        const cible = Number(el.dataset.count)
        if (!Number.isFinite(cible)) return

        const obj = { n: 0 }
        gsap.to(obj, {
            n: cible,
            duration: 1,
            ease: 'power2.out',
            onUpdate: () => { el.textContent = Math.round(obj.n) },
            // La valeur exacte est posée à l'arrivée : un arrondi de fin de
            // course afficherait 61 au lieu de 62 sur une page qui promet
            // des faits.
            onComplete: () => { el.textContent = cible },
            scrollTrigger: { trigger: el, start: 'top 92%', once: true },
        })
    })
}

/**
 * Les barreaux de l'échelle s'allument du bas vers le haut. C'est la seule
 * animation de la page qui a un sens narratif : elle rejoue la progression
 * de la vérification.
 */
function echelle() {
    const barreaux = gsap.utils.toArray('[data-rung]')
    if (!barreaux.length) return

    gsap.from(barreaux, {
        opacity: 0,
        x: -10,
        duration: .5,
        ease: 'power2.out',
        stagger: .09,
        scrollTrigger: { trigger: barreaux[0].parentElement, start: 'top 85%', once: true },
    })
}

/**
 * L'encart de prix prend une ombre quand il devient collant : sans elle, il
 * paraît coincé dans le texte au lieu de flotter au-dessus.
 */
function ombreEncart() {
    const encart = document.querySelector('[data-sticky]')
    if (!encart) return

    ScrollTrigger.create({
        trigger: encart,
        start: 'top 120px',
        end: 99999,
        onToggle: ({ isActive }) => {
            gsap.to(encart, {
                boxShadow: isActive
                    ? '0 18px 40px -24px rgba(23, 20, 28, .35)'
                    : '0 1px 2px rgba(23, 20, 28, .04)',
                duration: .4,
                ease: 'power2.out',
            })
        },
    })
}
