<script setup>
/**
 * Carte d'annonce. Vue de haut, elle ressemble à n'importe quel marché
 * de location ; ce qui la distingue est la jauge de confiance posée sur
 * l'image, avant même le prix.
 */
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import SceneArt from './SceneArt.vue'
import TrustGauge from './TrustGauge.vue'
import { photoSrc, photoSrcset } from '@/Support/photo.js'
import { nombre } from '@/Support/format.js'
import { useDevise } from '@/Composables/useDevise.js'

const props = defineProps({
    listing: { type: Object, required: true },
    photos: { type: Object, default: () => ({}) },
    wide: { type: Boolean, default: false },
    /** Le séjour en cours de recherche, à reporter sur la fiche. */
    stay: { type: Object, default: null },
})

const TRUST_LABELS = {
    1: 'Déclarée',
    2: 'Contact confirmé',
    3: 'Visité en visio',
    4: 'Séjour confirmé',
}

const price = computed(() =>
    nombre(props.listing.price)
)

// L'euro en second, jamais seul : le voyageur règle en ariary sur place.
const { euros } = useDevise()
const priceEur = computed(() => euros(props.listing.price))

const trustLabel = computed(() => TRUST_LABELS[props.listing.trust] ?? TRUST_LABELS[1])

/**
 * Le séjour saisi suit le clic. Sans lui, un voyageur qui a posé ses dates
 * dans le moteur les retrouvait effacées sur la fiche et devait les ressaisir
 * dans un second calendrier — le moment exact où l'on abandonne.
 */
const href = computed(() => {
    const base = `/logements/${props.listing.slug}`
    const s = props.stay

    if (!s?.arrival || !s?.departure) return base

    return `${base}?arrival=${s.arrival}&departure=${s.departure}`
})
</script>

<template>
    <article class="lc card" :class="{ 'lc--wide': wide }">
        <Link class="lc__media" :href="href" :aria-label="listing.title">
            <!-- Photo si la fiche en porte une, sinon l'illustration générée.
                 Le repli existe pour le jour où un propriétaire publie sans image. -->
            <img
                v-if="listing.photo"
                class="lc__art lc__photo"
                :src="photoSrc(photos[listing.photo], 800)"
                :srcset="photoSrcset(photos[listing.photo])"
                sizes="(min-width: 1120px) 350px, (min-width: 640px) 45vw, 92vw"
                :alt="photos[listing.photo]?.caption ?? listing.title"
                width="800"
                height="600"
                loading="lazy"
                decoding="async"
            >
            <SceneArt v-else :variant="listing.scene" class="lc__art" />

            <span class="lc__trust" :class="`lc__trust--n${listing.trust}`">
                <TrustGauge :level="listing.trust" />
                <span class="lc__trust-txt">{{ trustLabel }}</span>
            </span>

            <span v-if="listing.trust === 4" class="lc__seal" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="m4 12.5 5 5L20 6.5" />
                </svg>
            </span>
        </Link>

        <div class="lc__body">
            <header class="lc__head">
                <h3 class="lc__title">
                    <Link :href="href" class="lc__link">{{ listing.title }}</Link>
                </h3>
                <p class="lc__place">{{ listing.place }}<span aria-hidden="true"> · </span><span class="lc__region">{{ listing.region }}</span></p>
            </header>

            <ul class="lc__perks">
                <li v-for="p in listing.perks" :key="p" class="chip chip--line">{{ p }}</li>
            </ul>

            <footer class="lc__foot">
                <div class="lc__money">
                    <p class="lc__price">
                        <span class="num lc__amount">{{ price }}</span>
                        <span class="lc__unit">Ar&nbsp;/&nbsp;nuit</span>
                    </p>
                    <p v-if="priceEur" class="eur">{{ priceEur }}&nbsp;/&nbsp;nuit</p>
                </div>
                <p class="lc__capacity num">{{ listing.guests }} pers. max · {{ listing.bedrooms }} ch.</p>
            </footer>
        </div>
    </article>
</template>

<style scoped>
.lc {
    position: relative;
    isolation: isolate;
    display: flex;
    flex-direction: column;
    height: 100%;
}

/* La carte ne « décolle » pas : elle se pose. L'ombre arrive, pas le vol. */
.lc:hover { transform: translateY(-4px); }

.lc__media {
    position: relative;
    display: block;
    aspect-ratio: 4 / 3;
    overflow: hidden;
    border-radius: var(--r-lg);
    background: var(--off-2);
    box-shadow: var(--sh-1);
    transition: box-shadow .5s var(--ease);
}
.lc:hover .lc__media { box-shadow: var(--sh-2); }
.lc__media:focus-visible { outline: 2.5px solid var(--terre-500); outline-offset: 3px; }

.lc__art {
    width: 100%;
    height: 100%;
    transition: transform 1.1s var(--ease);
}

.lc__photo {
    display: block;
    object-fit: cover;
}
.lc:hover .lc__art { transform: scale(1.055); }

/* Badge de confiance : verre dépoli, il vit sur l'image */
.lc__trust {
    position: absolute;
    left: .75rem;
    bottom: .75rem;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .42rem .7rem .42rem .6rem;
    border-radius: var(--r-pill);
    background: rgba(255, 255, 255, .92);
    backdrop-filter: saturate(160%) blur(10px);
    box-shadow: 0 2px 10px -4px rgba(23, 20, 28, .4);
}

.lc__trust-txt {
    font-size: .73rem;
    font-weight: 700;
    letter-spacing: -.01em;
    color: var(--ink);
}

/* Le libellé suit le niveau : gris tant que rien n'est vérifié,
   lagon dès qu'un contrôle a eu lieu. */
.lc__trust--n1 .lc__trust-txt { color: var(--text-3); }
.lc__trust--n2 .lc__trust-txt,
.lc__trust--n3 .lc__trust-txt { color: var(--lagon-600); }
.lc__trust--n4 .lc__trust-txt { color: var(--lagon-700); }

/* Sceau du niveau 4 : le seul endroit où le fuchsia est plein sur l'image */
.lc__seal {
    position: absolute;
    top: .75rem;
    right: .75rem;
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    color: #fff;
    background: var(--lagon-500);
    box-shadow: 0 4px 14px -4px rgba(14, 144, 128, .75);
}
.lc__seal svg { width: 16px; height: 16px; }

.lc__body {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: .8rem;
    padding: 1rem .15rem 0;
}

.lc__title {
    margin: 0;
    font-size: 1.02rem;
    font-weight: 700;
    line-height: 1.3;
    letter-spacing: -.022em;
    color: var(--ink);
    text-wrap: balance;
}

.lc__link { color: inherit; text-decoration: none; }
/* Toute la carte mène à la fiche. Elle se soulève au survol comme un lien :
   elle doit se cliquer comme un lien, pas seulement sur la photo et le titre.
   Le lien du titre s'étend sur la carte entière (`.lc` est positionnée). */
.lc__link::after { content: ''; position: absolute; inset: 0; z-index: 1; border-radius: var(--r-lg); }
.lc__link:hover { text-decoration: underline; text-underline-offset: 3px; }
.lc__link:focus-visible { outline: 2.5px solid var(--terre-500); outline-offset: 3px; border-radius: 4px; }

.lc__place {
    margin: .2rem 0 0;
    font-size: .88rem;
    font-weight: 500;
    color: var(--text-2);
}
.lc__region { color: var(--text-3); }

.lc__perks {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.lc__foot {
    margin-top: auto;
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: space-between;
    gap: .5rem 1rem;
    margin-top: auto;
    padding-top: .5rem;
}

/* L'ariary et l'euro forment **un seul bloc** dans le pied de carte : les
   mettre côte à côte dans le même conteneur flex faisait varier la largeur
   intrinsèque du prix, et la capacité passait à la ligne sur une carte et pas
   sur l'autre. */
.lc__money { min-width: 0; }
.lc__price { margin: 0; display: flex; align-items: baseline; gap: .3rem; }
.lc__money .eur { margin-top: .1rem; }

.lc__amount {
    font-size: 1.12rem;
    font-weight: 800;
    letter-spacing: -.035em;
    color: var(--ink);
}

.lc__unit { font-size: .8rem; font-weight: 500; color: var(--text-3); }

.lc__capacity { font-size: .8rem; font-weight: 500; color: var(--text-3); }

/* Carte vedette : l'image passe en paysage large et le corps se pose à côté.
   Pas d'aspect-ratio ici — combiné à height:100%, il fait calculer la largeur
   depuis la hauteur et l'image déborde sur toute la carte. */
@media (min-width: 900px) {
    .lc--wide { display: grid; grid-template-columns: 1.32fr 1fr; gap: 1.6rem; align-items: stretch; }
    .lc--wide .lc__media { aspect-ratio: auto; height: 100%; min-height: clamp(280px, 25vw, 380px); }
    .lc--wide .lc__body { padding-top: 0; gap: 1rem; justify-content: center; }
    .lc--wide .lc__title { font-size: clamp(1.3rem, 1.9vw, 1.7rem); letter-spacing: -.03em; }
    .lc--wide .lc__amount { font-size: 1.4rem; }
}
</style>
