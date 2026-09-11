import gsap from 'gsap'

/**
 * Le geste de chaque pictogramme des raccourcis.
 *
 * **Chaque geste joue le mot de la section**, comme la clé qui tourne et la
 * loupe qui glisse sur `/connexion` : le soleil se lève sur la photographie,
 * l'éclair clignote, la coche se trace, les faits se cochent un à un, le pion
 * pointe une case, la plage du séjour se pose. Un simple grossissement au
 * survol aurait fait le même bruit sans rien dire.
 *
 * **Il joue au survol, au focus *et* à l'arrivée dans la section** — jamais
 * au seul survol : sur un téléphone il n'y a pas de survol, et c'est
 * l'arrivée qui montre le geste. C'est la règle de la maison, et c'est aussi
 * ce qui apprend l'icône : on la voit bouger au moment où l'on atteint ce
 * qu'elle désigne.
 *
 * **Court, et il revient à son état de repos** : moins de 0,7 s, et chaque
 * pièce finit exactement où elle a commencé. Un pictogramme qui resterait
 * dans une pose intermédiaire après un survol raté aurait l'air cassé.
 *
 * **Coupé sous `prefers-reduced-motion`** : le geste est un agrément, le
 * libellé porte déjà l'information. Rien n'est laissé à moitié transparent —
 * on ne lance tout simplement rien.
 *
 * Une chronologie par pictogramme, construite à la première lecture et
 * **relancée** ensuite (`restart`) : deux survols rapprochés ne superposent
 * pas deux animations qui se battraient pour la même pièce.
 */
const chronologies = new WeakMap()

const sansMouvement = () => typeof window !== 'undefined'
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches

/** Trace un chemin comme au crayon : sa longueur, puis son décalage. */
const tracer = (tl, pieces, position = 0, duree = .42, decalage = 0) => {
    pieces.forEach((piece, i) => {
        // Une pièce d'une forme masquée (`display: none`) n'a pas de rendu :
        // selon le moteur, `getTotalLength` y rend 0 ou lève. La longueur par
        // défaut garde le geste sans conséquence plutôt que de casser la page.
        let longueur = 20

        try {
            longueur = piece.getTotalLength() || 20
        } catch {
            // longueur par défaut
        }

        tl.fromTo(piece,
            { strokeDasharray: longueur, strokeDashoffset: longueur },
            { strokeDashoffset: 0, duration: duree, ease: 'power2.out' },
            position + i * decalage,
        )
    })
}

const GESTES = {
    // Le soleil se lève derrière le relief : une photographie, pas un outil.
    photos: (tl, $) => {
        tl.fromTo($('soleil'),
            { y: 3.5, scale: .55, opacity: .25 },
            { y: 0, scale: 1, opacity: 1, duration: .55, ease: 'back.out(2)', transformOrigin: '50% 50%' })
    },

    // L'éclair vacille comme une ampoule sur un réseau fragile, puis tient.
    energie: (tl, $) => {
        tl.to($('eclair'), { opacity: .2, duration: .05 })
            .to($('eclair'), { opacity: 1, duration: .05 })
            .to($('eclair'), { opacity: .45, duration: .05 })
            .to($('eclair'), { opacity: 1, scale: 1.12, duration: .12, transformOrigin: '50% 50%' })
            .to($('eclair'), { scale: 1, duration: .25, ease: 'back.out(3)' })
            .fromTo($('etincelles'),
                { opacity: 0, scale: .4 },
                { opacity: 1, scale: 1, duration: .3, ease: 'back.out(3)', transformOrigin: '50% 50%' }, .12)
    },

    // La coche du monogramme se trace dans l'écusson.
    verification: (tl, $) => tracer(tl, $('coche', true), 0, .5),

    // Les faits se cochent l'un après l'autre — c'est ce qu'est un avis ici.
    avis: (tl, $) => tracer(tl, $('tic', true), 0, .3, .18),

    // Le pion pointe la case voisine, puis celle d'en dessous, et revient.
    equipements: (tl, $) => {
        tl.to($('pion'), { x: 9.2, duration: .18, ease: 'power2.out' })
            .to($('pion'), { y: 9.2, duration: .18, ease: 'power2.out' })
            .to($('pion'), { x: 0, y: 0, duration: .3, ease: 'back.out(2.2)' })
    },

    // La plage du séjour se pose, de l'arrivée au départ.
    calendrier: (tl, $) => {
        tl.fromTo($('plage'),
            { scaleX: 0 },
            { scaleX: 1, duration: .5, ease: 'power3.out', transformOrigin: '0% 50%' })
    },
}

/**
 * Joue le geste du pictogramme contenu dans `element`.
 *
 * `element` peut être le bouton entier : on y cherche le `<svg data-glyph>`.
 */
export function jouerGlyphe(element) {
    if (! element || sansMouvement()) {
        return
    }

    const svg = element.matches?.('[data-glyph]') ? element : element.querySelector?.('[data-glyph]')
    const geste = GESTES[svg?.dataset.glyph]

    if (! svg || ! geste) {
        return
    }

    let tl = chronologies.get(svg)

    if (! tl) {
        tl = gsap.timeline({ paused: true })

        const $ = (nom, tous = false) => {
            const pieces = svg.querySelectorAll(`[data-part="${nom}"]`)

            return tous ? [...pieces] : pieces[0]
        }

        geste(tl, $)
        chronologies.set(svg, tl)
    }

    tl.restart()
}
