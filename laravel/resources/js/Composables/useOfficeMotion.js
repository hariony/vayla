import { onMounted, onUnmounted } from 'vue'
import gsap from 'gsap'

import { nombre } from '@/Support/format.js'

/**
 * Le mouvement du back-office : **il montre le travail qui arrive et le
 * travail qui part**, rien d'autre.
 *
 * On passe ses journées ici. Un mouvement qui se remarque à la vingtième
 * ouverture d'une liste est un mouvement qui fatigue ; tout est donc court
 * (≤ 0,6 s), de faible course (≤ 12 px), et **rien ne boucle**. Chaque geste
 * porte une information :
 *
 * - **`data-reveal`** — les blocs d'un écran montent dans l'ordre de lecture :
 *   la file d'abord, les chiffres ensuite.
 * - **`data-count`** — un compte monte jusqu'à sa valeur. Sur une file de
 *   travail, voir « 0 → 7 » dit qu'il y a sept choses à faire mieux qu'un 7
 *   posé là ; à zéro, rien ne bouge, et c'est aussi une information.
 * - **`data-bar`** — une barre se remplit jusqu'à sa part : l'échelle de
 *   confiance du catalogue, les heures restantes d'une demande.
 *
 * Mêmes règles que partout : `gsap.context()` pour le nettoyage,
 * `gsap.matchMedia()` pour couper sous `prefers-reduced-motion`, et **l'état
 * final est l'état de repos** — l'animation ne fait qu'y arriver. Là où elle
 * ne se joue pas, tout est déjà à sa place.
 */
export function useOfficeMotion(racine) {
    let ctx

    onMounted(() => {
        if (! racine.value) {
            return
        }

        ctx = gsap.context((self) => {
            const blocs = self.selector('[data-reveal]')
            const comptes = self.selector('[data-count]')
            const barres = self.selector('[data-bar]')
            const mm = gsap.matchMedia()

            mm.add('(prefers-reduced-motion: no-preference)', () => {
                if (blocs.length) {
                    gsap.from(blocs, { y: 12, opacity: 0, duration: .5, stagger: .045, ease: 'power3.out', clearProps: 'transform,opacity' })
                }

                comptes.forEach((el) => {
                    const cible = Number(el.dataset.count)

                    if (! cible) {
                        return
                    }

                    const etat = { v: 0 }

                    gsap.to(etat, {
                        v: cible,
                        duration: Math.min(1.1, .45 + cible / 400),
                        delay: .15,
                        ease: 'power2.out',
                        onUpdate: () => { el.textContent = nombre(Math.round(etat.v)) },
                        onComplete: () => { el.textContent = nombre(cible) },
                    })
                })

                if (barres.length) {
                    gsap.from(barres, { scaleX: 0, transformOrigin: 'left center', duration: .7, stagger: .06, delay: .2, ease: 'power3.out' })
                }
            })
        }, racine.value)
    })

    onUnmounted(() => ctx?.revert())
}

/**
 * Une ligne qui sort de la file, **avant** que la requête ne revienne.
 *
 * Marquer un message WhatsApp comme envoyé est un geste qu'on répète vingt
 * fois de suite : attendre la réponse du serveur pour voir la ligne partir
 * ferait douter d'avoir cliqué. La ligne se replie tout de suite ; si le
 * serveur refuse, la page revient avec la ligne et le bandeau d'erreur.
 */
export function replier(el, apres) {
    if (! el || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        apres()

        return
    }

    gsap.timeline({ onComplete: apres })
        .to(el, { x: 24, opacity: 0, duration: .22, ease: 'power2.in' })
        .to(el, { height: 0, paddingTop: 0, paddingBottom: 0, marginTop: 0, borderTopWidth: 0, duration: .26, ease: 'power2.inOut' })
}
