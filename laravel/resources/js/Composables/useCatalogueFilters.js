import { computed, reactive, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Les filtres du catalogue.
 *
 * L'état de vérité est **l'URL**, pas un objet réactif : un filtre doit se
 * partager, se mettre en favori, survivre au retour arrière du navigateur et
 * être indexable. Le composable tient donc une copie locale pour l'affichage
 * immédiat des champs, et pousse une visite Inertia à chaque changement.
 *
 * Les visites sont partielles (`only`) : seuls la grille, ses compteurs et
 * l'écho des critères repartent du serveur. Le vocabulaire des équipements,
 * les destinations et l'échelle de confiance ne changent pas entre deux
 * clics — les recharger à chaque case cochée serait dix fois la charge utile
 * pour rien.
 *
 * `preserveScroll` est délibéré : cocher un équipement ne doit pas renvoyer
 * l'utilisateur en haut de page, alors qu'il est dans le panneau latéral.
 */
export function useCatalogueFilters(initial) {
    const state = reactive({
        destination: initial.destination ?? '',
        category: initial.category ?? 'all',
        guests: initial.guests ?? null,
        // Les dates vont par paire — le serveur refuse une arrivée seule.
        arrival: initial.arrival ?? null,
        departure: initial.departure ?? null,
        min_trust: initial.min_trust ?? null,
        kind: initial.kind ?? '',
        max_price: initial.max_price ?? null,
        amenities: [...(initial.amenities ?? [])],
        sort: initial.sort ?? 'confiance',
    })

    const loading = ref(false)
    let timer = null

    /** Les critères vides ne partent pas dans l'URL : elle doit rester lisible. */
    const params = () => {
        const out = {}

        if (state.destination) out.destination = state.destination
        if (state.category && state.category !== 'all') out.category = state.category
        if (state.guests) out.guests = state.guests
        if (state.arrival && state.departure) {
            out.arrival = state.arrival
            out.departure = state.departure
        }
        if (state.min_trust) out.min_trust = state.min_trust
        if (state.kind) out.kind = state.kind
        if (state.max_price) out.max_price = state.max_price
        if (state.amenities.length) out.amenities = state.amenities
        if (state.sort && state.sort !== 'confiance') out.sort = state.sort

        return out
    }

    const visit = (extra = {}, { replace = true } = {}) => {
        router.get('/logements', { ...params(), ...extra }, {
            preserveState: true,
            preserveScroll: true,
            replace,
            only: ['listings', 'meta', 'filtre'],
            onStart: () => (loading.value = true),
            onFinish: () => (loading.value = false),
        })
    }

    /**
     * Un délai sur les champs qui se saisissent au clavier : sans lui,
     * « 120000 » déclencherait six requêtes, dont cinq déjà périmées.
     */
    const apply = (delay = 0) => {
        clearTimeout(timer)
        timer = setTimeout(visit, delay)
    }

    const toggleAmenity = (key) => {
        const i = state.amenities.indexOf(key)
        i === -1 ? state.amenities.push(key) : state.amenities.splice(i, 1)
        apply()
    }

    const setCategory = (key) => {
        state.category = key
        apply()
    }

    const goToPage = (page) => {
        // La pagination, elle, remonte en haut : on change de contenu, pas
        // de critère.
        router.get('/logements', { ...params(), page }, {
            preserveState: true,
            only: ['listings', 'meta', 'filtre'],
            onStart: () => (loading.value = true),
            onFinish: () => (loading.value = false),
        })
    }

    const reset = () => {
        Object.assign(state, {
            destination: '', category: 'all', guests: null, min_trust: null,
            arrival: null, departure: null,
            kind: '', max_price: null, amenities: [], sort: 'confiance',
        })
        apply()
    }

    const activeCount = computed(() =>
        (state.destination ? 1 : 0) +
        (state.category && state.category !== 'all' ? 1 : 0) +
        (state.guests ? 1 : 0) +
        // Un séjour compte pour **un** critère, pas deux : les deux dates ne
        // se posent ni ne s'effacent séparément.
        (state.arrival && state.departure ? 1 : 0) +
        (state.min_trust ? 1 : 0) +
        (state.kind ? 1 : 0) +
        (state.max_price ? 1 : 0) +
        state.amenities.length
    )

    // Le serveur renvoie les critères qu'il a réellement retenus : on s'y
    // réaligne, sinon un retour arrière laisserait les champs mentir sur le
    // contenu affiché.
    watch(() => initial, (next) => {
        if (!next) return
        state.destination = next.destination ?? ''
        state.category = next.category ?? 'all'
        state.guests = next.guests ?? null
        state.arrival = next.arrival ?? null
        state.departure = next.departure ?? null
        state.min_trust = next.min_trust ?? null
        state.kind = next.kind ?? ''
        state.max_price = next.max_price ?? null
        state.amenities = [...(next.amenities ?? [])]
        state.sort = next.sort ?? 'confiance'
    })

    /** Les deux dates partent ensemble : les séparer ferait un 422. */
    const clearDates = () => {
        state.arrival = null
        state.departure = null
        apply()
    }

    return { state, loading, activeCount, apply, visit, toggleAmenity, setCategory, goToPage, reset, clearDates }
}
