<script setup>
/**
 * Réserver — l'écran qui remplace le lien mort « Demander un séjour ».
 *
 * **Aucun champ de paiement, et c'est le sujet.** Vayla ne demande ni carte,
 * ni acompte, ni compte à créer : la réservation met en relation et bloque
 * les dates, le reste se convient entre le voyageur et le propriétaire. Un
 * champ de paiement ici serait à la fois inutile et un risque que le projet
 * a explicitement décidé de ne pas porter à Madagascar.
 *
 * L'écran dit **exactement ce qui va se passer** — le propriétaire a 48 h,
 * les dates sont tenues jusque-là, et rien n'est prélevé. Une réservation
 * sans paiement inquiète si on ne l'explique pas : le voyageur se demande
 * ce qu'il vient d'engager.
 */
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

import SiteHeader from '@/Components/SiteHeader.vue'
import SiteFooter from '@/Components/SiteFooter.vue'
import SceneArt from '@/Components/SceneArt.vue'
import TrustGauge from '@/Components/TrustGauge.vue'
import StayCalendar from '@/Components/StayCalendar.vue'
import AmenityIcon from '@/Components/AmenityIcon.vue'
import { photoSrc, photoSrcset } from '@/Support/photo.js'

import { useStayDates, formatLong } from '@/Composables/useStayDates.js'
import { nombre } from '@/Support/format.js'
import { useDevise } from '@/Composables/useDevise.js'

const props = defineProps({
    listing: { type: Object, required: true },
    calendar: { type: Object, required: true },
    rules: { type: Object, required: true },
    prefill: { type: Object, default: () => ({}) },
    holdHours: { type: Number, default: 48 },
    photos: { type: Object, default: () => ({}) },
    credits: { type: Array, default: () => [] },
})

const calendrier = computed(() => props.calendar)
const dates = useStayDates(calendrier)

// Les dates arrivent de la fiche par l'URL : le voyageur ne les ressaisit pas.
if (props.prefill.arrival) dates.arrivee.value = props.prefill.arrival
if (props.prefill.departure) dates.depart.value = props.prefill.departure

const form = useForm({
    traveller: '',
    traveller_phone: '',
    traveller_email: '',
    guests: props.prefill.guests ?? 2,
    arrival: props.prefill.arrival ?? '',
    departure: props.prefill.departure ?? '',
    message: '',
})

const money = nombre

// L'euro en second : le total « à régler sur place » est le chiffre qu'un
// voyageur étranger doit pouvoir évaluer avant de s'engager.
const { euros, mention } = useDevise()

const total = computed(() => dates.nuits.value * props.listing.price)

function envoyer() {
    form.arrival = dates.arrivee.value ?? ''
    form.departure = dates.depart.value ?? ''
    form.post(`/logements/${props.listing.slug}/reserver`, { preserveScroll: true })
}
</script>

<template>
    <Head :title="`Réserver — ${listing.title}`" />

    <div class="page">
        <SiteHeader :search="false" />

        <main class="bk">
            <div class="shell">
                <nav class="bk__crumbs" aria-label="Fil d'Ariane">
                    <Link :href="`/logements/${listing.slug}`" class="bk__crumb">{{ listing.title }}</Link>
                    <span aria-hidden="true">·</span>
                    <span>Réserver</span>
                </nav>

                <h1 class="display display--md bk__title">Demander ces dates</h1>

                <div class="bk__body">
                    <!-- ── Le formulaire ── -->
                    <form class="bk__form" @submit.prevent="envoyer">
                        <section class="bk__block">
                            <h2 class="bk__h2">Vos dates</h2>

                            <div class="bk__cal">
                                <StayCalendar :dates="dates" :calendar="calendar" :months="2" />
                            </div>

                            <p v-if="dates.erreur.value" class="bk__error">{{ dates.erreur.value }}</p>
                            <p v-if="form.errors.arrival" class="bk__error">{{ form.errors.arrival }}</p>
                        </section>

                        <section class="bk__block">
                            <h2 class="bk__h2">Vous</h2>

                            <div class="bk__grid">
                                <label class="bk__field">
                                    <span class="bk__label">Nom</span>
                                    <input v-model="form.traveller" type="text" autocomplete="name" required>
                                    <span v-if="form.errors.traveller" class="bk__hint bk__hint--err">{{ form.errors.traveller }}</span>
                                </label>

                                <label class="bk__field">
                                    <span class="bk__label">Téléphone</span>
                                    <input v-model="form.traveller_phone" type="tel" autocomplete="tel" required
                                           placeholder="+261 34 …">
                                    <span class="bk__hint">C'est par là que le propriétaire vous répondra.</span>
                                    <span v-if="form.errors.traveller_phone" class="bk__hint bk__hint--err">{{ form.errors.traveller_phone }}</span>
                                </label>

                                <label class="bk__field">
                                    <span class="bk__label">E-mail <span class="bk__opt">facultatif</span></span>
                                    <input v-model="form.traveller_email" type="email" autocomplete="email">
                                    <span v-if="form.errors.traveller_email" class="bk__hint bk__hint--err">{{ form.errors.traveller_email }}</span>
                                </label>

                                <label class="bk__field bk__field--short">
                                    <span class="bk__label">Voyageurs</span>
                                    <input v-model.number="form.guests" class="num" type="number" min="1"
                                           :max="rules.guests" required>
                                    <span class="bk__hint num">{{ rules.guests }} maximum</span>
                                </label>
                            </div>

                            <label class="bk__field bk__field--wide">
                                <span class="bk__label">Un mot pour le propriétaire <span class="bk__opt">facultatif</span></span>
                                <textarea v-model="form.message" rows="4"
                                          placeholder="Heure d'arrivée prévue, questions, besoins particuliers…"></textarea>
                            </label>
                        </section>

                        <button
                            type="submit"
                            class="btn btn--terre btn--lg bk__submit"
                            :disabled="form.processing || !dates.complet.value"
                        >
                            {{ form.processing ? 'Envoi…' : 'Envoyer la demande' }}
                        </button>

                        <p v-if="!dates.complet.value" class="bk__note">
                            Choisissez vos dates pour continuer.
                        </p>
                    </form>

                    <!-- ── Le récapitulatif ── -->
                    <aside class="bk__aside">
                        <div class="bk__card">
                            <div class="bk__media">
                                <img
                                    v-if="listing.photo"
                                    :src="photoSrc(photos[listing.photo], 800)"
                                    :srcset="photoSrcset(photos[listing.photo])"
                                    sizes="(min-width: 1000px) 340px, 92vw"
                                    :alt="photos[listing.photo]?.caption ?? listing.title"
                                    width="800" height="600" loading="eager" decoding="async"
                                >
                                <SceneArt v-else :variant="listing.scene" />
                            </div>

                            <h2 class="bk__name">{{ listing.title }}</h2>
                            <p class="bk__place">{{ listing.place }} · {{ listing.region }}</p>

                            <p class="bk__trust">
                                <TrustGauge :level="listing.trust" />
                                <span>{{ listing.perks[0] ?? '' }}</span>
                            </p>

                            <dl v-if="dates.complet.value" class="bk__detail">
                                <div>
                                    <dt>Arrivée</dt>
                                    <dd>{{ formatLong(dates.arrivee.value) }}</dd>
                                </div>
                                <div>
                                    <dt>Départ</dt>
                                    <dd>{{ formatLong(dates.depart.value) }}</dd>
                                </div>
                                <div>
                                    <dt>{{ money(listing.price) }} Ar × {{ dates.nuits.value }} nuits</dt>
                                    <dd class="num">{{ money(total) }} Ar</dd>
                                </div>
                                <div class="bk__detail-total">
                                    <dt>À régler sur place</dt>
                                    <dd class="num">
                                        {{ money(total) }} Ar
                                        <span v-if="euros(total)" class="eur">{{ euros(total) }}</span>
                                    </dd>
                                </div>
                            </dl>

                            <p v-if="dates.complet.value && mention" class="bk__rate">{{ mention }}</p>
                        </div>

                        <!-- Une réservation sans paiement inquiète si on ne
                             l'explique pas : on dit exactement la suite. -->
                        <ol class="bk__steps">
                            <li>
                                <span class="bk__n num">1</span>
                                <span>
                                    <strong>Vos dates sont tenues {{ holdHours }} h.</strong>
                                    Personne d'autre ne peut les réserver pendant ce temps.
                                </span>
                            </li>
                            <li>
                                <span class="bk__n num">2</span>
                                <span>
                                    <strong>Le propriétaire est prévenu tout de suite</strong>
                                    et vous répond directement, par téléphone.
                                </span>
                            </li>
                            <li>
                                <span class="bk__n num">3</span>
                                <span>
                                    <strong>Vous convenez ensemble</strong> de l'acompte —
                                    s'il en demande un — et vous réglez sur place.
                                </span>
                            </li>
                        </ol>

                        <p class="bk__nofee">
                            <AmenityIcon name="shield" />
                            Vayla ne demande ni carte, ni acompte, ni compte à créer.
                            Aucun montant n'est prélevé ici.
                        </p>
                    </aside>
                </div>
            </div>
        </main>

        <SiteFooter :credits="credits" />
    </div>
</template>

<style scoped>
.page { overflow-x: clip; }

.bk {
    padding-top: calc(var(--header-h) + clamp(1.25rem, 3vw, 2.25rem));
    padding-bottom: clamp(4rem, 8vw, 7rem);
}

.bk__crumbs {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
    font-size: .82rem;
    font-weight: 600;
    color: var(--text-3);
}
.bk__crumb { color: var(--text-2); text-decoration: none; }
.bk__crumb:hover { color: var(--terre-600); text-decoration: underline; }

.bk__title { margin: .9rem 0 0; }

.bk__body {
    display: grid;
    grid-template-columns: 1fr;
    gap: clamp(2.25rem, 5vw, 3.5rem);
    margin-top: clamp(2rem, 4vw, 3rem);
}

.bk__block {
    padding-bottom: clamp(1.75rem, 3.5vw, 2.5rem);
    margin-bottom: clamp(1.75rem, 3.5vw, 2.5rem);
    border-bottom: 1px solid var(--line);
}

.bk__h2 {
    margin: 0 0 1.25rem;
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: -.03em;
    color: var(--ink);
}

.bk__grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.1rem 1.25rem;
}

.bk__field { display: flex; flex-direction: column; gap: .35rem; }
.bk__field--wide { margin-top: 1.1rem; }

.bk__label {
    font-size: .74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .11em;
    color: var(--text-3);
}
.bk__opt { text-transform: none; letter-spacing: 0; font-weight: 500; color: var(--line-dark); color: var(--text-3); opacity: .8; }

.bk__field input,
.bk__field textarea {
    width: 100%;
    padding: .7rem .85rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--white);
    font: inherit;
    font-size: .92rem;
    color: var(--ink);
    resize: vertical;
}
.bk__field input:focus-visible,
.bk__field textarea:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 1px; }

.bk__hint { font-size: .78rem; color: var(--text-3); }
.bk__hint--err { color: var(--terre-600); font-weight: 600; }

.bk__error {
    margin: 1rem 0 0;
    padding: .7rem .9rem;
    border-radius: var(--r-sm);
    background: var(--terre-050);
    font-size: .85rem;
    font-weight: 600;
    color: var(--terre-700);
}

.bk__submit { width: 100%; justify-content: center; }
.bk__submit:disabled { opacity: .45; cursor: not-allowed; }

.bk__note { margin: .7rem 0 0; text-align: center; font-size: .8rem; color: var(--text-3); }

/* ── Récapitulatif ─────────────────────────────────────────────── */
.bk__aside { display: flex; flex-direction: column; gap: 1.25rem; }

.bk__card {
    padding: clamp(1.2rem, 3vw, 1.5rem);
    border: 1px solid var(--line);
    border-radius: var(--r-lg);
    background: var(--white);
    box-shadow: var(--sh-1);
}

.bk__media {
    aspect-ratio: 4 / 3;
    overflow: hidden;
    border-radius: var(--r-md);
    background: var(--off-2);
}
.bk__media img, .bk__media :deep(svg) { display: block; width: 100%; height: 100%; object-fit: cover; }

.bk__name {
    margin: .9rem 0 0;
    font-size: 1.02rem;
    font-weight: 700;
    letter-spacing: -.022em;
    color: var(--ink);
}
.bk__place { margin: .2rem 0 0; font-size: .85rem; color: var(--text-3); }

.bk__trust {
    display: flex;
    align-items: center;
    gap: .55rem;
    margin: .9rem 0 0;
    font-size: .82rem;
    color: var(--text-2);
}

.bk__detail {
    margin: 1.1rem 0 0;
    padding-top: .9rem;
    border-top: 1px solid var(--line);
    font-size: .86rem;
}
.bk__detail > div { display: flex; justify-content: space-between; gap: 1rem; margin-bottom: .45rem; }
.bk__detail dt { color: var(--text-2); }
.bk__detail dd { margin: 0; color: var(--text-2); font-weight: 600; }

.bk__detail-total {
    margin-top: .7rem !important;
    padding-top: .7rem;
    border-top: 1px solid var(--line);
    font-weight: 800;
}
.bk__detail-total dt, .bk__detail-total dd { color: var(--ink) !important; }

.bk__steps {
    display: flex;
    flex-direction: column;
    gap: .8rem;
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: .85rem;
    line-height: 1.55;
    color: var(--text-2);
}
.bk__steps li { display: flex; align-items: flex-start; gap: .7rem; }
.bk__steps strong { color: var(--ink); }

.bk__n {
    display: grid;
    place-items: center;
    flex: none;
    width: 1.4rem;
    height: 1.4rem;
    border-radius: 50%;
    background: var(--ink);
    color: var(--white);
    font-size: .72rem;
    font-weight: 700;
}

.bk__nofee {
    display: flex;
    align-items: flex-start;
    gap: .55rem;
    margin: 0;
    padding: .9rem 1.05rem;
    border-radius: var(--r-md);
    background: var(--off-2);
    font-size: .82rem;
    line-height: 1.55;
    color: var(--text-2);
}
.bk__nofee :deep(.ai) { margin-top: .1rem; flex: none; color: var(--lagon-600); }

@media (min-width: 620px) {
    .bk__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .bk__field--short { max-width: 10rem; }
}

@media (min-width: 1000px) {
    .bk__body { grid-template-columns: minmax(0, 1fr) 22rem; }
    .bk__aside { position: sticky; top: calc(var(--header-h) + 1.5rem); align-self: start; }
}
.bk__rate {
    margin: .9rem 0 0;
    padding-top: .8rem;
    border-top: 1px solid var(--line);
    font-size: .74rem;
    line-height: 1.5;
    color: var(--text-3);
}
</style>
