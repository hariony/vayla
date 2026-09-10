import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

import { nombre } from '@/Support/format.js'
import { formatLong } from '@/Composables/useStayDates.js'

/**
 * L'euro affiché à côté de l'ariary.
 *
 * **L'ariary est le prix, l'euro est une aide à la lecture.** Un voyageur
 * étranger ne sait pas ce que valent 185 000 Ar : il ne peut pas dire s'il
 * regarde un studio ou une villa, exactement au moment où il choisit. Mais il
 * règle sur place, en ariary : lui montrer un prix en euros seul le ferait
 * arriver avec une idée fausse de ce qu'il doit sortir.
 *
 * D'où trois règles qui ne se négocient pas :
 *
 * 1. **Jamais l'euro seul, jamais l'euro en premier.** Le montant en ariary
 *    reste au premier plan ; l'euro est en dessous, plus petit, en gris.
 * 2. **Jamais de centimes.** « ≈ 37 € » et non « 36,84 € » : une précision au
 *    centime sur une conversion indicative est une fausse précision, et sur un
 *    site dont l'argument est la vérification, un faux chiffre précis coûte
 *    plus cher qu'un chiffre rond.
 * 3. **Le taux porte sa date**, écrite là où l'argent se décide — l'encart de
 *    réservation, le formulaire, la confirmation. Un montant converti sans sa
 *    date est invérifiable.
 *
 * **Rien de tout ça côté propriétaire** : il est réglé en ariary par mobile
 * money, et un euro sur sa facture ne serait que du bruit.
 *
 * Le taux vient des props partagées (`devise`), lui-même fourni par
 * `App\Services\Currency\ExchangeRateProvider` — configuration aujourd'hui,
 * API de change demain, sans qu'une ligne d'affichage bouge.
 */
export function useDevise() {
    const page = usePage()

    const devise = computed(() => page.props.devise ?? null)
    const taux = computed(() => Number(devise.value?.taux ?? 0))

    /** Le montant en euros, arrondi à l'unité — ou `null` si aucun taux utilisable. */
    const enEuros = (ariary) => {
        if (!taux.value || taux.value <= 0 || !Number.isFinite(ariary)) return null

        return Math.round(ariary / taux.value)
    }

    /**
     * « ≈ 37 € », ou `null`. Espace insécable avant le symbole : c'est la
     * typographie française, et un montant ne se coupe pas en fin de ligne.
     */
    const euros = (ariary) => {
        const v = enEuros(ariary)

        return v === null ? null : `≈ ${nombre(v)} ${devise.value.symbole}`
    }

    /**
     * Une fourchette : « ≈ 14 — 37 € ».
     *
     * Un « ≈ » et un symbole, pas deux de chacun : « ≈ 14 € — ≈ 37 € » se lit
     * deux fois plus lentement pour la même information, et sur un panneau de
     * filtres chaque caractère compte.
     */
    const eurosFourchette = (min, max) => {
        const a = enEuros(min)
        const b = enEuros(max)
        if (a === null || b === null) return null

        return a === b
            ? `≈ ${nombre(a)} ${devise.value.symbole}`
            : `≈ ${nombre(a)} — ${nombre(b)} ${devise.value.symbole}`
    }

    /** La mention à écrire là où l'argent se décide. */
    const mention = computed(() =>
        devise.value
            ? `Montants en euros indicatifs, au taux du ${formatLong(devise.value.releveLe)}. `
              + 'Vous réglez en ariary, sur place, auprès du propriétaire.'
            : null
    )

    return { devise, taux, enEuros, euros, eurosFourchette, mention }
}
