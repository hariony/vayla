<script setup>
/**
 * Les réservations du voyageur connecté.
 *
 * **C'est tout ce que le compte apporte, et c'est déjà ce qui manquait le
 * plus** : sans lui, un voyageur qui avait perdu sa référence n'avait aucun
 * chemin vers son séjour. Le compte ne conditionne toujours rien — la demande
 * de séjour reste ouverte sans, et la référence continue d'ouvrir une
 * réservation.
 *
 * **Les séjours se rattachent par l'adresse e-mail.** Celui qui a réservé
 * avant de créer son compte les retrouve dès l'inscription, sans qu'on recolle
 * quoi que ce soit à la main. C'est aussi pourquoi l'écran dit à quelle
 * adresse il regarde : un séjour réservé avec une autre adresse n'apparaîtra
 * pas, et il faut pouvoir le comprendre sans appeler.
 */
import { computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'

import SiteHeader from '@/Components/SiteHeader.vue'
import SiteFooter from '@/Components/SiteFooter.vue'
import { nombre } from '@/Support/format.js'
import { formatLong } from '@/Composables/useStayDates.js'

const props = defineProps({
    traveller: { type: Object, required: true },
    bookings: { type: Array, default: () => [] },
})

const page = usePage()
const flash = computed(() => page.props.flash ?? {})

const sortir = () => router.post('/deconnexion')

/**
 * **Le nom peut ne pas exister**, et l'écran ne doit pas s'en apercevoir
 * bruyamment. Il n'est plus demandé à l'inscription — il arrive avec la
 * première demande de séjour, où il sert. `traveller.name.split(...)` levait
 * sur un compte neuf, c'est-à-dire sur tous ceux ouverts depuis la porte à
 * une seule adresse.
 */
const accueil = computed(() => {
    const prenom = props.traveller.name?.trim().split(' ')[0]

    return prenom ? `Bonjour ${prenom}` : 'Vos séjours'
})
</script>

<template>
    <Head title="Mes réservations — Vayla" />

    <SiteHeader />

    <main class="mb">
        <div class="shell">
            <header class="mb__head">
                <div>
                    <p class="eyebrow">Mon compte</p>
                    <h1 class="display display--lg mb__title">{{ accueil }}</h1>
                    <p class="mb__sub">{{ traveller.email }}</p>
                </div>
                <button type="button" class="btn btn--outline mb__out" @click="sortir">Se déconnecter</button>
            </header>

            <p v-if="flash.succes" class="mb__ok" role="status">{{ flash.succes }}</p>

            <div v-if="!bookings.length" class="mb__empty">
                <p class="mb__empty-t">Aucune réservation pour l'instant.</p>
                <p class="mb__empty-s">
                    Les séjours demandés avec l'adresse <strong>{{ traveller.email }}</strong>
                    apparaîtront ici. Si vous avez réservé avec une autre adresse,
                    ouvrez votre réservation avec sa référence.
                </p>
                <Link href="/logements" class="btn btn--terre btn--lg">Chercher un logement</Link>
            </div>

            <ul v-else class="mb__list">
                <li v-for="b in bookings" :key="b.reference" class="mb__row">
                    <div class="mb__main">
                        <p class="mb__when">
                            {{ formatLong(b.arrival) }} → {{ formatLong(b.departure) }}
                        </p>
                        <p class="mb__listing">{{ b.listing }}<template v-if="b.place"> · {{ b.place }}</template></p>
                        <p class="mb__facts num">
                            {{ b.nights }} nuit{{ b.nights > 1 ? 's' : '' }} ·
                            {{ b.guests }} pers. · {{ nombre(b.total) }} Ar
                        </p>
                    </div>

                    <div class="mb__side">
                        <span class="mb__state" :class="`mb__state--${b.status}`">{{ b.statusLabel }}</span>
                        <Link :href="`/reservations/${b.reference}`" class="btn btn--outline mb__open">
                            Ouvrir
                        </Link>
                        <span class="mb__ref num">{{ b.reference }}</span>
                    </div>
                </li>
            </ul>
        </div>
    </main>

    <SiteFooter />
</template>

<style scoped>
.mb { padding-block: calc(var(--header-h) + clamp(2rem, 5vw, 3.5rem)) clamp(3rem, 8vw, 6rem); }

.mb__head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.75rem;
}
.mb__title { margin: .35rem 0 0; }
.mb__sub { margin: .3rem 0 0; font-size: .92rem; color: var(--text-3); }
.mb__out { flex: none; }

.mb__ok {
    margin: 0 0 1.5rem;
    padding: .85rem 1.1rem;
    border-radius: var(--r-md);
    background: var(--ink);
    font-size: .92rem;
    font-weight: 600;
    color: var(--white);
}

.mb__empty {
    padding: clamp(2rem, 6vw, 3.5rem);
    border: 1px dashed var(--line-2);
    border-radius: var(--r-lg);
    text-align: center;
    background: var(--white);
}
.mb__empty-t { margin: 0; font-size: 1.15rem; font-weight: 800; letter-spacing: -.025em; color: var(--ink); }
.mb__empty-s { margin: .5rem auto 1.5rem; max-width: 48ch; font-size: .92rem; line-height: 1.6; color: var(--text-2); }
.mb__empty-s strong { color: var(--ink); }

.mb__list { display: grid; gap: .7rem; margin: 0; padding: 0; list-style: none; }

.mb__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .9rem 1.25rem;
    padding: 1.1rem 1.25rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
}

.mb__main { min-width: 0; }
.mb__when { margin: 0; font-size: 1.02rem; font-weight: 800; letter-spacing: -.025em; color: var(--ink); }
.mb__listing { margin: .2rem 0 0; font-size: .92rem; color: var(--text-2); }
.mb__facts { margin: .25rem 0 0; font-size: .82rem; color: var(--text-3); }

.mb__side { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; }

/* Neutre : la terre porte l'action, le lagon ne dit que « vérifié ». Un état
   de réservation n'est ni l'un ni l'autre. */
.mb__state {
    padding: .28rem .8rem;
    border-radius: var(--r-pill);
    background: var(--off-2);
    font-size: .74rem;
    font-weight: 800;
    color: var(--text-2);
}
.mb__state--accepted, .mb__state--completed { background: var(--ink); color: var(--white); }

.mb__ref { font-size: .74rem; color: var(--text-3); }
</style>
