<script setup>
/**
 * Le cadre du back-office — **persistant** : chaque écran le déclare comme
 * gabarit (`defineOptions({ layout: OfficeShell })`), et Inertia le garde en
 * place d'une page à l'autre.
 *
 * **Pourquoi persistant.** On passe ici la journée, d'une liste à une fiche et
 * retour. Un cadre remonté à chaque visite rejouerait l'entrée de la colonne à
 * chaque clic — le genre de mouvement qu'on remarque la première fois et qui
 * fatigue la vingtième. La colonne entre une fois ; seul le contenu change.
 *
 * **Une colonne d'encre, et c'est la seule dalle sombre du produit.** Ce n'est
 * pas une coquetterie : un administrateur a souvent l'espace propriétaire ou le
 * site ouvert dans l'onglet d'à côté, pour vérifier une fiche. Le même cadre
 * blanc des deux côtés, et l'on finit par publier depuis le mauvais onglet. La
 * colonne sombre dit « ici, vous agissez au nom de Vayla » avant qu'on lise un
 * mot.
 *
 * Le reste reprend les règles des espaces : la rubrique où l'on est prend la
 * terre ; les compteurs comptent des choses qui attendent quelqu'un ; la sortie
 * est un bouton bordé au pied de la colonne ; sous 960 px, la colonne devient
 * une rangée qui défile — jamais un menu hamburger.
 */
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import gsap from 'gsap'

import OfficeIcon from '@/Components/OfficeIcon.vue'
import VaylaMark from '@/Components/VaylaMark.vue'
import { RUBRIQUES_OFFICE } from '@/Support/office.js'

const page = usePage()
const rail = ref(null)

const admin = computed(() => page.props.admin ?? {})
const compteurs = computed(() => page.props.officeCompteurs ?? {})
const flash = computed(() => page.props.flash ?? {})

const url = computed(() => page.url.split('?')[0])

/** Le tableau de bord n'est actif que sur lui-même : sinon il le serait partout. */
const actif = (item) => (item.href === '/' ? url.value === '/' : url.value.startsWith(item.href))

/**
 * Un sous-écran n'est actif que sur lui-même : le premier partage l'adresse
 * de sa rubrique. Et **il emporte les réglages de l'adresse** (la période, la
 * démonstration) : changer d'écran ne doit pas faire repartir sur douze mois.
 */
const sousActif = (s) => url.value === s.href
const requete = computed(() => {
    const i = page.url.indexOf('?')
    return i === -1 ? '' : page.url.slice(i)
})

const compteur = (item) => (item.compteur ? compteurs.value[item.compteur] ?? 0 : 0)

/** Le fait en gras, la suite en romain — la même coupe que dans les espaces. */
const decouper = (message = '') => {
    const coupe = message.search(/[.!?:]\s/)

    return coupe === -1 ? [message, ''] : [message.slice(0, coupe + 1), message.slice(coupe + 2)]
}
const suite = (message) => {
    const reste = decouper(message)[1]

    return reste ? ` ${reste}` : ''
}

/** Un POST, jamais un lien : un GET destructeur se déclenche au préchargement. */
const sortir = () => router.post('/deconnexion')

// L'entrée de la colonne, une fois par chargement : le cadre est persistant,
// elle ne se rejoue pas à chaque page.
let ctx
onMounted(() => {
    ctx = gsap.context((self) => {
        const items = self.selector('[data-rail-item]')
        const mm = gsap.matchMedia()

        mm.add('(prefers-reduced-motion: no-preference)', () => {
            gsap.from(items, { x: -10, opacity: 0, duration: .4, stagger: .03, ease: 'power3.out', clearProps: 'transform,opacity' })
            gsap.from(self.selector('.of__filigrane path'), { strokeDashoffset: 260, duration: 1.6, ease: 'power2.inOut', delay: .2 })
        })
    }, rail.value)
})
onUnmounted(() => ctx?.revert())

const MARQUE = 'M2.18,4.81 L19.79,45.70 Q38.12,20.37 45.82,2.30 L38.58,4.12 '
    + 'Q27.42,20.47 21.66,26.12 L9.01,7.47 Z'
</script>

<template>
    <div class="of">
        <aside ref="rail" class="of__rail" aria-label="Back-office">
            <Link href="/" class="of__marque" data-rail-item>
                <VaylaMark class="of__v" />
                <span class="of__nom">Vayla</span>
                <span class="of__office">Back-office</span>
            </Link>

            <nav class="of__nav">
                <div v-for="(groupe, i) in RUBRIQUES_OFFICE" :key="groupe.titre ?? i" class="of__groupe">
                    <p v-if="groupe.titre" class="of__groupe-t" data-rail-item>{{ groupe.titre }}</p>

                    <template v-for="item in groupe.items" :key="item.href">
                        <Link
                            :href="item.href"
                            class="of__lien"
                            :class="{ 'is-on': actif(item) }"
                            :aria-current="actif(item) && ! item.sous ? 'page' : undefined"
                            data-rail-item
                        >
                            <OfficeIcon :name="item.icone" />
                            <span class="of__label">{{ item.label }}</span>
                            <span v-if="compteur(item)" class="of__badge of-num">
                                {{ compteur(item) }}<span class="sr-only"> en attente</span>
                            </span>
                        </Link>

                        <!-- Le sous-menu s'ouvre sous sa rubrique quand on y est. -->
                        <div v-if="item.sous && actif(item)" class="of__sous" role="group" :aria-label="item.label">
                            <Link
                                v-for="s in item.sous"
                                :key="s.href"
                                :href="s.href + requete"
                                class="of__sous-lien"
                                :class="{ 'is-on': sousActif(s) }"
                                :aria-current="sousActif(s) ? 'page' : undefined"
                            >{{ s.label }}</Link>
                        </div>
                    </template>
                </div>
            </nav>

            <div class="of__moi" data-rail-item>
                <span class="of__init" aria-hidden="true">{{ admin.initiales }}</span>
                <span class="of__qui">
                    <span class="of__qui-nom">{{ admin.name }}</span>
                    <span class="of__qui-mail">{{ admin.email }}</span>
                </span>
                <div class="of__gestes">
                    <Link href="/compte" class="of__sortir" :class="{ 'is-on': url === '/compte' }">Mon compte</Link>
                    <button type="button" class="of__sortir" @click="sortir">
                        <OfficeIcon name="sortir" />
                        Se déconnecter
                    </button>
                </div>
            </div>

            <svg class="of__filigrane" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                <path :d="MARQUE" stroke="currentColor" stroke-width=".6" stroke-linejoin="round" stroke-dasharray="260" />
            </svg>
        </aside>

        <main class="of__corps">
            <div v-if="flash.succes" :key="flash.succes" class="of__flash of__flash--ok" role="status">
                <span class="of__flash-m" aria-hidden="true"><OfficeIcon name="coche" /></span>
                <p class="of__flash-t"><strong>{{ decouper(flash.succes)[0] }}</strong>{{ suite(flash.succes) }}</p>
            </div>
            <div v-if="flash.erreur" :key="flash.erreur" class="of__flash of__flash--ko" role="alert">
                <span class="of__flash-m" aria-hidden="true"><OfficeIcon name="alerte" /></span>
                <p class="of__flash-t"><strong>{{ decouper(flash.erreur)[0] }}</strong>{{ suite(flash.erreur) }}</p>
            </div>

            <slot />
        </main>
    </div>
</template>

<style scoped>
.of {
    display: grid;
    grid-template-columns: 15.5rem minmax(0, 1fr);
    min-height: 100vh;
    background: var(--off);
}

/* ── La colonne ───────────────────────────────────────────────────────── */
.of__rail {
    position: sticky;
    top: 0;
    display: flex;
    flex-direction: column;
    height: 100vh;
    overflow: hidden auto;
    padding: 1.25rem .85rem 1rem;
    background:
        radial-gradient(120% 40% at 0% 0%, rgba(201, 69, 42, .16), transparent 70%),
        var(--ink);
    color: rgba(255, 255, 255, .72);
    scrollbar-width: none;
}
.of__rail::-webkit-scrollbar { display: none; }

.of__marque {
    display: grid;
    grid-template-columns: auto 1fr;
    grid-template-rows: auto auto;
    column-gap: .6rem;
    align-items: center;
    margin: 0 .45rem 1.6rem;
    color: var(--white);
    text-decoration: none;
}
.of__v {
    grid-row: 1 / 3;
    width: 2rem;
    height: 2rem;
    color: var(--white);
}
.of__nom { font-size: 1.05rem; font-weight: 800; letter-spacing: -.035em; line-height: 1.1; }
.of__office {
    font-size: .64rem;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--terre-300);
}
.of__marque:focus-visible { outline: 2px solid var(--terre-400); outline-offset: 4px; border-radius: var(--r-xs); }

.of__nav { display: flex; flex-direction: column; gap: 1.15rem; }
.of__groupe { display: flex; flex-direction: column; gap: .1rem; }

.of__groupe-t {
    margin: 0 .6rem .25rem;
    font-size: .64rem;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, .42);
}

/* 2,75 rem : la cible tactile minimale, même ici — un outil interne n'a pas le
   droit d'être moins lisible, c'est là qu'on passe ses journées. */
.of__lien {
    position: relative;
    display: flex;
    align-items: center;
    gap: .7rem;
    min-height: 2.75rem;
    padding: .4rem .65rem;
    border-radius: var(--r-sm);
    font-size: .9rem;
    font-weight: 500;
    letter-spacing: -.01em;
    color: rgba(255, 255, 255, .74);
    text-decoration: none;
    transition: background-color .2s var(--ease), color .2s var(--ease);
}
.of__lien::before {
    content: '';
    position: absolute;
    left: -.85rem;
    top: 50%;
    width: 3px;
    height: 1.5rem;
    border-radius: 0 3px 3px 0;
    background: var(--terre-400);
    transform: translateY(-50%) scaleY(0);
    transition: transform .3s var(--ease);
}
.of__lien:hover { background: rgba(255, 255, 255, .06); color: var(--white); }
.of__lien:focus-visible { outline: 2px solid var(--terre-400); outline-offset: -2px; }

.of__lien.is-on { background: rgba(255, 255, 255, .09); color: var(--white); font-weight: 700; }
.of__lien.is-on::before { transform: translateY(-50%) scaleY(1); }
.of__lien.is-on .oi { color: var(--terre-300); }

.of__label { flex: 1; }

/* Le sous-menu : sous sa rubrique, aligné sur son libellé, tenu par un filet.
   Plus léger que les rubriques — on y choisit un écran, pas un endroit. La
   cible reste de 2,75 rem : c'est une cible tactile comme les autres. */
.of__sous {
    display: flex;
    flex-direction: column;
    gap: .05rem;
    margin: .1rem 0 .35rem 1.55rem;
    padding-left: .6rem;
    border-left: 1px solid rgba(255, 255, 255, .14);
}
.of__sous-lien {
    position: relative;
    display: flex;
    align-items: center;
    min-height: 2.75rem;
    padding: .3rem .65rem;
    border-radius: var(--r-sm);
    font-size: .84rem;
    font-weight: 500;
    color: rgba(255, 255, 255, .62);
    text-decoration: none;
    transition: background-color .2s var(--ease), color .2s var(--ease);
}
.of__sous-lien::before {
    content: '';
    position: absolute;
    left: calc(-.6rem - 1px);
    top: 50%;
    width: 2px;
    height: 1.2rem;
    border-radius: 2px;
    background: var(--terre-400);
    transform: translateY(-50%) scaleY(0);
    transition: transform .3s var(--ease);
}
.of__sous-lien:hover { background: rgba(255, 255, 255, .06); color: var(--white); }
.of__sous-lien:focus-visible { outline: 2px solid var(--terre-400); outline-offset: -2px; }
.of__sous-lien.is-on { color: var(--white); font-weight: 700; }
.of__sous-lien.is-on::before { transform: translateY(-50%) scaleY(1); }

.of__badge {
    display: grid;
    place-items: center;
    min-width: 1.4rem;
    height: 1.4rem;
    padding: 0 .4rem;
    border-radius: var(--r-pill);
    background: var(--terre-500);
    color: var(--white);
    font-size: .72rem;
    font-weight: 800;
}

.of__moi {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: .35rem .6rem;
    align-items: center;
    margin-top: auto;
    padding: 1rem .45rem 0;
    border-top: 1px solid var(--line-dark);
}
.of__init {
    display: grid;
    place-items: center;
    width: 2.1rem;
    height: 2.1rem;
    border-radius: var(--r-pill);
    background: var(--terre-500);
    color: var(--white);
    font-size: .76rem;
    font-weight: 800;
}
.of__qui { display: grid; min-width: 0; }
.of__qui-nom, .of__qui-mail { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.of__qui-nom { font-size: .86rem; font-weight: 700; color: var(--white); }
.of__qui-mail { font-size: .74rem; color: rgba(255, 255, 255, .5); }

.of__gestes { grid-column: 1 / -1; display: grid; gap: .4rem; margin-top: .55rem; }
.of__sortir {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    min-height: 2.5rem;
    border: 1px solid rgba(255, 255, 255, .22);
    border-radius: var(--r-pill);
    background: transparent;
    font: inherit;
    font-size: .82rem;
    font-weight: 700;
    color: rgba(255, 255, 255, .85);
    cursor: pointer;
    transition: border-color .2s var(--ease), background-color .2s var(--ease), color .2s var(--ease);
}
.of__sortir .oi { width: 1rem; height: 1rem; }
a.of__sortir { text-decoration: none; }
.of__sortir.is-on { border-color: var(--terre-400); color: var(--white); }
.of__sortir:hover { border-color: var(--white); background: var(--white); color: var(--ink); }
.of__sortir:focus-visible { outline: 2px solid var(--terre-400); outline-offset: 2px; }

.of__filigrane {
    position: absolute;
    right: -3.5rem;
    bottom: 5rem;
    width: 13rem;
    height: 13rem;
    color: var(--terre-400);
    opacity: .16;
    pointer-events: none;
}

/* ── Le contenu ───────────────────────────────────────────────────────── */
/* **Pleine largeur.** Le back-office se lit sur un écran de travail, souvent
   large : borné à 76 rem, il laissait un tiers de l'écran vide pendant qu'une
   liste de réservations se tassait et qu'un graphique se lisait mal. Ce qui
   doit rester étroit — un texte, un formulaire de mot de passe — porte sa
   propre mesure. */
.of__corps {
    width: 100%;
    min-width: 0;
    padding: clamp(1.25rem, 3vw, 2.25rem) clamp(1rem, 3vw, 2.5rem) 4rem;
}

.of__flash {
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    margin: 0 0 1.25rem;
    padding: .8rem 1rem .8rem .8rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--white);
    box-shadow: var(--sh-1);
    animation: of-flash .4s var(--ease) both;
}
@keyframes of-flash { from { opacity: 0; transform: translateY(-6px); } }

.of__flash-m {
    display: grid;
    place-items: center;
    flex: none;
    width: 1.9rem;
    height: 1.9rem;
    border-radius: var(--r-pill);
}
.of__flash-m .oi { width: 1.05rem; height: 1.05rem; stroke-width: 2.3; }
.of__flash-t { margin: 0; padding-top: .2rem; font-size: .9rem; line-height: 1.5; color: var(--text-2); }
.of__flash-t strong { font-weight: 800; color: var(--ink); }

/* La coche de réussite garde l'exception bornée des espaces : le lagon sur la
   coche seule, jamais sur la carte ni sur le texte — voir `SpaceShell`. */
.of__flash--ok .of__flash-m { background: var(--lagon-050); box-shadow: inset 0 0 0 1px var(--lagon-100); color: var(--lagon-600); }

.of__flash--ko { border-color: var(--terre-300); background: var(--terre-050); box-shadow: none; }
.of__flash--ko .of__flash-m { background: var(--terre-500); color: var(--white); }
.of__flash--ko .of__flash-t, .of__flash--ko .of__flash-t strong { color: var(--terre-700); }

/* ── Sous 960 px : la colonne devient un bandeau ─────────────────────────
   La marque et le compte sur une ligne, les rubriques sur une rangée qui
   défile. Jamais un bouton hamburger : ce qui est caché derrière trois traits
   n'existe pas pour celui qui ne pense pas à l'ouvrir. */
@media (max-width: 960px) {
    .of { grid-template-columns: minmax(0, 1fr); }

    .of__rail {
        position: static;
        display: grid;
        grid-template-columns: 1fr auto;
        height: auto;
        padding: .85rem 1rem .6rem;
        overflow: visible;
    }

    .of__marque { margin: 0; }

    .of__nav {
        grid-column: 1 / -1;
        flex-direction: row;
        gap: .2rem;
        margin: .7rem -1rem 0;
        padding: 0 1rem .2rem;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .of__nav::-webkit-scrollbar { display: none; }
    .of__groupe { flex-direction: row; gap: .2rem; }
    .of__groupe-t { display: none; }
    .of__lien { flex: none; white-space: nowrap; }
    .of__lien::before { display: none; }
    /* Sur la rangée, le sous-menu suit sa rubrique, en pastilles plus claires. */
    .of__sous { flex-direction: row; gap: .2rem; margin: 0; padding: 0; border: 0; }
    .of__sous-lien { flex: none; white-space: nowrap; background: rgba(255, 255, 255, .05); }
    .of__sous-lien::before { display: none; }
    .of__sous-lien.is-on { background: rgba(255, 255, 255, .14); }

    .of__moi {
        grid-row: 1;
        grid-column: 2;
        display: flex;
        margin: 0;
        padding: 0;
        border: 0;
    }
    .of__qui { display: none; }
    .of__gestes { display: flex; margin: 0; }
    .of__sortir { padding-inline: .8rem; }

    .of__filigrane { display: none; }
}

@media (max-width: 420px) {
    button.of__sortir { font-size: 0; gap: 0; width: 2.5rem; padding: 0; }
    button.of__sortir .oi { width: 1.1rem; height: 1.1rem; }
}

@media (prefers-reduced-motion: reduce) {
    .of__flash { animation: none; }
}
</style>
