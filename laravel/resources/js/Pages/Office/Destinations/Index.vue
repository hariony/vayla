<script setup>
/**
 * Les destinations : ce qui porte les annonces, et les pages « quand venir,
 * comment y aller, jusqu'où la vérification est allée ».
 *
 * **Une destination sans logement reste affichée** — c'est le cas
 * majoritaire, et l'atlas le dit aussi. Le compte de logements est calculé,
 * jamais saisi. Une destination sans accès renseigné le signale : sa page
 * écrit alors « aucune information de trajet », ce qu'il faut corriger.
 */
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { photoSrc } from '@/Support/photo.js'

defineOptions({ layout: OfficeShell })

defineProps({
    destinations: { type: Array, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)
</script>

<template>
    <Head title="Destinations — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Contenu" titre="Destinations" lede="Le nom, l'accroche, la saison, le trajet depuis Tana et la photo de chaque destination. L'adresse de sa page ne change jamais.">
            <template #actions>
                <Link href="/destinations/nouvelle" class="btn btn--sm btn--ink"><OfficeIcon name="plus" /> Nouvelle destination</Link>
            </template>
        </OfficeHead>

        <ul class="dx" data-reveal>
            <li v-for="d in destinations" :key="d.id">
                <Link :href="`/destinations/${d.id}`" class="dx__carte">
                    <img v-if="d.photo" class="dx__photo" :src="photoSrc(d.photo, 800)" :alt="d.photo.caption ?? ''" loading="lazy" decoding="async">
                    <span v-else class="dx__photo dx__photo--vide">Pas de photo</span>
                    <span class="dx__corps">
                        <span class="dx__nom">{{ d.name }}</span>
                        <span class="dx__region">{{ d.region }} · {{ d.zone }}</span>
                        <span class="dx__accroche">{{ d.tagline }}</span>
                        <span class="dx__puces">
                            <span class="of-chip" :class="d.listings ? 'of-chip--actif' : 'of-chip--clos'">{{ d.listings }} logement{{ d.listings > 1 ? 's' : '' }}</span>
                            <span v-if="d.featured" class="of-chip of-chip--attente">À la une</span>
                            <span v-if="!d.acces" class="of-chip of-chip--attente">Trajet non renseigné</span>
                        </span>
                    </span>
                </Link>
            </li>
        </ul>
    </div>
</template>

<style scoped>
.dx {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(16rem, 1fr));
    gap: .8rem;
    margin: 0;
    padding: 0;
    list-style: none;
}
.dx__carte {
    display: grid;
    height: 100%;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: var(--r-md);
    background: var(--white);
    color: inherit;
    text-decoration: none;
    transition: border-color .2s var(--ease), box-shadow .3s var(--ease), transform .3s var(--ease);
}
.dx__carte:hover { border-color: var(--line-2); box-shadow: var(--sh-2); transform: translateY(-2px); }
.dx__carte:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }
.dx__photo { display: grid; place-items: center; width: 100%; aspect-ratio: 16 / 9; object-fit: cover; background: var(--off-2); }
.dx__photo--vide { font-size: .78rem; color: var(--text-3); }
.dx__corps { display: grid; gap: .2rem; padding: .85rem 1rem 1rem; }
.dx__nom { font-size: 1rem; font-weight: 800; letter-spacing: -.02em; color: var(--ink); }
.dx__region { font-size: .78rem; color: var(--text-3); }
.dx__accroche { margin-top: .2rem; font-size: .84rem; line-height: 1.45; color: var(--text-2); }
.dx__puces { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .5rem; }
</style>
