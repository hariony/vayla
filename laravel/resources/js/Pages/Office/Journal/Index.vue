<script setup>
/**
 * Le journal de l'équipe : **qui a fait quoi, sur quoi, et quand.**
 *
 * C'est la contrepartie du pouvoir de publier. Mettre en ligne, attribuer un
 * niveau, annuler un séjour : chacun de ces gestes engage la promesse de Vayla
 * auprès de quelqu'un qui n'était pas là. Le journal ne se modifie pas, ne se
 * vide pas, et garde le nom de celui qui a agi même après son départ de
 * l'équipe.
 *
 * Les lignes se regroupent par jour : on relit un journal pour retrouver
 * « ce qui s'est passé mardi », rarement une heure précise.
 */
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficePager from '@/Components/Office/OfficePager.vue'
import OfficeTabs from '@/Components/Office/OfficeTabs.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    lignes: { type: Object, required: true },
    onglets: { type: Array, required: true },
    filtre: { type: Object, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

const jour = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
const heure = new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit' })

const jours = computed(() => {
    const groupes = []

    for (const l of props.lignes.data) {
        const cle = l.at.slice(0, 10)
        const dernier = groupes[groupes.length - 1]

        if (dernier?.cle === cle) dernier.lignes.push(l)
        else groupes.push({ cle, label: jour.format(new Date(l.at)), lignes: [l] })
    }

    return groupes
})

/** Le sujet d'une ligne, quand il a un écran dans le back-office. */
const lien = (s) => {
    if (! s) return null
    return {
        listing: `/annonces/${s.id}`,
        owner: `/proprietaires/${s.id}`,
        destination: `/destinations/${s.id}`,
        photo: `/phototheque?photo=${s.id}`,
        stay_request: '/demandes',
    }[s.type] ?? null
}
</script>

<template>
    <Head title="Journal — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Équipe" titre="Journal" lede="Chaque geste qui engage quelqu'un laisse une ligne, écrite une fois. Consulter ne s'écrit pas : seulement ce qui change quelque chose." />

        <OfficeTabs :onglets="onglets" :actif="filtre.onglet" base="/journal" param="famille" defaut="tout" />

        <template v-if="jours.length">
            <section v-for="j in jours" :key="j.cle" class="jo__jour" data-reveal>
                <h2 class="jo__date">{{ j.label }}</h2>
                <ol class="of-card jo__liste">
                    <li v-for="l in j.lignes" :key="l.id" class="jo__l" :class="`jo__l--${l.famille}`">
                        <time class="jo__h of-num" :datetime="l.at">{{ heure.format(new Date(l.at)) }}</time>
                        <div class="jo__txt">
                            <p class="jo__label">{{ l.label }}</p>
                            <p class="jo__resume">
                                <Link v-if="lien(l.subject)" :href="lien(l.subject)" class="jo__sujet">{{ l.summary }}</Link>
                                <template v-else>{{ l.summary }}</template>
                            </p>
                            <p v-if="l.note" class="jo__note">« {{ l.note }} »</p>
                        </div>
                        <span class="jo__qui">{{ l.admin }}</span>
                    </li>
                </ol>
            </section>
        </template>
        <p v-else class="of-card of-vide" data-reveal><strong>Rien encore.</strong>Le premier geste de l'équipe apparaîtra ici.</p>

        <OfficePager :meta="lignes.meta" unite="ligne" />
    </div>
</template>

<style scoped>
.jo__jour { margin-bottom: 1.1rem; }
.jo__date { margin: 0 0 .5rem .2rem; font-size: .8rem; font-weight: 700; color: var(--text-2); text-transform: capitalize; }

.jo__liste { margin: 0; padding: 0; list-style: none; }
.jo__l {
    position: relative;
    display: grid;
    grid-template-columns: 3.2rem minmax(0, 1fr) auto;
    gap: .9rem;
    padding: .75rem 1.15rem .75rem 1.3rem;
    border-top: 1px solid var(--line);
}
.jo__l:first-child { border-top: 0; }
.jo__l::before {
    content: '';
    position: absolute;
    left: 0;
    top: .9rem;
    bottom: .9rem;
    width: 3px;
    border-radius: 0 3px 3px 0;
    background: var(--line-2);
}
/* Les décisions sur les annonces et les séjours sont celles qui engagent le
   plus : elles prennent le filet de terre. Le reste garde l'encre. */
.jo__l--annonces::before, .jo__l--reservations::before { background: var(--terre-500); }
.jo__l--facturation::before, .jo__l--equipe::before { background: var(--ink); }

.jo__h { padding-top: .1rem; font-size: .8rem; font-weight: 700; color: var(--text-3); }
.jo__label { margin: 0; font-size: .72rem; font-weight: 700; letter-spacing: .02em; color: var(--text-3); }
.jo__resume { margin: .1rem 0 0; font-size: .9rem; line-height: 1.45; color: var(--ink); }
.jo__sujet { color: var(--ink); text-decoration: underline; text-decoration-color: var(--line-2); text-underline-offset: .2em; }
.jo__sujet:hover { text-decoration-color: var(--ink); }
.jo__note { margin: .3rem 0 0; font-size: .84rem; line-height: 1.5; color: var(--text-2); }
.jo__qui { padding-top: .1rem; font-size: .78rem; font-weight: 600; color: var(--text-2); white-space: nowrap; }

@media (max-width: 640px) {
    .jo__l { grid-template-columns: 2.8rem minmax(0, 1fr); }
    .jo__qui { grid-column: 2; }
}
</style>
