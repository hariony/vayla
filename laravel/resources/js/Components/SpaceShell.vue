<script setup>
/**
 * Le cadre des deux espaces — celui du client et celui du propriétaire.
 *
 * **Une barre latérale à gauche, et la même pour les deux.** Les rubriques
 * étaient des onglets posés en travers du haut de l'espace propriétaire : ça
 * tenait à trois, ça ne tient plus dès qu'on ajoute la facturation, les
 * messages et le profil — au-delà de quatre ou cinq, une rangée horizontale
 * défile et **ce qui dépasse de l'écran n'existe plus** pour celui qui ne
 * pense pas à faire glisser. Une colonne, elle, s'allonge sans rien cacher :
 * c'est pour ça que tous les outils sérieux la mettent là, et c'est la seule
 * raison de la reprendre.
 *
 * **Un seul cadre pour les deux espaces**, comme `AccessShell` pour les huit
 * écrans d'accès : deux barres recopiées auraient divergé au premier
 * ajustement, et l'une aurait fini par ne plus dire ce que l'autre dit.
 *
 * Ce qu'il porte, et pourquoi :
 *
 * - **Les rubriques à venir sont écrites, pas cachées.** Savoir que les
 *   messages arrivent change ce qu'on attend du produit — et surtout, quand
 *   ils arriveront, on saura déjà où les chercher. Elles vivent dans un groupe
 *   **titré « Bientôt »**, et n'ont donc pas besoin d'une pastille chacune :
 *   ce serait dire quatre fois ce que l'intitulé dit une. Ce ne sont **pas**
 *   des liens et elles n'en portent aucun signal — pas de fond au survol, pas
 *   de curseur de main : une entrée qui ressemble à un lien sans en être un
 *   est le faux signal que la maison s'interdit. Elles ne s'effacent pas non
 *   plus pour autant : un mot à 30 % d'opacité n'annonce rien, il disparaît.
 * - **La rubrique où l'on est prend la terre.** C'est déjà ce que faisait le
 *   trait sous l'onglet actif ; le repère a seulement tourné d'un quart de
 *   tour. Un repère qu'on doit chercher n'en est pas un.
 * - **Le cadre ne porte aucun en-tête à lui.** Les deux espaces montent
 *   `SiteHeader`, celui du site : même marque, même navigation, même menu de
 *   compte — et c'est ce menu qui porte la déconnexion. L'espace propriétaire
 *   a eu successivement son propre bandeau, puis plus rien du tout ; deux
 *   barres différentes pour deux espaces du même produit obligent à
 *   réapprendre où sont les choses en changeant de casquette.
 * - **La sortie est au pied de la colonne, et aussi dans le menu de
 *   l'en-tête.** Ce n'est pas une redondance à supprimer : dans un espace où
 *   l'on reste — le propriétaire y passe ses semaines — la colonne est
 *   l'endroit qu'on parcourt, et c'est là qu'on cherche à sortir. Le menu de
 *   l'en-tête, lui, sert **partout ailleurs sur le site**, où il n'y a pas de
 *   colonne. Deux surfaces différentes, pas deux fois la même.
 * - **Le monogramme se trace en filigrane** au pied de la barre. Le même geste
 *   que le littoral de l'accueil et le V de `/connexion` : c'est ce qui fait de
 *   ces écrans des pages de Vayla plutôt qu'un tableau de bord interchangeable.
 * - **Sous 960 px, la barre repasse en rangée qui défile**, jamais dans un
 *   bouton hamburger : un menu caché derrière trois traits n'est pas une
 *   navigation pour quelqu'un dont c'est le premier outil en ligne.
 *
 * **Ce qui rendait l'en-tête du site inacceptable ici a disparu.** La règle
 * « pas d'en-tête dans l'espace propriétaire » tenait à une raison précise :
 * la barre **recrutait**, et « Devenir hôte » n'a aucun sens pour quelqu'un
 * qui l'est déjà. Depuis, l'en-tête suit la session — le bouton se retire pour
 * un propriétaire, et « Connexion » est devenu le menu de son compte. La
 * raison tombée, la règle tombe avec elle, et l'homogénéité l'emporte.
 */
import { computed, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

import SpaceIcon from '@/Components/SpaceIcon.vue'
import { portraitSrc, portraitSrcset } from '@/Support/portrait.js'
import { useSpaceMotion } from '@/Composables/useSpaceMotion.js'

const props = defineProps({
    /** Le nom de l'espace, écrit en tête de la barre. */
    espace: { type: String, required: true },
    /**
     * Les rubriques, en groupes : `{ titre, items: [{ href, label, icone,
     * compteur, avenir }] }`. Une rubrique `avenir` n'a pas de `href`.
     */
    groupes: { type: Array, required: true },
    /** L'action principale de l'espace, en tête de barre. Optionnelle. */
    action: { type: Object, default: null },
    /** Le retour, quand l'écran est en dessous d'une rubrique. */
    back: { type: Object, default: null },
    /**
     * La garde de cet espace — c'est elle qui dit **de quelle session on
     * sort**. Les deux peuvent être ouvertes en même temps : se déconnecter
     * depuis l'espace client ne doit pas fermer celle du propriétaire.
     */
    garde: { type: String, default: 'web' },
})

const page = usePage()
const racine = ref(null)

useSpaceMotion(racine)

const flash = computed(() => page.props.flash ?? {})

/**
 * **Le fait d'abord, en gras ; ce qu'il faut faire ensuite, en romain.**
 * « Compte créé. Décrivez votre logement… » disait deux choses de même poids,
 * et on lisait la seconde comme la suite de la première au lieu de la lire
 * comme la consigne. La coupe tombe à la première phrase : « Photo ajoutée. »
 * reste entier, et un message sans point n'est pas coupé du tout.
 */
const decouper = (message = '') => {
    const coupe = message.search(/[.!?]\s/)

    return coupe === -1
        ? [message, '']
        : [message.slice(0, coupe + 1), message.slice(coupe + 2)]
}

/** La suite, précédée de son espace — ou rien. */
const suite = (message) => {
    const reste = decouper(message)[1]

    return reste ? ` ${reste}` : ''
}

/** L'adresse courante, sans ses paramètres : c'est elle qui dit où l'on est. */
const url = computed(() => page.url.split('?')[0])

/**
 * La rubrique la plus précise qui contient l'écran courant.
 *
 * La racine d'un espace ne peut être active que sur elle-même : sinon elle le
 * serait partout, puisque toutes les autres commencent par elle.
 */
const actif = (item) => {
    if (! item.href) {
        return false
    }

    const racines = ['/proprietaire', '/mes-reservations']

    return racines.includes(item.href)
        ? url.value === item.href
        : url.value.startsWith(item.href)
}

const compteur = (item) => (item.compteur ? page.props[item.compteur] ?? 0 : 0)

/**
 * Qui est connecté, et par où l'on sort.
 *
 * Le cadre le déduit de sa garde plutôt que de le recevoir : trois écrans
 * client montent ce cadre, et le leur faire recomposer chacun aurait garanti
 * qu'un des trois finisse par afficher autre chose.
 */
const compte = computed(() => {
    const proprietaire = page.props.auth?.owner
    const voyageur = page.props.auth?.user

    return props.garde === 'proprietaire'
        ? {
            nom: proprietaire?.name ?? 'Mon compte',
            // Le numéro plutôt que l'adresse : c'est par WhatsApp que Vayla
            // rappelle, et c'est le numéro qu'il reconnaît comme le sien.
            contact: proprietaire?.phone ?? '',
            portrait: proprietaire?.portrait ?? null,
            sortie: '/proprietaire/deconnexion',
        }
        : {
            nom: voyageur?.name ?? 'Mon compte',
            contact: voyageur?.email ?? '',
            sortie: '/deconnexion',
        }
})

/** Les initiales — deux lettres au plus, au-delà c'est illisible à 2 rem. */
const initiales = computed(() => {
    const nom = compte.value.nom?.trim()

    if (! nom || nom === 'Mon compte') {
        return ''
    }

    return nom.split(/\s+/).slice(0, 2).map((m) => m[0]).join('').toUpperCase()
})

/**
 * Se déconnecter est un POST, jamais un lien : un GET destructeur se déclenche
 * au préchargement d'un navigateur ou d'un antivirus, et on se retrouve dehors
 * sans avoir rien touché.
 */
const sortir = () => router.post(compte.value.sortie)

/**
 * Un groupe entièrement à venir.
 *
 * Il est **titré** « Bientôt », et c'est ce titre qui porte l'information —
 * pas une pastille répétée sur chacune de ses lignes, qui dirait quatre fois
 * la même chose sous un intitulé qui la dit déjà. Sur un écran étroit, où les
 * titres de groupe disparaissent, il redevient un mot en tête de ligne.
 */
const aVenir = (groupe) => groupe.items.every((item) => ! item.href)

/**
 * Le contour du monogramme, tracé et non rempli — le même chemin que
 * `VaylaMark`, emprunté ici comme filigrane. Le dupliquer plutôt que
 * d'importer le composant permet de le laisser en trait : un tracé rempli
 * n'aurait pas la même légèreté au pied d'une colonne.
 */
const MARQUE = 'M2.18,4.81 L19.79,45.70 Q38.12,20.37 45.82,2.30 L38.58,4.12 '
    + 'Q27.42,20.47 21.66,26.12 L9.01,7.47 Z'
</script>

<template>
    <div ref="racine" class="esp">
        <div class="shell esp__grille">
            <!-- ── La barre ────────────────────────────────────────── -->
            <aside class="esp__rail" :aria-label="espace">
                <div class="esp__rail-in">
                    <p class="esp__espace" data-espace-item>{{ espace }}</p>

                    <Link
                        v-if="action"
                        :href="action.href"
                        class="btn btn--sm btn--ink esp__action"
                        data-espace-item
                    >
                        <SpaceIcon name="publier" />
                        {{ action.label }}
                    </Link>

                    <nav class="esp__nav">
                        <div
                            v-for="(groupe, i) in groupes"
                            :key="groupe.titre ?? i"
                            class="esp__groupe"
                            :class="{ 'esp__groupe--avenir': aVenir(groupe) }"
                        >
                            <p v-if="groupe.titre" class="esp__groupe-t" data-espace-item>{{ groupe.titre }}</p>

                            <template v-for="item in groupe.items" :key="item.label">
                                <Link
                                    v-if="item.href"
                                    :href="item.href"
                                    class="esp__lien"
                                    :class="{ 'is-on': actif(item) }"
                                    :aria-current="actif(item) ? 'page' : undefined"
                                    data-espace-item
                                >
                                    <SpaceIcon :name="item.icone" />
                                    <span class="esp__label">{{ item.label }}</span>
                                    <!-- On compte les conversations en attente,
                                         pas les messages : « 2 » veut dire deux
                                         échanges, pas quatorze lignes. -->
                                    <span v-if="compteur(item)" class="esp__badge num">{{ compteur(item) }}</span>
                                </Link>

                                <!-- À venir : un mot, pas un lien. Il n'ouvre
                                     rien, donc il ne prend aucun signal de
                                     contrôle — ni fond au survol, ni curseur
                                     de main. C'est le titre du groupe qui dit
                                     pourquoi il est là. -->
                                <span v-else class="esp__lien esp__lien--avenir" data-espace-item>
                                    <SpaceIcon :name="item.icone" />
                                    <span class="esp__label">{{ item.label }}</span>
                                </span>
                            </template>
                        </div>
                    </nav>

                    <div class="esp__compte" data-espace-item>
                        <!-- Le visage s'il existe, les initiales sinon : un
                             rond de lettres n'est là que faute de mieux. -->
                        <img
                            v-if="compte.portrait"
                            class="esp__init esp__init--photo"
                            :src="portraitSrc(compte.portrait)"
                            :srcset="portraitSrcset(compte.portrait)"
                            sizes="34px"
                            alt=""
                            width="34"
                            height="34"
                        >
                        <span v-else-if="initiales" class="esp__init" aria-hidden="true">{{ initiales }}</span>
                        <span class="esp__qui">
                            <span class="esp__nom">{{ compte.nom }}</span>
                            <span v-if="compte.contact" class="esp__contact">{{ compte.contact }}</span>
                        </span>
                        <button type="button" class="esp__sortir" @click="sortir">Se déconnecter</button>
                    </div>

                    <svg class="esp__filigrane" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                        <path :d="MARQUE" stroke="currentColor" stroke-width=".9" stroke-linejoin="round" />
                    </svg>
                </div>
            </aside>

            <!-- ── Le contenu ──────────────────────────────────────── -->
            <main class="esp__corps" data-espace-corps>
                <!-- Un retour qui se voit comme un bouton : une flèche seule,
                     sans contour ni fond, ne se lit pas comme un contrôle. -->
                <Link v-if="back" :href="back.href" class="esp__back">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 5.5 8.5 12l6.5 6.5" />
                    </svg>
                    {{ back.label }}
                </Link>

                <!-- Le retour d'action est en haut et annoncé aux lecteurs
                     d'écran : sans lui, agir ne dit rien, et on reclique. -->
                <div v-if="flash.succes" class="esp__flash esp__flash--ok" role="status">
                    <span class="esp__flash-marque" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path class="esp__flash-coche" d="M6.5 12.4l3.6 3.6 7.4-8" />
                        </svg>
                    </span>
                    <!-- L'espace entre les deux phrases est **dans**
                         l'interpolation : laissé en tête d'un nœud de texte,
                         le compilateur de gabarits le condensait, et on
                         lisait « Compte créé.Décrivez ». -->
                    <p class="esp__flash-texte">
                        <strong>{{ decouper(flash.succes)[0] }}</strong>{{ suite(flash.succes) }}
                    </p>
                </div>

                <div v-if="flash.erreur" class="esp__flash esp__flash--ko" role="alert">
                    <span class="esp__flash-marque" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                             stroke-linecap="round">
                            <path d="M12 7.2v6.2M12 16.9h.01" />
                        </svg>
                    </span>
                    <p class="esp__flash-texte">
                        <strong>{{ decouper(flash.erreur)[0] }}</strong>{{ suite(flash.erreur) }}
                    </p>
                </div>

                <slot />

                <p v-if="$slots.pied" class="esp__pied">
                    <slot name="pied" />
                </p>
            </main>
        </div>
    </div>
</template>

<style scoped>
.esp { background: var(--off); }

/* Les deux espaces s'ouvrent sous l'en-tête du site. La hauteur de fenêtre
   n'est pas imposée : elle laisserait un vide entre deux réservations et le
   bas de page. */
.esp {
    min-height: 60vh;
    padding-block: calc(var(--header-h) + clamp(1rem, 3vw, 2rem)) clamp(3rem, 6vw, 4.5rem);
}

.esp__grille {
    display: grid;
    grid-template-columns: minmax(0, 16.5rem) minmax(0, 1fr);
    align-items: start;
    gap: clamp(1.25rem, 2.5vw, 2.25rem);
}

/* **La barre colle, le contenu défile.** Une longue fiche de logement fait
   trois mille pixels : sans ça, changer de rubrique demanderait de remonter
   tout en haut. */
.esp__rail { position: sticky; top: calc(var(--header-h) + 1rem); }

.esp__rail-in {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: .35rem;
    overflow: hidden;
    padding: 1.1rem .8rem 1rem;
    border: 1px solid var(--line);
    border-radius: var(--r-lg);
    background: var(--white);
    box-shadow: var(--sh-1);
}

.esp__espace {
    margin: 0 .5rem .75rem;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: var(--text-3);
}

/* L'action principale en haut de la barre, pas noyée dans la liste : c'est ce
   qu'un propriétaire vient faire, et ce n'est pas une rubrique où l'on va,
   c'est un geste. */
.esp__action {
    justify-content: flex-start;
    gap: .5rem;
    margin: 0 0 .9rem;
    padding-inline: .85rem;
}

.esp__nav { display: flex; flex-direction: column; gap: 1.1rem; }
.esp__groupe { display: flex; flex-direction: column; gap: .15rem; }

.esp__groupe-t {
    margin: 0 .55rem .3rem;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--text-3);
}

/* 2,75 rem de haut : la cible tactile minimale, la même que partout ailleurs.
   Le repère de la rubrique active est une barre de terre à gauche — le trait
   qui était sous l'onglet, tourné d'un quart de tour. */
.esp__lien {
    position: relative;
    display: flex;
    align-items: center;
    gap: .6rem;
    min-height: 2.75rem;
    padding: .4rem .7rem;
    border-radius: var(--r-sm);
    font-size: .92rem;
    font-weight: 600;
    letter-spacing: -.012em;
    color: var(--text-2);
    text-decoration: none;
    transition: background-color .2s var(--ease), color .2s var(--ease);
}
.esp__lien::before {
    content: '';
    position: absolute;
    left: -.8rem;
    top: 50%;
    width: 3px;
    height: 1.6rem;
    border-radius: 0 3px 3px 0;
    background: var(--terre-500);
    transform: translateY(-50%) scaleY(0);
    transform-origin: center;
    transition: transform .3s var(--ease);
}

.esp__lien:hover { background: var(--off-2); color: var(--ink); }
.esp__lien:focus-visible { outline: 2px solid var(--terre-500); outline-offset: -2px; }

.esp__lien.is-on {
    background: var(--terre-050);
    color: var(--terre-700);
    font-weight: 800;
}
.esp__lien.is-on::before { transform: translateY(-50%) scaleY(1); }

.esp__label { flex: 1; }

.esp__badge {
    display: grid;
    place-items: center;
    min-width: 1.35rem;
    height: 1.35rem;
    padding: 0 .35rem;
    border-radius: var(--r-pill);
    background: var(--terre-500);
    color: var(--white);
    font-size: .72rem;
    font-weight: 800;
}

/* **À venir se lit, ça ne s'efface pas.** Le texte reste franchement lisible
   — 8,4:1 sur le fond — parce que ce qui dit « pas encore », c'est le titre du
   groupe et l'absence de tout signal de contrôle : ni fond au survol, ni
   curseur de main, ni contour. Une opacité réduite, elle, ne dirait rien : le
   mot disparaîtrait au lieu d'annoncer ce qui arrive. */
.esp__lien--avenir { min-height: 2.4rem; color: var(--ink-3); cursor: default; }
.esp__lien--avenir:hover { background: none; color: var(--ink-3); }
.esp__lien--avenir .si { color: var(--text-3); }

/* **La sortie au pied de la colonne.** C'est là qu'on la cherche dans un
   espace où l'on reste, et le menu de l'en-tête sert partout ailleurs — deux
   surfaces, pas deux fois la même. */
.esp__compte {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: .3rem .6rem;
    margin-top: 1.1rem;
    padding-top: .9rem;
    border-top: 1px solid var(--line);
}

.esp__init {
    display: grid;
    place-items: center;
    width: 2.1rem;
    height: 2.1rem;
    border-radius: var(--r-pill);
    background: var(--ink);
    color: var(--white);
    font-size: .78rem;
    font-weight: 800;
}

.esp__init--photo { object-fit: cover; background: var(--off-2); }

.esp__qui { display: grid; min-width: 0; align-content: center; }
.esp__nom {
    font-size: .88rem;
    font-weight: 800;
    letter-spacing: -.02em;
    color: var(--ink);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.esp__contact {
    font-size: .78rem;
    color: var(--text-3);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Un contrôle doit se voir comme un contrôle : contour franc et fond blanc,
   pas un mot gris posé dans un coin. */
.esp__sortir {
    grid-column: 1 / -1;
    margin-top: .55rem;
    min-height: 2.4rem;
    padding: .4rem .9rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font: inherit;
    font-size: .84rem;
    font-weight: 700;
    color: var(--text-2);
    cursor: pointer;
    transition: border-color .2s var(--ease), color .2s var(--ease);
}
.esp__sortir:hover { border-color: var(--ink); color: var(--ink); }
.esp__sortir:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }

/* Le filigrane : une texture, pas une image. Au-delà de quelques pour cent il
   se met à concurrencer les rubriques qu'il porte. */
.esp__filigrane {
    position: absolute;
    right: -2.5rem;
    bottom: -3rem;
    width: 12rem;
    height: 12rem;
    color: var(--terre-500);
    opacity: .05;
    pointer-events: none;
}

/* La colonne fait sinon 1 100 px sous `.shell` : bien au-delà de ce qu'une
   ligne de texte supporte, et l'oeil perd le début de la ligne suivante. */
.esp__corps { min-width: 0; max-width: 66rem; padding-bottom: 1rem; }

.esp__back {
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
.esp__back:hover { background: var(--off-2); }
.esp__back svg { width: 1.1rem; height: 1.1rem; }

/* **Un message de retour, pas une dalle.** C'était un aplat d'encre plein
   posé en haut de l'écran : la chose la plus sombre de la page, au-dessus
   d'un formulaire blanc — il prenait l'œil comme une alerte alors qu'il dit
   « c'est fait », et il écrasait la consigne qui le suivait. C'est maintenant
   une carte blanche bordée, portée par une marque ronde : le fait se lit en
   gras, la suite en romain. */
.esp__flash {
    display: flex;
    align-items: flex-start;
    gap: .8rem;
    margin: 0 0 1.25rem;
    padding: .85rem 1.1rem .85rem .85rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--white);
    box-shadow: var(--sh-1);
    animation: esp-flash .4s var(--ease) both;
}

@keyframes esp-flash {
    from { opacity: 0; transform: translateY(-6px); }
}

.esp__flash-marque {
    display: grid;
    place-items: center;
    flex: none;
    width: 2rem;
    height: 2rem;
    border-radius: var(--r-pill);
}
.esp__flash-marque svg { width: 1.1rem; height: 1.1rem; }

.esp__flash-texte {
    margin: 0;
    padding-top: .2rem;
    font-size: .92rem;
    line-height: 1.5;
    color: var(--text-2);
}
.esp__flash-texte strong { font-weight: 800; color: var(--ink); }

/* **La coche est verte — une exception à la règle du lagon, décidée et
   bornée.** Partout ailleurs le lagon ne dit que « vérifié ». Ici il signe
   « c'est fait », sur demande : un disque d'encre se lisait comme un trou
   noir, pas comme une réussite. L'exception tient à trois conditions — **la
   coche seule**, jamais la carte ni le texte, qui restent neutres ; **une
   forme que la vérification n'emploie pas** — un rond et une coche, là où la
   jauge est une barre à quatre segments et le sceau une pastille légendée ;
   et **un fond de lagon très pâle**, pas un aplat. La jauge apparaît pourtant
   dans les espaces (« Mes logements », le tableau de bord) : c'est la forme,
   pas l'endroit, qui doit empêcher de confondre les deux. Voir CLAUDE.md. */
.esp__flash--ok .esp__flash-marque {
    background: var(--lagon-050);
    box-shadow: inset 0 0 0 1px var(--lagon-100);
    color: var(--lagon-600);
}

/* **Visible au repos, tracée à l'entrée.** La première version la posait
   invisible (`stroke-dashoffset: 18`) et comptait sur l'animation pour la
   faire paraître : là où l'animation ne se jouait pas, il ne restait qu'un
   disque vide. C'est la règle de la maison — la coupure rend visible : l'état
   final est l'état de repos, l'animation ne fait qu'y arriver. */
.esp__flash-coche {
    stroke-dasharray: 18;
    stroke-dashoffset: 0;
    animation: esp-coche .45s .2s var(--ease) backwards;
}

@keyframes esp-coche {
    from { stroke-dashoffset: 18; }
}

/* L'erreur prend la terre, qui porte déjà l'avertissement sur le ruban des
   saisons — la même carte, une autre famille de couleur : ce qui distingue
   les deux, c'est la couleur et la marque, pas la forme. */
.esp__flash--ko {
    border-color: var(--terre-300);
    background: var(--terre-050);
    box-shadow: none;
}
.esp__flash--ko .esp__flash-marque { background: var(--terre-500); color: var(--white); }
.esp__flash--ko .esp__flash-texte { color: var(--terre-700); }
.esp__flash--ko .esp__flash-texte strong { color: var(--terre-700); }

@media (prefers-reduced-motion: reduce) {
    .esp__flash { animation: none; }
    .esp__flash-coche { animation: none; stroke-dashoffset: 0; }
}

.esp__pied {
    margin: clamp(2.5rem, 5vw, 3.5rem) 0 0;
    padding-top: 1.25rem;
    border-top: 1px solid var(--line);
    font-size: .8rem;
    line-height: 1.6;
    color: var(--text-3);
}

/* ── Sous 960 px : la colonne redevient une rangée ──────────────────
   Elle défile horizontalement plutôt que de se replier dans un bouton
   hamburger — un menu caché derrière trois traits n'est pas une navigation
   pour quelqu'un dont c'est le premier outil en ligne. Les rubriques à venir
   sortent de la rangée : sur un téléphone, la place se donne à ce qui marche
   aujourd'hui, et le filigrane n'a plus de colonne où se poser. */
@media (max-width: 960px) {
    .esp__grille { grid-template-columns: minmax(0, 1fr); }

    .esp__rail { position: static; }

    .esp__rail-in {
        gap: .5rem;
        padding: .9rem .9rem .75rem;
        border-radius: var(--r-md);
    }

    /* Chaque groupe devient sa propre rangée : le groupe des rubriques
       réelles défile horizontalement, celui des rubriques à venir se replie
       en une ligne de texte sous lui. Une seule rangée pour les deux ferait
       sortir de l'écran ce qui marche aujourd'hui, poussé par ce qui n'existe
       pas encore. */
    .esp__nav { display: block; }

    .esp__groupe {
        flex-direction: row;
        gap: .25rem;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .esp__groupe::-webkit-scrollbar { display: none; }
    .esp__groupe-t { display: none; }

    .esp__lien { flex: none; min-height: 2.6rem; white-space: nowrap; }
    .esp__lien::before { display: none; }
    .esp__label { flex: none; }

    /* Les rubriques à venir ne prennent pas la place de celles qui marchent :
       elles se rangent sur une ligne, à plat, derrière le mot qui les annonce
       — c'est là que le titre du groupe redevient utile. */
    .esp__groupe--avenir {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: 0 .25rem;
        margin-top: .55rem;
        padding-top: .6rem;
        border-top: 1px solid var(--line);
        overflow: visible;
    }
    .esp__groupe--avenir .esp__groupe-t {
        display: block;
        margin: 0 .25rem 0 .55rem;
    }
    .esp__groupe--avenir .esp__lien {
        min-height: 0;
        padding: .15rem .3rem;
        font-size: .84rem;
    }
    .esp__groupe--avenir .si { display: none; }

    .esp__espace { margin-bottom: .35rem; }
    .esp__action { margin-bottom: .35rem; }

    /* La rangée est déjà à l'étroit : le compte passe sur une seule ligne,
       pastille, nom et sortie alignés. */
    .esp__compte {
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        margin-top: .6rem;
    }
    .esp__sortir { grid-column: auto; margin-top: 0; }

    .esp__filigrane { display: none; }
}
</style>
