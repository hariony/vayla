<script setup>
/**
 * L'atlas : la carte et la liste des regions, a parts egales.
 *
 * La destination survolee est un etat purement local — la carte et la liste
 * s'eclairent ensemble, mais cela ne concerne aucune autre section. Cliquer
 * une region, en revanche, filtre la grille : c'est ce que remonte `pick`.
 */
import { ref } from 'vue'

import MadagascarMap from '@/Components/MadagascarMap.vue'
import { photoSrc, photoSrcset } from '@/Support/photo.js'

defineProps({
    destinations: { type: Array, default: () => [] },
    photos: { type: Object, default: () => ({}) },
})

defineEmits(['pick'])

const active = ref('')
</script>

<template>
    <!-- ═══════════ ATLAS ═══════════ -->
    <section id="atlas" class="atlas section">
        <div class="shell">
            <div class="bar">
                <div>
                    <p class="eyebrow" data-anim>L'atlas</p>
                    <h2 class="display display--lg" data-anim>Où l'on est déjà passé</h2>
                    <p class="lede bar__lede" data-anim>
                        On ouvre région par région, jamais avant d'avoir un correspondant
                        sur place. Survolez la carte.
                    </p>
                </div>
                <a href="#demande" class="btn btn--outline" data-anim>Demander une autre région</a>
            </div>

            <div class="atlas__grid">
                <div class="atlas__map" data-anim data-depth="0.08">
                    <MadagascarMap
                        :destinations="destinations"
                        :active="active"
                        @activate="active = $event"
                    />
                </div>

                <ul class="atlas__list" data-anim-group>
                    <li
                        v-for="d in destinations"
                        :key="d.slug"
                        class="atlas__item"
                        :class="{ 'is-active': active === d.slug }"
                        @mouseenter="active = d.slug"
                        @mouseleave="active = ''"
                    >
                        <button
                            type="button"
                            class="atlas__btn"
                            @click="$emit('pick', d.slug)"
                        >
                            <span class="atlas__thumb">
                                <img
                                    :src="photoSrc(photos[d.photo], 800)"
                                    :srcset="photoSrcset(photos[d.photo])"
                                    sizes="96px"
                                    :alt="photos[d.photo]?.caption ?? d.name"
                                    width="560"
                                    height="420"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </span>
                            <span class="atlas__text">
                                <span class="atlas__name">{{ d.name }}</span>
                                <span class="atlas__region">{{ d.region }}</span>
                                <span class="atlas__tagline">{{ d.tagline }}</span>
                            </span>
                            <span class="atlas__count num">
                                {{ d.listings }}
                                <span class="atlas__count-l">{{ d.listings > 1 ? 'logements' : 'logement' }}</span>
                            </span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* ═══════════ ATLAS ═══════════ */

.atlas { background: var(--off); border-top: 1px solid var(--line); }

.atlas__grid { display: grid; grid-template-columns: 1fr; gap: clamp(2rem, 4vw, 3.5rem); align-items: start; }

/* La zone de dessin a été élargie pour loger les étiquettes des deux
   côtes : le conteneur suit, sinon la carte rétrécit d'un quart. */
.atlas__map { max-width: 400px; margin-inline: auto; }

.atlas__list { margin: 0; padding: 0; list-style: none; display: grid; gap: .5rem; }

.atlas__btn {
    display: grid;
    grid-template-columns: 92px minmax(0, 1fr) auto;
    align-items: center;
    gap: 1rem;
    width: 100%;
    padding: .7rem;
    font-family: inherit;
    text-align: left;
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--r-md);
    cursor: pointer;
    transition: background-color .3s, border-color .3s, transform .4s var(--ease);
}
.atlas__item.is-active .atlas__btn,
.atlas__btn:hover { background: #fff; border-color: var(--line-2); transform: translateX(4px); }
.atlas__btn:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }

.atlas__thumb {
    display: block;
    width: 92px;
    height: 70px;
    overflow: hidden;
    border-radius: var(--r-sm);
    background: var(--off-2);
}

.atlas__thumb img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .6s var(--ease);
}
.atlas__btn:hover .atlas__thumb img { transform: scale(1.06); }

.atlas__text { display: flex; flex-direction: column; min-width: 0; }
.atlas__name { font-size: 1.02rem; font-weight: 800; letter-spacing: -.028em; color: var(--ink); }
.atlas__region { font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: var(--terre-600); }
.atlas__tagline { margin-top: .2rem; font-size: .86rem; color: var(--text-2); }

.atlas__count { display: flex; flex-direction: column; align-items: flex-end; font-size: 1.15rem; font-weight: 800; color: var(--ink); }
.atlas__count-l { font-size: .68rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--text-3); }

@media (min-width: 900px) {
    /* Deux colonnes égales. On lève simplement le plafond de largeur : le
       SVG porte son propre rapport (470 × 760 via la viewBox), il remplit
       donc sa moitié et se met à l'échelle tout seul. Pas de hauteur
       imposée — une boîte étirée laisserait la carte flotter au milieu
       d'un vide sur les écrans moyens, où la liste est bien plus haute. */
    .atlas__grid { grid-template-columns: 1fr 1fr; }
    .atlas__map { max-width: none; margin-inline: 0; }
}
</style>
