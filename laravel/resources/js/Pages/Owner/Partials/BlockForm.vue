<script setup>
/**
 * Fermer des nuits au calendrier.
 *
 * **Le geste est celui d'un voyageur : on clique une arrivée, puis un
 * départ.** Deux champs de dates à saisir au clavier auraient été plus courts
 * à écrire et impraticables au doigt ; et surtout, le propriétaire connaît
 * déjà ce calendrier — c'est celui de sa propre annonce.
 *
 * **Toute la difficulté tient dans une phrase, et elle est écrite en toutes
 * lettres.** « Je suis pris du 12 au 18 » ne veut rien dire tant qu'on n'a
 * pas dit si la nuit du 18 est prise ou si on part ce jour-là. Le récapitulatif
 * tranche à chaque fois : « 6 nuits fermées, la dernière du 17 au 18. Le 18,
 * le logement est de nouveau réservable. » C'est l'invariant du dépôt —
 * `ends_on` est la dernière nuit occupée — rendu lisible plutôt que
 * documenté. Se tromper ici retire une nuit vendable à chaque période.
 *
 * **Les bornes de séjour du logement ne s'appliquent pas.** Un logement qui
 * se loue au minimum trois nuits doit pouvoir être fermé une seule soirée :
 * ces bornes encadrent ce qu'un voyageur réserve, pas ce qu'un propriétaire
 * ferme. Le serveur les retire du calendrier envoyé ici.
 *
 * **Le motif est une liste, pas un champ libre.** Cinq boutons se cochent en
 * une seconde sur un téléphone ; une zone de texte ne se remplit pas.
 */
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

import StayCalendar from '@/Components/StayCalendar.vue'
import { addDays, formatLong, useStayDates } from '@/Composables/useStayDates.js'

const props = defineProps({
    calendar: { type: Object, required: true },
    reasons: { type: Array, default: () => [] },
    action: { type: String, required: true },
})

const dates = useStayDates(computed(() => props.calendar))
const motif = ref(props.reasons[0]?.value ?? 'loue_direct')
const envoi = ref(false)

const page = usePage()
const erreurs = computed(() => page.props.errors ?? {})

/**
 * La consigne, numérotée, qui avance avec l'utilisateur.
 *
 * Un calendrier qui ne dit pas « cliquez sur une date » se regarde au lieu de
 * se remplir — ce qui est évident pour quelqu'un rompu aux plateformes ne
 * l'est pas pour un propriétaire dont c'est le premier outil en ligne.
 */
const consigne = computed(() => {
    const { arrivee, depart } = dates

    if (arrivee.value && depart.value) {
        return { etape: '✓', texte: 'Vérifiez les dates ci-dessous, puis fermez ces nuits.', fait: true }
    }
    if (arrivee.value) {
        return { etape: '2', texte: 'Cliquez maintenant sur le jour où le logement se libère.', fait: false }
    }
    return { etape: '1', texte: 'Cliquez sur la première nuit à fermer.', fait: false }
})

/**
 * Le récapitulatif : c'est lui qui empêche de perdre une nuit.
 *
 * `depart` est le jour de libération, jamais une nuit fermée. On nomme donc
 * la dernière nuit (`depart − 1`) **et** le jour où le logement rouvre.
 */
const recap = computed(() => {
    const { arrivee, depart, nuits } = dates
    if (!arrivee.value || !depart.value) return null

    return {
        nuits: nuits.value,
        premiere: formatLong(arrivee.value),
        derniere: formatLong(addDays(depart.value, -1)),
        libre: formatLong(depart.value),
    }
})

function fermer() {
    if (!recap.value || envoi.value) return

    envoi.value = true
    router.post(props.action, {
        arrival: dates.arrivee.value,
        departure: dates.depart.value,
        reason: motif.value,
    }, {
        preserveScroll: true,
        onSuccess: () => dates.effacer(),
        onFinish: () => (envoi.value = false),
    })
}
</script>

<template>
    <section class="bf">
        <header class="bf__head">
            <h2 class="bf__title">Fermer des dates</h2>
            <p class="bf__lede">
                Les nuits que vous fermez ici disparaissent du calendrier public :
                plus personne ne peut les demander.
            </p>
            <p class="bf__hint" :class="{ 'is-done': consigne.fait }" aria-live="polite">
                <span class="bf__step" aria-hidden="true">{{ consigne.etape }}</span>
                {{ consigne.texte }}
            </p>
        </header>

        <StayCalendar :dates="dates" :calendar="calendar" :months="2" />

        <!-- Le récapitulatif nomme la dernière nuit **et** le jour de
             libération : c'est le seul endroit où la règle « une nuit
             appartient à sa date d'arrivée » se lit sans la connaître. -->
        <div v-if="recap" class="bf__recap">
            <p class="bf__recap-n">
                <span class="num">{{ recap.nuits }}</span>
                nuit{{ recap.nuits > 1 ? 's' : '' }} à fermer
            </p>
            <p class="bf__recap-d">
                De la nuit du <strong>{{ recap.premiere }}</strong>
                à celle du <strong>{{ recap.derniere }}</strong>.
            </p>
            <p class="bf__recap-f">
                Le <strong>{{ recap.libre }}</strong>, le logement est de nouveau réservable.
            </p>
        </div>

        <div class="bf__form">
            <fieldset class="bf__reasons">
                <legend class="bf__legend">Pourquoi ces nuits sont-elles fermées ?</legend>
                <div class="bf__opts">
                    <label v-for="r in reasons" :key="r.value" class="bf__opt" :class="{ 'is-on': motif === r.value }">
                        <input v-model="motif" type="radio" name="motif" :value="r.value">
                        <span>{{ r.label }}</span>
                    </label>
                </div>
            </fieldset>

            <p v-for="(m, champ) in erreurs" :key="champ" class="bf__err" role="alert">{{ m }}</p>

            <button
                type="button"
                class="btn btn--terre btn--lg bf__go"
                :disabled="!recap || envoi"
                @click="fermer"
            >
                {{ envoi ? 'Enregistrement…' : 'Fermer ces nuits' }}
            </button>

            <!-- Un bouton désactivé se lit, il ne s'efface pas : il doit dire
                 ce qui manque, pas disparaître à 30 % d'opacité. -->
            <p v-if="!recap" class="bf__why">Choisissez d'abord deux dates dans le calendrier.</p>
        </div>
    </section>
</template>

<style scoped>
.bf__head { margin-bottom: 1.1rem; }
.bf__title { margin: 0; font-size: 1.3rem; font-weight: 800; letter-spacing: -.032em; color: var(--ink); }
.bf__lede { margin: .5rem 0 0; max-width: 56ch; font-size: .9rem; line-height: 1.55; color: var(--text-2); }

.bf__hint {
    display: flex;
    align-items: center;
    gap: .55rem;
    margin: .9rem 0 0;
    font-size: .92rem;
    font-weight: 700;
    color: var(--ink);
}
.bf__step {
    display: grid;
    place-items: center;
    flex: none;
    width: 1.6rem;
    height: 1.6rem;
    border-radius: var(--r-pill);
    background: var(--terre-500);
    font-size: .82rem;
    font-weight: 800;
    color: var(--white);
}
.bf__hint.is-done .bf__step { background: var(--ink); }

.bf__recap {
    margin-top: 1.25rem;
    padding: 1rem 1.25rem 1.1rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
}
.bf__recap-n { margin: 0; font-size: 1.15rem; font-weight: 800; letter-spacing: -.025em; color: var(--ink); }
.bf__recap-d { margin: .4rem 0 0; font-size: .92rem; line-height: 1.6; color: var(--text-2); }
/* La phrase de libération porte le filet : c'est celle qu'on oublie de lire,
   et c'est celle qui coûte une nuit vendable. */
.bf__recap-f {
    margin: .55rem 0 0;
    padding-top: .55rem;
    border-top: 1px solid var(--line);
    font-size: .92rem;
    line-height: 1.6;
    color: var(--text-2);
}
.bf__recap strong { font-weight: 800; color: var(--ink); }

.bf__form { margin-top: 1.25rem; }

.bf__reasons { margin: 0; padding: 0; border: 0; }
.bf__legend { padding: 0; margin-bottom: .55rem; font-size: .82rem; font-weight: 700; color: var(--text-2); }
.bf__opts { display: flex; flex-wrap: wrap; gap: .5rem; }

/* Des pastilles cochables, pas une liste déroulante : cinq valeurs tiennent
   à l'écran, et un choix visible se corrige sans rouvrir un menu. */
.bf__opt {
    display: inline-flex;
    align-items: center;
    min-height: 2.75rem;
    padding: .5rem 1rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font-size: .88rem;
    font-weight: 700;
    color: var(--text-2);
    cursor: pointer;
    transition: border-color .2s var(--ease), background-color .2s var(--ease);
}
.bf__opt input { position: absolute; opacity: 0; pointer-events: none; }
.bf__opt:hover { border-color: var(--ink); }
.bf__opt.is-on { border-color: var(--ink); background: var(--ink); color: var(--white); }
.bf__opt:focus-within { outline: 2px solid var(--terre-500); outline-offset: 2px; }

.bf__err {
    margin: .9rem 0 0;
    padding: .7rem 1rem;
    border: 1px solid var(--terre-300);
    border-radius: var(--r-md);
    background: var(--terre-050);
    font-size: .88rem;
    font-weight: 600;
    color: var(--terre-700);
}

.bf__go { margin-top: 1.1rem; width: 100%; max-width: 22rem; }
.bf__why { margin: .6rem 0 0; font-size: .84rem; color: var(--text-3); }
</style>
