<script setup>
/**
 * En-tête. Au repos : marque, navigation, appel à publier. Dès qu'on
 * défile au-delà du moteur du hero, le résumé de recherche vient s'y
 * encastrer — la recherche ne quitte jamais l'écran.
 */
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import GoogleOneTap from './GoogleOneTap.vue'
import VaylaMark from './VaylaMark.vue'
import SearchPanel from './SearchPanel.vue'

const props = defineProps({
    destinations: { type: Array, default: () => [] },
    recherche: { type: Object, default: null },
    // Les ancres de section n'existent que sur l'accueil. Ailleurs elles
    // doivent repartir vers `/`, sans quoi « Confiance » ne fait rien.
    home: { type: Boolean, default: false },
    search: { type: Boolean, default: true },
})

const emit = defineEmits(['expand'])

const scrolled = ref(false)
const compact = ref(false)
const menuOpen = ref(false)

// `to` = une vraie page (visite Inertia), `hash` = une section de l'accueil.
const LINKS = [
    { to: '/logements', label: 'Logements' },
    { hash: 'confiance', label: 'Confiance' },
    { to: '/destinations', label: 'Destinations' },
    { hash: 'proprietaires', label: 'Propriétaires' },
]

const anchor = (hash) => (props.home ? `#${hash}` : `/#${hash}`)

const page = usePage()

/**
 * **Connecté, « Connexion » devient la porte de son espace — il ne disparaît
 * pas.** Le retirer laisserait quelqu'un de connecté sans aucun chemin vers
 * ses réservations depuis le site public : la même impasse qu'avant, dans
 * l'autre sens. Un lien qui propose de se connecter à qui l'est déjà est en
 * revanche un faux signal, et il fait douter — « est-ce que j'ai été
 * déconnecté ? ».
 *
 * **Le libellé nomme l'espace, parce qu'ici on sait lequel.** « Connexion »
 * reste le mot juste pour un visiteur — « Mon espace » lui demanderait de
 * savoir lequel des deux — mais une fois la garde connue, l'ambiguïté n'existe
 * plus. Le propriétaire l'emporte s'il se trouve que les deux sessions sont
 * ouvertes : c'est celle qui a des demandes qui expirent.
 */
const espace = computed(() => {
    if (page.props.auth?.owner) {
        return { href: '/proprietaire', label: 'Mon espace' }
    }

    if (page.props.auth?.user) {
        return { href: '/mes-reservations', label: 'Mes réservations' }
    }

    return { href: '/connexion', label: 'Connexion' }
})

/** « Devenir hôte » n'a aucun sens pour quelqu'un qui l'est déjà. */
const recrute = computed(() => ! page.props.auth?.owner)

const links = computed(() =>
    LINKS.map((l) => ({
        label: l.label,
        href: l.to ?? anchor(l.hash),
        page: Boolean(l.to),
    }))
)

const demande = computed(() => anchor('demande'))
const proprietaires = computed(() => anchor('proprietaires'))

const onScroll = () => {
    const y = window.scrollY
    scrolled.value = y > 16
    // 320 px : le moteur du hero vient de sortir du cadre. Sans moteur à
    // encastrer — sur une fiche, un catalogue, une destination — la forme
    // compacte n'a rien à mettre à la place : elle effaçait la navigation
    // et laissait un en-tête vide au milieu.
    compact.value = props.search && y > 320
}

onMounted(() => {
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
})

onUnmounted(() => window.removeEventListener('scroll', onScroll))
</script>

<template>
    <header class="hdr" :class="{ 'hdr--solid': scrolled, 'hdr--compact': compact }">
        <div class="shell hdr__inner">
            <!-- Sur l'accueil, la marque remonte en haut (ancre) ; ailleurs,
                 elle ramène à l'accueil (visite Inertia). -->
            <component
                :is="home ? 'a' : Link"
                :href="home ? '#top' : '/'"
                class="hdr__brand"
                aria-label="Vayla, accueil"
            >
                <VaylaMark class="hdr__mark" />
                <span class="hdr__word">vayla</span>
            </component>

            <div class="hdr__mid">
                <nav class="hdr__nav" aria-label="Navigation principale">
                    <component
                        :is="l.page ? Link : 'a'"
                        v-for="l in links"
                        :key="l.href"
                        :href="l.href"
                        class="hdr__link"
                    >{{ l.label }}</component>
                </nav>

                <div v-if="search" class="hdr__search">
                    <SearchPanel
                        v-if="recherche"
                        compact
                        :destinations="destinations"
                        :recherche="recherche"
                        @expand="emit('expand')"
                    />
                </div>
            </div>

            <div class="hdr__actions">
                <!-- Avant « Devenir hôte » : celui qui revient cherche une
                     porte, celui qui découvre lit d'abord la page. Le libellé
                     suit la session — voir `espace` — parce qu'un lien qui
                     propose de se connecter à qui l'est déjà fait douter. -->
                <Link :href="espace.href" class="hdr__ghost hdr__login">{{ espace.label }}</Link>
                <a v-if="recrute" :href="proprietaires" class="hdr__ghost">Devenir hôte</a>
                <a :href="demande" class="btn btn--sm btn--ink hdr__cta">Demander un séjour</a>

                <button
                    class="hdr__burger"
                    type="button"
                    :aria-expanded="menuOpen"
                    aria-controls="hdr-drawer"
                    @click="menuOpen = !menuOpen"
                >
                    <span class="sr-only">Menu</span>
                    <span class="hdr__burger-bar"></span>
                    <span class="hdr__burger-bar"></span>
                </button>
            </div>
        </div>

        <div id="hdr-drawer" class="hdr__drawer" :hidden="!menuOpen">
            <component
                :is="l.page ? Link : 'a'"
                v-for="l in links"
                :key="l.href"
                :href="l.href"
                class="hdr__drawer-link"
                @click="menuOpen = false"
            >{{ l.label }}</component>
            <a :href="demande" class="btn btn--terre hdr__drawer-cta" @click="menuOpen = false">
                Demander un séjour
            </a>

            <!-- Le carrefour, pas l'espace propriétaire directement : le
                 tiroir sert aussi aux voyageurs, et deux entrées séparées ici
                 obligeraient à choisir avant d'avoir lu ce que chacune ouvre. -->
            <Link :href="espace.href" class="hdr__drawer-owner" @click="menuOpen = false">
                {{ espace.label }}
            </Link>
        </div>
    </header>

    <GoogleOneTap />
</template>

<style scoped>
.hdr {
    position: fixed;
    inset: 0 0 auto;
    z-index: 100;
    background: rgba(255, 255, 255, 0);
    transition: background-color .4s var(--ease), box-shadow .4s var(--ease);
}

.hdr--solid {
    background: rgba(255, 255, 255, .82);
    backdrop-filter: saturate(180%) blur(18px);
    box-shadow: 0 1px 0 var(--line);
}

.hdr__inner {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    height: var(--header-h);
}

.hdr__brand {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    text-decoration: none;
    color: var(--ink);
    flex: none;
}

.hdr__mark {
    width: 28px;
    height: 28px;
    flex: none;
    color: var(--terre-500);
    /* La marque n'a plus de cadre : elle a besoin d'air à sa droite. */
    margin-right: .12rem;
}

.hdr__word {
    font-size: 1.42rem;
    font-weight: 800;
    letter-spacing: -.055em;
}

/* La navigation et le résumé de recherche occupent la même case :
   l'un s'efface quand l'autre entre. */
.hdr__mid {
    position: relative;
    display: none;
    flex: 1;
    justify-content: center;
    min-width: 0;
    height: 100%;
}

.hdr__nav,
.hdr__search {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2rem;
    transition: opacity .35s var(--ease), transform .45s var(--ease);
}

.hdr__search { opacity: 0; transform: translateY(10px); pointer-events: none; }

.hdr--compact .hdr__nav { opacity: 0; transform: translateY(-10px); pointer-events: none; }
.hdr--compact .hdr__search { opacity: 1; transform: none; pointer-events: auto; }

.hdr__link {
    font-size: .92rem;
    font-weight: 600;
    letter-spacing: -.012em;
    color: var(--text-2);
    text-decoration: none;
    white-space: nowrap;
    transition: color .25s;
}
.hdr__link:hover { color: var(--terre-500); }

.hdr__actions {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-left: auto;
    flex: none;
}

.hdr__ghost {
    display: none;
    padding: .68rem 1rem;
    border-radius: var(--r-pill);
    font-size: .9rem;
    font-weight: 700;
    letter-spacing: -.012em;
    color: var(--ink);
    text-decoration: none;
    transition: background-color .25s;
}
.hdr__ghost:hover { background: var(--off-2); }

.hdr__cta { display: none; }

/* « Connexion » reste visible quand « Devenir hôte » disparaît : c'est le
   seul lien de la barre dont un utilisateur perdu a besoin, et le renvoyer
   dans le tiroir lui demande un geste de plus. Double classe pour passer
   devant le `display: none` de `.hdr__ghost`, qui vient après dans la
   feuille — jouer sur l'ordre se serait défait au premier déplacement. */
.hdr__ghost.hdr__login { display: inline-flex; padding-inline: .8rem; }

@media (max-width: 460px) {
    /* Sous 460 px la barre porte déjà la marque, le CTA et le menu : le
       tiroir prend le relais. */
    .hdr__ghost.hdr__login { display: none; }
}

.hdr__burger {
    display: grid;
    place-content: center;
    gap: 5px;
    width: 42px;
    height: 42px;
    padding: 0;
    background: #fff;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    cursor: pointer;
}
.hdr__burger-bar {
    display: block;
    width: 16px;
    height: 1.8px;
    border-radius: 2px;
    background: var(--ink);
}
.hdr__burger:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }

.hdr__drawer {
    display: flex;
    flex-direction: column;
    gap: .25rem;
    padding: .75rem var(--gutter) 1.35rem;
    background: #fff;
    box-shadow: 0 20px 40px -28px rgba(23, 20, 28, .5);
    border-top: 1px solid var(--line);
}

.hdr__drawer-link {
    padding: .7rem 0;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -.02em;
    color: var(--ink);
    text-decoration: none;
    border-bottom: 1px solid var(--line);
}

.hdr__drawer-owner {
    display: block;
    margin-top: .9rem;
    padding-top: .9rem;
    border-top: 1px solid var(--line);
    font-size: .9rem;
    font-weight: 700;
    color: var(--text-2);
    text-align: center;
    text-decoration: none;
}

.hdr__drawer-cta { margin-top: 1rem; }

/* display:flex bat le [hidden] de la feuille par défaut : sans cette
   règle le tiroir reste ouvert au chargement sur mobile. */
.hdr__drawer[hidden] { display: none; }

@media (min-width: 720px) {
    .hdr__ghost { display: inline-flex; }
    .hdr__cta { display: inline-flex; }
}

@media (min-width: 1000px) {
    .hdr__mid { display: flex; }
    .hdr__burger { display: none; }
    .hdr__drawer { display: none; }
}
</style>
