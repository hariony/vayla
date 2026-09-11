<script setup>
/**
 * La seule dalle de couleur pleine de la page — la terre de laterite.
 * Tout le reste est blanc : c'est ce qui lui donne son poids.
 *
 * Elle recrute, mais elle porte aussi **l'entrée du compte** : c'est la seule
 * section de l'accueil où un propriétaire déjà inscrit se reconnaît, et sans
 * ce lien celui qui a perdu son lien WhatsApp n'avait aucun chemin depuis le
 * site.
 */
import { Link } from '@inertiajs/vue3'

import TrustGauge from '@/Components/TrustGauge.vue'
import { useTextes } from '@/Composables/useTextes.js'

// Les textes viennent du back-office ; l'original reste dans `SiteTextCatalog`.
const { t, riche } = useTextes()
</script>

<template>
    <!-- ═══════════ PROPRIÉTAIRES — la seule dalle pleine ═══════════ -->
    <section id="proprietaires" class="owners">
        <div class="shell owners__inner">
            <div class="owners__copy">
                <p class="eyebrow eyebrow--light" data-anim>{{ t('accueil.proprietaires.surtitre') }}</p>
                <h2 class="display display--lg owners__title" data-anim>{{ t('accueil.proprietaires.titre') }}</h2>
                <p class="lede owners__lede" data-anim v-html="riche('accueil.proprietaires.accroche')" />
                <div class="owners__actions" data-anim>
                    <!-- Il pointait sur `#proprietaires`, c'est-à-dire sur
                         lui-même : le bouton principal de la section ne menait
                         nulle part. -->
                    <Link href="/proprietaire/inscription" class="btn btn--white btn--lg">Publier un logement</Link>
                    <a href="#confiance" class="btn btn--outline-light btn--lg">Comment on vérifie</a>
                </div>

                <!-- Discret, mais présent : cette section recrute, et celui
                     qui est déjà inscrit s'y reconnaît quand même. Sans ce
                     lien, un propriétaire qui a perdu son lien WhatsApp
                     n'avait aucun chemin depuis le site. -->
                <p class="owners__back" data-anim>
                    {{ t('accueil.proprietaires.deja') }}
                    <Link href="/proprietaire" class="owners__link">Accéder à mon espace</Link>
                </p>
            </div>

            <ul class="owners__list" data-anim-group>
                <li class="owners__item">
                    <TrustGauge :level="2" light />
                    <h3>{{ t('accueil.proprietaires.argument1_titre') }}</h3>
                    <p v-html="riche('accueil.proprietaires.argument1_texte')" />
                </li>
                <li class="owners__item">
                    <TrustGauge :level="3" light />
                    <h3>{{ t('accueil.proprietaires.argument2_titre') }}</h3>
                    <p v-html="riche('accueil.proprietaires.argument2_texte')" />
                </li>
                <li class="owners__item">
                    <TrustGauge :level="4" light />
                    <h3>{{ t('accueil.proprietaires.argument3_titre') }}</h3>
                    <p v-html="riche('accueil.proprietaires.argument3_texte')" />
                </li>
            </ul>
        </div>
    </section>
</template>

<style scoped>
/* ═══════════ PROPRIÉTAIRES ═══════════ */

.owners {
    position: relative;
    padding-block: clamp(4rem, 8vw, 7rem);
    color: #fff;
    background:
        radial-gradient(72% 90% at 88% 8%, var(--terre-400), transparent 58%),
        radial-gradient(60% 80% at 4% 96%, var(--terre-700), transparent 60%),
        var(--terre-500);
    overflow: hidden;
}

.owners::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: var(--grain);
    opacity: .07;
    mix-blend-mode: overlay;
    pointer-events: none;
}

.owners__inner { position: relative; display: grid; grid-template-columns: 1fr; gap: clamp(2.5rem, 5vw, 4rem); }

.owners__title { color: #fff; }
.owners__lede { margin: 1.2rem 0 2rem; max-width: 44ch; color: rgba(255, 255, 255, .86); }
.owners__actions { display: flex; flex-wrap: wrap; gap: .75rem; }

.owners__back { margin: 1.1rem 0 0; font-size: .88rem; color: rgba(255, 255, 255, .8); }
.owners__link { font-weight: 700; color: #fff; text-decoration: underline; text-underline-offset: .2em; }

.owners__list { margin: 0; padding: 0; list-style: none; display: grid; gap: 1.5rem; align-content: center; }

.owners__item {
    padding-top: 1.1rem;
    border-top: 1px solid rgba(255, 255, 255, .28);
}
.owners__item h3 { margin: .7rem 0 .35rem; font-size: 1.02rem; font-weight: 800; letter-spacing: -.025em; }
.owners__item p { margin: 0; font-size: .92rem; line-height: 1.6; color: rgba(255, 255, 255, .82); }

@media (min-width: 900px) {
    .owners__inner { grid-template-columns: minmax(0, 1fr) minmax(0, .62fr); }
}
</style>
