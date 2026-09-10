<script setup>
/**
 * La connexion du propriétaire : la même porte que l'inscription.
 *
 * **Une adresse, un code, et c'est tout.** Le mot de passe a disparu, et avec
 * lui l'écran de pose et celui du « mot de passe oublié ». Le code prouve
 * l'adresse **à chaque connexion** ; un mot de passe ne prouve jamais que ça —
 * seulement qu'on connaît une chaîne.
 *
 * **L'identifiant est devenu l'adresse, plus le téléphone.** L'argument du
 * numéro était que beaucoup de propriétaires n'ont pas de boîte qu'ils
 * relèvent ; il ne tient plus, puisque l'inscription exige désormais une
 * adresse à laquelle un code arrive vraiment. Le téléphone reste ce par quoi
 * Vayla **appelle**, ce qu'il a toujours été de plus utile.
 *
 * **Le lien WhatsApp reste le chemin court** — il ouvre l'espace en un geste,
 * là où le propriétaire lit vraiment. Mais **l'écran ne le dit plus** : celui
 * qui a ce lien ne passe pas par ici, il clique dedans. L'expliquer sur une
 * page qu'il n'atteint que *faute* d'avoir ce lien, c'est décrire une porte
 * qu'il n'a pas sous la main.
 *
 * **Ni renvoi vers l'espace client.** La pastille « Retour » en haut ramène
 * déjà à l'aiguillage, qui porte les deux espaces : un second chemin vers la
 * même destination ne fait qu'ajouter une décision à un écran qui n'en
 * demande qu'une — entrer.
 *
 * Le formulaire poste sur `/proprietaire/inscription` : s'inscrire et se
 * connecter sont le même geste, et deux points d'entrée auraient divergé sur
 * le renvoi de code ou sur l'expiration.
 */
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

import AccessShell from '@/Components/AccessShell.vue'
import SocialButtons from '@/Components/SocialButtons.vue'

const form = useForm({ email: '' })

const complet = computed(() => /.+@.+\..+/.test(form.email.trim()))

const envoyer = () => form.post('/proprietaire/inscription')
</script>

<template>
    <AccessShell
        titre-page="Connexion propriétaire — Vayla"
        eyebrow="Espace propriétaire"
        titre="Votre espace propriétaire"
        lede="Vos demandes de réservation, votre calendrier et vos logements. Entrez votre adresse e-mail : nous vous envoyons un code, il n'y a pas de mot de passe."
        retour="/connexion"
    >
        <div class="acces__panel" data-access-card>
            <SocialButtons espace="proprietaire" />

            <p v-if="$page.props.social?.length" class="acces__ou" aria-hidden="true"><span>ou</span></p>

            <form class="acces__form" @submit.prevent="envoyer">
                <div class="acces__field">
                    <label class="acces__label" for="email">Adresse e-mail</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="acces__input"
                        inputmode="email"
                        autocomplete="email"
                        placeholder="vous@exemple.com"
                        maxlength="190"
                        autofocus
                        required
                    >
                    <p class="acces__help">Celle que vous avez donnée à l'inscription.</p>
                    <p v-if="form.errors.email" class="acces__err" role="alert">{{ form.errors.email }}</p>
                </div>

                <button type="submit" class="btn btn--terre btn--lg acces__go"
                        :disabled="!complet || form.processing">
                    {{ form.processing ? 'Envoi du code…' : 'Continuer' }}
                </button>

                <p v-if="!complet" class="acces__why">Entrez une adresse e-mail pour continuer.</p>
            </form>
        </div>
    </AccessShell>
</template>
