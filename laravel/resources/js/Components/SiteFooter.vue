<script setup>
/**
 * Pied de page. Blanc lui aussi : la page n'a plus de point final sombre,
 * elle se termine sur un mot géant en dégradé fuchsia.
 */
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import VaylaMark from './VaylaMark.vue'

// Les licences CC BY et CC BY-SA imposent de créditer l'auteur et de
// nommer la licence. On le fait ici, replié par défaut pour ne pas
// alourdir le pied de page.
const props = defineProps({
    credits: { type: Array, default: () => [] },
    // Comme dans l'en-tête : les ancres de section n'existent que sur
    // l'accueil, ailleurs elles doivent repartir vers `/`.
    home: { type: Boolean, default: false },
})

const anchor = (hash) => (props.home ? `#${hash}` : `/#${hash}`)

/**
 * `to` = une page qui existe (visite Inertia). Les autres restent inertes :
 * mieux vaut un lien mort visible qu'une page vide promise. Ils s'activeront
 * au fur et à mesure.
 */
const COLUMNS = [
    {
        title: 'Voyageurs',
        links: [
            { label: 'Rechercher un logement', to: '/logements' },
            { label: 'Déposer une demande de séjour', hash: 'demande' },
            { label: 'Comment ça marche', hash: 'confiance' },
            { label: 'Sécurité et confiance', hash: 'confiance' },
        ],
    },
    {
        title: 'Propriétaires',
        links: [
            { label: 'Publier un logement', to: '/proprietaire/inscription' },
            { label: 'Faire vérifier mon annonce', hash: 'proprietaires' },
            // L'entrée du compte, et non l'ancre de recrutement : un
            // propriétaire déjà inscrit qui a perdu son lien WhatsApp n'avait
            // aucun chemin depuis le site.
            { label: 'Accéder à mon espace', to: '/proprietaire' },
            { label: 'Tarifs et abonnements' },
            { label: 'Guide du propriétaire' },
        ],
    },
    {
        title: 'Vayla',
        links: [
            { label: 'À propos' },
            { label: 'Destinations couvertes', to: '/destinations' },
            { label: 'Nous contacter' },
            { label: 'Conditions d\'utilisation' },
        ],
    },
]

const columns = computed(() =>
    COLUMNS.map((c) => ({
        title: c.title,
        links: c.links.map((l) => ({
            label: l.label,
            href: l.to ?? (l.hash ? anchor(l.hash) : '#'),
            page: Boolean(l.to),
        })),
    }))
)

const year = new Date().getFullYear()

// Deux natures, deux listes : une image générée n'est pas une photographie, et
// la fondre dans la même énumération la ferait passer pour une prise de vue.
const photographies = computed(() => props.credits.filter((c) => !c.generated))
const generees = computed(() => props.credits.filter((c) => c.generated))
</script>

<template>
    <footer class="ft">
        <div class="shell">
            <div class="ft__top">
                <div class="ft__brandcol">
                    <component
                        :is="home ? 'a' : Link"
                        :href="home ? '#top' : '/'"
                        class="ft__brand"
                    >
                        <VaylaMark class="ft__mark" />
                        <span>vayla</span>
                    </component>
                    <p class="ft__pitch">
                        Des locations meublées vérifiées, partout à Madagascar.
                        On contrôle avant vous, pour que vous réserviez sans
                        retenir votre souffle.
                    </p>
                    <a :href="anchor('proprietaires')" class="btn btn--sm btn--terre">
                        Publier un logement
                    </a>
                </div>

                <div v-for="c in columns" :key="c.title" class="ft__col">
                    <h3 class="ft__coltitle">{{ c.title }}</h3>
                    <ul class="ft__list">
                        <li v-for="l in c.links" :key="l.label">
                            <component :is="l.page ? Link : 'a'" :href="l.href" class="ft__link">
                                {{ l.label }}
                            </component>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="ft__wordmark" aria-hidden="true">vayla</div>

            <details v-if="credits.length" class="ft__credits">
                <summary class="ft__credits-sum">
                    Crédits photo
                    <span class="ft__credits-n num">{{ credits.length }}</span>
                </summary>
                <p class="ft__credits-lede">
                    Photographies des lieux issues de Wikimedia Commons, sous licence libre.
                </p>
                <ul class="ft__credits-list">
                    <li v-for="c in photographies" :key="c.key">
                        <a :href="c.source" target="_blank" rel="noopener noreferrer">{{ c.caption }}</a>
                        <span class="ft__credits-meta">
                            {{ c.author }} ·
                            <a :href="c.licence_url" target="_blank" rel="noopener noreferrer">{{ c.licence }}</a>
                        </span>
                    </li>
                </ul>

                <!-- Séparées, et nommées pour ce qu'elles sont. Les fondre dans
                     la liste des photographies reviendrait à les faire passer
                     pour des prises de vue. -->
                <template v-if="generees.length">
                    <p class="ft__credits-lede ft__credits-lede--ia">
                        Les intérieurs des annonces de démonstration sont des
                        <strong>images générées</strong> : ces logements n'existent pas.
                        Elles disparaîtront avec les annonces fictives.
                    </p>
                    <ul class="ft__credits-list">
                        <li v-for="c in generees" :key="c.key">
                            {{ c.caption }}
                            <span class="ft__credits-meta">{{ c.author }}</span>
                        </li>
                    </ul>
                </template>
            </details>

            <div class="ft__bottom">
                <span class="num">© {{ year }} Vayla — Madagascar</span>
                <span class="num ft__note">
                    Conçu par
                    <a href="https://genius-at-work.com" target="_blank" rel="noopener noreferrer">Genius at work</a>
                </span>
            </div>
        </div>
    </footer>
</template>

<style scoped>
.ft {
    position: relative;
    padding-top: clamp(3.5rem, 6vw, 5.5rem);
    color: var(--text-2);
    background: var(--white);
    border-top: 1px solid var(--line);
    overflow: hidden;
}

.ft::before {
    content: '';
    position: absolute;
    inset: auto 0 -30% 0;
    height: 60%;
    background: radial-gradient(60% 100% at 50% 100%, var(--terre-050), transparent 70%);
    pointer-events: none;
}

.ft__top {
    position: relative;
    display: grid;
    grid-template-columns: 1fr;
    gap: 2.5rem;
    padding-bottom: 3rem;
}

.ft__brand {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: 1rem;
    font-size: 1.4rem;
    font-weight: 800;
    letter-spacing: -.055em;
    color: var(--ink);
    text-decoration: none;
}
.ft__mark { width: 26px; height: 26px; color: var(--terre-500); margin-right: .1rem; }

.ft__pitch {
    max-width: 34ch;
    margin: 0 0 1.5rem;
    font-size: .94rem;
    line-height: 1.68;
    color: var(--text-2);
}

.ft__coltitle {
    margin: 0 0 1.1rem;
    padding-bottom: .7rem;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .16em;
    color: var(--terre-500);
    border-bottom: 1px solid var(--line);
}

.ft__list { margin: 0; padding: 0; list-style: none; }
.ft__list li + li { margin-top: .55rem; }

.ft__link {
    font-size: .92rem;
    font-weight: 500;
    color: var(--text-2);
    text-decoration: none;
    transition: color .25s;
}
.ft__link:hover { color: var(--terre-500); }

/* ---------- Crédits photo ---------- */

.ft__credits {
    position: relative;
    margin-bottom: 1.5rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--line);
}

.ft__credits-sum {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: var(--text-3);
    cursor: pointer;
    list-style: none;
    transition: color .25s;
}
.ft__credits-sum::-webkit-details-marker { display: none; }
.ft__credits-sum:hover { color: var(--terre-600); }
.ft__credits-sum::after {
    content: '';
    width: .42rem; height: .42rem;
    border-right: 1.6px solid currentColor;
    border-bottom: 1.6px solid currentColor;
    rotate: 45deg;
    translate: 0 -2px;
    transition: rotate .3s var(--ease);
}
.ft__credits[open] .ft__credits-sum::after { rotate: -135deg; translate: 0 2px; }

.ft__credits-n {
    padding: .1rem .45rem;
    border-radius: var(--r-pill);
    font-size: .7rem;
    background: var(--off-2);
    color: var(--text-2);
}

.ft__credits-lede {
    max-width: 62ch;
    margin: 1rem 0 1.1rem;
    font-size: .84rem;
    line-height: 1.6;
    color: var(--text-2);
}

/* La mention se lit, elle ne se devine pas : un ton plus soutenu que la liste
   des crédits Commons, et un filet qui la détache. */
.ft__credits-lede--ia {
    margin-top: 1.6rem;
    padding-top: 1.1rem;
    border-top: 1px solid var(--line);
    color: var(--text);
}
.ft__credits-lede--ia strong { font-weight: 700; }

.ft__credits-list {
    display: grid;
    gap: .55rem 2rem;
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: .8rem;
}
.ft__credits-list li { display: flex; flex-direction: column; gap: .1rem; }
.ft__credits-list a { color: var(--text-2); text-decoration: none; border-bottom: 1px solid var(--line-2); }
.ft__credits-list a:hover { color: var(--terre-600); border-bottom-color: currentColor; }
.ft__credits-meta { color: var(--text-3); font-size: .76rem; }

@media (min-width: 720px) {
    .ft__credits-list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (min-width: 1080px) {
    .ft__credits-list { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

/* Le mot géant : la page se termine sur la marque, pas sur des mentions */
.ft__wordmark {
    position: relative;
    font-size: clamp(4rem, 19vw, 16rem);
    font-weight: 800;
    line-height: .78;
    letter-spacing: -.07em;
    text-align: center;
    color: transparent;
    background: linear-gradient(178deg, var(--terre-100) 0%, rgba(255, 255, 255, 0) 88%);
    background-clip: text;
    -webkit-background-clip: text;
    user-select: none;
}

.ft__bottom {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: .75rem;
    padding: 1.5rem 0 1.75rem;
    font-size: .74rem;
    font-weight: 600;
    letter-spacing: .04em;
    color: var(--text-3);
    border-top: 1px solid var(--line);
}

.ft__note a { color: inherit; text-decoration: none; border-bottom: 1px solid var(--line-2); }
.ft__note a:hover { color: var(--terre-600); border-bottom-color: currentColor; }

@media (min-width: 780px) {
    .ft__top { grid-template-columns: 1.5fr repeat(3, 1fr); gap: 3rem 2rem; }
}
</style>
