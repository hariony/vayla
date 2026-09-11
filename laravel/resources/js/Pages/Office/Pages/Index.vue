<script setup>
/**
 * Les pages éditoriales du site. Rangées par colonne du pied de page, dans
 * l'ordre où elles y apparaissent — une page publiée y prend sa place d'elle-
 * même, une page en brouillon n'y est pas.
 *
 * **Une page qui porte encore des « [à compléter] » le dit**, et ne se publie
 * pas : ce sont les pages légales et le contact, dont les faits ne sont pas
 * dans le code.
 */
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { ilYA } from '@/Support/format.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    pages: { type: Array, required: true },
    groupes: { type: Object, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

const parGroupe = computed(() => {
    const cles = [...Object.keys(props.groupes), null]
    return cles
        .map((cle) => ({ cle, titre: cle ? props.groupes[cle] : 'Hors du pied de page', pages: props.pages.filter((p) => p.footer_group === cle) }))
        .filter((g) => g.pages.length)
})
</script>

<template>
    <Head title="Pages — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Contenu" titre="Pages" lede="Les pages du site écrites par l'équipe : comment ça marche, tarifs, guide, pages légales. Une page publiée prend sa place dans le pied de page ; un brouillon n'y apparaît pas.">
            <template #actions>
                <Link href="/pages/nouvelle" class="btn btn--sm btn--ink"><OfficeIcon name="plus" /> Nouvelle page</Link>
            </template>
        </OfficeHead>

        <section v-for="g in parGroupe" :key="g.cle ?? 'aucun'" class="of-card pg__groupe" data-reveal>
            <header class="of-card__h"><h2 class="of-card__t">{{ g.titre }}</h2></header>
            <ul class="of-rows">
                <li v-for="p in g.pages" :key="p.id">
                    <Link :href="`/pages/${p.id}`" class="of-row pg__row">
                        <OfficeIcon name="pages" class="pg__ico" />
                        <span class="pg__txt">
                            <span class="of-row__t">{{ p.title }}</span>
                            <span class="of-row__s of-num">/{{ p.slug }} · modifiée {{ ilYA(p.misAJour) }}</span>
                        </span>
                        <span class="pg__puces">
                            <span v-if="p.aCompleter" class="of-chip of-chip--attente">À compléter</span>
                            <span class="of-chip" :class="p.publiee ? 'of-chip--actif' : 'of-chip--clos'">{{ p.publiee ? 'En ligne' : 'Brouillon' }}</span>
                        </span>
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>

<style scoped>
.pg__groupe { margin-bottom: .9rem; }
.pg__row { grid-template-columns: auto minmax(0, 1fr) auto; }
.pg__ico { width: 1.3rem; height: 1.3rem; color: var(--text-3); }
.pg__txt { min-width: 0; }
.pg__puces { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .3rem; }
</style>
