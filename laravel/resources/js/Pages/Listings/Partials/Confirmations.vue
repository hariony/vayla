<script setup>
/**
 * « Ce que les voyageurs ont confirmé » — la réponse de Vayla aux avis.
 *
 * **Pas d'étoiles, et c'est le sujet.** Une note sur cinq agrège tout en un
 * chiffre : on ne sait pas si le 4,6 vient d'une douche froide ou d'une
 * adresse fausse. Elle se fabrique, et sur les grandes plateformes tout le
 * monde finit à 4,8 — ce qui n'informe plus personne. Ici chaque point est
 * une question fermée sur un fait vérifiable, et le voyageur peut répondre
 * non.
 *
 * Deux règles de dessin qui portent tout le sens :
 *
 * 1. **Un point signalé s'affiche à côté du point confirmé, à la même
 *    taille.** Enterrer les « non » en bas de page est exactement ce que
 *    Vayla reproche aux autres. La part signalée est une portion visible de
 *    la barre, pas une note de bas de page.
 * 2. **Le lagon est légitime ici** — c'est de la vérification, son seul
 *    emploi autorisé. Le gris tient ce qui a été signalé : pas le rouge, qui
 *    dramatiserait un fait rapporté honnêtement.
 *
 * L'état vide n'est pas un raté à cacher : c'est l'état réel aujourd'hui, et
 * il explique ce qu'une confirmation contiendra. Mieux vaut une promesse
 * tenue plus tard qu'un mur d'avis inventés.
 */
import { computed } from 'vue'
import AmenityIcon from '@/Components/AmenityIcon.vue'

const props = defineProps({
    summary: { type: Object, default: null },
    confirmations: { type: Array, default: () => [] },
    trust: { type: Number, default: 1 },
})

const stays = computed(() => props.summary?.stays ?? 0)
const nights = computed(() => props.summary?.nights ?? 0)
const points = computed(() => props.summary?.points ?? [])

const pct = (p) => (p.answered ? Math.round((p.confirmed / p.answered) * 100) : 0)
</script>

<template>
    <section class="cf" data-anim>
        <header class="cf__head">
            <h2 class="cf__title">
                <template v-if="stays">Ce que les voyageurs ont confirmé</template>
                <template v-else>Aucun séjour confirmé pour l'instant</template>
            </h2>

            <p class="cf__lede">
                <template v-if="stays">
                    <strong class="num">{{ stays }}</strong>
                    séjour{{ stays > 1 ? 's' : '' }} ·
                    <strong class="num">{{ nights }}</strong>
                    nuits au total. Pas de note sur cinq : chaque voyageur
                    coche ce qui correspondait — et signale ce qui ne
                    correspondait pas.
                </template>
                <template v-else>
                    Nous n'inventons pas d'avis. Dès qu'un voyageur aura dormi
                    ici, il confirmera point par point ce qui correspondait à
                    l'annonce — et signalera ce qui ne correspondait pas.
                </template>
            </p>
        </header>

        <!-- ── Le récapitulatif : une barre par fait vérifiable ── -->
        <ul v-if="points.length" class="cf__points" data-anim-group>
            <li v-for="p in points" :key="p.key" class="cf__point">
                <div class="cf__point-head">
                    <AmenityIcon :name="p.icon" />
                    <span class="cf__point-label">{{ p.label }}</span>
                    <span class="cf__point-score num">{{ p.confirmed }}/{{ p.answered }}</span>
                </div>

                <div class="cf__bar" role="img" :aria-label="`${p.long} : ${p.confirmed} sur ${p.answered}`">
                    <span class="cf__bar-fill" :style="{ width: `${pct(p)}%` }"></span>
                </div>

                <p v-if="p.flagged" class="cf__point-flag">
                    {{ p.flagged }} signalement{{ p.flagged > 1 ? 's' : '' }}
                </p>
            </li>
        </ul>

        <!-- ── Les séjours, un par un ── -->
        <ol v-if="confirmations.length" class="cf__list">
            <li v-for="(c, i) in confirmations" :key="i" class="cf__item">
                <header class="cf__item-head">
                    <span class="cf__who">{{ c.traveller }}</span>
                    <span v-if="c.from" class="cf__from">{{ c.from }}</span>
                    <span class="cf__when num">
                        {{ c.nights }} nuit{{ c.nights > 1 ? 's' : '' }} · {{ c.month }}
                    </span>
                </header>

                <p v-if="c.comment" class="cf__comment">{{ c.comment }}</p>

                <p class="cf__tally">
                    <span class="cf__ok">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m4 12.5 5 5L20 6.5" />
                        </svg>
                        {{ c.points.length }} point{{ c.points.length > 1 ? 's' : '' }} confirmé{{ c.points.length > 1 ? 's' : '' }}
                    </span>
                </p>

                <!-- Le signalement est aussi lisible que la confirmation. -->
                <p v-if="c.mismatch" class="cf__mismatch">
                    <span class="cf__mismatch-tag">Signalé</span>
                    {{ c.mismatch }}
                </p>
            </li>
        </ol>

        <!-- L'annonce entière est fictive, mais un témoignage rapporte les
             mots d'une personne : il porte son propre avertissement. -->
        <p v-if="summary?.demo" class="cf__demo">
            Exemple d'affichage. Ces confirmations sont fictives, comme
            l'annonce : aucun voyageur n'a encore confirmé de séjour sur Vayla.
        </p>

        <p v-if="!stays" class="cf__empty">
            <AmenityIcon name="shield" />
            Ce logement est au niveau {{ trust }} de l'échelle. Le niveau 4 ne
            s'obtient qu'ici, par des voyageurs qui y ont dormi.
        </p>
    </section>
</template>

<style scoped>
.cf {
    padding-top: clamp(2.5rem, 5vw, 3.5rem);
    border-top: 1px solid var(--line);
}

.cf__head { max-width: 46rem; }

.cf__title {
    margin: 0;
    font-size: clamp(1.35rem, 2.4vw, 1.6rem);
    font-weight: 800;
    letter-spacing: -.035em;
    color: var(--ink);
    text-wrap: balance;
}

.cf__lede {
    margin: .5rem 0 0;
    font-size: .93rem;
    line-height: 1.6;
    color: var(--text-2);
}
.cf__lede strong { color: var(--ink); font-weight: 800; }

/* ── Les barres ────────────────────────────────────────────────── */
.cf__points {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem clamp(1.5rem, 4vw, 3rem);
    margin: clamp(1.75rem, 3.5vw, 2.5rem) 0 0;
    padding: 0;
    list-style: none;
}

.cf__point-head {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: .5rem;
}
.cf__point-head :deep(.ai) { color: var(--text-3); }

.cf__point-label {
    flex: 1;
    font-size: .88rem;
    font-weight: 600;
    color: var(--ink);
}

.cf__point-score {
    font-size: .82rem;
    font-weight: 700;
    color: var(--text-3);
}

.cf__bar {
    height: .38rem;
    border-radius: 99px;
    /* Le fond de barre EST la part signalée : elle n'est pas cachée, elle
       est ce qui reste quand la confirmation s'arrête. */
    background: var(--line-2);
    overflow: hidden;
}

.cf__bar-fill {
    display: block;
    height: 100%;
    border-radius: 99px;
    background: var(--lagon-500);
    transition: width .8s var(--ease);
}

.cf__point-flag {
    margin: .4rem 0 0;
    font-size: .76rem;
    font-weight: 600;
    color: var(--text-3);
}

/* ── Les séjours ───────────────────────────────────────────────── */
.cf__list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem clamp(1.5rem, 4vw, 3rem);
    margin: clamp(2rem, 4vw, 3rem) 0 0;
    padding: 0;
    list-style: none;
}

.cf__item {
    padding: 1.25rem 1.4rem;
    border: 1px solid var(--line);
    border-radius: var(--r-md);
    background: var(--white);
}

.cf__item-head {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: .1rem .55rem;
}

.cf__who { font-size: .95rem; font-weight: 700; color: var(--ink); }
.cf__from { font-size: .82rem; color: var(--text-3); }
.cf__when { margin-left: auto; font-size: .78rem; color: var(--text-3); }

.cf__comment {
    margin: .7rem 0 0;
    font-size: .9rem;
    line-height: 1.65;
    color: var(--text-2);
}

.cf__tally { margin: .9rem 0 0; }

.cf__ok {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .8rem;
    font-weight: 700;
    color: var(--lagon-700);
}
.cf__ok svg { width: .8rem; height: .8rem; }

/* Signalé : gris et non rouge. Un fait rapporté honnêtement n'a pas à être
   dramatisé — c'est justement parce qu'il est là que le reste est crédible. */
.cf__mismatch {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: .5rem;
    margin: .8rem 0 0;
    padding: .7rem .85rem;
    border-radius: var(--r-sm);
    background: var(--off-2);
    font-size: .84rem;
    line-height: 1.55;
    color: var(--text-2);
}

.cf__mismatch-tag {
    flex: none;
    padding: .1rem .45rem;
    border-radius: var(--r-pill);
    background: var(--ink);
    color: var(--white);
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
}

.cf__demo,
.cf__empty {
    margin: 1.5rem 0 0;
    font-size: .82rem;
    line-height: 1.6;
    color: var(--text-3);
}

.cf__empty {
    display: flex;
    align-items: flex-start;
    gap: .55rem;
    max-width: 46rem;
    padding: .9rem 1.1rem;
    border-radius: var(--r-md);
    background: var(--off-2);
}
.cf__empty :deep(.ai) { margin-top: .1rem; color: var(--lagon-600); }

@media (min-width: 720px) {
    .cf__points { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .cf__list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (min-width: 1180px) {
    .cf__points { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (prefers-reduced-motion: reduce) {
    .cf__bar-fill { transition: none; }
}
</style>
