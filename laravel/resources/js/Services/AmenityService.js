import { api } from '@/Services/api.js'

/**
 * Le vocabulaire des équipements.
 *
 * Il se lit, il ne se recopie pas : les libellés vivent en base et sont
 * servis groupés par rubrique. Écrire « Groupe électrogène » en dur dans un
 * composant ferait diverger le site de l'application mobile au premier
 * ajustement de formulation.
 *
 * `catalogue()` sert le formulaire du propriétaire, `filters()` le panneau
 * de recherche — un sous-ensemble du même vocabulaire.
 */
export const AmenityService = {
    async catalogue() {
        const { data } = await api.get('/amenities')
        return data
    },

    async filters() {
        const { data } = await api.get('/amenities/filters')
        return data
    },
}
