<script setup>
/**
 * Le compte du voyageur.
 *
 * **Trois champs, et chacun sert deux fois.** Vayla ne demande ni mot de
 * passe, ni date de naissance, ni adresse postale : le compte sert à retrouver
 * ses séjours et à ne pas resaisir la même chose à chaque demande, pas à
 * constituer un dossier. Le nom et le prénom sont ce que le propriétaire lit
 * quand il décide d'accepter quelqu'un chez lui ; le numéro est ce par quoi il
 * rappelle. Les trois **pré-remplissent la demande de séjour** — sans ça, ils
 * ne seraient qu'un dossier de plus.
 *
 * **Nom et prénom séparés, parce qu'un champ unique ne se relit pas.**
 * « RAKOTOBE Jean » est une écriture courante ici, « Jean Rakotobe » l'est
 * ailleurs, et rien ne dit laquelle on a sous les yeux : cet écran-là en avait
 * fait « Bonjour RAKOTOBE », un patronyme crié à quelqu'un qu'on voulait
 * accueillir.
 *
 * **Tout est facultatif.** Le compte s'ouvre avec une adresse et rien d'autre :
 * exiger un nom pour enregistrer une correction de numéro serait exiger deux
 * fois ce qui n'a jamais été demandé.
 *
 * **L'adresse est affichée, pas modifiable, et l'écran dit pourquoi.** Elle
 * est l'identifiant de connexion *et* ce qui rattache les réservations au
 * compte : la changer d'un formulaire détacherait des séjours déjà faits. On
 * écrit donc combien de séjours en dépendent — un chiffre explique mieux
 * qu'une règle.
 *
 * **Aucun bouton de suppression de compte pour l'instant**, et ce n'est pas un
 * oubli : effacer un compte voyageur toucherait des réservations qui
 * appartiennent aussi à un propriétaire. Tant que cette question n'est pas
 * tranchée, un bouton qui promettrait de « tout effacer » mentirait.
 */
import { Head, useForm } from '@inertiajs/vue3'

import SiteHeader from '@/Components/SiteHeader.vue'
import SiteFooter from '@/Components/SiteFooter.vue'
import SpaceShell from '@/Components/SpaceShell.vue'
import { RUBRIQUES_CLIENT } from '@/Support/espaces.js'

const props = defineProps({
    compte: { type: Object, required: true },
})

const form = useForm({
    first_name: props.compte.firstName ?? '',
    last_name: props.compte.lastName ?? '',
    phone: props.compte.phone ?? '',
})

const enregistrer = () => form.post('/mon-compte', { preserveScroll: true })
</script>

<template>
    <Head title="Mes informations — Vayla" />

    <SiteHeader :search="false" />

    <SpaceShell espace="Espace client" :groupes="RUBRIQUES_CLIENT">
        <header class="espace__tete">
            <h1 class="espace__titre">Mes informations</h1>
            <p class="espace__lede">
                Vayla ne vous demande rien d'autre. Pas de mot de passe, pas de
                dossier&nbsp;: un code arrive à votre adresse à chaque connexion.
            </p>
        </header>

        <form class="mc__form" @submit.prevent="enregistrer">
            <p class="mc__intro">
                Rien n'est obligatoire ici. Ce que vous remplissez vous évite
                simplement de le retaper à chaque demande de séjour.
            </p>
            <div class="mc__paire">
                <div class="acces__field">
                    <label class="acces__label" for="last_name">Nom</label>
                    <input id="last_name" v-model="form.last_name" type="text" class="acces__input"
                           autocomplete="family-name" maxlength="60" placeholder="Rakotobe">
                    <p v-if="form.errors.last_name" class="acces__err" role="alert">{{ form.errors.last_name }}</p>
                </div>

                <div class="acces__field">
                    <label class="acces__label" for="first_name">Prénom</label>
                    <input id="first_name" v-model="form.first_name" type="text" class="acces__input"
                           autocomplete="given-name" maxlength="60" placeholder="Jean">
                    <p v-if="form.errors.first_name" class="acces__err" role="alert">{{ form.errors.first_name }}</p>
                </div>
            </div>

            <p class="acces__help mc__aide">
                C'est ainsi que le propriétaire vous lira quand vous demanderez un
                séjour. Deux champs plutôt qu'un&nbsp;: «&nbsp;RAKOTOBE Jean&nbsp;» et
                «&nbsp;Jean Rakotobe&nbsp;» s'écrivent tous les deux, et personne ne peut
                deviner lequel des deux mots est le prénom.
            </p>

            <div class="acces__field">
                <label class="acces__label" for="phone">Téléphone</label>
                <input id="phone" v-model="form.phone" type="tel" class="acces__input"
                       inputmode="tel" autocomplete="tel" maxlength="30"
                       placeholder="+261 34 00 000 00">
                <p class="acces__help">
                    C'est par là que le propriétaire vous répond. Vayla ne le publie
                    nulle part et ne s'en sert pas pour vous écrire.
                </p>
                <p v-if="form.errors.phone" class="acces__err" role="alert">{{ form.errors.phone }}</p>
            </div>

            <!-- Pas de champ : l'adresse ouvre le compte et rattache les séjours. -->
            <div class="mc__fixe">
                <p class="mc__fixe-lab">Adresse e-mail</p>
                <p class="mc__fixe-val">{{ compte.email }}</p>
                <p class="acces__help">
                    C'est elle qui ouvre votre compte, et c'est elle qui y rattache vos
                    séjours<template v-if="compte.sejours">
                        — <strong class="num">{{ compte.sejours }}</strong>
                        pour l'instant</template>. Pour en changer, écrivez à Vayla&nbsp;:
                    nous vérifions la nouvelle adresse avant de basculer, sinon vos
                    réservations se décrocheraient du compte.
                </p>
            </div>

            <div class="mc__barre">
                <button type="submit" class="btn btn--terre" :disabled="form.processing">
                    {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                </button>
                <span v-if="form.isDirty" class="mc__dirty">Des modifications ne sont pas enregistrées.</span>
            </div>
        </form>
    </SpaceShell>

    <SiteFooter />
</template>

<style scoped>
.mc__form {
    display: grid;
    gap: 1.25rem;
    max-width: 36rem;
    padding: clamp(1.25rem, 3vw, 1.75rem);
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
}

/* Ce qui ne se change pas ici garde la place et la lisibilité d'un champ :
   un champ grisé aurait été un faux signal, un libellé se lit pour ce
   qu'il est. */
.mc__fixe {
    display: grid;
    gap: .35rem;
    padding: .9rem 1rem;
    border: 1px solid var(--line);
    border-radius: var(--r-md);
    background: var(--off);
}
.mc__fixe-lab { margin: 0; font-size: .82rem; font-weight: 700; color: var(--ink); }
.mc__fixe-val { margin: 0; font-size: .98rem; color: var(--text-2); overflow-wrap: anywhere; }
.mc__fixe strong { color: var(--ink); }

.mc__paire { display: grid; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr)); gap: 1.25rem; }

/* L'explication porte sur les deux champs à la fois : posée sous l'un des
   deux, elle aurait eu l'air de ne concerner que celui-là. */
.mc__aide { margin: -.85rem 0 0; max-width: 52ch; line-height: 1.5; }

.mc__intro { margin: 0; font-size: .88rem; line-height: 1.55; color: var(--text-2); }

.mc__barre { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1rem; }
.mc__dirty { font-size: .82rem; color: var(--text-3); }
</style>
