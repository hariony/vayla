<script setup>
/**
 * La demande est partie.
 *
 * Cet écran a un seul travail : **dire ce qui va se passer et donner la
 * référence**. Sur une plateforme qui n'encaisse rien, le voyageur vient de
 * remplir un formulaire sans rien payer — il a besoin de savoir qu'il s'est
 * passé quelque chose, sinon il recommence ailleurs dans la minute.
 *
 * La référence est en gros, sélectionnable, et le texte dit à quoi elle sert :
 * c'est ce que le voyageur et le propriétaire vont s'échanger sur WhatsApp.
 */
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

import SiteHeader from '@/Components/SiteHeader.vue'
import SiteFooter from '@/Components/SiteFooter.vue'
import AmenityIcon from '@/Components/AmenityIcon.vue'
import { formatLong } from '@/Composables/useStayDates.js'
import { nombre } from '@/Support/format.js'
import Conversation from '@/Components/Conversation.vue'
import { useDevise } from '@/Composables/useDevise.js'

const props = defineProps({
    booking: { type: Object, required: true },
    holdHours: { type: Number, default: 48 },
    photos: { type: Object, default: () => ({}) },
    credits: { type: Array, default: () => [] },
    messages: { type: Array, default: () => [] },
})

const money = nombre

const { euros, mention } = useDevise()
const b = computed(() => props.booking)
</script>

<template>
    <Head :title="`Demande ${b.reference} envoyée`" />

    <div class="page">
        <SiteHeader :search="false" />

        <main class="cf">
            <div class="shell shell--tight">
                <div class="cf__seal" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="m4 12.5 5 5L20 6.5" />
                    </svg>
                </div>

                <h1 class="display display--md cf__title">Votre demande est partie.</h1>

                <p class="lede cf__lede">
                    Le propriétaire de <strong>{{ b.listing.title }}</strong> vient d'être
                    prévenu. Vos dates lui sont réservées pendant {{ holdHours }} heures — le
                    temps qu'il vous réponde.
                </p>

                <div class="cf__ref">
                    <p class="cf__ref-label">Votre référence</p>
                    <p class="cf__ref-code num">{{ b.reference }}</p>
                    <p class="cf__ref-note">
                        Gardez-la : c'est ce que vous et le propriétaire allez vous
                        échanger pour vous retrouver.
                    </p>
                </div>

                <dl class="cf__recap">
                    <div>
                        <dt>Logement</dt>
                        <dd>{{ b.listing.title }} — {{ b.listing.place }}</dd>
                    </div>
                    <div>
                        <dt>Séjour</dt>
                        <dd>
                            {{ formatLong(b.arrival) }} → {{ formatLong(b.departure) }}
                            <span class="num">({{ b.nights }} nuit{{ b.nights > 1 ? 's' : '' }})</span>
                        </dd>
                    </div>
                    <div>
                        <dt>Voyageurs</dt>
                        <dd class="num">{{ b.guests }}</dd>
                    </div>
                    <div>
                        <dt>À régler sur place</dt>
                        <dd class="num cf__total">
                            {{ money(b.total) }} Ar
                            <span v-if="euros(b.total)" class="eur">{{ euros(b.total) }}</span>
                        </dd>
                    </div>
                </dl>

                <p v-if="mention" class="cf__rate">{{ mention }}</p>

                <ol class="cf__steps">
                    <li>
                        <span class="cf__n num">1</span>
                        <span>
                            <strong>{{ b.ownerName ?? 'Le propriétaire' }} vous appelle</strong>
                            au numéro que vous avez laissé. Vous convenez ensemble des détails
                            et de l'acompte, s'il en demande un.
                        </span>
                    </li>
                    <li>
                        <span class="cf__n num">2</span>
                        <span>
                            <strong>Vous séjournez</strong>, et vous réglez sur place, comme
                            vous l'avez convenu.
                        </span>
                    </li>
                    <li>
                        <span class="cf__n num">3</span>
                        <span>
                            <strong>Au retour, vous confirmez le séjour</strong> en un tap.
                            C'est ce qui fait monter ce logement sur l'échelle de confiance —
                            et ce qui aide le voyageur suivant.
                        </span>
                    </li>
                </ol>

                <p class="cf__nofee">
                    <AmenityIcon name="shield" />
                    Aucun montant n'a été prélevé. Si quelqu'un vous demande de payer
                    quoi que ce soit « à Vayla », ce n'est pas nous.
                </p>

                <!-- L'échange écrit vient **après** les trois étapes : ce
                     qu'on cherche en ouvrant cette page, c'est de savoir ce
                     qui va se passer, pas d'écrire. -->
                <div class="cf__conv">
                    <Conversation
                        :messages="messages"
                        :action="`/reservations/${b.reference}/messages`"
                        destinataire="Écrire au propriétaire"
                    />
                </div>

                <div class="cf__actions">
                    <Link :href="`/logements/${b.listing.slug}`" class="btn btn--outline">
                        Revoir le logement
                    </Link>
                    <Link href="/logements" class="btn btn--ghost">Continuer à chercher</Link>
                </div>
            </div>
        </main>

        <SiteFooter :credits="credits" />
    </div>
</template>

<style scoped>
.page { overflow-x: clip; }

.cf {
    padding-top: calc(var(--header-h) + clamp(2rem, 5vw, 4rem));
    padding-bottom: clamp(4rem, 8vw, 7rem);
}

.cf__seal {
    display: grid;
    place-items: center;
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    color: var(--white);
    background: var(--lagon-500);
    box-shadow: 0 6px 20px -8px rgba(14, 144, 128, .8);
}
.cf__seal svg { width: 1.4rem; height: 1.4rem; }

.cf__title { margin: 1.4rem 0 0; }
.cf__lede { margin: 1rem 0 0; max-width: 46ch; }
.cf__lede strong { color: var(--ink); font-weight: 700; }

/* La référence : gros, sélectionnable, c'est ce qu'on recopie sur WhatsApp. */
.cf__ref {
    margin: clamp(2rem, 4vw, 2.75rem) 0 0;
    padding: clamp(1.25rem, 3vw, 1.75rem);
    border: 1px solid var(--line);
    border-radius: var(--r-lg);
    background: var(--off);
    text-align: center;
}

.cf__ref-label {
    margin: 0;
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .13em;
    color: var(--text-3);
}

.cf__ref-code {
    margin: .5rem 0 0;
    font-size: clamp(1.9rem, 5vw, 2.6rem);
    font-weight: 800;
    letter-spacing: .02em;
    color: var(--ink);
    user-select: all;
}

.cf__ref-note {
    margin: .6rem auto 0;
    max-width: 36ch;
    font-size: .82rem;
    line-height: 1.55;
    color: var(--text-3);
}

.cf__recap {
    margin: clamp(2rem, 4vw, 2.5rem) 0 0;
    padding: 1.4rem 0;
    border-top: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
    font-size: .92rem;
}
.cf__recap > div { display: flex; flex-wrap: wrap; justify-content: space-between; gap: .25rem 1.5rem; padding: .4rem 0; }
.cf__recap dt { color: var(--text-3); }
.cf__recap dd { margin: 0; font-weight: 600; color: var(--ink); text-align: right; }
.cf__total { font-size: 1.05rem; font-weight: 800; letter-spacing: -.02em; }

.cf__conv {
    margin: clamp(1.75rem, 4vw, 2.5rem) 0;
    padding: clamp(1.1rem, 3vw, 1.6rem);
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
}

.cf__steps {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin: clamp(2rem, 4vw, 2.5rem) 0 0;
    padding: 0;
    list-style: none;
    font-size: .92rem;
    line-height: 1.6;
    color: var(--text-2);
}
.cf__steps li { display: flex; align-items: flex-start; gap: .8rem; }
.cf__steps strong { color: var(--ink); }

.cf__n {
    display: grid;
    place-items: center;
    flex: none;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 50%;
    background: var(--ink);
    color: var(--white);
    font-size: .75rem;
    font-weight: 700;
}

.cf__nofee {
    display: flex;
    align-items: flex-start;
    gap: .6rem;
    margin: clamp(1.75rem, 3.5vw, 2.25rem) 0 0;
    padding: 1rem 1.15rem;
    border-radius: var(--r-md);
    background: var(--off-2);
    font-size: .86rem;
    line-height: 1.6;
    color: var(--text-2);
}
.cf__nofee :deep(.ai) { margin-top: .12rem; flex: none; color: var(--lagon-600); }

.cf__actions { display: flex; flex-wrap: wrap; gap: .7rem; margin-top: clamp(2rem, 4vw, 2.5rem); }
.cf__rate { margin: .9rem 0 0; font-size: .74rem; line-height: 1.5; color: var(--text-3); }
</style>
