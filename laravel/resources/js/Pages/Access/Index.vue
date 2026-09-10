<script setup>
/**
 * L'aiguillage : deux espaces, deux cartes, rien d'autre.
 *
 * **Choisir d'abord, se connecter ensuite.** La version précédente mettait
 * les deux formulaires côte à côte : on arrivait sur quatre champs et deux
 * boutons sans avoir décidé où l'on allait, et un voyageur pouvait commencer
 * à taper dans le formulaire du propriétaire. Une page qui ne demande qu'une
 * chose — « qui êtes-vous ? » — se traverse sans y penser, et ce qui suit est
 * sans ambiguïté. Un test vérifie qu'elle ne porte ni `useForm` ni `<input>`.
 *
 * **Le V du monogramme est lui-même un carrefour**, et c'est le parti pris de
 * la page : deux branches qui divergent depuis un point. Le tracé se dessine
 * en fond, comme le littoral de Madagascar sur l'accueil — même geste, même
 * langage. C'est ce qui fait de cet écran une page de Vayla plutôt qu'un
 * formulaire de connexion interchangeable.
 *
 * **Le fond n'est pas un aplat.** Une lueur de latérite en haut à gauche et un
 * grain très fin donnent de la matière sans ajouter une dalle de couleur : la
 * règle « une seule dalle pleine par page » tient, parce qu'il n'y a pas de
 * dalle — il y a de l'air.
 *
 * **Ni en-tête ni pied de page, et c'est le sujet même de l'écran.** Une page
 * qui demande « qui êtes-vous ? » ne peut pas rouvrir en même temps toute la
 * navigation du site : le catalogue, les destinations et « Devenir hôte » sont
 * exactement les chemins qu'on vient de refermer pour poser une question. Le
 * seul lien qui reste est le monogramme, qui ramène à l'accueil — celui qui
 * s'est trompé de porte n'est pas enfermé, il n'a simplement qu'une sortie.
 * C'est déjà la forme des trois écrans d'inscription (`Auth/Register`,
 * `Auth/Code`, `Owner/Register`) : cette page était la dernière à porter le
 * chrome du site public.
 *
 * **Plus de lien d'inscription sous les cartes.** Il y en avait deux — « Créer
 * un compte voyageur », « Inscrire mon logement » — et ils datent du temps où
 * s'inscrire et se connecter étaient deux gestes distincts. Depuis que chaque
 * espace n'a plus qu'**une porte**, où le code décide si le compte s'ouvre ou
 * s'ouvrait déjà, ces liens menaient exactement où mènent déjà les deux
 * cartes. Ils ajoutaient donc **quatre chemins pour deux destinations**, et
 * faisaient hésiter là où la page ne pose qu'une question.
 *
 * **Aucune carte n'est mise en avant.** Le rôle de la page est de distinguer,
 * pas d'orienter : donner plus de poids à l'une ferait cliquer dessus par
 * défaut, ce qui est exactement l'erreur qu'on veut éviter. La terre
 * n'apparaît qu'au survol et au focus — c'est la couleur de l'action, et une
 * carte au repos n'en est pas une.
 */
import { Link } from '@inertiajs/vue3'

import AccessShell from '@/Components/AccessShell.vue'
import AccessIcon from '@/Components/AccessIcon.vue'

const ESPACES = [
    {
        href: '/connexion/client',
        icone: 'chercher',
        titre: 'Je cherche un logement',
        texte: 'Suivre ma demande de séjour, écrire au propriétaire, retrouver mes dates et le montant à régler.',
        cta: 'Espace client',
    },
    {
        href: '/proprietaire',
        icone: 'louer',
        titre: 'Je loue mon logement',
        texte: 'Répondre aux demandes, gérer mon calendrier, remplir la fiche de mes logements et suivre ma facture.',
        cta: 'Espace propriétaire',
    },
]
</script>

<template>
    <AccessShell
        titre-page="Connexion — Vayla"
        eyebrow="Connexion"
        titre="Vous êtes&nbsp;?"
        lede="Les deux espaces n'ouvrent pas les mêmes portes. Choisissez le vôtre, on s'occupe du reste."
        largeur="large"
    >
        <div class="ac__cards">
            <Link
                v-for="espace in ESPACES"
                :key="espace.href"
                :href="espace.href"
                class="ac__card"
                data-access-card
            >
                <span class="ac__ico" data-access-ico>
                    <AccessIcon :name="espace.icone" />
                </span>

                <h2 class="ac__h">{{ espace.titre }}</h2>
                <p class="ac__what">{{ espace.texte }}</p>

                <span class="ac__go">
                    {{ espace.cta }}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h13M13 6.5 18.5 12 13 17.5" />
                    </svg>
                </span>
            </Link>
        </div>
    </AccessShell>
</template>

<style scoped>
/* Le fond, le monogramme, la sortie et la composition centrée vivent dans
   `AccessShell` : ce qui reste ici est ce que l'aiguillage seul possède —
   les deux cartes. */
.ac__cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 20rem), 1fr));
    gap: clamp(1rem, 2.5vw, 1.75rem);
    max-width: var(--as-col);
    margin: 0 auto;
}

/* La carte entière est le lien : elle ne mène qu'à un seul endroit, donc
   aucune zone ne se rate au doigt. */
.ac__card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: clamp(2rem, 4.5vw, 3rem) clamp(1.5rem, 3.5vw, 2.25rem);
    text-align: center;
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: rgba(255, 255, 255, .78);
    backdrop-filter: blur(14px);
    text-decoration: none;
    box-shadow: 0 2px 24px -18px rgba(23, 20, 28, .5);
    transition:
        transform .4s var(--ease),
        box-shadow .4s var(--ease),
        border-color .4s var(--ease),
        background-color .4s var(--ease);
}
.ac__card:hover,
.ac__card:focus-visible {
    transform: translateY(-5px);
    border-color: var(--terre-300);
    background: var(--white);
    box-shadow: 0 22px 54px -30px rgba(201, 69, 42, .38);
}
.ac__card:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 3px; }

/* Un trait de terre qui se déploie en haut de la carte au survol : le seul
   endroit où la couleur d'action apparaît sur une carte qu'on touche. */
.ac__card::before {
    content: '';
    position: absolute;
    top: -1px;
    left: clamp(1.5rem, 3.5vw, 2.25rem);
    right: clamp(1.5rem, 3.5vw, 2.25rem);
    height: 2px;
    border-radius: 2px;
    background: var(--terre-500);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform .45s var(--ease);
}
.ac__card:hover::before,
.ac__card:focus-visible::before { transform: scaleX(1); }

/* Grande, et c'est le sujet de la carte : à cette taille le pictogramme se lit
   avant le titre, ce qui est exactement ce qu'on veut d'un aiguillage — on
   reconnaît son espace sans lire. La pastille ne pivote pas : c'est le dessin
   qui bouge, et deux mouvements superposés se gênent. */
.ac__ico {
    display: grid;
    place-items: center;
    width: 7rem;
    height: 7rem;
    margin-bottom: 1.75rem;
    border: 1px solid var(--line);
    border-radius: 2rem;
    background: var(--white);
    color: var(--ink);
    box-shadow: 0 6px 22px -14px rgba(23, 20, 28, .45);
    transition: transform .45s var(--ease), border-color .45s var(--ease), box-shadow .45s var(--ease), background-color .45s var(--ease);
}
.ac__ico :deep(svg) { width: 4rem; height: 4rem; overflow: visible; }
.ac__card:hover .ac__ico,
.ac__card:focus-visible .ac__ico {
    transform: translateY(-3px);
    border-color: var(--terre-300);
    background: var(--terre-050);
    box-shadow: 0 16px 34px -20px rgba(201, 69, 42, .45);
}
/* Le trou de l'anneau de la clé suit le fond de la pastille : figé en blanc,
   il se verrait comme une tache dès que la pastille passe à la terre. */
.ac__card:hover .ac__ico,
.ac__card:focus-visible .ac__ico { --icon-hole: var(--terre-050); }

.ac__h {
    margin: 0;
    font-size: clamp(1.3rem, 2.4vw, 1.55rem);
    font-weight: 800;
    letter-spacing: -.042em;
    color: var(--ink);
}
.ac__what {
    margin: .7rem auto 0;
    max-width: 28ch;
    font-size: .95rem;
    line-height: 1.65;
    color: var(--text-2);
}

.ac__go {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    margin-top: auto;
    padding-top: 1.85rem;
    font-size: .95rem;
    font-weight: 800;
    letter-spacing: -.02em;
    color: var(--ink);
    transition: color .3s var(--ease);
}
.ac__go svg { width: 1.15rem; height: 1.15rem; transition: transform .4s var(--ease); }
.ac__card:hover .ac__go,
.ac__card:focus-visible .ac__go { color: var(--terre-600); }
.ac__card:hover .ac__go svg { transform: translateX(5px); }

/* Sous 560 px la carte se resserre : la grande icône garde son poids, mais la
   carte ne doit pas remplir l'écran à elle seule — on doit voir qu'il y en a
   deux sans avoir à défiler. */
@media (max-width: 560px) {
    .ac__card { padding: 1.75rem 1.35rem; }
    .ac__ico { width: 5rem; height: 5rem; margin-bottom: 1.2rem; border-radius: 1.5rem; }
    .ac__ico :deep(svg) { width: 2.9rem; height: 2.9rem; }
    .ac__go { padding-top: 1.4rem; }
}

@media (prefers-reduced-motion: reduce) {
    .ac__card { backdrop-filter: none; background: var(--white); }
}
</style>
