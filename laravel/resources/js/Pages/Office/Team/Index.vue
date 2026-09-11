<script setup>
/**
 * L'équipe du back-office.
 *
 * **On y ajoute quelqu'un ; on ne l'invite pas.** Aucun e-mail ne part : le
 * nouveau membre reçoit un **mot de passe provisoire**, affiché une seule fois
 * ici, à transmettre de vive voix. Il en choisit un à sa première connexion.
 * Même geste pour un collègue qui a perdu le sien — « Nouveau mot de passe ».
 *
 * Le provisoire s'affiche dans une carte qui dit qu'elle ne reviendra pas :
 * il vit dans la session flash, et le rechargement suivant l'efface. Il n'est
 * écrit nulle part ailleurs, ni envoyé par e-mail.
 *
 * **Retirer demande une confirmation, et deux cas sont refusés** — se retirer
 * soi-même, retirer le dernier membre : l'un comme l'autre fermerait le
 * back-office de l'intérieur. Les gestes d'un membre retiré restent au journal,
 * sous son nom.
 */
import { computed, ref } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { ilYA } from '@/Support/format.js'

defineOptions({ layout: OfficeShell })

defineProps({
    membres: { type: Array, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

const ajout = useForm({ name: '', email: '' })
const ajouter = () => ajout.post('/equipe', { preserveScroll: true, onSuccess: () => ajout.reset() })

const page = usePage()
const provisoire = computed(() => page.props.provisoire)
const copie = ref(false)
const copier = async () => {
    try {
        await navigator.clipboard.writeText(provisoire.value.password)
        copie.value = true
        setTimeout(() => { copie.value = false }, 2000)
    } catch {
        // Presse-papiers refusé (contexte non sécurisé) : le mot de passe reste
        // lisible à l'écran, c'est ce qui compte.
    }
}

const reinit = useForm({})
const aReinitialiser = ref(null)
const reinitialiser = (m) => reinit.post(`/equipe/${m.id}/mot-de-passe`, { preserveScroll: true, onFinish: () => { aReinitialiser.value = null } })

const aRetirer = ref(null)
const retrait = useForm({})
const retirer = (m) => retrait.post(`/equipe/${m.id}/retirer`, { preserveScroll: true, onFinish: () => { aRetirer.value = null } })
</script>

<template>
    <Head title="Équipe — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Équipe" titre="Membres" lede="Qui peut entrer ici. Chacun a son adresse et son mot de passe, qu'il choisit lui-même : on ne partage jamais un compte." />

        <!-- Le mot de passe provisoire, une fois : la carte dit qu'elle ne
             reviendra pas, et comment le transmettre. -->
        <section v-if="provisoire" class="te__prov" role="status" data-reveal>
            <p class="of-kicker te__prov-k">À transmettre de vive voix — il ne sera plus affiché</p>
            <p class="te__prov-qui">{{ provisoire.name }} · {{ provisoire.email }}</p>
            <div class="te__prov-ligne">
                <code class="te__prov-mdp">{{ provisoire.password }}</code>
                <button type="button" class="btn btn--sm btn--outline" @click="copier">{{ copie ? 'Copié' : 'Copier' }}</button>
            </div>
            <p class="te__prov-p">Provisoire : à sa première connexion, le back-office lui demandera d'en choisir un. Ne l'envoyez pas par e-mail — un mot de passe dans une boîte de réception y reste.</p>
        </section>

        <div class="te">
            <section class="of-card" data-reveal aria-labelledby="me-t">
                <header class="of-card__h"><h2 id="me-t" class="of-card__t">L'équipe</h2></header>
                <ul class="of-rows">
                    <li v-for="m in membres" :key="m.id" class="of-row te__row">
                        <span class="te__init" aria-hidden="true">{{ m.initiales }}</span>
                        <span class="te__txt">
                            <span class="of-row__t">
                                {{ m.name }}<span v-if="m.moi" class="te__moi"> · vous</span>
                                <span v-if="m.provisoire" class="of-chip of-chip--attente te__puce">Mot de passe provisoire</span>
                            </span>
                            <span class="of-row__s">{{ m.email }}</span>
                        </span>
                        <span class="te__meta">
                            {{ m.lastLoginAt ? `connecté ${ilYA(m.lastLoginAt)}` : 'jamais connecté' }}
                            <span class="of-num">· {{ m.actions }} geste{{ m.actions > 1 ? 's' : '' }}</span>
                        </span>
                        <span class="te__action">
                            <template v-if="!m.moi">
                                <span v-if="aRetirer === m.id" class="te__confirm">
                                    <button type="button" class="btn btn--sm btn--ink" :disabled="retrait.processing" @click="retirer(m)">Confirmer le retrait</button>
                                    <button type="button" class="btn btn--sm btn--ghost" @click="aRetirer = null">Annuler</button>
                                </span>
                                <span v-else-if="aReinitialiser === m.id" class="te__confirm">
                                    <button type="button" class="btn btn--sm btn--ink" :disabled="reinit.processing" @click="reinitialiser(m)">Confirmer</button>
                                    <button type="button" class="btn btn--sm btn--ghost" @click="aReinitialiser = null">Annuler</button>
                                </span>
                                <span v-else class="te__confirm">
                                    <button type="button" class="btn btn--sm btn--outline" @click="aReinitialiser = m.id">Nouveau mot de passe</button>
                                    <button type="button" class="btn btn--sm btn--outline" @click="aRetirer = m.id">Retirer</button>
                                </span>
                            </template>
                            <Link v-else href="/compte" class="btn btn--sm btn--outline">Mon compte</Link>
                        </span>
                    </li>
                </ul>
            </section>

            <section class="of-card" data-reveal aria-labelledby="aj-t">
                <header class="of-card__h"><h2 id="aj-t" class="of-card__t">Ajouter un membre</h2></header>
                <form class="of-card__b te__form" @submit.prevent="ajouter">
                    <div class="of-field">
                        <label class="of-label" for="nom">Nom</label>
                        <input id="nom" v-model="ajout.name" class="of-input" maxlength="80" autocomplete="off" required>
                        <p class="of-help">Il signe chacun de ses gestes au journal.</p>
                        <p v-if="ajout.errors.name" class="of-err" role="alert">{{ ajout.errors.name }}</p>
                    </div>
                    <div class="of-field">
                        <label class="of-label" for="email">Adresse e-mail</label>
                        <input id="email" v-model="ajout.email" class="of-input" type="email" maxlength="190" autocomplete="off" required>
                        <p class="of-help">Son identifiant de connexion. Aucun message n'y part : un mot de passe provisoire s'affiche ici, à lui transmettre.</p>
                        <p v-if="ajout.errors.email" class="of-err" role="alert">{{ ajout.errors.email }}</p>
                    </div>
                    <button type="submit" class="btn btn--ink" :disabled="ajout.processing || !ajout.name.trim() || !ajout.email.trim()">
                        Ajouter à l'équipe
                    </button>
                </form>
            </section>
        </div>
    </div>
</template>

<style scoped>
.te { display: grid; grid-template-columns: minmax(0, 1fr) minmax(17rem, 22rem); align-items: start; gap: 1rem; }

.te__row { grid-template-columns: auto minmax(0, 1fr) auto auto; }
.te__init {
    display: grid;
    place-items: center;
    width: 2.3rem;
    height: 2.3rem;
    border-radius: 50%;
    background: var(--ink);
    color: var(--white);
    font-size: .76rem;
    font-weight: 800;
}
.te__txt { min-width: 0; }
.te__moi { font-weight: 600; color: var(--terre-600); }
.te__meta { font-size: .76rem; color: var(--text-3); text-align: right; }
.te__confirm { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .3rem; }
.te__puce { margin-left: .4rem; vertical-align: middle; }

/* Le provisoire : une carte d'encre, la seule de l'écran — on ne doit pas la
   manquer, puisqu'elle ne reviendra pas. */
.te__prov {
    margin-bottom: 1rem;
    padding: 1rem 1.15rem;
    border-radius: var(--r-md);
    background: var(--ink);
    color: var(--white);
}
.te__prov-k { color: var(--terre-300); }
.te__prov-qui { margin: .3rem 0 0; font-size: .86rem; color: rgba(255, 255, 255, .75); }
.te__prov-ligne { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; margin-top: .6rem; }
.te__prov-mdp {
    padding: .5rem .8rem;
    border-radius: var(--r-xs);
    background: rgba(255, 255, 255, .1);
    font-family: ui-monospace, 'SF Mono', Menlo, monospace;
    font-size: 1.1rem;
    font-weight: 600;
    letter-spacing: .06em;
    user-select: all;
}
.te__prov .btn--outline { border-color: rgba(255, 255, 255, .4); background: transparent; color: var(--white); }
.te__prov .btn--outline:hover { background: var(--white); color: var(--ink); }
.te__prov-p { margin: .7rem 0 0; max-width: 60ch; font-size: .8rem; line-height: 1.5; color: rgba(255, 255, 255, .6); }

.te__form { display: grid; gap: 1rem; }

@media (max-width: 960px) {
    .te { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 620px) {
    .te__row { grid-template-columns: auto minmax(0, 1fr); }
    .te__meta, .te__action { grid-column: 2; text-align: left; }
}
</style>
