<script setup>
/**
 * Le sens inverse : le voyageur decrit son sejour, on va chercher chez les
 * proprietaires. La maquette telephone montre le fil WhatsApp, qui est le
 * canal reel.
 */
import { Link } from '@inertiajs/vue3'

import SceneArt from '@/Components/SceneArt.vue'
import TrustGauge from '@/Components/TrustGauge.vue'
import { useTextes } from '@/Composables/useTextes.js'
import { lienDemande } from '@/Support/liens.js'

defineProps({
    /** La recherche en cours : la demande part pré-remplie. */
    criteres: { type: Object, default: () => ({}) },
})

// Les textes viennent du back-office ; l'original reste dans `SiteTextCatalog`.
const { t, riche } = useTextes()
</script>

<template>
    <!-- ═══════════ DEMANDE DE SÉJOUR ═══════════ -->
    <section id="demande" class="ask section">
        <div class="shell ask__grid">
            <div class="ask__copy">
                <p class="eyebrow" data-anim>{{ t('accueil.demande.surtitre') }}</p>
                <h2 class="display display--lg" data-anim>
                    {{ t('accueil.demande.titre') }}<br>
                    <em>{{ t('accueil.demande.titre_accent') }}</em>
                </h2>
                <p class="lede ask__lede" data-anim v-html="riche('accueil.demande.accroche')" />

                <ol class="ask__steps" data-anim-group>
                    <li class="ask__step">
                        <span class="ask__step-n num">01</span>
                        <span>{{ t('accueil.demande.etape1') }}</span>
                    </li>
                    <li class="ask__step">
                        <span class="ask__step-n num">02</span>
                        <span>{{ t('accueil.demande.etape2') }}</span>
                    </li>
                    <li class="ask__step">
                        <span class="ask__step-n num">03</span>
                        <span>{{ t('accueil.demande.etape3') }}</span>
                    </li>
                </ol>

                <!-- Il pointait sur `#demande`, c'est-à-dire sur lui-même : il n'y
                     avait rien derrière. La demande emporte la recherche en cours. -->
                <Link :href="lienDemande(criteres)" class="btn btn--terre btn--lg" data-anim>Déposer une demande</Link>
            </div>

            <div class="ask__phone" data-anim data-depth="0.14">
                <div class="phone">
                    <div class="phone__notch" aria-hidden="true"></div>
                    <div class="phone__screen">
                        <p class="phone__head">Vayla · demandes</p>
                        <div class="phone__bubble phone__bubble--in">
                            <strong>Nosy Be</strong> · 12–19 déc. · 4 voyageurs<br>
                            budget 150 000 Ar / nuit
                        </div>
                        <div class="phone__bubble phone__bubble--out">
                            3 propriétaires ont répondu. On en a écarté un :
                            photos reprises d'une autre annonce.
                        </div>
                        <div class="phone__card">
                            <span class="phone__card-art"><SceneArt variant="lagoon" /></span>
                            <span class="phone__card-txt">
                                <strong>Villa vue lagon</strong>
                                <TrustGauge :level="4" />
                                <span class="phone__card-price num">185 000 Ar</span>
                            </span>
                        </div>
                        <div class="phone__bubble phone__bubble--out phone__bubble--last">
                            Visite en visio possible demain 9 h ?
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* ═══════════ DEMANDE ═══════════ */

.ask { border-top: 1px solid var(--line); }

.ask__grid { display: grid; grid-template-columns: 1fr; gap: clamp(2.5rem, 5vw, 4.5rem); align-items: center; }

.ask__lede { margin: 1.2rem 0 2rem; max-width: 44ch; }

.ask__steps { margin: 0 0 2.25rem; padding: 0; list-style: none; display: grid; gap: .85rem; }

.ask__step {
    display: flex;
    gap: .9rem;
    align-items: baseline;
    font-size: .98rem;
    font-weight: 500;
    color: var(--text-2);
}

.ask__step-n {
    font-size: .78rem;
    font-weight: 800;
    letter-spacing: .04em;
    color: var(--terre-500);
    flex: none;
}

/* Le téléphone : la démonstration du canal WhatsApp, pas une capture */
.ask__phone { display: flex; justify-content: center; }

.phone {
    position: relative;
    width: min(320px, 100%);
    padding: 12px;
    background: var(--ink);
    border-radius: 42px;
    box-shadow: var(--sh-3);
}

.phone__notch {
    position: absolute;
    top: 18px; left: 50%;
    translate: -50% 0;
    width: 86px; height: 6px;
    border-radius: 3px;
    background: rgba(255, 255, 255, .22);
}

.phone__screen {
    display: flex;
    flex-direction: column;
    gap: .55rem;
    padding: 2.4rem 1rem 1.2rem;
    background: var(--white);
    border-radius: 32px;
    min-height: 430px;
}

.phone__head {
    margin: 0 0 .35rem;
    font-size: .7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .16em;
    color: var(--text-3);
}

.phone__bubble {
    max-width: 88%;
    padding: .65rem .85rem;
    font-size: .82rem;
    line-height: 1.5;
    border-radius: var(--r-md);
}

.phone__bubble--in {
    align-self: flex-end;
    background: var(--terre-500);
    color: #fff;
    border-bottom-right-radius: 6px;
}
.phone__bubble--in strong { font-weight: 800; }

.phone__bubble--out {
    align-self: flex-start;
    background: var(--off-2);
    color: var(--text);
    border-bottom-left-radius: 6px;
}

.phone__bubble--last { margin-top: auto; }

.phone__card {
    display: grid;
    grid-template-columns: 72px minmax(0, 1fr);
    gap: .7rem;
    align-self: flex-start;
    width: 88%;
    padding: .5rem;
    background: #fff;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    box-shadow: var(--sh-1);
}

.phone__card-art { display: block; width: 72px; height: 58px; overflow: hidden; border-radius: var(--r-xs); }
.phone__card-txt { display: flex; flex-direction: column; gap: .3rem; justify-content: center; }
.phone__card-txt strong { font-size: .84rem; font-weight: 800; letter-spacing: -.02em; }
.phone__card-price { font-size: .82rem; font-weight: 700; color: var(--ink); }

@media (min-width: 900px) {
    .ask__grid { grid-template-columns: minmax(0, 1fr) minmax(0, .68fr); }
}
</style>
