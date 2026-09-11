<script setup>
/**
 * `/demande` — le sens inverse : le voyageur décrit le séjour qu'il cherche,
 * l'équipe va le chercher chez les propriétaires.
 *
 * C'est la promesse de la section « Vous ne trouvez pas ? » de l'accueil, dont
 * les quatre boutons « Déposer une demande » pointaient sur eux-mêmes : il n'y
 * avait rien derrière.
 *
 * - **La recherche en cours arrive avec le clic** : destination, dates et
 *   voyageurs voyagent dans l'adresse, et le même état que le moteur les tient
 *   (`useSearchQuery`) — même calendrier, même consigne numérotée.
 * - **On dit ce qui existe déjà.** Si des logements correspondent à ces
 *   critères, la page le dit et y mène : envoyer une demande pour trouver ce
 *   qu'on a sous les yeux ferait attendre quelqu'un pour rien.
 * - **Seul ce qui permet de répondre est obligatoire** : un nom, et WhatsApp ou
 *   un e-mail. « Je ne sais pas encore » est une réponse valable pour tout le
 *   reste.
 * - **Rien n'est réservé et rien n'est dû**, et la page l'écrit avant et après
 *   l'envoi : c'est une recherche, pas une réservation.
 */
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

import SiteFooter from '@/Components/SiteFooter.vue'
import SiteHeader from '@/Components/SiteHeader.vue'
import StayCalendar from '@/Components/StayCalendar.vue'
import { useDevise } from '@/Composables/useDevise.js'
import { useSearchQuery } from '@/Composables/useSearchQuery.js'
import { formatCompact } from '@/Composables/useStayDates.js'

const props = defineProps({
    destinations: { type: Array, required: true },
    initial: { type: Object, required: true },
    envoyee: { type: Object, default: null },
})

const recherche = useSearchQuery({
    destination: props.destinations.some((d) => d.value === props.initial.destination) ? props.initial.destination : '',
    guests: props.initial.guests,
    arrival: props.initial.arrival,
    departure: props.initial.departure,
})
const { destination, guests, dates, calendrier, criteres, total } = recherche

const form = useForm({
    place: '',
    budget: '',
    name: props.initial.name ?? '',
    phone: props.initial.phone ?? '',
    email: props.initial.email ?? '',
    message: '',
    site: '',
})

const envoyer = () => form
    .transform((x) => ({
        ...x,
        destination: destination.value || null,
        guests: guests.value,
        arrival: dates.arrivee.value && dates.depart.value ? dates.arrivee.value : null,
        departure: dates.arrivee.value && dates.depart.value ? dates.depart.value : null,
        budget: x.budget || null,
    }))
    .post('/demande', { preserveScroll: true })

const { euros } = useDevise()
const budgetEuros = computed(() => {
    const n = Number(String(form.budget).replace(/\D+/g, ''))
    return n >= 10000 ? euros(n) : null
})

const sejour = computed(() => (dates.arrivee.value && dates.depart.value
    ? `${formatCompact(dates.arrivee.value)} → ${formatCompact(dates.depart.value)}, ${dates.nuits.value} nuit${dates.nuits.value > 1 ? 's' : ''}`
    : null))

// Ce qui existe déjà pour ces critères : le catalogue, filtré.
const catalogue = computed(() => {
    const p = new URLSearchParams(Object.entries(criteres.value).filter(([, v]) => v !== undefined && v !== ''))
    return `/logements${p.toString() ? `?${p}` : ''}`
})

const joignable = computed(() => form.name.trim().length >= 2 && (form.phone.trim() || form.email.trim()))
const VOYAGEURS = Array.from({ length: 16 }, (_, i) => i + 1)
</script>

<template>
    <Head title="Déposer une demande de séjour" />

    <div class="page">
        <SiteHeader :search="false" />

        <main class="dm">
            <div class="shell dm__shell">
                <!-- ── Après l'envoi ─────────────────────────────────── -->
                <section v-if="envoyee" class="dm__fait" aria-live="polite">
                    <span class="dm__coche" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5" /></svg>
                    </span>
                    <p class="eyebrow">Demande reçue</p>
                    <h1 class="display display--md dm__titre">Merci, {{ envoyee.nom }}. On cherche.</h1>
                    <p class="lede dm__lede">
                        <template v-if="envoyee.canal === 'whatsapp'">Nous vous écrivons sur WhatsApp, au <strong class="num">{{ envoyee.contact }}</strong>,</template>
                        <template v-else>Nous vous écrivons à <strong>{{ envoyee.contact }}</strong>,</template>
                        dès que des propriétaires de la zone ont répondu — et seulement avec ce que nous avons vérifié.
                    </p>
                    <p class="dm__rien">Rien n'est réservé et rien n'est dû : c'est une recherche, pas une réservation.</p>
                    <div class="dm__apres">
                        <Link href="/logements" class="btn btn--terre">Parcourir les logements en attendant</Link>
                        <Link href="/" class="btn btn--outline">Retour à l'accueil</Link>
                    </div>
                </section>

                <!-- ── Le formulaire ──────────────────────────────────── -->
                <template v-else>
                    <header class="dm__tete">
                        <p class="eyebrow">Le sens inverse</p>
                        <h1 class="display display--md dm__titre">Décrivez le séjour que vous cherchez</h1>
                        <p class="lede dm__lede">
                            Nous le cherchons auprès des propriétaires de la zone, nous vérifions ce qui remonte, et nous vous répondons —
                            sur WhatsApp de préférence. <strong>Sans compte, et sans engagement.</strong>
                        </p>
                    </header>

                    <form class="dm__form" novalidate @submit.prevent="envoyer">
                        <!-- 1. Où -->
                        <section class="dm__bloc">
                            <h2 class="dm__h2"><span class="dm__n num">1</span> Où</h2>
                            <div class="dm__grille">
                                <label class="dm__champ">
                                    <span class="dm__label">Destination</span>
                                    <select v-model="destination">
                                        <option value="">Je ne sais pas encore, ou ailleurs</option>
                                        <option v-for="d in destinations" :key="d.value" :value="d.value">{{ d.label }} — {{ d.detail }}</option>
                                    </select>
                                    <span v-if="form.errors.destination" class="dm__err">{{ form.errors.destination }}</span>
                                </label>
                                <label class="dm__champ">
                                    <span class="dm__label">Précisez <span class="dm__opt">facultatif</span></span>
                                    <input v-model="form.place" type="text" maxlength="120" placeholder="Un village, une plage, « près de l’aéroport »…">
                                </label>
                            </div>
                        </section>

                        <!-- 2. Quand -->
                        <section class="dm__bloc">
                            <h2 class="dm__h2"><span class="dm__n num">2</span> Quand <span class="dm__opt">facultatif</span></h2>
                            <p class="dm__aide">Laissez vide si vos dates ne sont pas fixées : nous en parlerons avec vous.</p>
                            <div class="dm__cal">
                                <StayCalendar :dates="dates" :calendar="calendrier" :months="2" />
                            </div>
                            <p v-if="sejour" class="dm__sejour">{{ sejour }}</p>
                            <span v-if="form.errors.arrival || form.errors.departure" class="dm__err">{{ form.errors.arrival || form.errors.departure }}</span>
                        </section>

                        <!-- 3. Qui et combien -->
                        <section class="dm__bloc">
                            <h2 class="dm__h2"><span class="dm__n num">3</span> Qui, et pour quel budget</h2>
                            <div class="dm__grille">
                                <label class="dm__champ">
                                    <span class="dm__label">Voyageurs</span>
                                    <select v-model.number="guests">
                                        <option v-for="n in VOYAGEURS" :key="n" :value="n">{{ n }} voyageur{{ n > 1 ? 's' : '' }}</option>
                                    </select>
                                </label>
                                <label class="dm__champ">
                                    <span class="dm__label">Budget par nuit, en ariary <span class="dm__opt">facultatif</span></span>
                                    <input v-model="form.budget" type="text" inputmode="numeric" maxlength="12" placeholder="150 000">
                                    <span v-if="budgetEuros" class="eur">{{ budgetEuros }} par nuit</span>
                                    <span v-if="form.errors.budget" class="dm__err">{{ form.errors.budget }}</span>
                                </label>
                            </div>
                        </section>

                        <!-- Ce qui existe déjà : on ne fait pas attendre pour ce qu'on a. -->
                        <p v-if="total" class="dm__deja">
                            <strong>{{ total }} logement{{ total > 1 ? 's' : '' }}</strong> correspond{{ total > 1 ? 'ent' : '' }} déjà à ces critères.
                            <Link :href="catalogue" class="dm__deja-lien">Les voir</Link>
                        </p>

                        <!-- 4. Vous -->
                        <section class="dm__bloc">
                            <h2 class="dm__h2"><span class="dm__n num">4</span> Pour vous répondre</h2>
                            <div class="dm__grille">
                                <label class="dm__champ">
                                    <span class="dm__label">Votre nom</span>
                                    <input v-model="form.name" type="text" autocomplete="name" maxlength="120" required>
                                    <span v-if="form.errors.name" class="dm__err">{{ form.errors.name }}</span>
                                </label>
                                <label class="dm__champ">
                                    <span class="dm__label">Numéro WhatsApp</span>
                                    <input v-model="form.phone" type="tel" autocomplete="tel" inputmode="tel" maxlength="40" placeholder="034 12 345 67 ou +33 6…">
                                    <span v-if="form.errors.phone" class="dm__err">{{ form.errors.phone }}</span>
                                </label>
                                <label class="dm__champ">
                                    <span class="dm__label">Ou adresse e-mail</span>
                                    <input v-model="form.email" type="email" autocomplete="email" maxlength="190">
                                    <span v-if="form.errors.email && !form.errors.phone" class="dm__err">{{ form.errors.email }}</span>
                                </label>
                            </div>
                            <p class="dm__aide">L'un des deux suffit : c'est par là que nous vous répondrons, pour cette demande seulement.</p>

                            <label class="dm__champ dm__champ--large">
                                <span class="dm__label">Ce qui compte pour vous <span class="dm__opt">facultatif</span></span>
                                <textarea v-model="form.message" rows="4" maxlength="1500" placeholder="Une piscine, le calme, un groupe électrogène, près d’une école de plongée…" />
                            </label>

                            <!-- Un champ piège : invisible, un robot le remplit. -->
                            <label class="dm__piege" aria-hidden="true">Site<input v-model="form.site" type="text" tabindex="-1" autocomplete="off"></label>
                        </section>

                        <button type="submit" class="btn btn--terre btn--lg dm__envoyer" :disabled="!joignable || form.processing">
                            {{ form.processing ? 'Envoi…' : 'Envoyer ma demande' }}
                        </button>
                        <p v-if="!joignable" class="dm__manque">Il faut votre nom, et un numéro WhatsApp ou une adresse e-mail.</p>
                        <p class="dm__note">Rien n'est réservé et rien n'est dû : c'est une recherche, pas une réservation.</p>
                    </form>
                </template>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>

<style scoped>
.dm {
    padding-top: calc(var(--header-h) + clamp(1.5rem, 3.5vw, 2.75rem));
    padding-bottom: clamp(4rem, 8vw, 7rem);
}
.dm__shell { max-width: 52rem; }
.dm__titre { margin: .6rem 0 0; }
.dm__lede { max-width: 42rem; margin: .9rem 0 0; }
.dm__lede strong { color: var(--ink); }

.dm__form { margin-top: clamp(2rem, 4vw, 2.75rem); }
.dm__bloc { padding-bottom: clamp(1.6rem, 3vw, 2.25rem); margin-bottom: clamp(1.6rem, 3vw, 2.25rem); border-bottom: 1px solid var(--line); }
.dm__h2 { display: flex; align-items: center; gap: .6rem; margin: 0 0 1.1rem; font-size: 1.15rem; font-weight: 800; letter-spacing: -.03em; color: var(--ink); }
.dm__n { display: grid; place-items: center; width: 1.8rem; height: 1.8rem; border: 1.5px solid var(--ink); border-radius: 50%; font-size: .85rem; font-weight: 800; }
.dm__grille { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 15rem), 1fr)); gap: 1.1rem 1.25rem; }
.dm__champ { display: flex; flex-direction: column; gap: .35rem; }
.dm__champ--large { margin-top: 1.1rem; }
.dm__label { font-size: .74rem; font-weight: 700; letter-spacing: .11em; text-transform: uppercase; color: var(--text-3); }
.dm__opt { font-size: .74rem; font-weight: 500; letter-spacing: 0; text-transform: none; color: var(--text-3); }
.dm__h2 .dm__opt { font-size: .82rem; }
.dm__champ input,
.dm__champ select,
.dm__champ textarea {
    width: 100%;
    min-height: 2.75rem;
    padding: .65rem .85rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--white);
    font: inherit;
    font-size: .95rem;
    color: var(--ink);
}
.dm__champ textarea { resize: vertical; }
.dm__champ input:focus-visible,
.dm__champ select:focus-visible,
.dm__champ textarea:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 1px; }
.dm__aide { margin: 0 0 .9rem; font-size: .84rem; line-height: 1.5; color: var(--text-2); }
.dm__bloc .dm__grille + .dm__aide { margin: .8rem 0 0; }
.dm__cal { max-width: 40rem; }
.dm__sejour { margin: .8rem 0 0; font-size: .9rem; font-weight: 700; color: var(--ink); }
.dm__err { font-size: .8rem; font-weight: 600; color: var(--terre-700); }

.dm__deja {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .4rem .9rem;
    margin: 0 0 clamp(1.6rem, 3vw, 2.25rem);
    padding: .9rem 1.1rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--off);
    font-size: .9rem;
    color: var(--text-2);
}
.dm__deja strong { color: var(--ink); }
.dm__deja-lien {
    display: inline-flex;
    align-items: center;
    min-height: 2.5rem;
    padding: 0 1rem;
    border: 1px solid var(--ink);
    border-radius: var(--r-pill);
    background: var(--white);
    font-weight: 700;
    color: var(--ink);
    text-decoration: none;
}
.dm__deja-lien:hover { background: var(--ink); color: var(--white); }

.dm__piege { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
.dm__envoyer { width: 100%; justify-content: center; }
.dm__manque { margin: .7rem 0 0; text-align: center; font-size: .84rem; font-weight: 600; color: var(--text-2); }
.dm__note { margin: .5rem 0 0; text-align: center; font-size: .8rem; color: var(--text-3); }

.dm__fait { max-width: 42rem; }
.dm__coche { display: grid; place-items: center; width: 3.25rem; height: 3.25rem; margin-bottom: 1.1rem; border: 1.5px solid var(--ink); border-radius: 50%; color: var(--ink); }
.dm__coche svg { width: 1.5rem; height: 1.5rem; }
.dm__rien { margin: 1rem 0 0; font-size: .88rem; color: var(--text-2); }
.dm__apres { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1.6rem; }
</style>
