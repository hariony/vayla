import { onMounted, onUnmounted } from 'vue'
import gsap from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import { MotionPathPlugin } from 'gsap/MotionPathPlugin'
import { CustomEase } from 'gsap/CustomEase'

gsap.registerPlugin(ScrollTrigger, MotionPathPlugin, CustomEase)

// Une marche qui s'interrompt : il avance, s'arrête pour observer, repart.
CustomEase.create('flanerie', 'M0,0 C0.08,0 0.16,0.42 0.4,0.42 0.62,0.42 0.7,1 1,1')

/**
 * Chorégraphie de la page d'accueil.
 *
 * Tout passe par gsap.context() : le nettoyage au démontage est garanti,
 * et gsap.matchMedia() coupe le mouvement pour `prefers-reduced-motion`
 * sans qu'on ait à dupliquer la logique.
 *
 * Conventions dans le template :
 *   data-hero-line    → ligne de titre, elle monte de son masque
 *   data-hero-item    → élément du hero, entrée simple décalée
 *   data-hero-search  → le moteur, il ferme la séquence
 *   data-underline    → barre de surlignage fuchsia, tracée de gauche à droite
 *   data-count        → nombre compté depuis zéro à l'entrée dans l'écran
 *   data-anim         → révélation à l'entrée dans l'écran
 *   data-anim-group   → révélation en cascade des enfants
 *   data-depth="0.4"  → parallaxe (0 = fixe, 1 = suit le scroll)
 */
export function usePageMotion(root) {
    let ctx

    onMounted(() => {
        ctx = gsap.context(() => {
            const mm = gsap.matchMedia()

            // Mouvement réduit : on se contente de rendre tout visible.
            mm.add('(prefers-reduced-motion: reduce)', () => {
                gsap.set(
                    '[data-hero-item], [data-hero-line], [data-hero-search], [data-anim], [data-anim-group] > *',
                    { opacity: 1, clearProps: 'transform,filter' }
                )
                gsap.set('[data-underline]', { scaleX: 1 })
                gsap.set('.hdr__mark', { clipPath: 'none' })
            })

            mm.add('(prefers-reduced-motion: no-preference)', () => {
                heroEntrance()
                const stopCanopy = canopyLife()
                makiWalk()
                spores()
                geckoClimb()
                scrollReveals()
                counters()
                parallax()
                drawCoastline()
                growLadder()

                // matchMedia exécute ce retour quand la condition cesse
                // de s'appliquer ou au revert du contexte.
                return () => stopCanopy?.()
            })
        }, root.value)
    })

    onUnmounted(() => ctx?.revert())
}

/* ── Chargement : la page se lève ─────────────────────────────── */

function heroEntrance() {
    const tl = gsap.timeline({ defaults: { ease: 'power3.out' } })

    // La canopée pousse avant que le texte n'arrive.
    tl.from('.cn__layer', {
        yPercent: 8,
        opacity: 0,
        duration: 1.5,
        stagger: 0.14,
        ease: 'power2.out',
    })

    // Les nervures se tracent, comme au crayon sur une planche d'herbier.
    const ribs = gsap.utils.toArray('.cn__rib')
    ribs.forEach((rib) => {
        const len = rib.getTotalLength?.() ?? 0
        if (len) gsap.set(rib, { strokeDasharray: len, strokeDashoffset: len })
    })
    tl.to(ribs, {
        strokeDashoffset: 0,
        duration: 1.7,
        stagger: { each: 0.012, from: 'random' },
        ease: 'power1.inOut',
    }, 0.25)

    // Le titre monte ligne par ligne depuis son propre masque : c'est
    // l'entrée qui porte la page, le reste ne fait que suivre.
    // La marque se trace de gauche à droite, dans le sens où l'on
    // dessine une coche. Une seule fois, au chargement.
    tl.from('.hdr__mark', {
        clipPath: 'inset(0 100% 0 0)',
        duration: 1,
        ease: 'power2.inOut',
    }, 0.1)

    tl.from('[data-hero-item]', { y: 18, opacity: 0, duration: .75, stagger: .12 }, 0.35)
      .from('[data-hero-line]', {
          yPercent: 112,
          duration: 1.1,
          stagger: .1,
          ease: 'power4.out',
      }, 0.42)
      // La barre fuchsia se trace une fois la ligne posée.
      .from('[data-underline]', {
          scaleX: 0,
          duration: .8,
          ease: 'power2.inOut',
      }, 1.25)
      .from('[data-hero-search]', { y: 34, opacity: 0, duration: 1, ease: 'power3.out' }, 1.0)
}

/* ── Les chiffres se comptent depuis zéro ─────────────────────── */

function counters() {
    gsap.utils.toArray('[data-count]').forEach((el) => {
        const target = parseFloat(el.dataset.count)
        if (Number.isNaN(target)) return

        const obj = { v: 0 }
        gsap.to(obj, {
            v: target,
            duration: 1.4,
            ease: 'power2.out',
            delay: 1.1,
            onUpdate: () => { el.textContent = Math.round(obj.v) },
        })
    })
}

/* ── La canopée respire et suit le curseur ────────────────────── */

function canopyLife() {
    gsap.to('.cn__fan', {
        rotation: 1.1,
        transformOrigin: '10% 100%',
        duration: 9,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut',
    })

    const layers = gsap.utils.toArray('.cn__layer')
    if (!layers.length) return

    const setters = layers.map((el) => ({
        depth: parseFloat(el.dataset.depth) || 0.2,
        x: gsap.quickTo(el, 'x', { duration: 1.1, ease: 'power3.out' }),
        y: gsap.quickTo(el, 'y', { duration: 1.1, ease: 'power3.out' }),
    }))

    const onMove = (e) => {
        const dx = (e.clientX / window.innerWidth - .5) * 2
        const dy = (e.clientY / window.innerHeight - .5) * 2
        setters.forEach((s) => {
            s.x(-dx * 26 * s.depth)
            s.y(-dy * 16 * s.depth)
        })
    }

    // Pointeur fin uniquement : sur mobile ça n'a pas de sens.
    if (window.matchMedia('(pointer: fine)').matches) {
        window.addEventListener('pointermove', onMove, { passive: true })
        return () => window.removeEventListener('pointermove', onMove)
    }
}

/* ── Le maki arpente la branche ───────────────────────────────── */

function makiWalk() {
    const maki = document.querySelector('.maki')
    const branch = document.querySelector('#cn-branch')
    if (!maki || !branch) return

    // Démarche : les membres opposés alternent, le corps tressaute.
    const gait = [
        gsap.to('.maki__leg--fore path', {
            rotation: 17, svgOrigin: '65 -26', transformOrigin: '50% 0%',
            duration: .34, repeat: -1, yoyo: true, ease: 'sine.inOut',
        }),
        gsap.to('.maki__leg--hind path', {
            rotation: -17, svgOrigin: '29 -27',
            duration: .34, repeat: -1, yoyo: true, ease: 'sine.inOut',
        }),
        gsap.to('.maki__leg--far path', {
            rotation: 14, svgOrigin: '45 -27',
            duration: .34, repeat: -1, yoyo: true, ease: 'sine.inOut', delay: .17,
        }),
        gsap.to('.maki__body', {
            y: -1.4, duration: .34, repeat: -1, yoyo: true, ease: 'sine.inOut',
        }),
    ]

    // La queue balance en permanence, même à l'arrêt.
    gsap.to('.maki__tail', {
        rotation: 6, svgOrigin: '22 -30',
        duration: 2.6, repeat: -1, yoyo: true, ease: 'sine.inOut',
    })

    const path = {
        path: branch,
        align: branch,
        alignOrigin: [0.5, 1],
        autoRotate: true,
    }

    const tl = gsap.timeline({ repeat: -1, repeatDelay: 7 })

    tl.set(maki, { opacity: 0 })
      .to(maki, { opacity: 1, duration: 1.4, ease: 'power1.out' }, 0)
      .to(maki, {
          motionPath: { ...path, start: 0, end: 1 },
          duration: 27,
          ease: 'flanerie',
          onStart: () => gait.forEach((t) => t.play()),
      }, 0)
      // Pendant le palier de l'ease, il s'arrête : on coupe la démarche
      // et il tourne la tête.
      .add(() => gait.forEach((t) => t.pause()), 11.5)
      .to('.maki__head', {
          rotation: -14, svgOrigin: '68 -40',
          duration: 1.1, repeat: 3, yoyo: true, ease: 'sine.inOut',
      }, 11.8)
      .add(() => gait.forEach((t) => t.play()), 16.5)
      .to(maki, { opacity: 0, duration: 1.6 }, 25.6)
}

/* ── Spores en suspension dans le sous-bois ───────────────────── */

function spores() {
    const rnd = gsap.utils.random

    gsap.utils.toArray('.cn__spore').forEach((s) => {
        const tl = gsap.timeline({ repeat: -1, delay: rnd(0, 12) })

        tl.fromTo(s,
            { y: 0, x: 0, opacity: 0 },
            { opacity: rnd(.2, .5), duration: 2.6, ease: 'power1.out' })
          .to(s, {
              y: rnd(-150, -300),
              x: rnd(-60, 80),
              duration: rnd(11, 19),
              ease: 'none',
          }, 0)
          .to(s, { opacity: 0, duration: 3.4 }, '-=3.8')
    })
}

/* ── Le gecko suit la pointe de la tige ───────────────────────── */

function geckoClimb() {
    const climber = document.querySelector('.ladder__climber')
    const stem = document.querySelector('.ladder__stem')
    if (!climber || !stem) return

    // Démarche diagonale : avant-gauche avec arrière-droite, puis l'inverse.
    const d = 1.6
    gsap.to('.gk__leg--fl, .gk__leg--hr', {
        rotation: 7, svgOrigin: '32 48',
        duration: d, repeat: -1, yoyo: true, ease: 'sine.inOut',
    })
    gsap.to('.gk__leg--fr, .gk__leg--hl', {
        rotation: -7, svgOrigin: '32 48',
        duration: d, repeat: -1, yoyo: true, ease: 'sine.inOut', delay: d / 2,
    })

    // La queue balaie, contretemps du corps.
    gsap.to('.gk__tail', {
        rotation: 5, svgOrigin: '32 68',
        duration: d, repeat: -1, yoyo: true, ease: 'sine.inOut', delay: d / 4,
    })

    gsap.to(climber, {
        // On l'arrête avant le dernier badge, qu'il masquait en fin de course.
        y: () => Math.max(0, stem.offsetHeight - climber.offsetHeight - 52),
        ease: 'none',
        scrollTrigger: {
            trigger: '.ladder',
            start: 'top 72%',
            end: 'bottom 72%',
            scrub: .6,
            invalidateOnRefresh: true,
        },
    })
}

/* ── Révélations au scroll — remplace l'ancienne directive ────── */

function scrollReveals() {
    ScrollTrigger.batch('[data-anim]', {
        start: 'top 88%',
        once: true,
        onEnter: (batch) =>
            gsap.from(batch, {
                y: 34,
                opacity: 0,
                duration: .95,
                stagger: .09,
                ease: 'power3.out',
                overwrite: true,
            }),
    })

    gsap.utils.toArray('[data-anim-group]').forEach((group) => {
        gsap.from(group.children, {
            y: 30,
            opacity: 0,
            duration: .9,
            stagger: .11,
            ease: 'power3.out',
            scrollTrigger: { trigger: group, start: 'top 84%', once: true },
        })
    })
}

/* ── Parallaxe ────────────────────────────────────────────────── */

function parallax() {
    gsap.utils.toArray('[data-depth]').forEach((el) => {
        const depth = parseFloat(el.dataset.depth) || 0.2
        gsap.to(el, {
            yPercent: depth * 24,
            ease: 'none',
            scrollTrigger: {
                trigger: '.hero',
                start: 'top top',
                end: 'bottom top',
                scrub: true,
            },
        })
    })

    // Le texte du hero s'efface en montant : la canopée et le moteur restent.
    gsap.to('.hero__inner', {
        yPercent: -14,
        opacity: 0,
        ease: 'none',
        scrollTrigger: {
            trigger: '.hero',
            start: 'center top',
            end: 'bottom top',
            scrub: true,
        },
    })
}

/* ── Le littoral se trace à l'entrée de la section ────────────── */

function drawCoastline() {
    const coast = document.querySelector('.js-coast')
    if (!coast) return

    const len = coast.getTotalLength()
    gsap.set(coast, { strokeDasharray: len, strokeDashoffset: len, fillOpacity: 0 })

    const tl = gsap.timeline({
        scrollTrigger: { trigger: '.atlas__map', start: 'top 78%', once: true },
    })

    tl.to(coast, { strokeDashoffset: 0, duration: 2.2, ease: 'power2.inOut' })
      .to(coast, { fillOpacity: 1, duration: 1 }, '-=0.9')
      .from('.map__pin', {
          scale: 0,
          opacity: 0,
          transformOrigin: 'center',
          duration: .6,
          stagger: .09,
          ease: 'back.out(2)',
      }, '-=0.8')
}

/* ── L'échelle de confiance pousse comme une tige ─────────────── */

function growLadder() {
    gsap.from('.ladder__stem', {
        scaleY: 0,
        transformOrigin: 'top center',
        ease: 'none',
        scrollTrigger: {
            trigger: '.ladder',
            start: 'top 72%',
            end: 'bottom 72%',
            scrub: .6,
        },
    })
}
