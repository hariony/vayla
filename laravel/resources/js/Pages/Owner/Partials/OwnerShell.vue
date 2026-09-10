<script setup>
/**
 * Le cadre de l'espace propriétaire : le bandeau, le fond, les messages de
 * retour, la mention du lien personnel.
 *
 * Il existe parce que l'espace a maintenant deux écrans — le tableau de bord
 * et le calendrier d'un logement — et que le bandeau, le fond et le retour
 * d'action doivent être identiques sur les deux. Recopiés, ils auraient
 * divergé au premier ajustement.
 *
 * **Toujours pas d'en-tête du site.** « Devenir hôte » n'a aucun sens pour
 * quelqu'un qui l'est déjà : la navigation commerciale n'aurait servi qu'à
 * égarer. Un bandeau sobre, un retour quand il y a d'où revenir, et c'est
 * tout — un outil, pas une vitrine.
 */
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

import VaylaMark from '@/Components/VaylaMark.vue'

defineProps({
    /** Le lien de retour, quand l'écran est en dessous d'une rubrique. */
    back: { type: Object, default: null },
})

/**
 * Le menu.
 *
 * L'espace tenait sur un écran tant qu'il ne servait qu'à répondre aux
 * demandes. Depuis qu'on y saisit des annonces, qu'on y téléverse des photos
 * et qu'on y relit l'historique, tout empiler donnerait une page de trois
 * mille pixels où l'on ne retrouve rien.
 *
 * **Quatre rubriques, pas plus, et ordonnées par urgence** — c'est encore une
 * demande qui expire qui fait ouvrir Vayla. Chacune porte un mot que le
 * propriétaire emploie lui-même : « Demandes », pas « Tableau de bord ».
 *
 * Elles défilent horizontalement sous 640 px plutôt que de se replier dans un
 * bouton hamburger : un menu caché derrière trois traits n'est pas une
 * navigation pour quelqu'un dont c'est le premier outil en ligne.
 */
const RUBRIQUES = [
    { href: '/proprietaire', label: 'Demandes' },
    { href: '/proprietaire/reservations', label: 'Réservations', compteur: 'ownerUnread' },
    { href: '/proprietaire/logements', label: 'Logements' },
]

/**
 * Le compte de conversations en attente, sur l'onglet.
 *
 * On compte les **échanges**, pas les messages : « 2 » veut dire « deux
 * conversations vous attendent », pas « quatorze lignes de texte ». Un compte
 * de messages ferait paniquer pour un voyageur bavard.
 */
const compteur = (r) => (r.compteur ? page.props[r.compteur] ?? 0 : 0)

const url = computed(() => page.url.split('?')[0])

/** La rubrique la plus précise qui contient l'écran courant. */
const active = (href) => href === '/proprietaire'
    ? url.value === '/proprietaire'
    : url.value.startsWith(href)

const page = usePage()
const flash = computed(() => page.props.flash ?? {})

/**
 * Se déconnecter est un POST, jamais un lien : un GET destructeur se
 * déclenche au préchargement d'un navigateur ou d'un antivirus, et le
 * propriétaire se retrouve dehors sans avoir rien touché.
 */
const sortir = () => router.post('/proprietaire/deconnexion')
</script>

<template>
    <div class="op">
        <header class="op__bar">
            <div class="shell op__bar-in">
                <span class="op__brand">
                    <VaylaMark class="op__mark" />
                    <span class="op__word">vayla</span>
                </span>
                <span class="op__tag">Espace propriétaire</span>
                <button type="button" class="op__out" @click="sortir">Se déconnecter</button>
            </div>

            <nav class="shell op__nav" aria-label="Sections">
                <Link
                    v-for="r in RUBRIQUES"
                    :key="r.href"
                    :href="r.href"
                    class="op__tab"
                    :class="{ 'is-on': active(r.href) }"
                    :aria-current="active(r.href) ? 'page' : undefined"
                >
                    {{ r.label }}
                    <span v-if="compteur(r)" class="op__badge num">{{ compteur(r) }}</span>
                </Link>
            </nav>
        </header>

        <main class="shell op__main">
            <!-- Un retour qui se voit comme un bouton : une flèche seule, sans
                 contour ni fond, ne se lit pas comme un contrôle. -->
            <Link v-if="back" :href="back.href" class="op__back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M15 5.5 8.5 12l6.5 6.5" />
                </svg>
                {{ back.label }}
            </Link>

            <!-- Le retour d'action est en haut et annoncé aux lecteurs d'écran :
                 sans lui, agir ne dit rien, et on reclique. -->
            <p v-if="flash.succes" class="op__flash op__flash--ok" role="status">{{ flash.succes }}</p>
            <p v-if="flash.erreur" class="op__flash op__flash--ko" role="alert">{{ flash.erreur }}</p>

            <slot />

            <p class="op__foot">
                Une question, un changement à faire vérifier ? Écrivez à Vayla sur WhatsApp.
                Pensez à vous déconnecter si ce téléphone n'est pas le vôtre.
            </p>
        </main>
    </div>
</template>

<style scoped>
.op { min-height: 100vh; background: var(--off); }

.op__bar { border-bottom: 1px solid var(--line); background: var(--white); }
.op__bar-in { display: flex; align-items: center; gap: .9rem; height: 3.75rem; }

/* Un contrôle doit se voir comme un contrôle : contour franc et fond blanc,
   pas un mot gris posé dans un coin. */
.op__out {
    margin-left: auto;
    padding: .45rem .9rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font: inherit;
    font-size: .8rem;
    font-weight: 700;
    color: var(--text-2);
    cursor: pointer;
    transition: border-color .2s var(--ease), color .2s var(--ease);
}
.op__out:hover { border-color: var(--ink); color: var(--ink); }

/* Des onglets qui se voient comme des onglets : le trait sous l'actif est
   franc, pas une nuance de gris. Un repère qu'on doit chercher n'en est pas
   un. */
.op__nav {
    display: flex;
    gap: .25rem;
    overflow-x: auto;
    scrollbar-width: none;
}
.op__nav::-webkit-scrollbar { display: none; }

.op__tab {
    flex: none;
    padding: .8rem .95rem;
    border-bottom: 3px solid transparent;
    font-size: .92rem;
    font-weight: 700;
    color: var(--text-3);
    text-decoration: none;
    white-space: nowrap;
    transition: color .2s var(--ease), border-color .2s var(--ease);
}
.op__tab:hover { color: var(--ink); }
.op__tab.is-on { border-bottom-color: var(--terre-500); color: var(--ink); }

/* La pastille prend la terre : c'est un appel à agir, et la terre porte
   l'action. Le lagon ne dit que « vérifié », il n'entre jamais ici. */
.op__badge {
    display: inline-grid;
    place-items: center;
    min-width: 1.35rem;
    height: 1.35rem;
    margin-left: .35rem;
    padding: 0 .35rem;
    border-radius: var(--r-pill);
    background: var(--terre-500);
    font-size: .72rem;
    font-weight: 800;
    color: var(--white);
}

.op__brand { display: inline-flex; align-items: center; gap: .45rem; }
.op__mark { width: 1.5rem; height: 1.5rem; color: var(--terre-500); }
.op__word { font-size: 1.05rem; font-weight: 800; letter-spacing: -.045em; color: var(--ink); }

.op__tag {
    padding: .25rem .7rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    font-size: .76rem;
    font-weight: 700;
    color: var(--text-2);
}

.op__main { max-width: 60rem; padding-block: clamp(1.75rem, 4vw, 2.75rem) clamp(3rem, 6vw, 4.5rem); }

.op__back {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    margin-bottom: 1.25rem;
    padding: .55rem 1rem .55rem .75rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font-size: .86rem;
    font-weight: 700;
    color: var(--ink);
    text-decoration: none;
    transition: background-color .2s var(--ease);
}
.op__back:hover { background: var(--off-2); }
.op__back svg { width: 1.1rem; height: 1.1rem; }

.op__flash {
    margin: 0 0 1.25rem;
    padding: .85rem 1.1rem;
    border-radius: var(--r-md);
    font-size: .92rem;
    font-weight: 600;
    line-height: 1.5;
}
/* Le succès est **neutre**, jamais lagon : le lagon ne dit que « vérifié »
   sur tout le site, et l'employer pour « ça a marché » rendrait l'échelle de
   confiance illisible d'un coup d'œil. L'erreur prend la terre, qui porte
   déjà l'avertissement sur le ruban des saisons. */
.op__flash--ok { background: var(--ink); color: var(--white); }
.op__flash--ko { border: 1px solid var(--terre-300); background: var(--terre-050); color: var(--terre-700); }

.op__foot {
    margin: clamp(2.5rem, 5vw, 3.5rem) 0 0;
    padding-top: 1.25rem;
    border-top: 1px solid var(--line);
    font-size: .8rem;
    color: var(--text-3);
}
</style>
