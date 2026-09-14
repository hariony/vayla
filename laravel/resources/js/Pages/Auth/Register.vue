<script setup>
/**
 * La porte du voyageur : une adresse e-mail, et rien d'autre.
 *
 * **Un champ, un bouton.** Chaque champ ajouté à une inscription en fait
 * abandonner une part, et aucun autre n'est nécessaire ici : le nom est
 * demandé à la demande de séjour, où il sert — le propriétaire doit savoir
 * qui arrive — et le mot de passe n'existe plus.
 *
 * **La même porte sert à s'inscrire et à se connecter.** C'est le code reçu
 * qui décide : le compte existe, on entre ; il n'existe pas, il s'ouvre. Faire
 * choisir avant, c'est demander de trancher une question dont beaucoup n'ont
 * pas la réponse — on ne sait plus si on s'est inscrit un jour.
 *
 * L'écran ne dit donc pas « créez un compte » comme une condition, mais ce
 * qu'il va se passer : un code part, on le saisit.
 */
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'

import AccessShell from '@/Components/AccessShell.vue'
import GoogleOneTap from '@/Components/GoogleOneTap.vue'
import SocialButtons from '@/Components/SocialButtons.vue'

const form = useForm({ email: '' })

const complet = computed(() => /.+@.+\..+/.test(form.email.trim()))

const envoyer = () => form.post('/inscription')
</script>

<template>
    <AccessShell
        titre-page="Créer un compte — Vayla"
        eyebrow="Espace client"
        titre="Créer un compte"
        lede="Entrez votre adresse e-mail. Nous vous envoyons un code à six chiffres pour continuer — il n'y a pas de mot de passe à choisir."
        retour="/connexion"
    >
        <div class="acces__panel" data-access-card>
            <!-- Les fournisseurs d'abord : c'est le chemin le plus court quand
                 on en a un, et il évite l'aller-retour vers la boîte mail. Le
                 champ reste dessous, entier, pour tous les autres. -->
            <SocialButtons espace="voyageur" />

            <!-- Le séparateur suit les boutons : seul, il annoncerait un choix
                 qui n'existe pas sur une installation sans fournisseur. -->
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
                    <p v-if="form.errors.email" class="acces__err" role="alert">{{ form.errors.email }}</p>
                </div>

                <button type="submit" class="btn btn--terre btn--lg acces__go"
                        :disabled="!complet || form.processing">
                    {{ form.processing ? 'Envoi du code…' : 'Continuer' }}
                </button>

                <!-- Un bouton désactivé se lit, il ne s'efface pas. -->
                <p v-if="!complet" class="acces__why">Entrez une adresse e-mail pour continuer.</p>
            </form>
        </div>

        <template #pied>
            Vous êtes propriétaire&nbsp;?
            <Link href="/proprietaire/inscription" class="acces__link">Inscrire mon logement</Link>
        </template>

        <!-- L'invite Google n'est ici que parce que cette porte est celle du
             voyageur : elle connecte sur la garde `web`, sans rien demander. -->
        <GoogleOneTap />
    </AccessShell>
</template>
