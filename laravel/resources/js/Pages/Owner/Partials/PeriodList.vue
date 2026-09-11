<script setup>
/**
 * Ce qui occupe déjà le calendrier, en deux listes qui ne se mélangent pas.
 *
 * **Les périodes déclarées se reprennent d'un bouton ; les nuits d'une
 * réservation, non.** Un bouton « Libérer » posé sur des nuits vendues
 * ferait disparaître un séjour sans que le voyageur l'apprenne. Pour ces
 * nuits-là, le chemin est ailleurs — refuser la demande ou annuler la
 * réservation — et la liste le dit plutôt que d'afficher un bouton inerte.
 *
 * **Chaque ligne nomme le jour de libération.** « Du 12 au 17 » laisse le
 * doute sur le 18 ; « libre le 18 » ne le laisse pas. C'est la même règle
 * que dans le formulaire, écrite au même endroit de la lecture.
 *
 * Rouvrir des nuits ne demande **pas** de confirmation : c'est réversible en
 * trois clics. On confirme ce qui ne se défait pas — refuser une demande.
 */
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

import { addDays, formatLong } from '@/Composables/useStayDates.js'

const props = defineProps({
    declared: { type: Array, default: () => [] },
    booked: { type: Array, default: () => [] },
    base: { type: String, required: true },
})

const envoi = ref(null)

function liberer(id) {
    envoi.value = id
    router.post(`${props.base}/${id}/liberer`, {}, {
        preserveScroll: true,
        onFinish: () => (envoi.value = null),
    })
}
</script>

<template>
    <section class="pl">
        <h2 class="espace__section pl__title">Ce qui est déjà fermé</h2>

        <h3 class="pl__sub">Vos périodes</h3>
        <p v-if="!declared.length" class="pl__empty">
            Vous n'avez fermé aucune date. Votre calendrier est ouvert sur les douze prochains mois.
        </p>
        <ul v-else class="pl__list">
            <li v-for="p in declared" :key="p.id" class="pl__row">
                <div class="pl__body">
                    <p class="pl__dates">
                        Du <strong>{{ formatLong(p.from) }}</strong>
                        à la nuit du <strong>{{ formatLong(p.to) }}</strong>
                    </p>
                    <p class="pl__meta">
                        <span class="num">{{ p.nights }}</span> nuit{{ p.nights > 1 ? 's' : '' }}
                        <template v-if="p.reasonLabel"> · {{ p.reasonLabel }}</template>
                        · libre le {{ formatLong(addDays(p.to, 1)) }}
                    </p>
                </div>
                <button
                    type="button"
                    class="btn btn--outline pl__free"
                    :disabled="envoi === p.id"
                    @click="liberer(p.id)"
                >
                    {{ envoi === p.id ? 'Envoi…' : 'Rouvrir' }}
                </button>
            </li>
        </ul>

        <h3 class="pl__sub pl__sub--2">Nuits réservées</h3>
        <p v-if="!booked.length" class="pl__empty">
            Aucune réservation en cours sur ce logement.
        </p>
        <ul v-else class="pl__list">
            <li v-for="b in booked" :key="b.reference" class="pl__row pl__row--locked">
                <div class="pl__body">
                    <p class="pl__dates">
                        Du <strong>{{ formatLong(b.from) }}</strong>
                        à la nuit du <strong>{{ formatLong(b.to) }}</strong>
                    </p>
                    <p class="pl__meta">
                        <span class="num">{{ b.reference }}</span> · {{ b.traveller }} ·
                        <span class="num">{{ b.nights }}</span> nuit{{ b.nights > 1 ? 's' : '' }}
                        · libre le {{ formatLong(addDays(b.to, 1)) }}
                    </p>
                </div>
                <span class="pl__lock">
                    {{ b.pending ? 'En attente de votre réponse' : 'Réservation acceptée' }}
                </span>
            </li>
        </ul>

        <p v-if="booked.length" class="pl__note">
            Ces nuits ne se rouvrent pas depuis le calendrier : il faut refuser la demande
            ou annuler la réservation, pour que le voyageur en soit prévenu.
        </p>
    </section>
</template>

<style scoped>
.pl__title { margin: 0 0 1rem; }

.pl__sub { margin: 0 0 .6rem; font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--text-3); }
.pl__sub--2 { margin-top: 1.75rem; }

.pl__empty {
    margin: 0;
    padding: 1.1rem 1.25rem;
    border: 1px dashed var(--line-2);
    border-radius: var(--r-lg);
    font-size: .9rem;
    line-height: 1.55;
    color: var(--text-3);
}

.pl__list { display: grid; gap: .6rem; margin: 0; padding: 0; list-style: none; }

.pl__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem 1rem;
    padding: .85rem 1.1rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
}
/* Les nuits vendues se distinguent d'un aplat, pas d'une couleur : la terre
   porte l'action, le lagon ne dit que « vérifié ». */
.pl__row--locked { background: var(--off); }

.pl__body { min-width: 0; }
.pl__dates { margin: 0; font-size: .95rem; color: var(--text-2); }
.pl__dates strong { font-weight: 800; color: var(--ink); }
.pl__meta { margin: .25rem 0 0; font-size: .82rem; color: var(--text-3); }

.pl__free { flex: none; }

.pl__lock {
    flex: none;
    padding: .35rem .8rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font-size: .78rem;
    font-weight: 700;
    color: var(--text-2);
}

.pl__note { margin: .8rem 0 0; max-width: 60ch; font-size: .82rem; line-height: 1.55; color: var(--text-3); }
</style>
