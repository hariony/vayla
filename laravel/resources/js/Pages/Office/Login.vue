<script setup>
/**
 * La connexion au back-office : une adresse et un mot de passe.
 *
 * **Un décor à part** (`OfficeGate`) : cette porte ne doit ressembler à aucune
 * de celles du site. Un voyageur qui y tomberait ne doit pas croire être au bon
 * endroit, et un membre de l'équipe doit savoir, avant de lire un mot, qu'il
 * entre dans l'outil qui publie.
 *
 * **Le mot de passe se montre d'un bouton bordé**, « Afficher » en toutes
 * lettres : un œil dessiné est une convention que tout le monde n'a pas, et un
 * mot de passe tapé à l'aveugle se rate au premier caractère.
 *
 * **L'échec ne dit jamais laquelle des deux valeurs est fausse**, et le champ du
 * mot de passe se vide : on ressaisit, sans indice. Trop d'essais, et l'écran
 * dit dans combien de secondes réessayer.
 *
 * Ni « rester connecté », ni connexion sociale : une session oubliée ouverte ici
 * peut publier des annonces.
 */
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

import OfficeGate from '@/Components/Office/OfficeGate.vue'

const form = useForm({ email: '', password: '' })
const visible = ref(false)

const complet = computed(() => /.+@.+\..+/.test(form.email.trim()) && form.password.length > 0)

const entrer = () => form.post('/connexion', {
    onError: () => form.reset('password'),
})
</script>

<template>
    <OfficeGate
        titre-page="Connexion — Back-office Vayla"
        titre="Vérifier, publier, relayer."
        lede="L'outil de l'équipe : les annonces à vérifier, les séjours à suivre, les messages à faire partir."
    >
        <form class="lg" novalidate @submit.prevent="entrer">
            <p class="of-kicker" data-og-champ>Connexion</p>

            <p v-if="form.errors.email" class="lg__erreur" role="alert" data-og-champ>{{ form.errors.email }}</p>

            <div class="of-field" data-og-champ>
                <label class="of-label" for="email">Adresse e-mail</label>
                <input
                    id="email"
                    v-model="form.email"
                    class="of-input lg__input"
                    type="email"
                    inputmode="email"
                    autocomplete="username"
                    placeholder="prenom@vayla.mg"
                    maxlength="190"
                    autofocus
                    required
                >
            </div>

            <div class="of-field" data-og-champ>
                <label class="of-label" for="password">Mot de passe</label>
                <div class="lg__mdp">
                    <input
                        id="password"
                        v-model="form.password"
                        class="of-input lg__input"
                        :type="visible ? 'text' : 'password'"
                        autocomplete="current-password"
                        maxlength="72"
                        required
                    >
                    <button
                        type="button"
                        class="lg__voir"
                        :aria-pressed="visible"
                        aria-controls="password"
                        @click="visible = !visible"
                    >
                        {{ visible ? 'Masquer' : 'Afficher' }}
                    </button>
                </div>
                <p v-if="form.errors.password" class="of-err" role="alert">{{ form.errors.password }}</p>
            </div>

            <button type="submit" class="btn btn--terre lg__go" :disabled="!complet || form.processing" data-og-champ>
                {{ form.processing ? 'Vérification…' : 'Entrer' }}
            </button>

            <p v-if="!complet" class="lg__why" data-og-champ>Saisissez votre adresse et votre mot de passe.</p>

            <p class="lg__aide" data-og-champ>
                Mot de passe perdu ? Un autre membre de l'équipe vous en donne un provisoire
                depuis l'écran « Membres ».
            </p>
        </form>
    </OfficeGate>
</template>

<style scoped>
.lg { display: grid; gap: 1.05rem; }

.lg__erreur {
    margin: 0;
    padding: .7rem .9rem;
    border: 1px solid var(--terre-300);
    border-left: 3px solid var(--terre-500);
    border-radius: var(--r-sm);
    background: var(--terre-050);
    font-size: .86rem;
    font-weight: 700;
    color: var(--terre-700);
}

.lg__input { min-height: 3rem; font-size: 1rem; }

.lg__mdp { position: relative; }
.lg__mdp .lg__input { padding-right: 6rem; }

/* Un vrai bouton, bordé et nommé : pas un œil dessiné qu'il faudrait deviner. */
.lg__voir {
    position: absolute;
    top: 50%;
    right: .35rem;
    min-height: 2.3rem;
    padding: 0 .8rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-xs);
    background: var(--white);
    font: inherit;
    font-size: .78rem;
    font-weight: 700;
    color: var(--ink);
    cursor: pointer;
    transform: translateY(-50%);
}
.lg__voir:hover { border-color: var(--ink); }
.lg__voir:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }

.lg__go { width: 100%; margin-top: .25rem; padding-block: 1rem; font-size: .95rem; }

.lg__why { margin: -.4rem 0 0; font-size: .78rem; text-align: center; color: var(--text-3); }

.lg__aide {
    margin: .35rem 0 0;
    padding-top: 1rem;
    border-top: 1px solid var(--line);
    font-size: .78rem;
    line-height: 1.5;
    color: var(--text-3);
}
</style>
