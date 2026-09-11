<script setup>
/**
 * Mon compte : le mot de passe.
 *
 * **Quand il est provisoire, c'est le seul écran ouvert**, et il le dit en
 * tête : « choisissez le vôtre ». Le provisoire a été vu par quelqu'un d'autre
 * — le collègue qui l'a dicté, le terminal qui l'a affiché — et publier sous un
 * secret partagé ferait mentir le journal sur qui a agi.
 *
 * L'actuel est toujours demandé : c'est la preuve que la personne devant
 * l'écran est celle qui est entrée, pas celle qui s'assoit devant une session
 * restée ouverte. Les deux champs du nouveau se montrent d'un bouton, parce
 * qu'un mot de passe inventé à l'aveugle, à saisir deux fois, est la recette
 * d'un compte dont on est enfermé dehors la minute suivante.
 */
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { ilYA } from '@/Support/format.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    compte: { type: Object, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

const form = useForm({ current_password: '', password: '', password_confirmation: '' })
const visible = ref(false)

const assez = computed(() => form.password.length >= 12)
const pareil = computed(() => form.password.length > 0 && form.password === form.password_confirmation)
const pret = computed(() => form.current_password.length > 0 && assez.value && pareil.value)

const enregistrer = () => form.post('/compte', {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onError: () => form.reset('current_password'),
})
</script>

<template>
    <Head title="Mon compte — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Équipe" titre="Mon compte">
            <template #lede>{{ compte.name }} · {{ compte.email }}</template>
        </OfficeHead>

        <div v-if="compte.provisoire" class="ac__provisoire" role="note" data-reveal>
            <p class="ac__prov-t">Choisissez votre mot de passe</p>
            <p class="ac__prov-p">
                Celui avec lequel vous êtes entré est provisoire : quelqu'un d'autre l'a vu.
                Le back-office s'ouvre dès que vous en avez choisi un à vous.
            </p>
        </div>

        <section class="of-card ac" data-reveal aria-labelledby="mdp-t">
            <header class="of-card__h">
                <h2 id="mdp-t" class="of-card__t">{{ compte.provisoire ? 'Votre mot de passe' : 'Changer de mot de passe' }}</h2>
                <span v-if="compte.depuis" class="ac__depuis">choisi {{ ilYA(compte.depuis) }}</span>
            </header>

            <form class="of-card__b ac__form" @submit.prevent="enregistrer">
                <div class="of-field">
                    <label class="of-label" for="actuel">{{ compte.provisoire ? 'Mot de passe provisoire' : 'Mot de passe actuel' }}</label>
                    <input id="actuel" v-model="form.current_password" class="of-input" :type="visible ? 'text' : 'password'" autocomplete="current-password" maxlength="72" required>
                    <p v-if="form.errors.current_password" class="of-err" role="alert">{{ form.errors.current_password }}</p>
                </div>

                <div class="of-field">
                    <label class="of-label" for="nouveau">Nouveau mot de passe</label>
                    <input id="nouveau" v-model="form.password" class="of-input" :type="visible ? 'text' : 'password'" autocomplete="new-password" maxlength="72" required>
                    <!-- La règle se coche à mesure qu'on la remplit — à l'encre,
                         jamais au lagon : le lagon ne dit que « vérifié ». -->
                    <p class="ac__regle" :class="{ 'is-ok': assez }">Douze caractères au moins</p>
                    <p v-if="form.errors.password" class="of-err" role="alert">{{ form.errors.password }}</p>
                </div>

                <div class="of-field">
                    <label class="of-label" for="confirmation">Le même, une seconde fois</label>
                    <input id="confirmation" v-model="form.password_confirmation" class="of-input" :type="visible ? 'text' : 'password'" autocomplete="new-password" maxlength="72" required>
                    <p class="ac__regle" :class="{ 'is-ok': pareil }">Les deux saisies sont identiques</p>
                </div>

                <label class="ac__voir">
                    <input v-model="visible" type="checkbox">
                    Afficher les mots de passe
                </label>

                <p class="of-help">Pas de majuscule ni de chiffre imposés. On refuse seulement les mots de passe connus des fuites de données.</p>

                <button type="submit" class="btn btn--ink ac__go" :disabled="!pret || form.processing">
                    {{ form.processing ? 'Enregistrement…' : (compte.provisoire ? 'Choisir ce mot de passe' : 'Changer de mot de passe') }}
                </button>
            </form>
        </section>
    </div>
</template>

<style scoped>
.ac { max-width: 32rem; }
.ac__form { display: grid; gap: 1rem; }
.ac__depuis { font-size: .76rem; color: var(--text-3); }

.ac__provisoire {
    max-width: 32rem;
    margin-bottom: 1rem;
    padding: .9rem 1.05rem;
    border: 1px solid var(--terre-200);
    border-left: 3px solid var(--terre-500);
    border-radius: var(--r-sm);
    background: var(--terre-050);
}
.ac__prov-t { margin: 0; font-size: .92rem; font-weight: 800; color: var(--terre-700); }
.ac__prov-p { margin: .25rem 0 0; font-size: .86rem; line-height: 1.5; color: var(--ink); }

.ac__regle { margin: 0; font-size: .78rem; color: var(--text-3); }
.ac__regle::before { content: '○ '; }
.ac__regle.is-ok { color: var(--ink); font-weight: 600; }
.ac__regle.is-ok::before { content: '✓ '; }

.ac__voir { display: flex; align-items: center; gap: .55rem; min-height: 2.5rem; font-size: .86rem; color: var(--text-2); cursor: pointer; }
.ac__voir input { width: 1.15rem; height: 1.15rem; accent-color: var(--ink); }

.ac__go { justify-self: start; }
</style>
