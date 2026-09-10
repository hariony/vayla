import { api } from '@/Services/api.js'

export const DestinationService = {
    async list() {
        const { data } = await api.get('/destinations')
        return data
    },

    async get(slug) {
        const { data } = await api.get(`/destinations/${slug}`)
        return data
    },
}
