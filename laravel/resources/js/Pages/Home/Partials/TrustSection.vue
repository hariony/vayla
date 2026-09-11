<script setup>
/**
 * L'echelle de confiance depliee. Ailleurs « verifie » ne veut rien dire ;
 * ici c'est une echelle, et le gecko la grimpe.
 */
import TrustGauge from '@/Components/TrustGauge.vue'
import GeckoClimb from '@/Components/GeckoClimb.vue'
import { useTextes } from '@/Composables/useTextes.js'

defineProps({
    trustLevels: { type: Array, default: () => [] },
})

// Les textes viennent du back-office ; l'original reste dans `SiteTextCatalog`.
const { t, riche } = useTextes()
</script>

<template>
    <!-- ═══════════ CONFIANCE ═══════════ -->
    <section id="confiance" class="trust section">
        <div class="shell trust__grid">
            <div class="trust__aside">
                <p class="eyebrow eyebrow--lagon" data-anim>{{ t('accueil.confiance.surtitre') }}</p>
                <h2 class="display display--lg" data-anim v-html="riche('accueil.confiance.titre')" />
                <p class="lede trust__lede" data-anim v-html="riche('accueil.confiance.accroche')" />

                <div class="trust__demo" data-anim>
                    <TrustGauge :level="4" large />
                    <span class="trust__demo-txt">Niveau 4 — séjour confirmé</span>
                </div>
            </div>

            <ol class="ladder" data-anim>
                <div class="ladder__rail" aria-hidden="true">
                    <span class="ladder__stem" data-ladder-stem></span>
                    <GeckoClimb class="ladder__climber" data-ladder-climber />
                </div>

                <li v-for="t in trustLevels" :key="t.key" class="ladder__step" data-anim>
                    <span class="ladder__badge" :class="`ladder__badge--n${t.level}`">
                        <span class="num">{{ t.level }}</span>
                    </span>
                    <div class="ladder__body">
                        <h3 class="ladder__name">{{ t.name }}</h3>
                        <p class="ladder__sum">{{ t.summary }}</p>
                        <TrustGauge :level="t.level" class="ladder__gauge" />
                    </div>
                </li>
            </ol>
        </div>
    </section>
</template>

<style scoped>
/* ═══════════ CONFIANCE ═══════════ */

.trust {
    background:
        radial-gradient(58% 46% at 96% 4%, var(--lagon-050), transparent 62%),
        var(--white);
    border-top: 1px solid var(--line);
}

.trust__grid { display: grid; grid-template-columns: 1fr; gap: clamp(2.5rem, 5vw, 4.5rem); }

.trust__lede { margin: 1.2rem 0 0; max-width: 40ch; }

.trust__demo {
    display: inline-flex;
    align-items: center;
    gap: .75rem;
    margin-top: 1.75rem;
    padding: .7rem 1.1rem;
    background: #fff;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    box-shadow: var(--sh-1);
}
.trust__demo-txt { font-size: .84rem; font-weight: 700; color: var(--lagon-700); }

.ladder {
    position: relative;
    margin: 0;
    padding: 0 0 0 4.25rem;
    list-style: none;
}

/* La tige et son grimpeur vivent dans leur propre couche */
.ladder__rail {
    position: absolute;
    /* Démarre sous le badge 1 : au repos le gecko ne le recouvre plus. */
    inset: 4.6rem auto 3.5rem 0;
    width: 40px;
}

.ladder__stem {
    position: absolute;
    left: 19px;
    top: 0;
    bottom: 0;
    width: 2px;
    border-radius: 2px;
    background: linear-gradient(180deg, var(--unverified), var(--lagon-300) 28%, var(--lagon-500) 62%, var(--lagon-700));
    transform-origin: top center;
}

.ladder__climber {
    position: absolute;
    left: 20px;
    top: 0;
    translate: -50% 0;
    z-index: 2;
}

.ladder__step {
    position: relative;
    display: flex;
    gap: 1.15rem;
    padding: 1.4rem 0;
}
.ladder__step + .ladder__step { border-top: 1px solid var(--line); }

.ladder__badge {
    position: absolute;
    left: -4.25rem;
    top: 1.4rem;
    display: grid;
    place-items: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: .95rem;
    font-weight: 800;
    background: #fff;
    border: 1.5px solid var(--line-2);
    color: var(--text-3);
}

/* Le badge 1 garde le gris du non-vérifié (règle par défaut ci-dessus). */
.ladder__badge--n2 { border-color: var(--lagon-300); color: var(--lagon-600); }
.ladder__badge--n3 { border-color: var(--lagon-500); background: var(--lagon-050); color: var(--lagon-700); }
.ladder__badge--n4 { border-color: transparent; background: var(--lagon-600); color: #fff; box-shadow: var(--sh-lagon); }

.ladder__body { flex: 1; }

.ladder__name {
    margin: 0 0 .4rem;
    font-size: 1.1rem;
    font-weight: 800;
    letter-spacing: -.028em;
    color: var(--ink);
}

.ladder__sum { margin: 0 0 .8rem; font-size: .95rem; color: var(--text-2); max-width: 52ch; }

@media (min-width: 1000px) {
    .trust__grid { grid-template-columns: minmax(0, .82fr) minmax(0, 1fr); gap: clamp(3rem, 6vw, 6rem); }
    .trust__aside { position: sticky; top: calc(var(--header-h) + 2.5rem); align-self: start; }
}
</style>
