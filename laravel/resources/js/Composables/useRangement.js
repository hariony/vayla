import { nextTick, ref, watch } from 'vue'
import gsap from 'gsap'
import { Flip } from 'gsap/Flip'

gsap.registerPlugin(Flip)

/** Distance à parcourir avant qu'un appui devienne un glisser : en deçà, c'est un clic. */
const SEUIL = 6
/** Bande, en haut et en bas de la fenêtre, où la page défile toute seule. */
const BORD = 90

/**
 * Ranger une galerie à la main : on attrape une photo, les autres s'écartent
 * pour lui faire place, on la lâche où l'on veut.
 *
 * **Au pointeur, pas au glisser-déposer natif du navigateur.** La première
 * version s'appuyait sur `draggable` : l'image fantôme est celle du système,
 * translucide et minuscule ; rien ne bouge avant qu'on lâche, donc on ne voit
 * pas où la photo va tomber ; les interstices de la grille refusent le dépôt ;
 * et surtout **rien ne marche au doigt**. Les évènements `pointer*` couvrent la
 * souris, le doigt et le stylet d'un seul code.
 *
 * - **Les autres photos s'écartent pendant le glisser** (GSAP Flip) : la place
 *   vide *est* l'endroit où la photo tombera. On ne vise plus une carte en
 *   espérant comprendre s'il s'agit d'« avant » ou d'« après ».
 * - **Les places sont mesurées une fois, au départ**, en coordonnées de page :
 *   viser une carte en cours d'animation ferait trembler l'ordre d'avant en
 *   arrière. On vise une *place*, pas une photo.
 * - **Un appui sans mouvement reste un clic** : il sert à voir la photo en grand.
 * - **Au doigt, on attrape par la poignée** (`data-poignee`, `touch-action:
 *   none`) : ailleurs sur la carte, le doigt fait défiler la page, comme partout.
 * - **La page défile seule près des bords**, pour ranger une galerie plus haute
 *   que l'écran ; **Échap annule** et remet l'ordre de départ.
 * - **Les flèches restent** : sans souris ni écran tactile, c'est le seul chemin.
 *
 * **L'ordre change à l'écran tout de suite, et part au serveur au lâcher**, une
 * seule fois et seulement s'il a changé. Quand les props reviennent, l'ordre du
 * serveur fait foi.
 *
 * @param {() => Array<{ id: number }>} source   la liste venue des props
 * @param {{ conteneur: import('vue').Ref<HTMLElement|null>, enregistrer: (ids: number[]) => void }} options
 */
export function useRangement(source, { conteneur, enregistrer }) {
    const ordre = ref([...source()])
    /** L'identifiant de la photo tenue, ou `null`. */
    const glisse = ref(null)
    /** Phrase lue par les lecteurs d'écran après chaque déplacement. */
    const annonce = ref('')

    let saisie = null

    watch(source, (v) => { if (! saisie) ordre.value = [...v] })

    const reduit = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches
    const cartes = () => [...(conteneur.value?.querySelectorAll('[data-flip]') ?? [])]

    /** Vrai tant qu'un réordonnancement attend d'être peint. */
    let enCours = false

    /** Réordonner à l'écran, en animant ce qui bouge. */
    const reordonner = async (de, vers) => {
        const etat = ! reduit() && cartes().length ? Flip.getState(cartes()) : null

        const copie = [...ordre.value]
        const [element] = copie.splice(de, 1)
        copie.splice(vers, 0, element)
        ordre.value = copie

        enCours = true
        await nextTick()
        enCours = false
        if (etat) Flip.from(etat, { duration: 0.26, ease: 'power2.out' })

        return copie
    }

    const annoncer = (vers) => { annonce.value = `Photo déplacée en position ${vers + 1} sur ${ordre.value.length}.` }

    /** Le chemin des flèches : un cran, enregistré aussitôt. */
    const deplacer = async (de, vers) => {
        if (de === null || de === vers || vers < 0 || vers >= ordre.value.length) return

        const copie = await reordonner(de, vers)
        annoncer(vers)
        enregistrer(copie.map((p) => p.id))
    }

    // ── Le glisser ──────────────────────────────────────────────────────

    /** Les places de la grille, en coordonnées de page, mesurées au départ. */
    const mesurer = () => cartes().map((el) => {
        const r = el.getBoundingClientRect()
        return { g: r.left + scrollX, h: r.top + scrollY, d: r.right + scrollX, b: r.bottom + scrollY }
    })

    /** La place visée par le pointeur, ou `null` s'il est hors de la grille. */
    const viser = (x, y) => {
        const { places } = saisie
        const px = x + scrollX
        const py = y + scrollY

        const dedans = places.findIndex((p) => px >= p.g && px <= p.d && py >= p.h && py <= p.b)
        if (dedans !== -1) return dedans

        // Dans un interstice ou juste au bord : la place la plus proche, tant
        // qu'on reste dans l'emprise de la grille.
        const g = Math.min(...places.map((p) => p.g)) - 24
        const d = Math.max(...places.map((p) => p.d)) + 24
        const h = Math.min(...places.map((p) => p.h)) - 24
        const b = Math.max(...places.map((p) => p.b)) + 24
        if (px < g || px > d || py < h || py > b) return null

        let meilleure = null
        let distance = Infinity
        places.forEach((p, i) => {
            const dist = Math.hypot(px - (p.g + p.d) / 2, py - (p.h + p.b) / 2)
            if (dist < distance) { distance = dist; meilleure = i }
        })
        return meilleure
    }

    // Un réordonnancement à la fois, et au plus un par image : deux photos qui
    // s'écartent sur la même position de départ se contrediraient.
    const suivre = () => {
        if (! saisie?.actif || enCours) return
        const vers = viser(saisie.x, saisie.y)
        const de = ordre.value.findIndex((p) => p.id === saisie.id)
        if (vers !== null && de !== -1 && vers !== de) reordonner(de, vers)
    }

    /**
     * Une fois par image : la page défile près des bords, puis la place visée
     * se relit — le pointeur a pu bouger, ou la page sous lui.
     */
    const defiler = () => {
        if (! saisie?.actif) return
        const { y } = saisie
        const vitesse = y < BORD ? -(BORD - y) / 4 : y > innerHeight - BORD ? (y - (innerHeight - BORD)) / 4 : 0
        if (vitesse) scrollBy(0, vitesse)
        suivre()
        saisie.boucle = requestAnimationFrame(defiler)
    }

    const commencer = () => {
        const el = saisie.element
        const r = el.getBoundingClientRect()

        saisie.actif = true
        saisie.places = mesurer()
        saisie.decalage = { x: saisie.x0 - r.left, y: saisie.y0 - r.top }
        glisse.value = saisie.id

        // Le fantôme : une copie de la carte, qui suit le pointeur au-dessus
        // de tout. La carte d'origine reste en place et devient l'emplacement.
        const fantome = el.cloneNode(true)
        fantome.removeAttribute('data-flip')
        // Une animation Flip en cours laisse sa transformation en ligne.
        fantome.style.transform = ''
        fantome.setAttribute('aria-hidden', 'true')
        Object.assign(fantome.style, {
            position: 'fixed',
            left: `${r.left}px`,
            top: `${r.top}px`,
            width: `${r.width}px`,
            height: `${r.height}px`,
            margin: '0',
            zIndex: '1000',
            pointerEvents: 'none',
            cursor: 'grabbing',
            transformOrigin: `${saisie.decalage.x}px ${saisie.decalage.y}px`,
        })
        fantome.classList.add('is-fantome')
        document.body.appendChild(fantome)
        saisie.fantome = fantome

        if (! reduit()) {
            gsap.to(fantome, { scale: 1.05, rotation: -1.5, boxShadow: '0 18px 40px -12px rgba(26, 21, 18, .45)', duration: 0.18, ease: 'power2.out' })
        }

        document.documentElement.style.cursor = 'grabbing'
        document.documentElement.style.userSelect = 'none'
        saisie.boucle = requestAnimationFrame(defiler)
    }

    const bouger = (e) => {
        if (! saisie || e.pointerId !== saisie.pointeur) return
        saisie.x = e.clientX
        saisie.y = e.clientY

        if (! saisie.actif) {
            if (Math.hypot(e.clientX - saisie.x0, e.clientY - saisie.y0) < SEUIL) return
            commencer()
        }

        e.preventDefault()
        gsap.set(saisie.fantome, { x: e.clientX - saisie.x0, y: e.clientY - saisie.y0 })
    }

    const nettoyer = () => {
        removeEventListener('pointermove', bouger)
        removeEventListener('pointerup', lacher)
        removeEventListener('pointercancel', annuler)
        removeEventListener('keydown', echap)
        if (saisie?.boucle) cancelAnimationFrame(saisie.boucle)
        document.documentElement.style.cursor = ''
        document.documentElement.style.userSelect = ''
    }

    /** Le fantôme rejoint sa place, puis disparaît. */
    const poser = async (fantome) => {
        if (! fantome) return
        const id = glisse.value
        await nextTick()
        const place = cartes().find((el) => el.dataset.id === String(id))
        // Une nouvelle saisie a pu commencer pendant l'atterrissage : on ne lui
        // retire pas son emplacement.
        const fin = () => { fantome.remove(); if (glisse.value === id) glisse.value = null }

        if (! place || reduit()) return fin()

        const r = place.getBoundingClientRect()
        const f = fantome.getBoundingClientRect()
        gsap.to(fantome, {
            x: `+=${r.left - f.left}`,
            y: `+=${r.top - f.top}`,
            scale: 1,
            rotation: 0,
            boxShadow: '0 0 0 0 rgba(26, 21, 18, 0)',
            duration: 0.2,
            ease: 'power2.out',
            onComplete: fin,
        })
    }

    const lacher = (e) => {
        if (! saisie || e.pointerId !== saisie.pointeur) return
        const { actif, fantome, depart } = saisie
        nettoyer()
        saisie = null

        if (! actif) return

        // Le clic qui suit le lâcher ne doit pas ouvrir la photo qu'on range.
        const avaler = (c) => { c.stopPropagation(); c.preventDefault() }
        addEventListener('click', avaler, { capture: true, once: true })
        setTimeout(() => removeEventListener('click', avaler, { capture: true }), 0)

        poser(fantome)

        const ids = ordre.value.map((p) => p.id)
        if (ids.join() !== depart.join()) {
            annoncer(ordre.value.findIndex((p) => p.id === glisse.value))
            enregistrer(ids)
        }
    }

    /** Échap, ou le navigateur qui reprend la main : l'ordre de départ revient. */
    const annuler = () => {
        if (! saisie) return
        const { actif, fantome, depart } = saisie
        nettoyer()
        saisie = null
        if (! actif) return

        const parId = new Map(ordre.value.map((p) => [p.id, p]))
        ordre.value = depart.map((id) => parId.get(id))
        poser(fantome)
    }

    const echap = (e) => { if (e.key === 'Escape') annuler() }

    /**
     * À poser sur `@pointerdown` de chaque carte, qui doit porter `data-flip`
     * et `data-id`. Les boutons de la carte ne l'attrapent pas — sauf celui qui
     * porte `data-prise` (la vignette qu'on clique pour voir la photo).
     */
    const saisir = (p, e) => {
        if (saisie || e.button !== 0) return
        if (e.target.closest('button:not([data-prise]), a, input, select, textarea')) return
        if (e.pointerType === 'touch' && ! e.target.closest('[data-poignee]')) return

        saisie = {
            id: p.id,
            element: e.currentTarget,
            pointeur: e.pointerId,
            x0: e.clientX,
            y0: e.clientY,
            x: e.clientX,
            y: e.clientY,
            actif: false,
            depart: ordre.value.map((x) => x.id),
        }

        addEventListener('pointermove', bouger, { passive: false })
        addEventListener('pointerup', lacher)
        addEventListener('pointercancel', annuler)
        addEventListener('keydown', echap)
    }

    return { ordre, glisse, annonce, deplacer, saisir }
}
