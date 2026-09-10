<script setup>
/**
 * Mes logements.
 *
 * **Le statut est ce qu'on lit en premier, avant la photo.** La question du
 * propriétaire n'est pas « à quoi ressemble mon annonce » mais « où elle en
 * est » : brouillon à finir, en attente de Vayla, ou en ligne. Chaque carte
 * porte donc son état **et la consigne qui va avec** — « complétez la fiche,
 * puis envoyez-la » se comprend, « Draft » non.
 *
 * **Créer un logement est un bouton plein, en haut.** C'est l'action qu'on
 * vient faire quand on n'a encore rien ; la reléguer en bas de liste la
 * rendrait invisible sur l'écran vide, qui est justement le premier qu'on voit.
 */
import { Head, Link } from '@inertiajs/vue3'

import OwnerShell from '../Partials/OwnerShell.vue'
import TrustGauge from '@/Components/TrustGauge.vue'
import { nombre } from '@/Support/format.js'
import { photoSrc, photoSrcset } from '@/Support/photo.js'

defineProps({
    listings: { type: Array, default: () => [] },
})
</script>

<template>
    <Head title="Mes logements — Vayla" />

    <OwnerShell>
        <header class="ml__head">
            <div>
                <h1 class="ml__title">Mes logements</h1>
                <p class="ml__lede">Vous remplissez la fiche, Vayla la vérifie avant qu'elle soit visible.</p>
            </div>
            <Link href="/proprietaire/logements/nouveau" class="btn btn--terre btn--lg ml__new">
                Ajouter un logement
            </Link>
        </header>

        <div v-if="!listings.length" class="ml__empty">
            <p class="ml__empty-t">Vous n'avez pas encore de logement sur Vayla.</p>
            <p class="ml__empty-s">
                Comptez une dizaine de minutes pour la première fiche : le nom, la capacité,
                le tarif, les équipements et trois photos.
            </p>
            <Link href="/proprietaire/logements/nouveau" class="btn btn--terre btn--lg">
                Ajouter mon premier logement
            </Link>
        </div>

        <ul v-else class="ml__list">
            <li v-for="l in listings" :key="l.slug" class="ml__card">
                <div class="ml__media">
                    <img
                        v-if="l.photo"
                        :src="photoSrc(l.photo, 800)"
                        :srcset="photoSrcset(l.photo)"
                        sizes="120px"
                        :alt="l.title"
                        width="800"
                        height="600"
                        loading="lazy"
                        decoding="async"
                    >
                    <span v-else class="ml__nophoto">Aucune photo</span>
                </div>

                <div class="ml__body">
                    <span class="ml__state" :class="`ml__state--${l.status}`">{{ l.statusLabel }}</span>
                    <h2 class="ml__name">{{ l.title }}</h2>
                    <p class="ml__place">{{ l.place }}</p>
                    <p class="ml__consigne">{{ l.consigne }}</p>

                    <p class="ml__facts num">
                        {{ nombre(l.price) }} Ar / nuit · {{ l.guests }} pers. max ·
                        {{ l.bedrooms }} ch. · {{ l.photos }} photo{{ l.photos > 1 ? 's' : '' }}
                    </p>

                    <p v-if="l.status === 'published'" class="ml__trust">
                        <TrustGauge :level="l.trust" />
                        <span>{{ l.trustName }}</span>
                    </p>
                </div>

                <div class="ml__acts">
                    <Link :href="`/proprietaire/logements/${l.slug}/modifier`" class="btn btn--ink">
                        Modifier la fiche
                    </Link>
                    <Link :href="`/proprietaire/logements/${l.slug}/calendrier`" class="btn btn--outline">
                        Calendrier
                    </Link>
                    <Link v-if="l.status === 'published'" :href="`/logements/${l.slug}`" class="ml__see">
                        Voir l'annonce publique
                    </Link>
                </div>
            </li>
        </ul>
    </OwnerShell>
</template>

<style scoped>
.ml__head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.75rem;
}
.ml__title { margin: 0; font-size: clamp(1.6rem, 4vw, 2.1rem); font-weight: 800; letter-spacing: -.045em; color: var(--ink); }
.ml__lede { margin: .35rem 0 0; max-width: 46ch; font-size: .92rem; line-height: 1.55; color: var(--text-2); }
.ml__new { flex: none; }

.ml__empty {
    padding: clamp(2rem, 6vw, 3.5rem);
    border: 1px dashed var(--line-2);
    border-radius: var(--r-lg);
    text-align: center;
    background: var(--white);
}
.ml__empty-t { margin: 0; font-size: 1.15rem; font-weight: 800; letter-spacing: -.025em; color: var(--ink); }
.ml__empty-s { margin: .5rem auto 1.5rem; max-width: 44ch; font-size: .92rem; line-height: 1.6; color: var(--text-2); }

.ml__list { display: grid; gap: 1rem; margin: 0; padding: 0; list-style: none; }

.ml__card {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 1rem 1.15rem;
    padding: 1rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
}

.ml__media {
    display: grid;
    place-items: center;
    width: 7.5rem;
    aspect-ratio: 4 / 3;
    border-radius: var(--r-md);
    overflow: hidden;
    background: var(--off-2);
}
.ml__media img { width: 100%; height: 100%; object-fit: cover; }
.ml__nophoto { font-size: .72rem; font-weight: 600; color: var(--text-3); }

.ml__body { min-width: 0; }

/* Le statut porte une pastille, pas une nuance de gris : c'est la première
   chose que le propriétaire cherche, elle doit se voir de loin.
   Ni terre ni lagon — la terre porte l'action, le lagon ne dit que
   « vérifié ». Un état d'avancement n'est ni l'un ni l'autre. */
.ml__state {
    display: inline-block;
    padding: .25rem .7rem;
    border-radius: var(--r-pill);
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .01em;
    text-transform: uppercase;
}
.ml__state--draft { background: var(--off-2); color: var(--text-2); }
.ml__state--submitted { background: var(--ink); color: var(--white); }
.ml__state--published { background: var(--off-2); color: var(--ink); }
.ml__state--archived { background: var(--off-2); color: var(--text-3); }

.ml__name { margin: .5rem 0 0; font-size: 1.1rem; font-weight: 800; letter-spacing: -.028em; color: var(--ink); }
.ml__place { margin: .1rem 0 0; font-size: .85rem; color: var(--text-3); }
.ml__consigne { margin: .5rem 0 0; max-width: 52ch; font-size: .86rem; line-height: 1.5; color: var(--text-2); }
.ml__facts { margin: .55rem 0 0; font-size: .84rem; color: var(--text-3); }

.ml__trust { display: inline-flex; align-items: center; gap: .45rem; margin: .5rem 0 0; font-size: .8rem; font-weight: 700; color: var(--lagon-600); }

.ml__acts {
    grid-column: 1 / -1;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .55rem;
    padding-top: .9rem;
    border-top: 1px solid var(--line);
}
.ml__see { margin-left: auto; font-size: .82rem; font-weight: 600; color: var(--text-2); }

@media (max-width: 560px) {
    .ml__card { grid-template-columns: 1fr; }
    .ml__media { width: 100%; }
    .ml__new { width: 100%; text-align: center; }
}
</style>
