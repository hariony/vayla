/**
 * Client de l'API v1.
 *
 * `fetch` et rien d'autre : aucune dépendance ajoutée pour trois appels.
 * Le préfixe de version est ici, une fois — le jour où v2 sort, l'ancienne
 * application mobile continue de parler à v1 et le site suit d'une ligne.
 */
const BASE = '/api/v1'

async function request(path, { params, ...options } = {}) {
    const url = new URL(BASE + path, window.location.origin)

    Object.entries(params ?? {}).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') {
            return
        }

        // Les listes partent en `clé[]=a&clé[]=b` : c'est la forme que
        // Laravel relit comme un tableau. `set` écraserait la valeur
        // précédente et le filtre par équipements n'en garderait qu'une.
        if (Array.isArray(value)) {
            value.forEach((v) => url.searchParams.append(`${key}[]`, v))
            return
        }

        url.searchParams.set(key, value)
    })

    const response = await fetch(url, {
        headers: { Accept: 'application/json', ...(options.headers ?? {}) },
        ...options,
    })

    const body = await response.json().catch(() => null)

    if (!response.ok) {
        throw Object.assign(
            new Error(body?.message ?? `Erreur ${response.status}`),
            { status: response.status, body }
        )
    }

    return body
}

export const api = {
    get: (path, params) => request(path, { params }),
}
