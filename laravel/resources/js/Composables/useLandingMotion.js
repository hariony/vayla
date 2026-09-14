import { onMounted, onUnmounted } from 'vue'
import gsap from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'

import { ariary } from '@/Support/format.js'

gsap.registerPlugin(ScrollTrigger)

/**
 * La chorégraphie de `/louer-mon-logement`, la page où mène la publicité.
 *
 * **Chaque mouvement porte une information, aucun n'est un ornement** — la
 * même règle que la fiche et la page de code :
 *
 * - **la jauge de la fiche d'exemple monte** de « déclarée » à « visité » :
 *   c'est, en trois secondes, tout ce que l'inscription promet. Elle s'arrête
 *   au niveau 3, jamais au 4 — le quatrième ne s'obtient qu'avec un séjour ;
 * - **le prix de l'inscription descend jusqu'à 0 Ar** : on lit un montant de
 *   nuitée ordinaire fondre à rien, et « gratuit » se voit avant de se lire ;
 * - **le fil des étapes se remplit** à mesure qu'on descend : on sait où l'on
 *   en est dans ce qui va se passer.
 *
 * **Le bouton collé en bas, sur téléphone**, apparaît quand celui du haut sort
 * de l'écran et disparaît quand celui du bas y entre. Il ne dépend pas du
 * mouvement : sous `prefers-reduced-motion`, il marche pareil.
 *
 * `gsap.context()` pour le nettoyage, `gsap.matchMedia()` pour couper sous
 * `prefers-reduced-motion`, et **la coupure rend visible** : jauge au niveau 3,
 * prix à 0 Ar, fil plein.
 */
export function useLandingMotion(racine, { niveau, barre }) {
    let ctx

    onMounted(() => {
        ctx = gsap.context((self) => {
            const q = self.selector
            const [prix] = q('[data-prix]')
            const [fil] = q('[data-fil]')

            // ── Le bouton collé : indépendant du mouvement ──────────────
            const [ctaHaut] = q('[data-cta-haut]')
            const [ctaBas] = q('[data-cta-bas]')
            let hautSorti = false
            let basVisible = false
            const majBarre = () => { barre.value = hautSorti && ! basVisible }

            if (ctaHaut) {
                ScrollTrigger.create({
                    trigger: ctaHaut,
                    start: 'bottom top',
                    onEnter: () => { hautSorti = true; majBarre() },
                    onLeaveBack: () => { hautSorti = false; majBarre() },
                })
            }
            if (ctaBas) {
                ScrollTrigger.create({
                    trigger: ctaBas,
                    start: 'top bottom',
                    end: 'bottom top',
                    onToggle: (st) => { basVisible = st.isActive; majBarre() },
                })
            }

            const mm = gsap.matchMedia()

            mm.add('(prefers-reduced-motion: reduce)', () => {
                niveau.value = 3
                if (prix) prix.textContent = ariary(0)
                if (fil) gsap.set(fil, { scaleY: 1 })
                q('[data-etape]').forEach((e) => e.classList.add('is-atteinte'))
            })

            mm.add('(prefers-reduced-motion: no-preference)', () => {
                // ── L'entrée ────────────────────────────────────────────
                const tl = gsap.timeline({ defaults: { ease: 'power3.out' } })

                tl.from(q('[data-ld-brow]'), { y: 10, opacity: 0, duration: .45 })
                    .from(q('[data-ld-ligne] > span'), { yPercent: 110, duration: .8, stagger: .09, ease: 'power4.out' }, .05)
                    .from(q('[data-ld-meuble]'), { scaleX: 0, duration: .55, ease: 'power3.inOut' }, .45)
                    .from(q('.ld-meuble__mot'), { color: '#1A1512', duration: .4 }, .6)
                    .from(q('[data-underline]'), { scaleX: 0, duration: .7, ease: 'power2.inOut' }, .8)
                    .from(q('[data-ld-item]'), { y: 16, opacity: 0, duration: .55, stagger: .07 }, .45)
                    .from(q('[data-ld-cadre]'), { y: 40, rotation: -9, opacity: 0, duration: 1, ease: 'power3.out' }, .2)
                    .from(q('[data-ld-fiche]'), { y: 60, rotation: 8, opacity: 0, duration: 1, ease: 'back.out(1.3)' }, .38)
                    .from(q('[data-ld-pastille]'), { scale: 0, opacity: 0, duration: .5, stagger: .12, ease: 'back.out(2.4)' }, .95)

                // Le cadre du lieu dérive un peu plus lentement que la fiche :
                // la profondeur dit que la fiche est posée sur le lieu.
                gsap.to(q('[data-ld-cadre]'), {
                    yPercent: -8,
                    ease: 'none',
                    scrollTrigger: { trigger: q('[data-ld-visuel]')[0], start: 'top top', end: 'bottom top', scrub: true },
                })

                // ── La jauge qui monte ──────────────────────────────────
                // Déclarée → contact confirmé → visité, puis une pause, puis
                // on recommence : la page ne bouge que là, et c'est le message.
                niveau.value = 1
                gsap.timeline({ repeat: -1, repeatDelay: 2.2, delay: 1.4 })
                    .call(() => { niveau.value = 1 })
                    .call(() => { niveau.value = 2 }, null, 1.3)
                    .call(() => { niveau.value = 3 }, null, 2.6)
                    .to({}, { duration: 1.2 })

                // ── 185 000 Ar qui fondent à 0 ──────────────────────────
                if (prix) {
                    const compteur = { v: 185000 }
                    prix.textContent = ariary(compteur.v)
                    gsap.to(compteur, {
                        v: 0,
                        duration: 1.6,
                        ease: 'power3.inOut',
                        onUpdate: () => { prix.textContent = ariary(Math.round(compteur.v / 1000) * 1000) },
                        scrollTrigger: { trigger: prix, start: 'top 80%', once: true },
                    })
                    gsap.from(q('[data-dalle-item]'), {
                        y: 24, opacity: 0, duration: .7, stagger: .1, ease: 'power3.out',
                        scrollTrigger: { trigger: prix, start: 'top 85%', once: true },
                    })
                }

                // ── Le fil des étapes ───────────────────────────────────
                if (fil) {
                    gsap.fromTo(fil, { scaleY: 0 }, {
                        scaleY: 1,
                        ease: 'none',
                        scrollTrigger: { trigger: q('[data-etapes]')[0], start: 'top 70%', end: 'bottom 60%', scrub: .4 },
                    })
                }
                q('[data-etape]').forEach((etape) => {
                    ScrollTrigger.create({
                        trigger: etape,
                        start: 'top 65%',
                        onEnter: () => etape.classList.add('is-atteinte'),
                        onLeaveBack: () => etape.classList.remove('is-atteinte'),
                    })
                    gsap.from(etape, {
                        x: -18, opacity: 0, duration: .6, ease: 'power3.out',
                        scrollTrigger: { trigger: etape, start: 'top 85%', once: true },
                    })
                })

                // ── Les arguments et la fin ─────────────────────────────
                // Posés cachés d'abord : un `from` lancé au moment de l'entrée
                // laisserait voir le bloc une image avant de le faire disparaître.
                gsap.set(q('[data-reveal]'), { y: 26, opacity: 0 })
                ScrollTrigger.batch(q('[data-reveal]'), {
                    start: 'top 88%',
                    once: true,
                    onEnter: (lot) => gsap.to(lot, { y: 0, opacity: 1, duration: .7, stagger: .1, ease: 'power3.out' }),
                })
            })
        }, racine.value)
    })

    onUnmounted(() => ctx?.revert())
}
