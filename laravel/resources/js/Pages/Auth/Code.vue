<script setup>
/**
 * La saisie du code reçu par e-mail — **partagée** par les deux inscriptions.
 *
 * Deux écrans identiques auraient divergé sur le renvoi ou sur le message
 * d'erreur, et l'un des deux aurait fini par laisser passer ce que l'autre
 * refuse. Seuls les liens changent, et ils arrivent en props.
 *
 * **Six cases dessinées, un seul champ réel dessous.** C'est le point qui
 * décide de tout le reste : six `<input>` séparés — la solution qu'on écrit
 * d'instinct — cassent le collage du code, cassent le remplissage automatique
 * `one-time-code` (celui qui propose le code depuis la notification, sans
 * changer d'application), et obligent à déplacer le focus à la main, ce qui se
 * retourne toujours contre le clavier et les lecteurs d'écran. Ici le champ
 * est unique, transparent, posé **par-dessus** les cases : le navigateur fait
 * son travail habituel, et les cases ne sont qu'un affichage.
 *
 * **Les cases servent à voir où on en est.** Un champ unique de six chiffres
 * ne dit pas combien il en reste ; six cases le disent sans un mot, et c'est
 * exactement ce dont a besoin quelqu'un qui recopie en va-et-vient entre sa
 * notification et l'écran. Celle qui attend porte un curseur qui bat.
 *
 * **L'adresse est rappelée en haut.** C'est la première question qu'on se pose
 * quand rien n'arrive : « est-ce que je l'ai bien tapée ? ». Le lien
 * « Corriger » revient au formulaire avec la saisie intacte.
 *
 * **Le renvoi porte son compte à rebours, dans la phrase.** Un bouton
 * « renvoyer » qui refuse sans dire pourquoi fait recliquer ; ici le délai est
 * écrit à la suite du lien — « demander un nouveau code dans 4 secondes » — et
 * la phrase redevient une action quand le compte atteint zéro. Le lien reste
 * **lisible** pendant l'attente au lieu de s'effacer : c'est lui qui porte
 * l'information, pas le décompte seul.
 *
 * « Spams » et non « indésirables » : c'est le mot que les gens lisent dans
 * leur boîte, et un écran de secours n'est pas l'endroit où corriger le
 * vocabulaire de quelqu'un.
 *
 * **Pas de sortie vers l'accueil, et c'est volontaire.** Ce n'est pas une page
 * où l'on arrive, c'est une étape à l'intérieur d'une inscription qui vit en
 * session : un lien vers l'accueil y proposerait d'abandonner à la seconde où
 * abandonner coûte le plus cher — il faudrait tout ressaisir.
 */
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'

import AccessShell from '@/Components/AccessShell.vue'
import { useCodeMotion } from '@/Composables/useCodeMotion.js'

const props = defineProps({
    email: { type: String, required: true },
    action: { type: String, required: true },
    renvoi: { type: String, required: true },
    retour: { type: String, required: true },
    attente: { type: Number, default: 0 },
})

const TAILLE = 6

const page = usePage()
const flash = computed(() => page.props.flash ?? {})

const form = useForm({ code: '' })
const champ = ref(null)
const racine = ref(null)
const focus = ref(false)
const reste = ref(props.attente)
let minuteur = null

const chiffres = computed(() => form.code.replace(/\D/g, '').slice(0, TAILLE))
const complet = computed(() => chiffres.value.length === TAILLE)

/** La case qui attend la frappe — la dernière une fois le code complet. */
const active = computed(() => Math.min(chiffres.value.length, TAILLE - 1))

/**
 * **L'erreur peut venir de deux endroits, et l'oublier rend le renvoi muet.**
 *
 * La validation du code passe par `useForm`, donc par `form.errors`. Le renvoi,
 * lui, part en `router.post` : ses erreurs atterrissent dans les props de page
 * et **jamais** dans `form.errors`. Un renvoi refusé — cinq codes par heure —
 * ne disait donc rien du tout, et on recliquait dans le vide.
 */
const erreur = computed(() => form.errors.code ?? page.props.errors?.code ?? '')

useCodeMotion(racine, chiffres, erreur)

onMounted(() => {
    minuteur = setInterval(() => {
        if (reste.value > 0) reste.value--
    }, 1000)

    champ.value?.focus()
})
onUnmounted(() => clearInterval(minuteur))

/**
 * Le champ n'accepte que des chiffres, et les prend d'où qu'ils viennent.
 *
 * Un code collé arrive souvent avec des espaces ou un retour à la ligne — on
 * ne va pas faire échouer quelqu'un qui a copié proprement sa notification.
 */
const saisir = (evenement) => {
    form.code = evenement.target.value.replace(/\D/g, '').slice(0, TAILLE)
    evenement.target.value = form.code
}

const envoyer = () => {
    if (! complet.value || form.processing) {
        return
    }

    form.post(props.action, { onFinish: () => form.reset('code') })
}

/**
 * **Le sixième chiffre valide tout seul.**
 *
 * Sur un téléphone, le clavier occupe la moitié de l'écran et cache le bouton :
 * beaucoup tapent le code puis attendent qu'il se passe quelque chose. Le
 * bouton reste — il est le contrôle visible, et la règle de la maison ne
 * souffre pas d'exception — mais il n'est plus le seul chemin.
 */
watch(complet, (fini) => {
    if (fini) envoyer()
})

/**
 * **Le lien se ferme pendant l'envoi, pas seulement après.**
 *
 * L'appel SMTP est synchrone : entre le clic et la réponse il s'écoule
 * plusieurs secondes, pendant lesquelles le lien restait actif et le texte
 * inchangé. On recliquait — c'est le réflexe quand rien ne bouge — et un
 * second code partait, consommant le quota horaire pour rien. Le dire («
 * envoi… ») vaut mieux que de le laisser deviner.
 */
const renvoiEnCours = ref(false)

const renvoyer = () => router.post(props.renvoi, {}, {
    preserveScroll: true,
    onStart: () => (renvoiEnCours.value = true),
    onFinish: () => (renvoiEnCours.value = false),
})

/**
 * **Le délai vient du serveur, jamais d'un 60 écrit ici.**
 *
 * Le renvoi répond par un `back()` : Inertia réutilise le composant, donc
 * `ref(props.attente)` ne se rejoue pas et le décompte restait à zéro — on
 * pouvait recliquer aussitôt et taper dans la limite horaire sans le moindre
 * retour. Suivre la prop corrige ça **et** supprime la seconde source de
 * vérité : le délai est calculé par `VerificationCodeService`, qui est le seul
 * à savoir quand le dernier code est parti.
 */
watch(() => props.attente, (valeur) => (reste.value = valeur))
</script>

<template>
    <AccessShell
        titre-page="Votre code — Vayla"
        eyebrow="Vérification"
        titre="Vérifiez votre adresse e-mail"
        :sortie="false"
    >
        <div ref="racine" class="acces__panel cd" data-access-card>
            <p class="cd__lede">
                Nous avons envoyé un code de vérification à cette adresse&nbsp;:
                <strong>{{ email }}</strong>. Veuillez le saisir pour continuer.
            </p>

            <p v-if="flash.succes" class="acces__ok" role="status">{{ flash.succes }}</p>

            <form class="acces__form" @submit.prevent="envoyer">
                <label class="acces__label cd__label" for="code">Code à six chiffres</label>

                <!-- Le champ réel couvre les cases : c'est lui qui reçoit la
                     frappe, le collage et le remplissage automatique. Les
                     cases ne sont qu'un affichage, d'où `aria-hidden`. -->
                <div class="cd__zone" :class="{ 'is-err': erreur }">
                    <input
                        id="code"
                        ref="champ"
                        :value="chiffres"
                        type="text"
                        class="cd__champ"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        :maxlength="TAILLE"
                        :aria-invalid="erreur ? 'true' : 'false'"
                        aria-describedby="cd-aide"
                        @input="saisir"
                        @focus="focus = true"
                        @blur="focus = false"
                    >

                    <div class="cd__cases" data-code-row aria-hidden="true">
                        <span
                            v-for="n in TAILLE"
                            :key="n"
                            class="cd__case num"
                            :class="{
                                'is-plein': chiffres.length >= n,
                                'is-actif': focus && active === n - 1,
                            }"
                            data-code-case
                        >{{ chiffres[n - 1] ?? '' }}</span>
                    </div>
                </div>

                <!-- Le code est dans l'objet : sur un téléphone, la
                     notification l'affiche et on le lit sans ouvrir la boîte. -->
                <p id="cd-aide" class="acces__help cd__aide">
                    Il est aussi dans l'objet du message, sans avoir à l'ouvrir.
                </p>

                <p v-if="erreur" class="acces__err" role="alert">{{ erreur }}</p>

                <button type="submit" class="btn btn--terre btn--lg acces__go"
                        :disabled="!complet || form.processing">
                    {{ form.processing ? 'Vérification…' : 'Valider' }}
                </button>

                <!-- Un bouton désactivé se lit, il ne s'efface pas : il dit
                     combien il manque, pas seulement qu'il manque. -->
                <p v-if="!complet" class="acces__why">
                    Encore {{ TAILLE - chiffres.length }}
                    {{ TAILLE - chiffres.length > 1 ? 'chiffres' : 'chiffre' }}.
                </p>
            </form>
        </div>

        <template #pied>
            <!-- Les deux questions qu'on se pose quand rien n'arrive, dans
                 l'ordre où elles viennent : « est-ce que c'est perdu ? », puis
                 « est-ce que j'ai bien tapé mon adresse ? ». -->
            <span class="cd__ligne" aria-live="polite">
                Vous n'avez pas reçu d'e-mail&nbsp;? Veuillez vérifier vos spams ou
                <button
                    type="button"
                    class="acces__link cd__renvoi"
                    :disabled="reste > 0 || renvoiEnCours"
                    @click="renvoyer"
                >demander un nouveau code</button><template v-if="renvoiEnCours"> — envoi en cours…</template><template
                    v-else-if="reste > 0"
                > dans {{ reste }} {{ reste > 1 ? 'secondes' : 'seconde' }}</template>.
            </span>
            <span class="cd__ligne">
                Adresse incorrecte&nbsp;?
                <Link :href="retour" class="acces__link">Corriger</Link>
            </span>
        </template>
    </AccessShell>
</template>

<style scoped>
.cd__lede { margin: 0 0 1.5rem; font-size: .95rem; line-height: 1.6; color: var(--text-2); }
.cd__label { margin-bottom: .1rem; }

/* Le champ couvre exactement les cases : on tape « dans » les cases, et le
   clavier s'ouvre où qu'on touche. Transparent, pas `display: none` — masqué,
   il ne recevrait ni frappe, ni collage, ni remplissage automatique. */
.cd__zone { position: relative; }

.cd__champ {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    padding: 0;
    border: 0;
    background: none;
    /* Le curseur du navigateur est masqué : on dessine le nôtre sur la case
       active, là où le regard est déjà. */
    color: transparent;
    caret-color: transparent;
    font: inherit;
    letter-spacing: 1em;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
}
.cd__champ:focus { outline: none; }

.cd__cases {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: clamp(.3rem, 1.6vw, .55rem);
}

/* Chaque case est une cible tactile à part entière : au-dessus de la cible
   minimale de 2,75 rem, parce qu'ici c'est aussi la zone de lecture. */
.cd__case {
    position: relative;
    display: grid;
    place-items: center;
    aspect-ratio: 3 / 4;
    min-height: 3.4rem;
    border: 1px solid var(--line-2);
    border-radius: clamp(.7rem, 2vw, 1rem);
    background: var(--white);
    font-size: clamp(1.35rem, 5vw, 1.75rem);
    font-weight: 800;
    letter-spacing: 0;
    color: var(--ink);
    box-shadow: 0 1px 2px rgba(23, 20, 28, .04);
    transition:
        border-color .25s var(--ease),
        box-shadow .25s var(--ease),
        background-color .25s var(--ease);
}

/* Une case remplie prend un fond, pas une bordure sombre : six contours
   encre feraient une grille lourde là où le chiffre porte déjà l'information.
   La teinte suffit à compter d'un coup d'œil ce qui reste. */
.cd__case.is-plein { background: var(--off-2); }

/* La case qui attend porte la terre — c'est le seul endroit de l'écran où
   quelque chose est demandé — et un curseur qui bat, parce qu'une bordure
   colorée seule ne dit pas « tapez ici ». */
.cd__case.is-actif {
    border-color: var(--terre-500);
    border-width: 2px;
    background: var(--white);
    box-shadow: 0 0 0 4px var(--terre-050);
}
.cd__case.is-actif::after {
    content: '';
    position: absolute;
    width: 2px;
    height: 1.6rem;
    border-radius: 2px;
    background: var(--terre-500);
    animation: cd-bat 1.1s steps(1) infinite;
}

@keyframes cd-bat {
    0%, 50% { opacity: 1; }
    50.01%, 100% { opacity: 0; }
}

/* Le refus teinte les six cases, jamais une seule : le serveur ne dit pas
   quel chiffre est faux, et il aurait tort de le dire. */
.cd__zone.is-err .cd__case { border-color: var(--terre-300); background: var(--terre-050); }

.cd__aide { margin-top: .1rem; }

/* Deux idées, deux lignes. Séparées par un point médian, la phrase de renvoi
   étant longue, le second membre partait à la suite d'un retour à la ligne et
   le séparateur se retrouvait perdu au milieu d'un blanc. */
.cd__ligne { display: block; }
.cd__ligne + .cd__ligne { margin-top: .5rem; }

.cd__renvoi {
    padding: 0;
    border: 0;
    background: none;
    font: inherit;
    cursor: pointer;
}
/* Désactivé, il se lit toujours : il porte le compte à rebours, qui est
   l'information. */
.cd__renvoi:disabled { color: var(--text-3); text-decoration: none; cursor: default; }

/* Le curseur qui bat est du mouvement : il s'arrête aussi quand on le refuse,
   et la case active reste reconnaissable à sa bordure. */
@media (prefers-reduced-motion: reduce) {
    .cd__case.is-actif::after { animation: none; }
}
</style>
