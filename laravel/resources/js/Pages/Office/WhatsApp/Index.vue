<script setup>
/**
 * La file WhatsApp — ce que `php artisan vayla:whatsapp` faisait en terminal.
 *
 * **Pourquoi c'est encore à la main.** L'API WhatsApp Business de Meta exige
 * une entreprise enregistrée, que Vayla n'a pas. Chaque message est donc déjà
 * écrit, et un lien `wa.me` ouvre WhatsApp avec le texte prêt : un clic, un
 * appui sur « envoyer », puis « c'est parti » ici.
 *
 * **Deux gestes, dans cet ordre, et l'écran les numérote.** Marquer comme
 * envoyé avant d'avoir envoyé ferait disparaître de la file un message qui
 * n'est jamais parti — la demande expirerait sans que le propriétaire l'ait su.
 * Le second bouton ne s'allume donc qu'une fois le premier cliqué.
 *
 * **La ligne sort de la file à l'instant** (`replier`) : on enchaîne vingt
 * messages, attendre le serveur à chaque fois ferait douter d'avoir cliqué.
 * S'il refuse, la page revient avec la ligne et le bandeau d'erreur.
 *
 * L'urgent passe devant — nouvelle demande, demande qui expire : celui qui
 * envoie à la main n'a pas le temps de trier.
 */
import { reactive, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import OfficePager from '@/Components/Office/OfficePager.vue'
import OfficeTabs from '@/Components/Office/OfficeTabs.vue'
import { replier, useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { ilYA } from '@/Support/format.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    messages: { type: Object, required: true },
    onglets: { type: Array, required: true },
    filtre: { type: Object, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

/** Les messages dont WhatsApp a été ouvert : le second geste s'allume. */
const ouverts = reactive(new Set())
const lignes = ref({})

const envoye = (m) => {
    replier(lignes.value[m.id], () => {
        router.post(`/whatsapp/${m.id}/envoye`, {}, { preserveScroll: true })
    })
}

const aEnvoyer = props.filtre.onglet === 'a-envoyer'
</script>

<template>
    <Head title="WhatsApp — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Relais" titre="Messages WhatsApp" lede="Chaque message est déjà écrit. Ouvrez-le dans WhatsApp, envoyez-le, puis dites-le ici : il sort de la file." />

        <OfficeTabs :onglets="onglets" :actif="filtre.onglet" base="/whatsapp" param="onglet" defaut="a-envoyer" />

        <ul v-if="messages.data.length" class="wa">
            <li
                v-for="m in messages.data"
                :key="m.id"
                :ref="(el) => { if (el) lignes[m.id] = el }"
                class="of-card wa__msg"
                :class="{ 'is-urgent': m.urgent && aEnvoyer }"
                data-reveal
            >
                <header class="wa__h">
                    <span class="wa__kind">
                        <span v-if="m.urgent && aEnvoyer" class="of-chip of-chip--attente">Urgent</span>
                        {{ m.kind }}
                    </span>
                    <span class="wa__quand">{{ m.sentAt ? `envoyé ${ilYA(m.sentAt)}` : `écrit ${ilYA(m.createdAt)}` }}</span>
                </header>

                <p class="wa__pour">
                    Pour
                    <Link v-if="m.owner" :href="`/proprietaires/${m.owner.id}`" class="wa__qui">{{ m.owner.name }}</Link>
                    <strong v-else>un numéro de test</strong>
                    · <span class="of-num">{{ m.to }}</span>
                    <template v-if="m.booking"> · <Link :href="`/reservations/${m.booking.reference}`" class="wa__qui of-num">{{ m.booking.reference }}</Link></template>
                </p>

                <!-- Le texte tel qu'il partira : c'est lui qu'on relit avant
                     d'appuyer sur « envoyer » dans WhatsApp. -->
                <p class="wa__corps">{{ m.body }}</p>

                <div v-if="!m.sentAt" class="wa__gestes">
                    <a :href="m.lien" target="_blank" rel="noopener" class="btn btn--sm btn--ink" @click="ouverts.add(m.id)">
                        <span class="wa__etape" aria-hidden="true">1</span> Ouvrir dans WhatsApp <OfficeIcon name="externe" />
                    </a>
                    <button type="button" class="btn btn--sm btn--outline" :disabled="!ouverts.has(m.id)" @click="envoye(m)">
                        <span class="wa__etape" aria-hidden="true">2</span> C'est parti
                    </button>
                    <p v-if="!ouverts.has(m.id)" class="wa__why">Ouvrez-le d'abord : on ne marque comme envoyé que ce qui est parti.</p>
                </div>
            </li>
        </ul>

        <p v-else class="of-card of-vide" data-reveal>
            <strong>{{ aEnvoyer ? 'Rien à envoyer.' : 'Aucun message envoyé.' }}</strong>
            {{ aEnvoyer ? 'Les nouvelles demandes, les rappels et les messages des voyageurs arrivent ici.' : '' }}
        </p>

        <OfficePager :meta="messages.meta" unite="message" />
    </div>
</template>

<style scoped>
.wa { display: grid; gap: .75rem; margin: 0; padding: 0; list-style: none; }

.wa__msg { padding: 1rem 1.15rem 1.1rem; overflow: hidden; }
.wa__msg.is-urgent { border-left: 3px solid var(--terre-500); }

.wa__h { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .4rem 1rem; }
.wa__kind { display: inline-flex; align-items: center; gap: .45rem; font-size: .9rem; font-weight: 800; letter-spacing: -.015em; color: var(--ink); }
.wa__quand { font-size: .76rem; color: var(--text-3); }

.wa__pour { margin: .3rem 0 0; font-size: .82rem; color: var(--text-2); }
.wa__qui { font-weight: 700; color: var(--ink); text-decoration: underline; text-decoration-color: var(--line-2); text-underline-offset: .2em; }
.wa__qui:hover { text-decoration-color: var(--ink); }

/* Le corps en bulle claire, à la ligne près : c'est ce qui partira. */
.wa__corps {
    margin: .75rem 0 0;
    padding: .8rem .95rem;
    border-radius: var(--r-sm) var(--r-sm) var(--r-sm) 4px;
    background: var(--off-2);
    font-size: .86rem;
    line-height: 1.55;
    color: var(--ink);
    white-space: pre-line;
    overflow-wrap: anywhere;
}

.wa__gestes { display: flex; flex-wrap: wrap; align-items: center; gap: .45rem; margin-top: .8rem; }
.wa__gestes .btn .oi { width: .95rem; height: .95rem; }
.wa__etape {
    display: grid;
    place-items: center;
    width: 1.2rem;
    height: 1.2rem;
    border-radius: 50%;
    background: rgba(255, 255, 255, .18);
    font-size: .68rem;
    font-weight: 800;
}
.btn--outline .wa__etape { background: var(--off-2); color: var(--ink); }
.btn--outline:disabled .wa__etape { background: var(--line); color: var(--ink-3); }
.wa__why { flex-basis: 100%; margin: 0; font-size: .76rem; color: var(--text-3); }
</style>
