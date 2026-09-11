<script setup>
/**
 * Les propriétaires. L'onglet « Numéro à vérifier » est la liste d'appels de
 * la journée : c'est l'appel qui confirme le numéro, et le numéro qui ouvre le
 * niveau 2 de toutes ses annonces.
 */
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

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
</script>

<template>
    <Head title="Propriétaires — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Catalogue" titre="Propriétaires" lede="Le numéro WhatsApp est ce par quoi Vayla appelle. Il n'est tenu pour vérifié qu'après l'appel." />

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

@media (max-width: 860px) {
    .pi__row { grid-template-columns: auto minmax(0, 1fr); }
    .pi__tel, .pi__cpt { grid-column: 2; }
    .pi__cpt { justify-content: flex-start; }
}
</style>
