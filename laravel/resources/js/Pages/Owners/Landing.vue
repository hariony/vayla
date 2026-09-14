<script setup>
/**
 * **`/louer-mon-logement` — la page où mène la publicité.**
 *
 * On y arrive depuis Facebook, au téléphone, en ayant lu une phrase. La page a
 * cinq secondes pour dire trois choses, et chacune a sa forme :
 *
 * 1. **Ce qu'on obtient : une annonce qu'on croit.** Une fiche d'exemple, sur
 *    une vraie photographie, dont la jauge monte sous les yeux de « déclarée »
 *    à « visité ». Elle s'arrête au niveau 3 : le quatrième ne s'obtient
 *    qu'avec un séjour confirmé.
 * 2. **Ce que ça coûte : 0 Ar.** C'est la question de celui qui hésite. La
 *    seule dalle terre de la page lui est donnée, et le montant y fond jusqu'à
 *    zéro.
 * 3. **Ce qui va se passer.** Trois étapes sur un fil qui se remplit. S'inscrire
 *    n'est pas publier, et la page le dit avant le bouton du bas.
 *
 * **Elle ne promet pas de clients** : le site n'est pas encore ouvert aux
 * voyageurs, et une marque dont l'argument est la vérification ne commence pas
 * par un chiffre qu'elle ne tient pas.
 *
 * **Un seul geste, répété** : « Inscrire mon logement », en haut, en bas, et
 * collé au bas de l'écran sur téléphone quand les deux autres sont hors de
 * vue. Les arguments reprennent les textes `accueil.proprietaires.*`,
 * modifiables depuis le back-office.
 */
import { computed, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'

import SiteHeader from '@/Components/SiteHeader.vue'
import TrustGauge from '@/Components/TrustGauge.vue'
import { useLandingMotion } from '@/Composables/useLandingMotion.js'
import { useTextes } from '@/Composables/useTextes.js'
import { photoSrc, photoSrcset } from '@/Support/photo.js'

const props = defineProps({
    lieu: { type: Object, default: null },
    fiche: { type: Object, default: null },
    niveaux: { type: Array, required: true },
})

const page = usePage()
const { t } = useTextes()

// Déjà connecté, il ne vient plus s'inscrire : il vient finir sa fiche.
const connecte = computed(() => Boolean(page.props.auth?.owner))
const cta = computed(() => (connecte.value
    ? { href: '/proprietaire/logements', label: 'Compléter mes logements' }
    : { href: '/proprietaire/inscription', label: 'Inscrire mon logement' }))

const legales = computed(() => page.props.pied?.legal ?? [])
const credits = computed(() => [props.lieu, props.fiche].filter((p) => p?.author))

// Le niveau affiché par la fiche d'exemple : 3 au repos, animé par la chorégraphie.
const niveau = ref(3)
const nomNiveau = computed(() => props.niveaux.find((n) => n.level === niveau.value)?.name ?? '')

const barre = ref(false)
const racine = ref(null)
useLandingMotion(racine, { niveau, barre })

const ARGUMENTS = [1, 2, 3]
</script>

<template>
    <Head title="Inscrire mon logement — Vayla">
        <meta name="description" content="Vayla ouvre bientôt. Inscrivez gratuitement votre villa ou votre appartement meublé à Madagascar : il sera vérifié et prêt pour l'ouverture.">
    </Head>

    <SiteHeader :search="false" />

    <main ref="racine" class="ld">
        <!-- ═══════════ L'ACCROCHE ═══════════ -->
        <section class="ld-hero">
            <div class="shell ld-hero__grille">
                <div class="ld-hero__txt">
                    <p class="eyebrow ld-hero__brow" data-ld-brow>
                        <span class="ld-hero__point" aria-hidden="true"></span>
                        Vayla Madagascar ouvre bientôt · Propriétaires
                    </p>

                    <h1 class="ld-titre">
                        <span class="ld-titre__ligne" data-ld-ligne><span>Votre logement <span class="ld-meuble"><i class="ld-meuble__fond" data-ld-meuble aria-hidden="true"></i><span class="ld-meuble__mot">meublé</span></span>,</span></span>
                        <span class="ld-titre__ligne" data-ld-ligne><span><span class="uline"><i class="uline__bar ld-titre__barre" data-underline aria-hidden="true"></i><span class="uline__txt">enfin crédible.</span></span></span></span>
                    </h1>

                    <p class="lede ld-hero__lede" data-ld-item>
                        Inscrivez votre villa ou votre appartement meublé, prêt à accueillir des voyageurs.
                        Vayla le vérifie avec vous, et votre annonce est prête le jour de l'ouverture.
                    </p>

                    <div class="ld-hero__cta" data-ld-item data-cta-haut>
                        <Link :href="cta.href" class="btn btn--terre btn--lg ld-bouton">
                            {{ cta.label }}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                        </Link>
                        <ul class="ld-rassure">
                            <li>Gratuit</li>
                            <li>Sans mot de passe</li>
                            <li>Depuis votre téléphone</li>
                        </ul>
                    </div>

                    <p v-if="!connecte" class="ld-deja" data-ld-item>
                        {{ t('accueil.proprietaires.deja') }}
                        <Link href="/proprietaire" class="ld-lien">Accéder à mon espace</Link>
                    </p>
                </div>

                <!-- La fiche d'exemple, posée sur le lieu. Décorative pour la
                     structure, mais la jauge dit son niveau en toutes lettres. -->
                <figure class="ld-visuel" data-ld-visuel>
                    <div v-if="lieu" class="ld-cadre" data-ld-cadre>
                        <img :src="photoSrc(lieu, 1600)" :srcset="photoSrcset(lieu)" sizes="(min-width: 1000px) 34vw, 70vw"
                             :alt="lieu.caption" width="1600" height="1200" fetchpriority="high">
                    </div>

                    <article class="ld-fiche" data-ld-fiche>
                        <div class="ld-fiche__photo">
                            <img v-if="fiche" :src="photoSrc(fiche, 800)" :srcset="photoSrcset(fiche)" sizes="(min-width: 1000px) 22rem, 72vw"
                                 :alt="fiche.caption" width="800" height="600">
                            <span class="ld-fiche__exemple">Exemple</span>
                        </div>
                        <div class="ld-fiche__corps">
                            <p class="ld-fiche__lieu">Ampefy</p>
                            <p class="ld-fiche__titre">Villa meublée avec piscine</p>
                            <div class="ld-fiche__niveau">
                                <TrustGauge :level="niveau" :label="nomNiveau" />
                                <Transition name="ld-mot" mode="out-in">
                                    <span :key="niveau" class="ld-fiche__nom" :class="{ 'is-verifie': niveau > 1 }">{{ nomNiveau }}</span>
                                </Transition>
                            </div>
                            <p class="ld-fiche__prix">Votre prix, <strong>en ariary</strong></p>
                        </div>
                    </article>

                    <span class="ld-pastille ld-pastille--haut" data-ld-pastille aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3" /><path d="m3 15 5-5 4 4 3-3 6 6" /></svg>
                        {{ t('accueil.proprietaires.argument1_titre') }}
                    </span>
                    <span class="ld-pastille ld-pastille--bas" data-ld-pastille aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="6" width="13" height="12" rx="2.5" /><path d="m15.5 10.5 6-3v9l-6-3" /></svg>
                        {{ t('accueil.proprietaires.argument2_titre') }}
                    </span>
                </figure>
            </div>
        </section>

        <!-- ═══════════ CE QUE ÇA COÛTE — la seule dalle terre ═══════════ -->
        <section class="ld-dalle" aria-labelledby="ld-dalle-t">
            <div class="shell ld-dalle__grille">
                <p class="ld-dalle__brow" data-dalle-item>Ce que ça vous coûte</p>
                <p class="ld-dalle__prix num" data-prix data-dalle-item>0&nbsp;Ar</p>
                <h2 id="ld-dalle-t" class="ld-dalle__titre" data-dalle-item>
                    pour vous inscrire, décrire votre logement et le faire vérifier.
                </h2>
                <p class="ld-dalle__texte" data-dalle-item>
                    Vayla ne prend une commission que sur un séjour <strong>effectué et confirmé par le voyageur</strong>,
                    jamais sur une réservation. Et Vayla n'encaisse rien : le voyageur vous règle directement.
                </p>
            </div>
        </section>

        <!-- ═══════════ CE QUI VA SE PASSER ═══════════ -->
        <section class="shell ld-etapes" aria-labelledby="ld-etapes-t">
            <div class="ld-etapes__tete">
                <p class="eyebrow">Comment ça se passe</p>
                <h2 id="ld-etapes-t" class="ld-h2">Trois étapes, depuis votre téléphone.</h2>
                <p class="ld-etapes__note">
                    <TrustGauge :level="1" />
                    <span><strong>S'inscrire n'est pas publier.</strong> Votre annonce part au premier niveau ; c'est l'appel de vérification qui la fait monter.</span>
                </p>
            </div>

            <div class="ld-fil" data-etapes>
                <span class="ld-fil__rail" aria-hidden="true"><span class="ld-fil__plein" data-fil></span></span>
                <ol class="ld-fil__liste">
                <li class="ld-etape" data-etape>
                    <span class="ld-etape__n num" aria-hidden="true">1</span>
                    <h3 class="ld-etape__t">Vous créez votre compte</h3>
                    <p class="ld-etape__s">Votre adresse e-mail, un code pour la confirmer, puis votre nom et votre numéro WhatsApp. Aucun mot de passe à retenir.</p>
                </li>
                <li class="ld-etape" data-etape>
                    <span class="ld-etape__n num" aria-hidden="true">2</span>
                    <h3 class="ld-etape__t">Vous décrivez votre logement</h3>
                    <p class="ld-etape__s">Photos, couchages, équipements, tarif en ariary. À votre rythme : tout s'enregistre, vous revenez quand vous voulez.</p>
                </li>
                <li class="ld-etape" data-etape>
                    <span class="ld-etape__n num" aria-hidden="true">3</span>
                    <h3 class="ld-etape__t">Vayla vous appelle pour le vérifier</h3>
                    <p class="ld-etape__s">Un appel vidéo, depuis chez vous. Vérifiée, votre annonce est prête pour l'ouverture, avec son niveau.</p>
                </li>
                </ol>
            </div>
        </section>

        <!-- ═══════════ POURQUOI VAYLA ═══════════ -->
        <section class="shell ld-args" aria-label="Pourquoi Vayla">
            <article v-for="n in ARGUMENTS" :key="n" class="ld-arg" data-reveal>
                <span class="ld-arg__n num" aria-hidden="true">0{{ n }}</span>
                <h2 class="ld-arg__t">{{ t(`accueil.proprietaires.argument${n}_titre`) }}</h2>
                <p class="ld-arg__s">{{ t(`accueil.proprietaires.argument${n}_texte`) }}</p>
            </article>
        </section>

        <!-- ═══════════ LA FIN ═══════════ -->
        <section class="shell ld-fin" data-reveal>
            <h2 class="ld-fin__titre">Prêt le jour de l'ouverture.</h2>
            <p class="ld-fin__texte">Les premiers logements vérifiés seront les premiers que les voyageurs verront.</p>
            <div class="ld-fin__cta" data-cta-bas>
                <Link :href="cta.href" class="btn btn--terre btn--lg ld-bouton">
                    {{ cta.label }}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </Link>
                <p class="ld-fin__question">Une question avant de vous inscrire ? Écrivez à Vayla sur WhatsApp.</p>
            </div>
        </section>

        <footer class="shell ld-pied">
            <nav v-if="legales.length" class="ld-pied__legal" aria-label="Informations légales">
                <Link v-for="l in legales" :key="l.href" :href="l.href">{{ l.titre }}</Link>
            </nav>
            <!-- CC BY et CC BY-SA l'exigent : chaque photographie affichée est créditée. -->
            <p v-if="credits.length" class="ld-pied__credits">
                Photos :
                <template v-for="(p, i) in credits" :key="p.key">
                    <template v-if="i">, </template>
                    {{ p.caption }} — {{ p.author }},
                    <a v-if="p.licence_url" :href="p.licence_url" rel="noopener" target="_blank">{{ p.licence }}</a>
                    <template v-else>{{ p.licence }}</template>
                </template>
            </p>
        </footer>

        <!-- Au téléphone, le geste reste à portée de pouce quand les deux
             boutons de la page sont hors de l'écran. -->
        <div class="ld-barre" :class="{ 'is-visible': barre }" :inert="!barre">
            <Link :href="cta.href" class="btn btn--terre ld-barre__bouton">{{ cta.label }}</Link>
        </div>
    </main>
</template>

<style scoped>
.ld { overflow-x: clip; }

/* ═══════════ L'ACCROCHE ═══════════ */

/* Pas un aplat : une lueur de latérite et un grain très fin, comme les écrans
   d'accès. Il n'y a pas de dalle, il y a de l'air. */
.ld-hero {
    position: relative;
    isolation: isolate;
    padding-top: calc(var(--header-h) + clamp(1.75rem, 5vw, 4rem));
    padding-bottom: clamp(3rem, 8vw, 6rem);
    background:
        radial-gradient(60rem 40rem at 88% 12%, rgba(201, 69, 42, .13), transparent 62%),
        radial-gradient(40rem 30rem at -10% 90%, rgba(201, 69, 42, .07), transparent 60%),
        var(--white);
}
.ld-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    z-index: -1;
    pointer-events: none;
    opacity: .5;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3'/%3E%3CfeColorMatrix type='saturate' values='0'/%3E%3C/filter%3E%3Crect width='140' height='140' filter='url(%23n)' opacity='.055'/%3E%3C/svg%3E");
}

.ld-hero__grille {
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr);
    align-items: center;
    gap: clamp(2.5rem, 6vw, 5rem);
}

/* Un cran au-dessus du sur-titre ordinaire (.7 rem) : c'est la première ligne
   lue en arrivant de la publicité, elle dit où l'on est. */
.ld-hero__brow { margin: 0; font-size: clamp(.8rem, 1.2vw, .9rem); gap: .6rem; }
.ld-hero__point {
    width: .5rem;
    height: .5rem;
    border-radius: 50%;
    background: var(--terre-500);
    box-shadow: 0 0 0 0 rgba(201, 69, 42, .45);
    animation: ld-pouls 2.2s ease-out infinite;
}
@keyframes ld-pouls {
    0% { box-shadow: 0 0 0 0 rgba(201, 69, 42, .45); }
    70%, 100% { box-shadow: 0 0 0 .6rem rgba(201, 69, 42, 0); }
}

/* Le titre tient sur deux lignes à 390 px : « enfin crédible. » porte le
   surlignage, en `nowrap`, et reste sous vingt caractères. */
.ld-titre {
    margin: 1.1rem 0 0;
    font-size: clamp(2.1rem, 5.6vw, 4.2rem);
    font-weight: 800;
    line-height: .98;
    letter-spacing: -.045em;
    color: var(--ink);
}
.ld-titre__ligne { display: block; overflow: hidden; padding-block: .04em .08em; }
.ld-titre__ligne > span { display: block; }
.ld-titre__barre { background: var(--terre-300, var(--terre-200)); opacity: .55; }

/* **« Meublé » se détache du titre**, parce que c'est ce qui trie : une maison
   vide louée à l'année n'est pas un logement Vayla. Le mot passe à la terre,
   posé sur une pastille pâle qui se déploie à l'entrée — la même famille que
   le surlignage de la ligne suivante, pas une deuxième couleur. */
.ld-meuble { position: relative; display: inline-block; white-space: nowrap; padding-inline: .1em; }
.ld-meuble__fond {
    position: absolute;
    inset: .12em -.02em .02em;
    border-radius: .22em;
    background: var(--terre-050);
    box-shadow: inset 0 0 0 .045em var(--terre-200);
    transform-origin: left center;
}
.ld-meuble__mot { position: relative; color: var(--terre-600); }

.ld-hero__lede { margin: 1.4rem 0 0; max-width: 40ch; }

.ld-hero__cta { display: grid; justify-items: start; gap: .9rem; margin-top: 2rem; }
.ld-bouton { gap: .6rem; box-shadow: 0 14px 30px -14px rgba(201, 69, 42, .75); }
.ld-bouton svg { width: 1.15rem; height: 1.15rem; transition: transform .3s var(--ease); }
.ld-bouton:hover svg, .ld-bouton:focus-visible svg { transform: translateX(4px); }

.ld-rassure { display: flex; flex-wrap: wrap; gap: .35rem 1.1rem; margin: 0; padding: 0; list-style: none; }
.ld-rassure li { display: inline-flex; align-items: center; gap: .4rem; font-size: .88rem; font-weight: 600; color: var(--text-2); }
/* La coche est de l'encre : le lagon ne dit que « vérifié ». */
.ld-rassure li::before {
    content: '';
    width: .45rem;
    height: .8rem;
    margin: -.2rem .1rem 0 .1rem;
    border: solid var(--ink);
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.ld-deja { margin: 1.4rem 0 0; font-size: .92rem; color: var(--text-2); }
.ld-lien { font-weight: 700; color: var(--ink); text-decoration: underline; text-underline-offset: .2em; }
.ld-lien:hover { color: var(--terre-600); }

/* ── La fiche posée sur le lieu ── */
.ld-visuel {
    position: relative;
    margin: 0;
    min-height: clamp(26rem, 48vw, 34rem);
}
.ld-cadre {
    position: absolute;
    inset: 0 0 12% 14%;
    border-radius: var(--r-lg);
    overflow: hidden;
    transform: rotate(3deg);
    box-shadow: var(--sh-2);
}
.ld-cadre img { width: 100%; height: 100%; object-fit: cover; display: block; }

.ld-fiche {
    position: absolute;
    left: 0;
    bottom: 0;
    width: min(21rem, 78%);
    border-radius: var(--r-lg);
    background: var(--white);
    box-shadow: 0 30px 60px -24px rgba(26, 21, 18, .45), 0 2px 6px rgba(26, 21, 18, .06);
    transform: rotate(-2.5deg);
    overflow: hidden;
}
.ld-fiche__photo { position: relative; aspect-ratio: 4 / 3; background: var(--off-2); }
.ld-fiche__photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
.ld-fiche__exemple {
    position: absolute;
    top: .7rem;
    left: .7rem;
    padding: .2rem .6rem;
    border-radius: var(--r-pill);
    background: rgba(255, 255, 255, .94);
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--ink);
}
.ld-fiche__corps { padding: .95rem 1.1rem 1.1rem; }
.ld-fiche__lieu { margin: 0; font-size: .74rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--text-3); }
.ld-fiche__titre { margin: .2rem 0 0; font-size: 1.08rem; font-weight: 700; letter-spacing: -.02em; color: var(--ink); }
.ld-fiche__niveau { display: flex; align-items: center; gap: .6rem; margin-top: .75rem; min-height: 1.5rem; }
.ld-fiche__nom { font-size: .84rem; font-weight: 700; color: var(--text-2); }
/* Le lagon, à sa place : sur un niveau vérifié, et nulle part ailleurs sur la page. */
.ld-fiche__nom.is-verifie { color: var(--lagon-700); }
.ld-fiche__prix { margin: .75rem 0 0; padding-top: .7rem; border-top: 1px solid var(--line); font-size: .84rem; color: var(--text-3); }
.ld-fiche__prix strong { color: var(--ink); }

.ld-mot-enter-active, .ld-mot-leave-active { transition: opacity .25s var(--ease), transform .25s var(--ease); }
.ld-mot-enter-from { opacity: 0; transform: translateY(6px); }
.ld-mot-leave-to { opacity: 0; transform: translateY(-6px); }

.ld-pastille {
    position: absolute;
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .5rem .85rem .5rem .6rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    box-shadow: var(--sh-1);
    font-size: .82rem;
    font-weight: 700;
    color: var(--ink);
    white-space: nowrap;
}
.ld-pastille svg { width: 1.15rem; height: 1.15rem; color: var(--terre-500); }
.ld-pastille--haut { top: 8%; left: 2%; }
.ld-pastille--bas { right: -2%; bottom: 20%; }

/* ═══════════ LA DALLE TERRE ═══════════ */
.ld-dalle {
    background: var(--terre-500);
    color: var(--white);
    padding-block: clamp(3.5rem, 9vw, 6.5rem);
}
.ld-dalle__grille { display: grid; justify-items: start; }
.ld-dalle__brow { margin: 0; font-size: .74rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: rgba(255, 255, 255, .85); }
.ld-dalle__prix {
    margin: .35rem 0 0;
    /* Assez grand pour « 185 000 Ar » en passant, sans déborder à 390 px. */
    font-size: clamp(3.4rem, 14.5vw, 11rem);
    font-weight: 800;
    line-height: .86;
    letter-spacing: -.06em;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.ld-dalle__titre {
    margin: 1.1rem 0 0;
    max-width: 22ch;
    font-size: clamp(1.5rem, 3.4vw, 2.5rem);
    font-weight: 700;
    line-height: 1.12;
    letter-spacing: -.03em;
    text-wrap: balance;
}
.ld-dalle__texte { margin: 1.25rem 0 0; max-width: 52ch; font-size: 1.02rem; line-height: 1.6; color: rgba(255, 255, 255, .92); }
.ld-dalle__texte strong { color: var(--white); }

/* ═══════════ LES ÉTAPES ═══════════ */
.ld-etapes {
    display: grid;
    grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr);
    gap: clamp(2rem, 6vw, 5rem);
    padding-block: clamp(3.5rem, 9vw, 6.5rem);
}
.ld-etapes__tete { align-self: start; position: sticky; top: calc(var(--header-h) + 2rem); }
.ld-h2 {
    margin: .7rem 0 0;
    font-size: clamp(1.9rem, 4vw, 3rem);
    font-weight: 800;
    line-height: 1.04;
    letter-spacing: -.04em;
    color: var(--ink);
    text-wrap: balance;
}
.ld-etapes__note { display: flex; align-items: flex-start; gap: .8rem; margin: 1.5rem 0 0; max-width: 40ch; font-size: .92rem; line-height: 1.55; color: var(--text-2); }
.ld-etapes__note .gauge { margin-top: .35rem; flex: none; }
.ld-etapes__note strong { color: var(--ink); }

.ld-fil { position: relative; padding-left: 3.6rem; }
.ld-fil__liste { display: grid; gap: clamp(2rem, 5vw, 3rem); margin: 0; padding: 0; list-style: none; }
.ld-fil__rail { position: absolute; left: 1.3rem; top: 1.4rem; bottom: 1.4rem; width: 3px; border-radius: 3px; background: var(--line); }
.ld-fil__plein { position: absolute; inset: 0; border-radius: inherit; background: var(--terre-500); transform-origin: top center; }

.ld-etape { position: relative; }
.ld-etape__n {
    position: absolute;
    left: -3.6rem;
    top: 0;
    display: grid;
    place-items: center;
    width: 2.75rem;
    height: 2.75rem;
    border: 2px solid var(--line-2);
    border-radius: 50%;
    background: var(--white);
    font-size: 1rem;
    font-weight: 800;
    color: var(--text-2);
    transition: background-color .35s var(--ease), border-color .35s var(--ease), color .35s var(--ease), transform .35s var(--ease);
}
/* Atteinte : le rond se remplit de terre, comme l'étape courante de la fiche. */
.ld-etape.is-atteinte .ld-etape__n { border-color: var(--terre-500); background: var(--terre-500); color: var(--white); transform: scale(1.06); }
.ld-etape__t { margin: .45rem 0 0; font-size: clamp(1.2rem, 2.2vw, 1.5rem); font-weight: 750; letter-spacing: -.025em; color: var(--ink); }
.ld-etape__s { margin: .5rem 0 0; max-width: 46ch; font-size: 1rem; line-height: 1.6; color: var(--text-2); }

/* ═══════════ POURQUOI ═══════════ */
.ld-args { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; padding-bottom: clamp(3.5rem, 9vw, 6rem); }
.ld-arg {
    padding: 1.5rem 1.4rem 1.6rem;
    border: 1px solid var(--line);
    border-radius: var(--r-lg);
    background: var(--off, var(--white));
    transition: transform .35s var(--ease), box-shadow .35s var(--ease), border-color .35s var(--ease);
}
.ld-arg:hover { transform: translateY(-4px); box-shadow: var(--sh-2); border-color: var(--line-2); }
@media (hover: none) { .ld-arg:hover { transform: none; box-shadow: none; } }
.ld-arg__n { font-size: 2.4rem; font-weight: 800; letter-spacing: -.05em; line-height: 1; color: var(--terre-500); }
.ld-arg__t { margin: 1rem 0 0; font-size: 1.2rem; font-weight: 750; letter-spacing: -.02em; color: var(--ink); }
.ld-arg__s { margin: .5rem 0 0; font-size: .96rem; line-height: 1.6; color: var(--text-2); }

/* ═══════════ LA FIN ═══════════ */
.ld-fin { display: grid; justify-items: center; text-align: center; padding-bottom: clamp(3.5rem, 9vw, 6rem); }
.ld-fin__titre { margin: 0; font-size: clamp(2.2rem, 6vw, 4.4rem); font-weight: 800; line-height: 1; letter-spacing: -.045em; color: var(--ink); text-wrap: balance; }
.ld-fin__texte { margin: 1rem 0 0; max-width: 44ch; font-size: 1.05rem; line-height: 1.6; color: var(--text-2); }
.ld-fin__cta { display: grid; justify-items: center; gap: .9rem; margin-top: 2rem; }
.ld-fin__question { margin: 0; font-size: .92rem; color: var(--text-2); }

.ld-pied { display: grid; gap: .6rem; padding-block: 1.5rem 7rem; border-top: 1px solid var(--line); }
.ld-pied__legal { display: flex; flex-wrap: wrap; gap: .4rem 1.4rem; }
.ld-pied__legal a { font-size: .85rem; color: var(--text-2); text-decoration: none; }
.ld-pied__legal a:hover { color: var(--ink); text-decoration: underline; }
.ld-pied__credits { margin: 0; font-size: .78rem; line-height: 1.6; color: var(--text-3); }
.ld-pied__credits a { color: inherit; }

/* ═══════════ LE BOUTON COLLÉ, AU TÉLÉPHONE ═══════════ */
.ld-barre {
    position: fixed;
    inset: auto 0 0;
    z-index: 90;
    display: none;
    padding: .75rem var(--gutter) calc(.75rem + env(safe-area-inset-bottom));
    background: rgba(255, 255, 255, .92);
    backdrop-filter: saturate(180%) blur(16px);
    border-top: 1px solid var(--line);
    transform: translateY(110%);
    transition: transform .4s var(--ease);
}
.ld-barre.is-visible { transform: translateY(0); }
.ld-barre__bouton { width: 100%; min-height: 3rem; justify-content: center; }

/* ═══════════ TÉLÉPHONE ═══════════ */
@media (max-width: 900px) {
    .ld-hero__grille { grid-template-columns: 1fr; }
    .ld-visuel { min-height: 25rem; max-width: 30rem; width: 100%; justify-self: center; }
    .ld-etapes { grid-template-columns: 1fr; }
    .ld-etapes__tete { position: static; }
    .ld-args { grid-template-columns: 1fr; }
}
@media (max-width: 760px) {
    .ld-barre { display: block; }
    .ld-pastille--bas { right: 0; }
}
@media (max-width: 420px) {
    .ld-visuel { min-height: 22rem; }
    .ld-pastille { font-size: .76rem; }
}

@media (prefers-reduced-motion: reduce) {
    .ld-hero__point { animation: none; }
    .ld-barre, .ld-etape__n, .ld-arg { transition: none; }
}
</style>
