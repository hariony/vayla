<script setup>
/**
 * L'inscription propriétaire, troisième étape : la fiche.
 *
 * **L'adresse est déjà prouvée**, et l'écran le montre plutôt que de la
 * redemander : elle s'affiche, verrouillée. La retaper serait un geste de plus
 * pour une donnée qu'on vient de vérifier.
 *
 * **Deux champs, et pas de mot de passe.** La connexion se fait par le code
 * reçu à l'adresse, comme côté voyageur : il n'y a rien à inventer, rien à
 * retenir, et rien à récupérer le jour où on l'oublie. Le numéro WhatsApp
 * n'est donc pas un identifiant — c'est par là que Vayla **appelle** pour la
 * vérification, et sans lui l'annonce ne dépassera jamais le niveau 1.
 */
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

import AccessShell from '@/Components/AccessShell.vue'

defineProps({
    email: { type: String, required: true },
})

const form = useForm({ name: '', phone: '' })

const complet = computed(() =>
    form.name.trim().length >= 3 && form.phone.replace(/\D/g, '').length >= 9
)

const envoyer = () => form.post('/proprietaire/inscription/fiche')
</script>

<template>
    <AccessShell
        titre-page="Votre fiche — Vayla"
        eyebrow="Espace propriétaire"
        titre="Encore deux choses"
        lede="Votre adresse est vérifiée. Il reste de quoi vous nommer et de quoi vous joindre."
        :sortie="false"
    >
        <div class="acces__panel" data-access-card>
            <form class="acces__form" @submit.prevent="envoyer">
                <div class="acces__field">
                    <label class="acces__label" for="email">Adresse e-mail</label>
                    <input id="email" :value="email" type="email" class="acces__input pr__fige" disabled>
                    <p class="acces__help">Vérifiée à l'étape précédente.</p>
                </div>

                <div class="acces__field">
                    <label class="acces__label" for="name">Votre nom</label>
                    <input id="name" v-model="form.name" type="text" class="acces__input"
                           autocomplete="name" maxlength="80" autofocus required>
                    <p class="acces__help">Celui que verront les voyageurs.</p>
                    <p v-if="form.errors.name" class="acces__err" role="alert">{{ form.errors.name }}</p>
                </div>

                <div class="acces__field">
                    <label class="acces__label" for="phone">Numéro WhatsApp</label>
                    <input id="phone" v-model="form.phone" type="tel" class="acces__input"
                           inputmode="tel" placeholder="034 00 000 00" maxlength="40" required>
                    <p class="acces__help">C'est par là que Vayla vous appelle, et votre identifiant de connexion.</p>
                    <p v-if="form.errors.phone" class="acces__err" role="alert">{{ form.errors.phone }}</p>
                </div>

                <button type="submit" class="btn btn--terre btn--lg acces__go"
                        :disabled="!complet || form.processing">
                    {{ form.processing ? 'Création…' : 'Créer mon compte' }}
                </button>
                <p v-if="!complet" class="acces__why">Remplissez les champs pour continuer.</p>
            </form>
        </div>
    </AccessShell>
</template>

<style scoped>
/* Désactivé mais lisible : un champ à 30 % d'opacité disparaît au lieu de dire
   « c'est déjà réglé ». */
.pr__fige { background: var(--off); color: var(--text-2); }
</style>
