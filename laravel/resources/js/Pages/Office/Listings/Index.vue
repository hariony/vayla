<script setup>
/**
 * Les annonces, toutes — brouillons et archives compris.
 *
 * **« À vérifier » se trie par ancienneté, la plus vieille d'abord** : c'est
 * le propriétaire qui attend l'appel depuis le plus longtemps. Partout ailleurs
 * la plus récemment modifiée vient en tête.
 *
 * Chaque ligne dit les trois choses qu'on vérifie ensemble : l'état de la
 * fiche, son niveau (la jauge — la seule surface qui nomme un niveau), et si
 * le numéro du propriétaire est déjà confirmé, puisque c'est lui qui ouvre le
 * niveau 2.
 */
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficePager from '@/Components/Office/OfficePager.vue'
import OfficeSearch from '@/Components/Office/OfficeSearch.vue'
import OfficeTabs from '@/Components/Office/OfficeTabs.vue'
import TrustGauge from '@/Components/TrustGauge.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { ariary, ilYA } from '@/Support/format.js'
import { photoSrc } from '@/Support/photo.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    annonces: { type: Object, required: true },
    onglets: { type: Array, required: true },
    filtre: { type: Object, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

const puce = (statut) => ({
    submitted: 'of-chip--attente',
    published: 'of-chip--actif',
    draft: '',
    archived: 'of-chip--clos',
}[statut] ?? '')

const params = props.filtre.statut !== 'toutes' ? { statut: props.filtre.statut } : {}
</script>

<template>
    <Head title="Annonces — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Catalogue" titre="Annonces" lede="Le propriétaire remplit, Vayla vérifie et publie. Une annonce ne passe en ligne qu'au niveau 2 au moins, après l'appel." />

        <div class="li__outils">
            <OfficeTabs :onglets="onglets" :actif="filtre.statut" base="/annonces" param="statut" defaut="toutes" :q="filtre.q" />
            <OfficeSearch base="/annonces" :q="filtre.q" :params="params" placeholder="Titre, propriétaire, destination…" label="Rechercher une annonce" />
        </div>

        <section class="of-card" data-reveal>
            <ul v-if="annonces.data.length" class="of-rows">
                <li v-for="a in annonces.data" :key="a.id">
                    <Link :href="`/annonces/${a.id}`" class="of-row li__row">
                        <img v-if="a.cover" class="li__vignette" :src="photoSrc(a.cover, 800)" alt="" width="72" height="54" loading="lazy" decoding="async">
                        <span v-else class="li__vignette li__vignette--vide" aria-hidden="true">Aucune photo</span>

                        <span class="li__txt">
                            <span class="of-row__t">{{ a.title }}</span>
                            <span class="of-row__s">
                                {{ a.destination }}<template v-if="a.owner"> · {{ a.owner.name }}</template>
                                · <span class="of-num">{{ ariary(a.price) }}</span>/nuit · {{ a.photos }} photo{{ a.photos > 1 ? 's' : '' }}
                            </span>
                        </span>

                        <span class="li__niveau">
                            <TrustGauge :level="a.trustLevel" />
                            <span class="li__niveau-l">{{ a.trustLabel }}</span>
                        </span>

                        <span class="li__etat">
                            <span class="of-chip" :class="puce(a.status)">{{ a.statusLabel }}</span>
                            <span v-if="a.isDemo" class="of-chip of-chip--demo">Démo</span>
                            <span class="li__age">{{ ilYA(a.updatedAt) }}</span>
                        </span>
                    </Link>
                </li>
            </ul>

            <p v-else class="of-vide">
                <strong>{{ filtre.q ? 'Aucune annonce ne correspond.' : 'Rien dans cette file.' }}</strong>
                {{ filtre.q ? 'Essayez le nom du propriétaire, ou une partie du titre.' : 'Changez d’onglet pour voir les autres états.' }}
            </p>
        </section>

        <OfficePager :meta="annonces.meta" unite="annonce" />
    </div>
</template>

<style scoped>
.li__outils { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 0 1rem; }

.li__row { grid-template-columns: auto minmax(0, 1fr) 11rem 9.5rem; }

.li__vignette {
    display: grid;
    place-items: center;
    width: 4.5rem;
    height: 3.4rem;
    border-radius: var(--r-xs);
    object-fit: cover;
    background: var(--off-2);
}
.li__vignette--vide { font-size: .6rem; font-weight: 700; line-height: 1.2; text-align: center; color: var(--text-3); }

.li__txt { min-width: 0; }

.li__niveau { display: grid; gap: .3rem; }
.li__niveau :deep(.gauge) { max-width: 7rem; }
.li__niveau-l { font-size: .74rem; font-weight: 600; color: var(--text-2); }

.li__etat { display: flex; flex-wrap: wrap; justify-content: flex-end; align-items: center; gap: .3rem; }
.li__age { flex-basis: 100%; font-size: .72rem; text-align: right; color: var(--text-3); }

@media (max-width: 860px) {
    .li__row { grid-template-columns: auto minmax(0, 1fr); }
    .li__niveau, .li__etat { grid-column: 2; }
    .li__etat { justify-content: flex-start; }
    .li__age { flex-basis: auto; text-align: left; }
}
</style>
