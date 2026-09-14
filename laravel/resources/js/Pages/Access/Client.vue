<script setup>
/**
 * L'espace client : la même porte que l'inscription, sous un autre titre.
 *
 * **Une adresse, un code, et c'est tout.** Le mot de passe a disparu : le code
 * reçu prouve l'adresse à *chaque* connexion, ce qu'un mot de passe ne fait
 * jamais — il prouve seulement qu'on connaît une chaîne. Et il n'y a plus rien
 * à récupérer le jour où on l'oublie, ce qui était le vrai point de perte sur
 * un premier compte en ligne.
 *
 * **Le formulaire poste sur `/inscription`, et ce n'est pas une négligence.**
 * S'inscrire et se connecter sont ici le même geste ; deux points d'entrée
 * auraient divergé sur le renvoi de code ou sur l'expiration, et l'un aurait
 * fini par laisser passer ce que l'autre refuse. Seule l'accroche change, pour
 * que celui qui revient et celui qui découvre se reconnaissent chacun.
 *
 * **La référence de réservation n'est plus ici** : `GET /reservations/{ref}`
 * n'a pas bougé, c'est le lien du message de confirmation.
 *
 * **Plus de renvoi vers le catalogue non plus.** « Pas besoin de compte pour
 * demander un séjour » est vrai, et reste écrit là où la question se pose —
 * sur la fiche et le formulaire de demande. Ici, la personne a déjà décidé
 * d'entrer : lui proposer de faire autre chose la fait douter d'être au bon
 * endroit, sur un écran qui ne demande qu'une adresse.
 */
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

import AccessShell from '@/Components/AccessShell.vue'
import GoogleOneTap from '@/Components/GoogleOneTap.vue'
import SocialButtons from '@/Components/SocialButtons.vue'

const form = useForm({ email: '' })

const complet = computed(() => /.+@.+\..+/.test(form.email.trim()))

const envoyer = () => form.post('/inscription')
</script>

<template>
    <AccessShell
        titre-page="Espace client — Vayla"
        eyebrow="Espace client"
        titre="Vos séjours"
        lede="Entrez votre adresse e-mail. Nous vous envoyons un code à six chiffres — pas de mot de passe à retenir."
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

                <p v-if="!complet" class="acces__why">Entrez une adresse e-mail pour continuer.</p>
            </form>
        </div>

        <!-- L'invite Google n'est ici que parce que cette porte est celle du
             voyageur : elle connecte sur la garde `web`, sans rien demander. -->
        <GoogleOneTap />
    </AccessShell>
</template>
