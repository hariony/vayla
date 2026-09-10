import { api } from '@/Services/api.js'

/**
 * Appels API des annonces.
 *
 * L'accueil ne s'en sert pas encore : Inertia lui livre les huit annonces
 * dans ses props et le filtrage tient en mémoire (useListingFilters). Ce
 * service est le même contrat, côté réseau — il entre en jeu le jour où la
 * grille pagine, et c'est exactement ce que consomme l'application Flutter.
 */
export const ListingService = {
    /**
     * `amenities` est conjonctif : deux clés posent deux conditions, comme
     * côté serveur. Cocher une case de plus réduit toujours le résultat.
     *
     * @param {{destination?: string, category?: string, guests?: number,
     *          min_trust?: number, kind?: string, max_price?: number,
     *          amenities?: string[], page?: number, per_page?: number}} params
     */
    async search(params = {}) {
        return api.get('/listings', params)
    },

    async get(slug) {
        const { data } = await api.get(`/listings/${slug}`)
        return data
    },
}
