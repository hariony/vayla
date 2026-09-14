<script setup>
/**
 * Le menu du compte : une pastille d'initiales dans l'en-tête, et sous elle
 * tout ce qu'on peut faire quand on est connecté.
 *
 * **Il répare une impasse, il n'ajoute pas un ornement.** Se déconnecter
 * n'existait que *dans* `/mes-reservations` et *dans* l'espace propriétaire :
 * depuis l'accueil, une fiche ou le catalogue, un voyageur connecté n'avait
 * aucun moyen de sortir de sa session. Sur un téléphone partagé — le cas
 * courant chez nous — c'est un vrai problème, pas une gêne.
 *
 * **La pastille dit qui est connecté avant même qu'on l'ouvre.** C'est ce
 * qu'un libellé « Mon espace » ne fait pas : on lit ses initiales, donc on
 * sait que la session est la sienne et pas celle d'un proche qui a emprunté le
 * téléphone. C'est la seule raison pour laquelle un avatar vaut mieux qu'un
 * mot ici.
 *
 * **Elle est neutre, jamais terre.** Un avatar dit une identité, pas une
 * action, et la barre ne porte qu'un seul objet coloré — le bouton noir.
 * Bordée et pleine en revanche : la règle « pas de contrôle sans contour ni
 * fond » vaut d'autant plus qu'un rond d'initiales n'est pas une convention
 * que tout notre public a déjà rencontrée.
 *
 * **Ce n'est pas un `role="menu"`.** Le motif ARIA « menu » impose un focus
 * roulant (flèches, `tabindex="-1"` partout) et casse la tabulation à laquelle
 * les gens s'attendent ; ce panneau ne contient que des liens et un bouton,
 * donc c'est une **divulgation** — `aria-expanded` sur le déclencheur, Tab qui
 * traverse, Échap qui referme. Les flèches marchent en plus, pour ceux qui les
 * essaient.
 *
 * **Les deux gardes peuvent être ouvertes en même temps** (`web` et
 * `proprietaire` sont deux sessions distinctes). Le menu montre alors les deux
 * espaces, chacun avec sa sortie : c'est le seul endroit du site où cet état
 * est visible, et le cacher ferait croire à une déconnexion qui n'a pas eu
 * lieu.
 *
 * **Aucun lien inventé.** Chaque entrée pointe sur une route qui existe — un
 * menu qui promet « Mon profil » ou « Paramètres » sans écran derrière est
 * exactement ce qu'on vient de retirer de la barre.
 */
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

import SpaceIcon from '@/Components/SpaceIcon.vue'
import { portraitSrc, portraitSrcset } from '@/Support/portrait.js'
import {
    ACTION_PROPRIETAIRE,
    RUBRIQUES_CLIENT,
    liensDe,
    rubriquesProprietaire,
} from '@/Support/espaces.js'

const page = usePage()

const voyageur = computed(() => page.props.auth?.user ?? null)
const proprietaire = computed(() => page.props.auth?.owner ?? null)

/** Les deux sessions ouvertes : rare, mais possible, et alors il faut le dire. */
const deuxEspaces = computed(() => Boolean(voyageur.value && proprietaire.value))

/**
 * Le propriétaire l'emporte pour nommer le compte : c'est la session qui a des
 * demandes qui expirent, donc celle qu'on ouvre Vayla pour traiter.
 */
const nom = computed(() => proprietaire.value?.name
    ?? voyageur.value?.name
    ?? 'Votre compte')

/** Sous le nom : ce par quoi Vayla joint la personne, pas un identifiant interne. */
const contact = computed(() => proprietaire.value?.phone ?? voyageur.value?.email ?? '')

/**
 * Le portrait, quand il existe.
 *
 * Il n'y en a que côté propriétaire pour l'instant : c'est lui qu'un voyageur
 * hésite à contacter sans savoir à qui il écrit, et lui que Vayla appelle pour
 * vérifier. Les initiales restent le repli — un rond de lettres n'est là que
 * faute de mieux.
 */
const portrait = computed(() => proprietaire.value?.portrait ?? null)

/**
 * Les initiales.
 *
 * Le nom d'un voyageur peut ne pas exister — il n'est plus demandé à
 * l'inscription — et l'adresse prend alors le relais. Deux lettres au
 * maximum : au-delà, la pastille devient illisible à 42 px.
 */
const initiales = computed(() => {
    const source = proprietaire.value?.name?.trim() || voyageur.value?.name?.trim()

    if (source) {
        return source.split(/\s+/).slice(0, 2).map((m) => m[0]).join('').toUpperCase()
    }

    return voyageur.value?.email?.[0]?.toUpperCase() ?? ''
})

/**
 * Ce qu'on peut faire, par espace ouvert.
 *
 * **Les rubriques viennent de `Support/espaces.js`, comme la barre latérale.**
 * Elles étaient écrites ici *aussi*, et les deux listes avaient déjà divergé :
 * le menu ignorait « Messages » et « Mes informations » côté client, si bien
 * que la même personne voyait deux menus différents selon qu'elle cliquait sur
 * sa pastille ou qu'elle était dans son espace. Une seule liste, deux
 * surfaces.
 *
 * Une rubrique **à venir** (sans `href`) est écartée : un menu déroulant n'a
 * pas de place pour une ligne qui ne mène nulle part — la barre, elle, a la
 * hauteur de l'expliquer.
 */
const espaces = computed(() => {
    const liste = []

    const compteur = (item) => (item.compteur ? page.props[item.compteur] ?? 0 : 0)

    if (proprietaire.value) {
        liste.push({
            cle: 'proprietaire',
            titre: 'Espace propriétaire',
            liens: [
                ...liensDe(rubriquesProprietaire(page.props.lancement)).map((item) => ({ ...item, compteur: compteur(item) })),
                ACTION_PROPRIETAIRE,
            ],
            sortie: {
                url: '/proprietaire/deconnexion',
                label: deuxEspaces.value ? 'Quitter l\'espace propriétaire' : 'Se déconnecter',
            },
        })
    }

    if (voyageur.value) {
        liste.push({
            cle: 'voyageur',
            titre: 'Espace client',
            liens: liensDe(RUBRIQUES_CLIENT).map((item) => ({ ...item, compteur: compteur(item) })),
            sortie: {
                url: '/deconnexion',
                label: deuxEspaces.value ? 'Quitter l\'espace client' : 'Se déconnecter',
            },
        })
    }

    return liste
})

/**
 * La rubrique où l'on se trouve déjà.
 *
 * Ouvrir le menu depuis « Messages » et y voir « Messages » comme les autres
 * lignes ne dit pas qu'on y est — on reclique, et rien ne se passe. La ligne
 * courante prend donc son pictogramme en terre et un poids de plus, comme la
 * rubrique active de la barre latérale. La racine d'un espace ne l'est que
 * sur elle-même, sinon elle le serait partout.
 *
 * **Une seule ligne à la fois : la correspondance la plus précise.** Sur
 * `/proprietaire/logements/nouveau`, « Mes logements » et « Publier un
 * logement » correspondent toutes les deux ; deux lignes allumées ne disent
 * plus où l'on est. On garde la plus longue. Et `startsWith(href + '/')`, pas
 * `startsWith(href)` : sinon une rubrique en éclairerait une autre dont
 * l'adresse commence par les mêmes lettres.
 */
const url = computed(() => page.url.split('?')[0])

const hrefCourant = computed(() => espaces.value
    .flatMap((espace) => espace.liens.map((lien) => lien.href))
    .filter((href) => (['/proprietaire', '/mes-reservations'].includes(href)
        ? url.value === href
        : url.value === href || url.value.startsWith(`${href}/`)))
    .sort((a, b) => b.length - a.length)[0] ?? null)

const courant = (href) => href === hrefCourant.value

const ouvert = ref(false)
const racine = ref(null)
const declencheur = ref(null)
const panneau = ref(null)

const basculer = () => (ouvert.value = ! ouvert.value)

const fermer = ({ rendreLeFocus = false } = {}) => {
    if (! ouvert.value) {
        return
    }

    ouvert.value = false

    if (rendreLeFocus) {
        declencheur.value?.focus()
    }
}

/**
 * Se déconnecter est un **POST**, jamais un lien : un GET destructeur se
 * déclenche au préchargement d'un navigateur ou d'un antivirus, et on se
 * retrouve dehors sans avoir rien touché.
 */
const sortir = (url) => {
    ouvert.value = false
    router.post(url)
}

/** Un clic ailleurs referme — y compris sur le décor de la page. */
const dehors = (e) => {
    if (racine.value && ! racine.value.contains(e.target)) {
        fermer()
    }
}

/**
 * Le focus qui sort du panneau le referme : celui qui tabule jusqu'au bout
 * continue sa route dans la page, il ne reste pas prisonnier d'un panneau
 * ouvert derrière lui.
 */
const focusPerdu = (e) => {
    if (racine.value && ! racine.value.contains(e.relatedTarget)) {
        fermer()
    }
}

/** Les flèches, en plus de la tabulation, pour qui les essaie. */
const auClavier = (e) => {
    if (e.key === 'Escape') {
        fermer({ rendreLeFocus: true })

        return
    }

    if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') {
        return
    }

    const cibles = [...(panneau.value?.querySelectorAll('[data-item]') ?? [])]

    if (! cibles.length) {
        return
    }

    e.preventDefault()

    const index = cibles.indexOf(document.activeElement)
    const suivant = e.key === 'ArrowDown' ? index + 1 : index - 1

    cibles[(suivant + cibles.length) % cibles.length]?.focus()
}

// Le panneau se referme quand la page change : Inertia réutilise le
// composant, et un menu resté ouvert sur l'écran suivant est un menu qu'on n'a
// pas demandé.
watch(() => page.url, () => (ouvert.value = false))

watch(ouvert, async (est) => {
    if (! est) {
        return
    }

    await nextTick()
    panneau.value?.querySelector('[data-item]')?.focus()
})

// Le panneau est ancré sous la pastille, dans un en-tête qui change de forme
// au défilement : le laisser ouvert pendant qu'on défile le détacherait de son
// point d'attache.
const auDefilement = () => fermer()

onMounted(() => {
    document.addEventListener('click', dehors)
    window.addEventListener('scroll', auDefilement, { passive: true })
})

// Les deux écouteurs sont retirés : Inertia démonte l'en-tête à chaque
// changement de page, et un écouteur oublié s'accumule à chaque visite.
onUnmounted(() => {
    document.removeEventListener('click', dehors)
    window.removeEventListener('scroll', auDefilement)
})
</script>

<template>
    <div ref="racine" class="cpt" @keydown="auClavier" @focusout="focusPerdu">
        <button
            ref="declencheur"
            type="button"
            class="cpt__pastille"
            :class="{ 'is-on': ouvert }"
            :aria-expanded="ouvert"
            aria-controls="compte-panneau"
            @click="basculer"
        >
            <span class="sr-only">Mon compte — {{ nom }}</span>
            <img
                v-if="portrait"
                class="cpt__init cpt__init--photo"
                :src="portraitSrc(portrait)"
                :srcset="portraitSrcset(portrait)"
                sizes="32px"
                alt=""
                width="32"
                height="32"
            >
            <span v-else-if="initiales" class="cpt__init" aria-hidden="true">{{ initiales }}</span>
            <!-- Sans nom ni adresse lisible, une silhouette plutôt qu'un point
                 d'interrogation : elle dit « c'est vous », pas « on ne sait pas ». -->
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" aria-hidden="true">
                <circle cx="12" cy="8.5" r="3.6" />
                <path d="M4.9 19.4c1-3.4 3.7-5.1 7.1-5.1s6.1 1.7 7.1 5.1" />
            </svg>
            <svg class="cpt__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m7 10 5 5 5-5" />
            </svg>
        </button>

        <div v-show="ouvert" id="compte-panneau" ref="panneau" class="cpt__panneau">
            <!-- Qui est connecté : le même visage que la pastille, en plus
                 grand, et ce par quoi Vayla joint la personne. -->
            <div class="cpt__qui">
                <img
                    v-if="portrait"
                    class="cpt__avatar cpt__avatar--photo"
                    :src="portraitSrc(portrait)"
                    :srcset="portraitSrcset(portrait)"
                    sizes="40px"
                    alt=""
                    width="40"
                    height="40"
                >
                <span v-else-if="initiales" class="cpt__avatar" aria-hidden="true">{{ initiales }}</span>
                <span class="cpt__identite">
                    <span class="cpt__nom">{{ nom }}</span>
                    <span v-if="contact" class="cpt__contact">{{ contact }}</span>
                </span>
            </div>

            <!-- Deux sessions ouvertes est un état réel : le taire ferait croire
                 à une déconnexion qui n'a pas eu lieu. -->
            <p v-if="deuxEspaces" class="cpt__double">
                Vos deux espaces sont ouverts sur cet appareil.
            </p>

            <div v-for="espace in espaces" :key="espace.cle" class="cpt__bloc">
                <p v-if="deuxEspaces" class="cpt__titre">{{ espace.titre }}</p>

                <Link
                    v-for="lien in espace.liens"
                    :key="lien.href"
                    :href="lien.href"
                    class="cpt__lien"
                    :class="{ 'is-courant': courant(lien.href) }"
                    :aria-current="courant(lien.href) ? 'page' : undefined"
                    data-item
                    @click="fermer()"
                >
                    <SpaceIcon :name="lien.icone" class="cpt__ico" />
                    <span class="cpt__libelle">{{ lien.label }}</span>
                    <!-- Le compte des conversations en attente, le même que
                         la rubrique de l'espace : on compte les échanges, pas
                         les messages. -->
                    <span v-if="lien.compteur" class="cpt__badge num">{{ lien.compteur }}</span>
                </Link>
            </div>

            <!-- La sortie tout en bas, séparée par un filet : c'est le seul
                 geste du panneau qui défait quelque chose. Avec deux sessions,
                 une sortie par espace, chacune nommée. -->
            <div class="cpt__bloc cpt__bloc--sortie">
                <button
                    v-for="espace in espaces"
                    :key="`sortie-${espace.cle}`"
                    type="button"
                    class="cpt__lien cpt__lien--sortie"
                    data-item
                    @click="sortir(espace.sortie.url)"
                >
                    <SpaceIcon name="sortir" class="cpt__ico" />
                    <span class="cpt__libelle">{{ espace.sortie.label }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.cpt { position: relative; display: flex; }

/* Un contrôle doit se voir comme un contrôle : contour franc, fond blanc, et
   la hauteur du bouton de menu — 2,625 rem, soit 42 px, la cible tactile
   minimale. Le chevron dit que ça ouvre quelque chose : un rond seul se prend
   pour une image. */
.cpt__pastille {
    display: inline-flex;
    align-items: center;
    gap: .1rem;
    height: 2.625rem;
    padding: 0 .45rem 0 .3rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: #fff;
    color: var(--ink);
    cursor: pointer;
    transition:
        border-color .25s var(--ease),
        box-shadow .25s var(--ease);
}
/* Au survol et ouverte, elle se soulève d'une ombre au lieu de noircir son
   contour : un filet noir épais faisait de la pastille l'objet le plus lourd
   de la barre. */
.cpt__pastille:hover,
.cpt__pastille.is-on { border-color: var(--line-2); box-shadow: 0 4px 14px -6px rgba(26, 21, 18, .28); }
.cpt__pastille:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }

/* Le rond d'initiales est neutre : un avatar dit une identité, pas une action,
   et la barre ne porte qu'un seul objet coloré. */
.cpt__init,
.cpt__pastille > svg:not(.cpt__chev) {
    display: grid;
    place-items: center;
    width: 2rem;
    height: 2rem;
    border-radius: var(--r-pill);
    background: var(--ink);
    color: #fff;
    font-size: .78rem;
    font-weight: 800;
    letter-spacing: .01em;
}
.cpt__pastille > svg:not(.cpt__chev) { padding: .3rem; background: var(--off-2); color: var(--ink-3); }
.cpt__init--photo { object-fit: cover; background: var(--off-2); }

.cpt__chev {
    width: 1rem;
    height: 1rem;
    color: var(--text-3);
    transition: transform .3s var(--ease);
}
.cpt__pastille.is-on .cpt__chev { transform: rotate(180deg); }

/* **Un panneau qui flotte, pas une boîte posée.** Pas de bordure franche :
   un filet presque invisible et une ombre longue et douce le détachent de la
   page. Il s'ouvre depuis la pastille — son origine est le coin haut-droit —
   pour qu'on voie d'où il vient. */
.cpt__panneau {
    position: absolute;
    top: calc(100% + .55rem);
    right: 0;
    z-index: 10;
    width: min(17.5rem, calc(100vw - 2 * var(--gutter)));
    padding: .4rem 0;
    border: 1px solid rgba(26, 21, 18, .06);
    border-radius: var(--r-md);
    background: #fff;
    box-shadow:
        0 2px 6px rgba(26, 21, 18, .04),
        0 18px 48px -14px rgba(26, 21, 18, .26);
    transform-origin: top right;
    animation: cpt-in .18s var(--ease);
}

@keyframes cpt-in {
    from { opacity: 0; transform: translateY(-4px) scale(.97); }
}

/* L'identité : l'avatar, le nom en demi-gras, le contact en léger. */
.cpt__qui {
    display: flex;
    align-items: center;
    gap: .7rem;
    padding: .55rem 1rem .8rem;
}

.cpt__avatar {
    display: grid;
    place-items: center;
    flex: none;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: var(--r-pill);
    background: var(--ink);
    color: #fff;
    font-size: .82rem;
    font-weight: 700;
    letter-spacing: .02em;
}
.cpt__avatar--photo { object-fit: cover; background: var(--off-2); }

.cpt__identite { display: grid; min-width: 0; }
.cpt__nom {
    font-size: .92rem;
    font-weight: 600;
    letter-spacing: -.01em;
    color: var(--ink);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
/* L'adresse d'un compte est longue et ne se coupe pas sur un espace : sans
   `overflow-wrap`, elle élargit le panneau au-delà de l'écran. */
.cpt__contact {
    margin-top: .1rem;
    font-size: .8rem;
    font-weight: 400;
    color: var(--text-3);
    overflow-wrap: anywhere;
}

.cpt__double {
    margin: 0 1rem .6rem;
    font-size: .78rem;
    line-height: 1.45;
    color: var(--text-3);
}

/* Les groupes sont séparés par un filet qui ne touche pas les bords : il
   sépare sans couper le panneau en deux boîtes. */
.cpt__bloc { position: relative; padding: .35rem 0; }
.cpt__bloc::before {
    content: '';
    position: absolute;
    top: 0;
    left: 1rem;
    right: 1rem;
    height: 1px;
    background: var(--line);
}

.cpt__titre {
    margin: .4rem 1rem .25rem;
    font-size: .68rem;
    font-weight: 600;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--text-3);
}

/* **Des lignes fines, sur toute la largeur.** 400 pour le libellé : c'est une
   liste de lieux où aller, pas une liste d'alertes — le poids est gardé pour
   la ligne où l'on est. 2,6 rem de haut : on vise ces lignes au doigt, sur un
   téléphone, souvent d'une main. */
.cpt__lien {
    display: flex;
    align-items: center;
    gap: .8rem;
    width: 100%;
    min-height: 2.6rem;
    padding: .45rem 1rem;
    border: 0;
    background: none;
    font-family: inherit;
    font-size: .9rem;
    font-weight: 400;
    letter-spacing: -.005em;
    color: var(--ink);
    text-align: left;
    text-decoration: none;
    cursor: pointer;
    transition: background-color .15s var(--ease), color .15s var(--ease);
}
.cpt__lien:hover { background: var(--off); }
.cpt__lien:focus-visible { outline: 2px solid var(--terre-500); outline-offset: -2px; }

/* Le pictogramme au trait, un ton en dessous du libellé : il repère, le mot
   informe. Il reprend l'encre au survol. */
.cpt__ico { width: 1.15rem; height: 1.15rem; color: var(--text-3); transition: color .15s var(--ease); }
.cpt__lien:hover .cpt__ico { color: var(--ink); }

.cpt__libelle { flex: 1; min-width: 0; }

/* La ligne où l'on est : pictogramme en terre, un poids de plus — la terre dit
   « vous êtes là », comme dans la barre latérale. */
.cpt__lien.is-courant { font-weight: 600; }
.cpt__lien.is-courant .cpt__ico { color: var(--terre-500); }

.cpt__lien--sortie { color: var(--text-2); }

.cpt__badge {
    display: grid;
    place-items: center;
    min-width: 1.3rem;
    height: 1.3rem;
    padding: 0 .35rem;
    border-radius: var(--r-pill);
    background: var(--terre-500);
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
}

@media (prefers-reduced-motion: reduce) {
    .cpt__panneau { animation: none; }
    .cpt__chev { transition: none; }
}
</style>
