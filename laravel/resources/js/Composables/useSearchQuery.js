import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

import { ListingService } from '@/Services/ListingService.js'
import { useStayDates, addDays, toISO } from '@/Composables/useStayDates.js'

/**
 * L'état du moteur de recherche, et son décompte en direct.
 *
 * **Pourquoi le moteur emmène au catalogue et ne filtre pas sur place.**
 * L'accueil filtre en mémoire — la grille est petite, le rail doit répondre à
 * l'instant. Mais une recherche par dates ne peut pas se faire en mémoire :
 * le navigateur n'a ni les périodes déclarées, ni les réservations en cours.
 * Chercher, c'est donc aller au catalogue, où l'URL porte les critères — un
 * filtre se partage, se met en favori et survit au retour arrière. Changer de
 * destination continue, lui, de filtrer la grille sur place : c'est le geste
 * le plus fréquent et il n'a pas à faire changer de page.
 *
 * **Pourquoi le décompte passe par l'API.** C'est le premier usage réel de
 * `ListingService`, et c'est le moment que l'architecture attendait : le
 * décompte doit dire la vérité sur *tout* le catalogue, pas sur les huit
 * annonces que la page d'accueil tient dans ses props. La même requête que
 * celle du catalogue, avec `per_page=1` : on ne veut que `meta.total`, pas les
 * annonces.
 */

/** Un calendrier sans contrainte : la recherche ne porte sur aucun logement. */
export function calendrierLibre(mois = 14) {
    const aujourdhui = toISO(Date.now())

    return {
        from: aujourdhui,
        to: addDays(aujourdhui, mois * 31),
        blocked: [],
        minNights: 1,
        maxNights: null,
        price: 0,
    }
}

export function useSearchQuery(initial = {}) {
    const destination = ref(initial.destination ?? '')
    const guests = ref(initial.guests ?? 2)

    const calendrier = computed(calendrierLibre)
    const dates = useStayDates(calendrier)

    if (initial.arrival && initial.departure) {
        dates.arrivee.value = initial.arrival
        dates.depart.value = initial.departure
    }

    /** Les critères, dans la forme exacte qu'attend `ListingIndexRequest`. */
    const criteres = computed(() => {
        const out = { destination: destination.value || undefined, guests: guests.value || undefined }

        // Les dates partent **par paire ou pas du tout** : le serveur refuse
        // une arrivée seule, et il a raison — un critère à moitié posé qui ne
        // filtre rien en silence est pire que pas de critère.
        if (dates.arrivee.value && dates.depart.value) {
            out.arrival = dates.arrivee.value
            out.departure = dates.depart.value
        }

        return out
    })

    // ── Le décompte en direct ────────────────────────────────────────
    const total = ref(null)
    const compte = ref(false)
    let jeton = 0

    async function recompter() {
        const mien = ++jeton
        compte.value = true

        try {
            const { meta } = await ListingService.search({ ...criteres.value, per_page: 1 })
            // Une réponse arrivée après une frappe plus récente est périmée :
            // l'ignorer évite que le décompte revienne en arrière.
            if (mien === jeton) total.value = meta?.total ?? null
        } catch {
            if (mien === jeton) total.value = null
        } finally {
            if (mien === jeton) compte.value = false
        }
    }

    let minuteur = null
    watch(criteres, () => {
        clearTimeout(minuteur)
        minuteur = setTimeout(recompter, 250)
    }, { deep: true, immediate: true })

    const libelle = computed(() => {
        if (total.value === null) return null
        if (total.value === 0) return 'Aucun logement pour ces critères'

        return `${total.value} logement${total.value > 1 ? 's' : ''} correspondent`
    })

    const chercher = () => router.get('/logements', criteres.value, { preserveState: false })

    return { destination, guests, dates, calendrier, criteres, total, compte, libelle, chercher }
}
