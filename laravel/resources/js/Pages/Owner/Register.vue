<script setup>
/**
 * L'inscription d'un propriétaire — première étape : l'adresse, seule.
 *
 * **Un formulaire de six champs devant quelqu'un qui n'a encore rien reçu de
 * Vayla est un formulaire qu'on quitte.** Une adresse et un bouton, non. Le
 * nom et le numéro WhatsApp viennent après le code, quand la personne a déjà
 * fait un geste et vu que le service répond.
 *
 * **S'inscrire n'est pas publier, et l'écran le dit avant le champ.** Le
 * compte s'ouvre tout seul, mais l'annonce naît au niveau 1 — « déclarée » —
 * et c'est l'appel de vérification qui la met en ligne. Un propriétaire qui
 * découvre trois jours plus tard que son annonce n'était pas publiée ne
 * revient pas ; celui qui le sait dès le départ attend l'appel.
 */
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'

import AccessShell from '@/Components/AccessShell.vue'
import TrustGauge from '@/Components/TrustGauge.vue'

const form = useForm({ email: '' })

const complet = computed(() => /.+@.+\..+/.test(form.email.trim()))

const envoyer = () => form.post('/proprietaire/inscription')
</script>

<template>
    <AccessShell
        titre-page="Inscrire mon logement — Vayla"
        eyebrow="Espace propriétaire"
        titre="Inscrire mon logement"
        lede="Entrez votre adresse e-mail. Nous vous envoyons un code, puis vous remplissez votre fiche."
        largeur="large"
        retour="/connexion"
    >
        <div class="or__grille">
            <!-- Ce que l'inscription donne, et surtout ce qu'elle ne donne
                 pas : la publication passe par la vérification, sinon
                 « vérifié » ne voudrait plus rien dire. -->
            <aside class="acces__panel or__steps" data-access-card>
                <p class="or__steps-t">Comment ça se passe</p>
                <ol class="or__list">
                    <li><span class="or__n num">1</span> Vous créez votre compte et remplissez votre fiche.</li>
                    <li><span class="or__n num">2</span> Vayla vous appelle sous 48 h et vérifie avec vous.</li>
                    <li><span class="or__n num">3</span> Votre annonce passe en ligne, avec son niveau.</li>
                </ol>
                <p class="or__trust">
                    <TrustGauge :level="1" />
                    Tant que la vérification n'est pas faite, votre annonce reste
                    « déclarée » et n'apparaît quasiment pas dans les recherches.
                </p>
            </aside>

            <div class="acces__panel" data-access-card>
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
                        <p class="acces__help">C'est là qu'arrive votre code de vérification.</p>
                        <p v-if="form.errors.email" class="acces__err" role="alert">{{ form.errors.email }}</p>
                    </div>

                    <button type="submit" class="btn btn--terre btn--lg acces__go"
                            :disabled="!complet || form.processing">
                        {{ form.processing ? 'Envoi du code…' : 'Continuer' }}
                    </button>

                    <p v-if="!complet" class="acces__why">Entrez une adresse e-mail pour continuer.</p>
                </form>
            </div>
        </div>

        <template #pied>
            Vous avez déjà un compte&nbsp;?
            <Link href="/proprietaire/connexion" class="acces__link">Me connecter</Link>
        </template>
    </AccessShell>
</template>

<style scoped>
/* Les trois étapes à gauche, le champ à droite : empilées, elles repoussaient
   le champ sous la ligne de flottaison. Sous 900 px elles repassent au-dessus
   — c'est encore là qu'elles doivent être lues d'abord. */
.or__grille {
    display: grid;
    grid-template-columns: minmax(0, 20rem) minmax(0, 1fr);
    align-items: start;
    gap: clamp(1rem, 2.5vw, 1.75rem);
    max-width: var(--as-col);
    margin: 0 auto;
}
@media (max-width: 900px) {
    .or__grille { grid-template-columns: 1fr; }
}

/* `.acces__panel` se centre par `margin-inline: auto` quand il est seul sous
   le cadre. Dans une grille, une auto-marge en ligne **désactive
   l'étirement** : le panneau se dimensionnait sur son contenu et laissait de
   la colonne vide à sa droite. Ici c'est la grille qui place. */
.or__grille > .acces__panel { max-width: none; margin-inline: 0; }

.or__steps-t { margin: 0 0 .75rem; font-size: .82rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--text-3); }
.or__list { display: grid; gap: .7rem; margin: 0; padding: 0; list-style: none; font-size: .9rem; line-height: 1.5; color: var(--text-2); }
.or__list li { display: grid; grid-template-columns: 1.6rem 1fr; align-items: start; gap: .6rem; }
.or__n {
    display: grid;
    place-items: center;
    width: 1.6rem;
    height: 1.6rem;
    border-radius: var(--r-pill);
    background: var(--terre-050);
    font-size: .8rem;
    font-weight: 800;
    color: var(--terre-600);
}

.or__trust {
    display: grid;
    gap: .5rem;
    margin: 1.1rem 0 0;
    padding-top: 1.1rem;
    border-top: 1px solid var(--line);
    font-size: .84rem;
    line-height: 1.55;
    color: var(--text-3);
}
</style>
