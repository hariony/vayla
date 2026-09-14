<script setup>
/**
 * Les propriétaires. L'onglet « Numéro à vérifier » est la liste d'appels de
 * la journée : c'est l'appel qui confirme le numéro, et le numéro qui ouvre le
 * niveau 2 de toutes ses annonces.
 */
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficePager from '@/Components/Office/OfficePager.vue'
import OfficeSearch from '@/Components/Office/OfficeSearch.vue'
import OfficeTabs from '@/Components/Office/OfficeTabs.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { portraitSrc } from '@/Support/portrait.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    proprietaires: { type: Object, required: true },
    onglets: { type: Array, required: true },
    filtre: { type: Object, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

const initiales = (nom = '') => nom.trim().split(/\s+/).slice(0, 2).map((m) => m[0] ?? '').join('').toUpperCase()

const params = props.filtre.onglet !== 'tous' ? { filtre: props.filtre.onglet } : {}

/*
 * **Inscrire un propriétaire au téléphone.** Le formulaire est replié : on
 * l'ouvre pendant un appel, pas à chaque passage sur la liste. Il ne demande que
 * ce qu'on obtient à voix haute — le nom, le numéro WhatsApp —, et l'adresse et
 * la ville s'il les donne. Le lien d'accès part dans la file WhatsApp.
 */
const ajoutOuvert = ref(false)
const ajout = useForm({ name: '', phone: '', email: '', city: '' })
const inscrire = () => ajout.post('/proprietaires', { preserveScroll: true })
</script>

<template>
    <Head title="Propriétaires — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Catalogue" titre="Propriétaires" lede="Le numéro WhatsApp est ce par quoi Vayla appelle. Il n'est tenu pour vérifié qu'après l'appel.">
            <template #actions>
                <button type="button" class="btn btn--ink btn--sm" :aria-expanded="ajoutOuvert" aria-controls="pi-ajout"
                        @click="ajoutOuvert = !ajoutOuvert">
                    {{ ajoutOuvert ? 'Fermer' : 'Ajouter un propriétaire' }}
                </button>
            </template>
        </OfficeHead>

        <section v-show="ajoutOuvert" id="pi-ajout" class="of-card pi__ajout" aria-labelledby="pi-ajout-t">
            <header class="of-card__h">
                <h2 id="pi-ajout-t" class="of-card__t">Inscrire un propriétaire</h2>
            </header>
            <form class="of-card__b pi__form" @submit.prevent="inscrire">
                <div class="of-field">
                    <label class="of-label" for="pi-nom">Nom</label>
                    <input id="pi-nom" v-model="ajout.name" class="of-input" maxlength="80" autocomplete="off" required>
                    <p v-if="ajout.errors.name" class="of-err" role="alert">{{ ajout.errors.name }}</p>
                </div>
                <div class="of-field">
                    <label class="of-label" for="pi-tel">Numéro WhatsApp</label>
                    <input id="pi-tel" v-model="ajout.phone" class="of-input" type="tel" inputmode="tel" maxlength="40" autocomplete="off" required>
                    <p class="of-help">Son lien d'accès part sur ce numéro.</p>
                    <p v-if="ajout.errors.phone" class="of-err" role="alert">{{ ajout.errors.phone }}</p>
                </div>
                <div class="of-field">
                    <label class="of-label" for="pi-mail">Adresse e-mail <span class="pi__option">facultatif</span></label>
                    <input id="pi-mail" v-model="ajout.email" class="of-input" type="email" maxlength="190" autocomplete="off">
                    <p class="of-help">Pour se connecter par code, le jour où il n'a plus son lien.</p>
                    <p v-if="ajout.errors.email" class="of-err" role="alert">{{ ajout.errors.email }}</p>
                </div>
                <div class="of-field">
                    <label class="of-label" for="pi-ville">Ville <span class="pi__option">facultatif</span></label>
                    <input id="pi-ville" v-model="ajout.city" class="of-input" maxlength="80" autocomplete="off">
                    <p v-if="ajout.errors.city" class="of-err" role="alert">{{ ajout.errors.city }}</p>
                </div>
                <div class="pi__envoi">
                    <button type="submit" class="btn btn--ink" :disabled="ajout.processing || !ajout.name.trim() || !ajout.phone.trim()">
                        Créer le compte et préparer son lien
                    </button>
                    <p class="of-help">Le compte naît au niveau 1, numéro à vérifier : créer n'est pas vérifier.</p>
                </div>
            </form>
        </section>

        <div class="pi__outils">
            <OfficeTabs :onglets="onglets" :actif="filtre.onglet" base="/proprietaires" param="filtre" defaut="tous" :q="filtre.q" />
            <OfficeSearch base="/proprietaires" :q="filtre.q" :params="params" placeholder="Nom, e-mail, ville, numéro…" label="Rechercher un propriétaire" />
        </div>

        <section class="of-card" data-reveal>
            <ul v-if="proprietaires.data.length" class="of-rows">
                <li v-for="p in proprietaires.data" :key="p.id">
                    <Link :href="`/proprietaires/${p.id}`" class="of-row pi__row">
                        <img v-if="p.portrait" class="pi__av" :src="portraitSrc(p.portrait)" alt="" width="40" height="40" loading="lazy">
                        <span v-else class="pi__av pi__av--init" aria-hidden="true">{{ initiales(p.name) }}</span>

                        <span class="pi__txt">
                            <span class="of-row__t">{{ p.name }}</span>
                            <span class="of-row__s">{{ p.email }}<template v-if="p.city"> · {{ p.city }}</template></span>
                        </span>

                        <span class="pi__tel of-num">
                            {{ p.telephone?.lisible ?? '—' }}
                            <span class="pi__statut" :class="{ 'is-ok': p.verified }">{{ p.verified ? 'Vérifié' : 'À vérifier' }}</span>
                        </span>

                        <span class="pi__cpt">
                            <span class="of-num"><strong>{{ p.enLigne }}</strong> en ligne</span>
                            <span v-if="p.aVerifier" class="of-chip of-chip--attente">{{ p.aVerifier }} à vérifier</span>
                            <span v-if="p.isDemo" class="of-chip of-chip--demo">Démo</span>
                            <span v-if="p.source" class="of-chip pi__source" :title="`Inscrit via « ${p.source} »`">{{ p.source }}</span>
                        </span>
                    </Link>
                </li>
            </ul>
            <p v-else class="of-vide"><strong>Personne ici.</strong>{{ filtre.q ? 'Un numéro se cherche avec ou sans espaces.' : 'Tous les numéros sont vérifiés.' }}</p>
        </section>

        <OfficePager :meta="proprietaires.meta" unite="propriétaire" />
    </div>
</template>

<style scoped>
.pi__outils { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 0 1rem; }

.pi__row { grid-template-columns: auto minmax(0, 1fr) 11.5rem 10rem; }

.pi__av { width: 2.5rem; height: 2.5rem; border-radius: 50%; object-fit: cover; background: var(--off-2); }
.pi__av--init { display: grid; place-items: center; background: var(--ink); color: var(--white); font-size: .78rem; font-weight: 800; }

.pi__txt { min-width: 0; }
.pi__tel { display: grid; font-size: .84rem; font-weight: 700; color: var(--ink); }

/* « Vérifié » est une vérification : il prend le lagon, et seulement lui. */
.pi__statut { font-size: .72rem; font-weight: 700; color: var(--terre-700); }
.pi__statut.is-ok { color: var(--lagon-700); }

.pi__cpt { display: flex; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: .3rem; font-size: .8rem; color: var(--text-2); }
.pi__cpt strong { color: var(--ink); }

/* Le formulaire d'inscription, sur deux colonnes : le nom et le numéro
   d'abord, ce qui est facultatif ensuite. */
.pi__ajout { margin-bottom: 1rem; }
.pi__form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.25rem; }
.pi__envoi { grid-column: 1 / -1; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1rem; }
.pi__option { font-weight: 400; color: var(--text-3); }
/* La source est une étiquette de suivi, pas un état : neutre. */
.pi__source { color: var(--text-2); }

@media (max-width: 860px) {
    .pi__form { grid-template-columns: 1fr; }
    .pi__row { grid-template-columns: auto minmax(0, 1fr); }
    .pi__tel, .pi__cpt { grid-column: 2; }
    .pi__cpt { justify-content: flex-start; }
}
</style>
