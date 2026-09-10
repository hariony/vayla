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
</script>

<template>
    <!-- ═══════════ PROPRIÉTAIRES — la seule dalle pleine ═══════════ -->
    <section id="proprietaires" class="owners">
        <div class="shell owners__inner">
            <div class="owners__copy">
                <p class="eyebrow eyebrow--light" data-anim>Propriétaires</p>
                <h2 class="display display--lg owners__title" data-anim>
                    Votre annonce, enfin crédible.
                </h2>
                <p class="lede owners__lede" data-anim>
                    Vous perdez des séjours parce que le voyageur n'ose pas envoyer
                    l'acompte. Faites vérifier votre logement une fois : le niveau
                    s'affiche sur votre annonce, et il parle pour vous.
                </p>
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
                    Déjà propriétaire sur Vayla ?
                    <Link href="/proprietaire" class="owners__link">Accéder à mon espace</Link>
                </p>
            </div>

            <ul class="owners__list" data-anim-group>
                <li class="owners__item">
                    <TrustGauge :level="2" light />
                    <h3>Photos protégées</h3>
                    <p>On compare vos images au reste du web : personne ne réutilisera votre logement.</p>
                </li>
                <li class="owners__item">
                    <TrustGauge :level="3" light />
                    <h3>La visio suffit</h3>
                    <p>Une visite guidée par appel vidéo, depuis chez vous, et le niveau 3 est acquis.</p>
                </li>
                <li class="owners__item">
                    <TrustGauge :level="4" light />
                    <h3>Les séjours comptent</h3>
                    <p>Chaque séjour confirmé par un voyageur consolide votre niveau.</p>
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
