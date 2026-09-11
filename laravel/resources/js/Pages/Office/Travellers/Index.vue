<script setup>
/**
 * Les comptes voyageurs. **Un compte ne conditionne rien** — on demande un
 * séjour sans lui — donc cette liste ne dit pas « nos clients » : elle dit qui
 * a ouvert un compte, et combien de séjours s'y rattachent par l'adresse.
 *
 * Aucun geste ici : ni suppression (elle toucherait des réservations qui
 * appartiennent aussi à un propriétaire), ni modification (l'adresse est
 * l'identifiant de connexion). Le nombre de séjours mène aux réservations,
 * filtrées sur l'adresse.
 */
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficePager from '@/Components/Office/OfficePager.vue'
import OfficeSearch from '@/Components/Office/OfficeSearch.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { ilYA, nombre } from '@/Support/format.js'

defineOptions({ layout: OfficeShell })

defineProps({
    voyageurs: { type: Object, required: true },
    total: { type: Number, default: 0 },
    filtre: { type: Object, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)
</script>

<template>
    <Head title="Voyageurs — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Séjours" titre="Voyageurs">
            <template #lede>
                <span class="of-num">{{ nombre(total) }}</span> compte{{ total > 1 ? 's' : '' }} ouvert{{ total > 1 ? 's' : '' }}. Un compte est facultatif :
                beaucoup de séjours sont demandés sans, et s'y rattachent par l'adresse le jour où il s'ouvre.
            </template>
        </OfficeHead>

        <OfficeSearch base="/voyageurs" :q="filtre.q" placeholder="Nom, e-mail, téléphone…" label="Rechercher un voyageur" />

        <section class="of-card" data-reveal>
            <ul v-if="voyageurs.data.length" class="of-rows">
                <li v-for="v in voyageurs.data" :key="v.id" class="of-row vi__row">
                    <span class="vi__txt">
                        <span class="of-row__t">{{ v.name || 'Sans nom' }}</span>
                        <span class="of-row__s">{{ v.email }}</span>
                    </span>
                    <span class="vi__tel of-num">{{ v.telephone?.lisible ?? '—' }}</span>
                    <span class="vi__depuis">compte ouvert {{ ilYA(v.createdAt) }}</span>
                    <Link v-if="v.bookings" :href="`/reservations?filtre=toutes&q=${encodeURIComponent(v.email)}`" class="vi__sejours">
                        <span class="of-num">{{ v.bookings }}</span> séjour{{ v.bookings > 1 ? 's' : '' }}
                    </Link>
                    <span v-else class="vi__sejours vi__sejours--zero">Aucun séjour</span>
                </li>
            </ul>
            <p v-else class="of-vide"><strong>Aucun compte.</strong>{{ filtre.q ? 'Essayez une partie de l’adresse.' : '' }}</p>
        </section>

        <OfficePager :meta="voyageurs.meta" unite="compte" />
    </div>
</template>

<style scoped>
.vi__row { grid-template-columns: minmax(0, 1fr) 10rem 10rem 7.5rem; }
.vi__txt { min-width: 0; }
.vi__tel { font-size: .84rem; color: var(--ink); }
.vi__depuis { font-size: .78rem; color: var(--text-3); }

/* Le compte de séjours est un lien bordé : il mène aux réservations filtrées. */
.vi__sejours {
    justify-self: end;
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    min-height: 2.2rem;
    padding: .2rem .8rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    font-size: .8rem;
    font-weight: 700;
    color: var(--ink);
    text-decoration: none;
    white-space: nowrap;
}
a.vi__sejours:hover { border-color: var(--ink); }
a.vi__sejours:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }
.vi__sejours--zero { border-style: dashed; font-weight: 500; color: var(--text-3); }

@media (max-width: 820px) {
    .vi__row { grid-template-columns: minmax(0, 1fr) auto; }
    .vi__tel, .vi__depuis { grid-column: 1; }
    .vi__sejours { grid-column: 2; grid-row: 1; }
}
</style>
