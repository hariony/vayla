<script setup>
/**
 * Le fil d'échange d'une réservation.
 *
 * Le **même composant** sert au propriétaire et au voyageur : deux écrans qui
 * rendraient la même conversation avec deux mises en page finiraient par ne
 * plus dire la même chose. La seule différence est `moi`, posé par le serveur
 * selon qui lit.
 *
 * **Ce n'est pas une messagerie instantanée, et ça se voit.** Pas de bulles
 * qui rebondissent, pas d'indicateur de frappe, pas de rechargement continu :
 * c'est une trace écrite, datée, qu'on relit. Le va-et-vient rapide se fera
 * sur WhatsApp, où les deux parties sont déjà — vouloir le remplacer serait
 * perdu d'avance, et la fausse promesse d'un « chat » qui ne notifie rien
 * ferait rater des messages.
 *
 * **Vayla peut lire, et c'est écrit.** Une trace dont personne ne sait
 * qu'elle est lisible ne sert de médiation à personne.
 */
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    messages: { type: Array, default: () => [] },
    action: { type: String, required: true },
    /** « Écrivez au propriétaire » ou « Répondre au voyageur » : on nomme l'autre. */
    destinataire: { type: String, required: true },
})

const form = useForm({ body: '' })
const zone = ref(null)

const envoyer = () => form.post(props.action, {
    preserveScroll: true,
    onSuccess: () => form.reset('body'),
})

/**
 * « 4 septembre, 15 h 12 ». Le mois en lettres, jamais 04/09 : Vayla sert des
 * voyageurs étrangers autant que des Malgaches, et 04/09 se lit « 4 septembre »
 * ici et « 9 avril » ailleurs.
 */
const MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin',
    'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre']

const quand = (iso) => {
    const d = new Date(iso)
    const h = String(d.getHours()).padStart(2, '0')
    const m = String(d.getMinutes()).padStart(2, '0')

    return `${d.getDate()} ${MOIS[d.getMonth()]}, ${h} h ${m}`
}
</script>

<template>
    <section class="cv">
        <h2 class="cv__title">Échange</h2>
        <p class="cv__lede">
            Ce qui est écrit ici reste attaché à la réservation. Pour les échanges rapides,
            appelez-vous — le numéro est plus haut.
        </p>

        <ul v-if="messages.length" class="cv__list">
            <li v-for="m in messages" :key="m.id" class="cv__msg" :class="{ 'is-me': m.moi }">
                <p class="cv__who">
                    {{ m.moi ? 'Vous' : m.authorLabel }}
                    <span class="cv__at">{{ quand(m.at) }}</span>
                </p>
                <p class="cv__body">{{ m.body }}</p>
            </li>
        </ul>

        <p v-else class="cv__empty">
            Aucun message pour l'instant.
        </p>

        <form class="cv__form" @submit.prevent="envoyer">
            <label class="cv__label" for="cv-body">{{ destinataire }}</label>
            <textarea
                id="cv-body"
                ref="zone"
                v-model="form.body"
                class="cv__area"
                rows="4"
                maxlength="2000"
                placeholder="Votre message…"
            ></textarea>

            <p v-if="form.errors.body" class="cv__err" role="alert">{{ form.errors.body }}</p>

            <div class="cv__actions">
                <button type="submit" class="btn btn--terre" :disabled="form.processing || form.body.trim().length < 2">
                    {{ form.processing ? 'Envoi…' : 'Envoyer' }}
                </button>
                <span class="cv__count num">{{ form.body.length }} / 2000</span>
            </div>
        </form>

        <p class="cv__note">
            En cas de désaccord, Vayla peut lire cet échange pour vous aider à trouver une solution.
        </p>
    </section>
</template>

<style scoped>
.cv__title { margin: 0 0 .4rem; font-size: 1.2rem; font-weight: 800; letter-spacing: -.03em; color: var(--ink); }
.cv__lede { margin: 0 0 1.15rem; max-width: 56ch; font-size: .88rem; line-height: 1.55; color: var(--text-2); }

.cv__list { display: grid; gap: .7rem; margin: 0 0 1.25rem; padding: 0; list-style: none; }

/* Une trace écrite, pas une messagerie : des blocs alignés à gauche pour
   l'autre partie et à droite pour soi, sans bulles ni queues de bulle —
   la mise en page d'un chat promettrait une instantanéité qui n'existe pas. */
.cv__msg {
    max-width: 46ch;
    padding: .8rem 1rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--white);
}
.cv__msg.is-me { margin-left: auto; background: var(--off); }

.cv__who { margin: 0 0 .3rem; font-size: .76rem; font-weight: 800; color: var(--ink); }
.cv__at { margin-left: .5rem; font-weight: 500; color: var(--text-3); }
.cv__body { margin: 0; font-size: .94rem; line-height: 1.6; color: var(--text-2); white-space: pre-wrap; }

.cv__empty {
    margin: 0 0 1.25rem;
    padding: 1.25rem;
    border: 1px dashed var(--line-2);
    border-radius: var(--r-md);
    text-align: center;
    font-size: .9rem;
    color: var(--text-3);
}

.cv__form { display: grid; gap: .4rem; }
.cv__label { font-size: .82rem; font-weight: 700; color: var(--ink); }

.cv__area {
    width: 100%;
    padding: .75rem .9rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--white);
    font: inherit;
    font-size: .95rem;
    line-height: 1.6;
    color: var(--ink);
    resize: vertical;
}
.cv__area:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 1px; }

.cv__err {
    margin: 0;
    padding: .6rem .9rem;
    border: 1px solid var(--terre-300);
    border-radius: var(--r-md);
    background: var(--terre-050);
    font-size: .84rem;
    font-weight: 600;
    color: var(--terre-700);
}

.cv__actions { display: flex; align-items: center; gap: .8rem; }
.cv__count { font-size: .74rem; color: var(--text-3); }

.cv__note {
    margin: 1.25rem 0 0;
    padding-top: 1rem;
    border-top: 1px solid var(--line);
    max-width: 60ch;
    font-size: .78rem;
    line-height: 1.5;
    color: var(--text-3);
}
</style>
