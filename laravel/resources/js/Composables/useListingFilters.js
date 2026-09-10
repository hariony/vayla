import { computed, ref } from 'vue'

/**
 * Le filtrage de la grille d'accueil.
 *
 * Il vit côté client, et c'est délibéré : la grille est petite, entièrement
 * chargée, et le rail de catégories doit répondre à l'instant. Passer par le
 * serveur ferait clignoter la page pour un filtre sur huit lignes.
 *
 * **Il ne détient pas les critères, il les lit.** Le moteur de recherche est
 * la source unique (`useSearchQuery`) : le hero, l'en-tête compact, l'atlas et
 * cette grille regardent le même état. Auparavant chaque recherche recopiait
 * un objet `query` local, ce qui faisait deux vérités pour une même
 * destination — et deux vérités finissent toujours par diverger.
 *
 * Les dates, elles, ne filtrent **pas** ici : le navigateur n'a ni les
 * périodes déclarées ni les réservations en cours. Chercher par dates emmène
 * au catalogue, où le serveur sait répondre.
 *
 * @param {import('vue').Ref<Array>} listings
 * @param {import('vue').Ref<Array>} destinations
 * @param {{destination: import('vue').Ref<string>, guests: import('vue').Ref<number>,
 *          dates: object}} recherche  l'état rendu par `useSearchQuery`
 */
export function useListingFilters(listings, destinations, recherche) {
    const category = ref('all')

    const results = computed(() =>
        listings.value
            .filter((l) => !recherche.destination.value || l.destination === recherche.destination.value)
            .filter((l) => category.value === 'all' || l.tags.includes(category.value))
            .filter((l) => l.guests >= (recherche.guests.value || 1))
            // Vedette d'abord, puis les mieux vérifiés : la confiance est le
            // critère de tri par défaut, pas le prix.
            .sort((a, b) => (b.featured - a.featured) || (b.trust - a.trust))
    )

    const searchLabel = computed(() => {
        const found = destinations.value.find((d) => d.slug === recherche.destination.value)
        return found ? found.name : 'partout à Madagascar'
    })

    /** Un clic dans l'atlas filtre la grille et rouvre toutes les catégories. */
    const pickDestination = (slug) => {
        recherche.destination.value = slug
        category.value = 'all'
    }

    const reset = () => {
        category.value = 'all'
        recherche.destination.value = ''
        recherche.guests.value = 1
        recherche.dates.effacer()
    }

    return { category, results, searchLabel, pickDestination, reset }
}
