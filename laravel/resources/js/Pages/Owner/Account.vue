<script setup>
/**
 * Le compte du propriétaire — ce qu'il peut corriger lui-même.
 *
 * **Rien de tout ça ne l'était.** Le nom, le numéro WhatsApp, la ville et le
 * compte mobile money étaient saisis à l'inscription puis figés : celui qui
 * changeait de numéro devait écrire à Vayla, et pendant ce temps ses demandes
 * arrivaient sur une ligne qu'il n'avait plus. C'est le pire moment pour être
 * injoignable — une demande expire en 48 h.
 *
 * **L'adresse e-mail n'a pas de champ, et l'écran dit pourquoi.** Elle est
 * l'identifiant de connexion depuis la disparition des mots de passe : la
 * laisser modifiable depuis une session ouverte reviendrait à offrir le compte
 * à qui a emprunté le téléphone. Un champ grisé sans explication aurait été un
 * faux signal de plus ; une phrase et un chemin valent mieux.
 *
 * **Changer de numéro annule sa confirmation, et c'est écrit avant de
 * changer.** `phone_verified_at` n'enregistre pas une vérification maison : il
 * enregistre le fait que le lien d'accès envoyé sur ce WhatsApp a servi. Le
 * numéro changé, cette preuve ne porte plus sur rien.
 */
import { computed, ref } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'

import OwnerShell from './Partials/OwnerShell.vue'
import { portraitSrc, portraitSrcset } from '@/Support/portrait.js'
import { preparerPhoto } from '@/Support/preparerPhoto.js'

const props = defineProps({
    compte: { type: Object, required: true },
    operateurs: { type: Array, default: () => [] },
})

const form = useForm({
    name: props.compte.name ?? '',
    phone: props.compte.phone ?? '',
    city: props.compte.city ?? '',
    address: props.compte.address ?? '',
    mobile_money: props.compte.mobileMoney ?? '',
    mobile_money_operator: props.compte.operator ?? '',
})

const page = usePage()

/*
 * **Le portrait part seul, dès qu'il est choisi.** Un fichier de dix
 * mégaoctets qui repartirait avec chaque correction de numéro serait une
 * minute d'attente sur une connexion malgache, et un enregistrement perdu
 * quand la ligne coupe. Il n'y a donc pas de bouton « envoyer la photo » :
 * choisir *est* l'envoi, ce qui fait un geste de moins.
 */
const champPhoto = ref(null)
const envoi = ref(false)

const choisir = async (evenement) => {
    const fichier = evenement.target.files?.[0]

    if (! fichier) {
        return
    }

    envoi.value = true

    // Réduit avant de partir : le portrait ne garde jamais plus de 480 px, un
    // original de 12 Mo n'a pas à traverser une connexion mobile.
    const portrait = await preparerPhoto(fichier, { largeur: 960, ratio: 1 })

    router.post('/proprietaire/compte/photo', { portrait }, {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            envoi.value = false
            // Sans ça, rechoisir le même fichier après un refus ne déclenche
            // aucun événement : le champ n'a pas changé de valeur.
            if (champPhoto.value) champPhoto.value.value = ''
        },
    })
}

const retirerPhoto = () => router.post('/proprietaire/compte/photo/retirer', {}, { preserveScroll: true })

/** L'erreur du téléversement arrive dans les props de page, pas dans `form`. */
const erreurPhoto = computed(() => page.props.errors?.portrait ?? '')

/** Le numéro a-t-il bougé ? La confirmation tombe avec lui — on le dit avant. */
const numeroChange = computed(() => form.phone.trim() !== (props.compte.phone ?? ''))

const enregistrer = () => form.post('/proprietaire/compte', { preserveScroll: true })
</script>

<template>
    <Head title="Mes informations — Vayla" />

    <OwnerShell>
        <header class="espace__tete">
            <h1 class="espace__titre">Mes informations</h1>
            <p class="espace__lede">
                Ce que Vayla utilise pour vous joindre, et ce que les voyageurs
                voient de vous. Votre logement, lui, se modifie depuis
                «&nbsp;Mes logements&nbsp;».
            </p>
        </header>

        <form class="cp__form" @submit.prevent="enregistrer">
            <section class="cp__bloc">
                <h2 class="espace__section cp__h">Qui vous êtes</h2>

                <!-- Le portrait : un visage rassure là où un rond d'initiales
                     ne dit rien. Il ne part nulle part sans qu'on le dise —
                     la mention est sous le bouton, pas dans des conditions
                     générales. -->
                <div class="cp__photo">
                    <span class="cp__vignette">
                        <img
                            v-if="compte.portrait"
                            :src="portraitSrc(compte.portrait)"
                            :srcset="portraitSrcset(compte.portrait)"
                            sizes="72px"
                            alt=""
                            width="72"
                            height="72"
                        >
                        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                             stroke-linecap="round" aria-hidden="true">
                            <circle cx="12" cy="8.5" r="3.6" />
                            <path d="M4.9 19.4c1-3.4 3.7-5.1 7.1-5.1s6.1 1.7 7.1 5.1" />
                        </svg>
                    </span>

                    <div class="cp__photo-corps">
                        <p class="acces__label">Votre photo</p>
                        <p class="acces__help">
                            Un visage rassure un voyageur qui hésite à écrire à un inconnu.
                            Elle n'apparaît nulle part publiquement pour l'instant&nbsp;: on
                            la voit dans votre espace, et Vayla la verra à la vérification.
                        </p>

                        <div class="cp__photo-actions">
                            <label class="btn btn--outline btn--sm cp__choisir">
                                {{ envoi ? 'Envoi…' : (compte.portrait ? 'Changer la photo' : 'Choisir une photo') }}
                                <input
                                    ref="champPhoto"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="sr-only"
                                    :disabled="envoi"
                                    @change="choisir"
                                >
                            </label>

                            <button v-if="compte.portrait" type="button" class="cp__retirer" @click="retirerPhoto">
                                Retirer
                            </button>
                        </div>

                        <p v-if="erreurPhoto" class="acces__err" role="alert">{{ erreurPhoto }}</p>
                    </div>
                </div>

                <div class="acces__field">
                    <label class="acces__label" for="name">Votre nom</label>
                    <input id="name" v-model="form.name" type="text" class="acces__input"
                           autocomplete="name" maxlength="80" required>
                    <p class="acces__help">C'est ce nom que voient les voyageurs sur leur réservation.</p>
                    <p v-if="form.errors.name" class="acces__err" role="alert">{{ form.errors.name }}</p>
                </div>

                <div class="acces__field">
                    <label class="acces__label" for="city">Votre ville</label>
                    <input id="city" v-model="form.city" type="text" class="acces__input"
                           autocomplete="address-level2" maxlength="80"
                           placeholder="Antananarivo">
                    <p class="acces__help">
                        Facultatif. Elle nous sert à savoir d'où vous gérez vos logements —
                        elle n'apparaît sur aucune annonce.
                    </p>
                    <p v-if="form.errors.city" class="acces__err" role="alert">{{ form.errors.city }}</p>
                </div>

                <div class="acces__field">
                    <label class="acces__label" for="address">Adresse exacte</label>
                    <input id="address" v-model="form.address" type="text" class="acces__input"
                           autocomplete="street-address" maxlength="200"
                           placeholder="Lot II M 12 bis, Analamahitsy">
                    <p class="acces__help">
                        Facultatif, et <strong>jamais publié</strong>. Elle sert à deux choses&nbsp;:
                        votre facture de fin de mois, qui doit désigner quelqu'un pour être
                        payable, et la vérification — celui qui passe voir un logement doit
                        savoir où aller.
                    </p>
                    <p v-if="form.errors.address" class="acces__err" role="alert">{{ form.errors.address }}</p>
                </div>
            </section>

            <section class="cp__bloc">
                <h2 class="espace__section cp__h">Comment Vayla vous joint</h2>

                <div class="acces__field">
                    <label class="acces__label" for="phone">Numéro WhatsApp</label>
                    <input id="phone" v-model="form.phone" type="tel" class="acces__input"
                           inputmode="tel" autocomplete="tel" maxlength="40" required
                           placeholder="+261 34 00 000 00">
                    <p class="acces__help">
                        C'est par là que Vayla vous prévient d'une demande et vous appelle
                        pour la vérification.
                    </p>

                    <p v-if="compte.phoneVerifie && !numeroChange" class="cp__note">
                        Ce numéro est confirmé&nbsp;: vous avez déjà ouvert votre espace
                        depuis le lien reçu dessus.
                    </p>
                    <p v-if="compte.phoneVerifie && numeroChange" class="cp__alerte">
                        En changeant de numéro, la confirmation tombe&nbsp;: elle prouvait
                        que vous teniez l'ancienne ligne. Vayla vous renverra un lien sur
                        la nouvelle.
                    </p>

                    <p v-if="form.errors.phone" class="acces__err" role="alert">{{ form.errors.phone }}</p>
                </div>

                <!-- Pas de champ : l'adresse ouvre le compte. -->
                <div class="cp__fixe">
                    <p class="cp__fixe-lab">Adresse e-mail</p>
                    <p class="cp__fixe-val">{{ compte.email }}</p>
                    <p class="acces__help">
                        C'est elle qui ouvre votre compte&nbsp;: vous recevez un code à chaque
                        connexion. Pour en changer, écrivez à Vayla sur WhatsApp — nous
                        vérifions la nouvelle adresse avant de la basculer.
                    </p>
                </div>
            </section>

            <section class="cp__bloc">
                <h2 class="espace__section cp__h">Comment vous réglez la commission</h2>
                <p class="cp__intro">
                    Vayla n'encaisse rien et ne prélève rien&nbsp;: c'est vous qui poussez
                    le règlement de votre facture vers ce compte. Nous n'avons aucun
                    moyen de le débiter.
                </p>

                <div class="cp__paire">
                    <div class="acces__field">
                        <label class="acces__label" for="mm">Numéro mobile money</label>
                        <input id="mm" v-model="form.mobile_money" type="tel" class="acces__input"
                               inputmode="tel" maxlength="40" placeholder="+261 34 00 000 00">
                        <p v-if="form.errors.mobile_money" class="acces__err" role="alert">{{ form.errors.mobile_money }}</p>
                    </div>

                    <div class="acces__field">
                        <label class="acces__label" for="op">Opérateur</label>
                        <select id="op" v-model="form.mobile_money_operator" class="acces__input">
                            <option value="">Je ne sais pas encore</option>
                            <option v-for="o in operateurs" :key="o" :value="o">{{ o }}</option>
                        </select>
                        <p v-if="form.errors.mobile_money_operator" class="acces__err" role="alert">
                            {{ form.errors.mobile_money_operator }}
                        </p>
                    </div>
                </div>
            </section>

            <div class="cp__barre">
                <button type="submit" class="btn btn--terre" :disabled="form.processing">
                    {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                </button>
                <span v-if="form.isDirty" class="cp__dirty">Des modifications ne sont pas enregistrées.</span>
            </div>
        </form>
    </OwnerShell>
</template>

<style scoped>
.cp__form { display: grid; gap: clamp(1rem, 2.5vw, 1.5rem); }

.cp__bloc {
    padding: clamp(1.25rem, 3vw, 1.75rem);
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
    display: grid;
    gap: 1.1rem;
}

.cp__h {
    margin: 0;
}

.cp__intro { margin: -.4rem 0 0; max-width: 60ch; font-size: .88rem; line-height: 1.55; color: var(--text-2); }

/* Le portrait et son explication côte à côte : l'un sans l'autre laisse
   deviner à quoi sert la photo, et une photo dont on ne sait pas où elle
   part ne se téléverse pas. */
.cp__photo { display: flex; align-items: flex-start; gap: 1rem; }

.cp__vignette {
    display: grid;
    place-items: center;
    flex: none;
    width: 4.5rem;
    height: 4.5rem;
    overflow: hidden;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--off-2);
    color: var(--text-3);
}
.cp__vignette img { width: 100%; height: 100%; object-fit: cover; }
.cp__vignette svg { width: 2.2rem; height: 2.2rem; }

.cp__photo-corps { min-width: 0; display: grid; gap: .35rem; }
.cp__photo-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; margin-top: .35rem; }

/* Un `<label>` qui porte un champ fichier masqué : c'est le seul moyen d'avoir
   un vrai bouton là où le navigateur impose son propre dessin. Il en garde
   donc le clavier — le champ reste focalisable, `sr-only` ne l'enlève pas de
   l'ordre de tabulation. */
.cp__choisir { cursor: pointer; }
.cp__choisir:focus-within { outline: 2px solid var(--terre-500); outline-offset: 2px; }

.cp__retirer {
    padding: .35rem .2rem;
    border: 0;
    background: none;
    font: inherit;
    font-size: .84rem;
    font-weight: 600;
    color: var(--text-3);
    text-decoration: underline;
    text-underline-offset: .2em;
    cursor: pointer;
}
.cp__retirer:hover { color: var(--terre-600); }

.cp__paire { display: grid; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); gap: 1.1rem; }

/* Ce qui ne se change pas ici garde la même place et la même lisibilité qu'un
   champ : un champ grisé aurait été un faux signal, un simple libellé se lit
   pour ce qu'il est. */
.cp__fixe {
    display: grid;
    gap: .35rem;
    padding: .9rem 1rem;
    border: 1px solid var(--line);
    border-radius: var(--r-md);
    background: var(--off);
}
.cp__fixe-lab { margin: 0; font-size: .82rem; font-weight: 700; color: var(--ink); }
.cp__fixe-val { margin: 0; font-size: .98rem; color: var(--text-2); overflow-wrap: anywhere; }

.cp__note {
    margin: 0;
    font-size: .82rem;
    font-weight: 600;
    color: var(--ink);
}

/* La terre porte l'avertissement, comme sur le ruban des saisons — jamais le
   lagon, qui ne dit que « vérifié ». */
.cp__alerte {
    margin: 0;
    padding: .7rem .9rem;
    border: 1px solid var(--terre-300);
    border-radius: var(--r-md);
    background: var(--terre-050);
    font-size: .84rem;
    line-height: 1.5;
    color: var(--terre-700);
}

.cp__barre {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .75rem 1rem;
    position: sticky;
    bottom: 0;
    padding: .9rem 0;
    background: linear-gradient(to top, var(--off) 65%, transparent);
}
.cp__dirty { font-size: .82rem; color: var(--text-3); }
</style>
