<script setup>
/**
 * La fiche du logement, saisie par son propriétaire.
 *
 * **Un assistant en cinq étapes, où chaque étape se clique.** La fiche était
 * un long formulaire à sections, par crainte de l'assistant classique — doux à
 * la première saisie, insupportable à la quinzième correction, puisqu'on
 * corrige un tarif dix fois pour une création. Ce qui rendait l'assistant
 * insupportable, c'était l'**ordre imposé** : ici chaque étape s'atteint
 * directement depuis la barre, et en modification « Enregistrer » reste
 * disponible à chaque étape. On garde la douceur de la première saisie — une
 * question à la fois — sans payer la quinzième correction.
 *
 * **« Suivant » ne bloque jamais.** Un champ obligatoire vide ne retient
 * personne sur une étape : la barre marque ce qui reste à remplir, et c'est le
 * bouton final qui refuse, **en nommant ce qui manque** et en y menant d'un
 * clic. Retenir quelqu'un sur l'étape 1 parce qu'il n'a pas encore choisi de
 * titre, c'est l'empêcher de voir ce qu'on attend de lui ensuite.
 *
 * **Une erreur du serveur ramène à son étape.** La validation reste celle de
 * `OwnerListingRequest`, une seule fois ; mais une erreur sur le titre, reçue
 * alors qu'on est sur les photos, serait invisible. On saute donc à la
 * première étape fautive, et chaque étape en erreur le dit dans la barre.
 *
 * **Rien n'est publié depuis cet écran.** On enregistre, puis on **envoie à la
 * vérification**. Le niveau de confiance n'apparaît nulle part dans ce que le
 * propriétaire peut écrire : il est attribué par Vayla, jamais déclaré — c'est
 * ce qui fait que « vérifié » veut dire quelque chose.
 *
 * **Après vérification, la fiche se referme partiellement.** Tarif,
 * description et règles de séjour restent libres ; capacité, type,
 * destination, équipements et photos ne bougent plus. Une annonce visitée en
 * visio dont on pourrait changer les photos ferait de la vérification un
 * tampon sans objet.
 *
 * Les cent deux équipements sont **groupés par rubrique et repliés** : une
 * seule liste de cent deux cases est illisible, et le propriétaire abandonne
 * avant la moitié.
 */
import { computed, nextTick, onMounted, ref } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import gsap from 'gsap'

import OwnerShell from '../Partials/OwnerShell.vue'
import AmenityIcon from '@/Components/AmenityIcon.vue'
import FormSteps from './FormSteps.vue'
import PhotoManager from './PhotoManager.vue'
import { nombre } from '@/Support/format.js'
import { useDevise } from '@/Composables/useDevise.js'

const props = defineProps({
    listing: { type: Object, default: null },
    destinations: { type: Array, default: () => [] },
    kinds: { type: Array, default: () => [] },
    amenityGroups: { type: Array, default: () => [] },
})

const creation = computed(() => props.listing === null)
const modifiable = computed(() => creation.value || props.listing.modifiable)

const { euros } = useDevise()

/** Les équipements cochés, sous la forme attendue par le serveur. */
const choisis = ref(new Map(
    (props.listing?.amenities ?? []).map((a) => [a.id, a.highlight])
))

const form = useForm({
    title: props.listing?.title ?? '',
    destination_id: props.listing?.destination_id ?? null,
    kind: props.listing?.kind ?? 'villa',
    summary: props.listing?.summary ?? '',
    description: props.listing?.description ?? '',

    guests: props.listing?.guests ?? 2,
    bedrooms: props.listing?.bedrooms ?? 1,
    beds: props.listing?.beds ?? 1,
    bathrooms: props.listing?.bathrooms ?? 1,
    surface: props.listing?.surface ?? null,

    price: props.listing?.price ?? null,
    min_nights: props.listing?.min_nights ?? 1,
    max_nights: props.listing?.max_nights ?? null,
    check_in_from: props.listing?.check_in_from ?? '14:00',
    check_out_before: props.listing?.check_out_before ?? '11:00',
    pets_allowed: props.listing?.pets_allowed ?? false,
    smoking_allowed: props.listing?.smoking_allowed ?? false,
    events_allowed: props.listing?.events_allowed ?? false,

    amenities: [],
})

const ouverte = ref(props.amenityGroups[0]?.key ?? null)
const envoiVerif = ref(false)

/**
 * Les cinq étapes, et **les champs que chacune porte**.
 *
 * La liste des champs n'est pas décorative : c'est elle qui ramène une erreur
 * du serveur à son étape. Un test vérifie que chaque règle de
 * `OwnerListingRequest` y figure — un champ oublié ici serait une erreur
 * qu'aucune étape n'afficherait.
 */
const ETAPES = [
    { cle: 'essentiel', label: "L'essentiel", champs: ['title', 'destination_id', 'kind', 'summary', 'description'] },
    { cle: 'capacite', label: 'Capacité', champs: ['guests', 'bedrooms', 'beds', 'bathrooms', 'surface'] },
    {
        cle: 'tarif',
        label: 'Tarif et séjour',
        champs: ['price', 'min_nights', 'max_nights', 'check_in_from', 'check_out_before',
            'pets_allowed', 'smoking_allowed', 'events_allowed'],
    },
    { cle: 'equipements', label: 'Équipements', champs: ['amenities'] },
    { cle: 'photos', label: 'Photos', champs: [] },
]

const DERNIERE = ETAPES.length - 1

const page = usePage()

/**
 * L'étape de départ vient de l'adresse (`?etape=photos`) : c'est ce qui dépose
 * le propriétaire **directement sur les photos** après la création — la
 * prochaine chose à faire — et ce qui garde l'étape quand on recharge.
 */
const depart = () => {
    const cle = new URLSearchParams(page.url.split('?')[1] ?? '').get('etape')
    const i = ETAPES.findIndex((e) => e.cle === cle)

    return i === -1 ? 0 : i
}

const etape = ref(depart())
const sens = ref(1)
const racine = ref(null)

/** Ce qu'une étape exige pour être « remplie ». Les étapes facultatives ne le sont qu'une fois touchées. */
const REMPLIE = {
    essentiel: () => form.title.trim().length >= 8 && Boolean(form.destination_id),
    capacite: () => form.guests >= 1 && form.beds >= 1 && form.bathrooms >= 1 && form.bedrooms !== '' && form.bedrooms !== null,
    tarif: () => form.price >= 5000 && form.min_nights >= 1,
    equipements: () => choisis.value.size > 0,
    photos: () => !creation.value && (props.listing.photos?.length ?? 0) > 0,
}

const enErreur = (e) => Object.keys(form.errors).some(
    (k) => e.champs.some((c) => k === c || k.startsWith(`${c}.`))
)

const etapes = computed(() => ETAPES.map((e) => ({
    ...e,
    etat: enErreur(e) ? 'erreur' : (REMPLIE[e.cle]() ? 'fait' : 'vide'),
})))

/**
 * Ce qui manque pour créer, **nommé et atteignable** : un bouton désactivé qui
 * ne dit pas pourquoi est une impasse. Chaque manque mène à son étape.
 */
const manques = computed(() => [
    form.title.trim().length < 8 && { etape: 0, texte: 'le nom du logement' },
    !form.destination_id && { etape: 0, texte: 'la localisation' },
    !(form.price >= 5000) && { etape: 2, texte: 'le prix pour une nuit' },
].filter(Boolean))

const sansMouvement = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches

/**
 * Change d'étape.
 *
 * L'adresse suit (`?etape=`) sans visite Inertia : `replaceState` en gardant
 * `history.state`, où Inertia range sa page — le vider casserait le bouton
 * retour. La page remonte en haut du formulaire : la barre d'action est
 * collée en bas, et changer d'étape en restant au milieu de la précédente
 * ferait commencer la lecture de la nouvelle par son milieu.
 */
const aller = async (i) => {
    const cible = Math.max(0, Math.min(DERNIERE, i))

    if (cible === etape.value) {
        return
    }

    sens.value = cible > etape.value ? 1 : -1
    etape.value = cible

    const adresse = new URL(window.location.href)
    adresse.searchParams.set('etape', ETAPES[cible].cle)
    window.history.replaceState(window.history.state, '', adresse)

    await nextTick()

    const haut = racine.value?.getBoundingClientRect().top ?? 0
    const entete = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--header-h')) || 78

    if (haut < entete) {
        window.scrollTo({ top: window.scrollY + haut - entete - 16, behavior: sansMouvement() ? 'auto' : 'smooth' })
    }

    // L'étape arrive du côté vers lequel on avance : vers la droite en
    // avançant, vers la gauche en revenant. Court, et rien sous
    // `prefers-reduced-motion`.
    if (! sansMouvement()) {
        const panneau = racine.value?.querySelector(`[data-etape="${ETAPES[cible].cle}"]`)

        if (panneau) {
            gsap.fromTo(panneau, { x: 18 * sens.value, opacity: 0 }, { x: 0, opacity: 1, duration: .32, ease: 'power2.out' })
        }
    }
}

/**
 * Y a-t-il quelque chose de non enregistré ?
 *
 * `form.isDirty` ne voit pas les équipements : ils vivent dans `choisis` et ne
 * passent dans le formulaire qu'à l'enregistrement. Sans cette comparaison, on
 * pouvait cocher un groupe électrogène puis envoyer à Vayla une fiche qui ne
 * le portait pas.
 */
const equipementsInitiaux = JSON.stringify(
    (props.listing?.amenities ?? []).map((a) => [a.id, Boolean(a.highlight)]).sort((a, b) => a[0] - b[0])
)

const modifie = computed(() => form.isDirty || JSON.stringify(
    [...choisis.value].map(([id, h]) => [id, Boolean(h)]).sort((a, b) => a[0] - b[0])
) !== equipementsInitiaux)

/** Après un refus du serveur : la première étape fautive, là où l'erreur se lit. */
const allerALaPremiereErreur = () => {
    const i = ETAPES.findIndex(enErreur)

    if (i !== -1) {
        aller(i)
    }
}

// Une erreur déjà présente au chargement (retour arrière, rechargement) mène
// aussi à son étape.
onMounted(() => {
    if (Object.keys(form.errors).length) {
        allerALaPremiereErreur()
    }
})

const basculer = (id) => {
    if (!modifiable.value) return
    choisis.value.has(id) ? choisis.value.delete(id) : choisis.value.set(id, false)
    choisis.value = new Map(choisis.value)
}

/**
 * « Mettre en avant » n'est pas un équipement de plus : c'est ce qui remonte
 * sur la carte du catalogue. Les trois arguments de la carte sont **dérivés**
 * de ce marqueur, jamais saisis à part — une carte ne peut donc pas vanter un
 * équipement que la fiche ne détaille pas.
 */
const vedette = (id) => {
    if (!modifiable.value || !choisis.value.has(id)) return
    choisis.value.set(id, !choisis.value.get(id))
    choisis.value = new Map(choisis.value)
}

const nbCoches = (groupe) => groupe.amenities.filter((a) => choisis.value.has(a.id)).length
const nbVedettes = computed(() => [...choisis.value.values()].filter(Boolean).length)

const enregistrer = () => {
    form.amenities = [...choisis.value].map(([id, highlight]) => ({ id, highlight }))

    creation.value
        ? form.post('/proprietaire/logements', { onError: allerALaPremiereErreur })
        : form.post(`/proprietaire/logements/${props.listing.slug}`, {
            preserveScroll: true,
            onError: allerALaPremiereErreur,
        })
}

const soumettre = () => {
    envoiVerif.value = true
    router.post(`/proprietaire/logements/${props.listing.slug}/soumettre`, {}, {
        preserveScroll: true,
        onFinish: () => (envoiVerif.value = false),
    })
}

/** Le prix en euros, sous le champ : le propriétaire fixe un tarif que des étrangers liront. */
const prixEur = computed(() => (form.price ? euros(form.price) : null))
</script>

<template>
    <Head :title="creation ? 'Nouveau logement — Vayla' : `${listing.title} — Vayla`" />

    <OwnerShell :back="{ href: '/proprietaire/logements', label: 'Mes logements' }">
        <header class="fm__head">
            <p class="fm__eyebrow">{{ creation ? 'Nouveau logement' : listing.statusLabel }}</p>
            <h1 class="espace__titre">{{ creation ? 'Décrivez votre logement' : listing.title }}</h1>
            <p v-if="!creation" class="fm__consigne">{{ listing.consigne }}</p>
            <!-- Le motif du renvoi, écrit par Vayla : c'est ce qui dit quoi
                 reprendre avant de renvoyer la fiche. -->
            <div v-if="!creation && listing.reviewNote" class="fm__renvoi" role="note">
                <p class="fm__renvoi-t">Vayla vous demande</p>
                <p class="fm__renvoi-p">{{ listing.reviewNote }}</p>
            </div>
        </header>

        <!-- Une fiche vérifiée n'est pas figée, elle est **partiellement**
             figée : on dit lesquels des champs bougent encore, plutôt que de
             griser sans expliquer. -->
        <p v-if="!creation && !modifiable" class="fm__locked">
            Cette annonce a été vérifiée par Vayla. Vous pouvez encore changer le
            <strong>tarif</strong>, la <strong>description</strong> et les <strong>règles de séjour</strong>.
            Pour la capacité, les équipements ou les photos, écrivez-nous : nous revérifions avec vous.
        </p>

        <div ref="racine" class="fm__cadre">
        <FormSteps :etapes="etapes" :courante="etape" @aller="aller" />

        <form class="fm" @submit.prevent="enregistrer">
            <!-- `v-show`, pas `v-if` : les champs restent montés d'une étape
                 à l'autre — le remplissage automatique du navigateur et ce
                 qu'on a tapé ne se perdent pas en changeant d'étape. -->
            <!-- 1 ────────────────────────────────────────────────── -->
            <section v-show="etape === 0" class="fm__block" data-etape="essentiel">
                <h2 class="espace__section fm__h">L'essentiel</h2>

                <div class="fm__field">
                    <label class="fm__label" for="title">Nom du logement</label>
                    <input id="title" v-model="form.title" type="text" class="fm__input"
                           :disabled="!modifiable" placeholder="Villa vue lagon, Ambatoloaka" maxlength="120">
                    <p class="fm__help">Le type et le lieu. C'est ce que le voyageur lit en premier.</p>
                    <p v-if="form.errors.title" class="fm__err">{{ form.errors.title }}</p>
                </div>

                <div class="fm__pair">
                    <div class="fm__field">
                        <!-- « Localisation » et non « Destination » : le mot du
                             voyageur qui choisit où partir n'est pas celui du
                             propriétaire qui situe sa maison. Seul le libellé
                             change — c'est la même liste de destinations, et
                             c'est ce qui garantit qu'une annonce reste
                             retrouvable depuis l'atlas. -->
                        <label class="fm__label" for="destination_id">Localisation</label>
                        <select id="destination_id" v-model="form.destination_id" class="fm__input" :disabled="!modifiable">
                            <option :value="null" disabled>Choisissez…</option>
                            <option v-for="d in destinations" :key="d.id" :value="d.id">{{ d.label }}</option>
                        </select>
                        <p v-if="form.errors.destination_id" class="fm__err">{{ form.errors.destination_id }}</p>
                    </div>

                    <div class="fm__field">
                        <label class="fm__label" for="kind">Type de logement</label>
                        <select id="kind" v-model="form.kind" class="fm__input" :disabled="!modifiable">
                            <option v-for="k in kinds" :key="k.value" :value="k.value">{{ k.label }}</option>
                        </select>
                    </div>
                </div>

                <div class="fm__field">
                    <label class="fm__label" for="summary">En une phrase</label>
                    <input id="summary" v-model="form.summary" type="text" class="fm__input" maxlength="200"
                           placeholder="Trois chambres au-dessus du lagon, piscine et groupe électrogène.">
                    <p class="fm__help">Ce qui distingue votre logement. Affiché sous le titre.</p>
                </div>

                <div class="fm__field">
                    <label class="fm__label" for="description">Description</label>
                    <textarea id="description" v-model="form.description" class="fm__input fm__area"
                              rows="7" maxlength="4000"
                              placeholder="Décrivez les pièces, la vue, la distance de la plage ou du centre, l'accès…"></textarea>
                    <p class="fm__help">
                        {{ form.description.length }} caractères.
                        Dites ce qui compte sur place : l'eau, l'électricité, l'accès, le bruit.
                    </p>
                </div>
            </section>

            <!-- 2 ────────────────────────────────────────────────── -->
            <section v-show="etape === 1" class="fm__block" data-etape="capacite">
                <h2 class="espace__section fm__h">Capacité</h2>
                <p class="fm__sub">
                    C'est ce que le voyageur regarde avant les photos : « est-ce que ça nous loge ? »
                </p>

                <div class="fm__grid">
                    <div class="fm__field">
                        <label class="fm__label" for="guests">Personnes au maximum</label>
                        <input id="guests" v-model.number="form.guests" type="number" min="1" max="30"
                               inputmode="numeric" class="fm__input" :disabled="!modifiable">
                        <p v-if="form.errors.guests" class="fm__err">{{ form.errors.guests }}</p>
                    </div>
                    <div class="fm__field">
                        <label class="fm__label" for="bedrooms">Chambres</label>
                        <input id="bedrooms" v-model.number="form.bedrooms" type="number" min="0" max="20"
                               inputmode="numeric" class="fm__input" :disabled="!modifiable">
                    </div>
                    <div class="fm__field">
                        <label class="fm__label" for="beds">Couchages</label>
                        <input id="beds" v-model.number="form.beds" type="number" min="1" max="40"
                               inputmode="numeric" class="fm__input" :disabled="!modifiable">
                    </div>
                    <div class="fm__field">
                        <label class="fm__label" for="bathrooms">Salles d'eau</label>
                        <input id="bathrooms" v-model.number="form.bathrooms" type="number" min="1" max="20"
                               inputmode="numeric" class="fm__input" :disabled="!modifiable">
                    </div>
                    <div class="fm__field">
                        <label class="fm__label" for="surface">Surface (m²)</label>
                        <input id="surface" v-model.number="form.surface" type="number" min="8" max="2000"
                               inputmode="numeric" class="fm__input" :disabled="!modifiable" placeholder="facultatif">
                    </div>
                </div>
            </section>

            <!-- 3 ────────────────────────────────────────────────── -->
            <section v-show="etape === 2" class="fm__block" data-etape="tarif">
                <h2 class="espace__section fm__h">Tarif et séjour</h2>

                <div class="fm__pair">
                    <div class="fm__field">
                        <label class="fm__label" for="price">Prix pour une nuit (Ar)</label>
                        <input id="price" v-model.number="form.price" type="number" min="5000" step="5000"
                               inputmode="numeric" class="fm__input" placeholder="185000">
                        <p class="fm__help">
                            <template v-if="form.price">{{ nombre(form.price) }} Ar<template v-if="prixEur"> · {{ prixEur }}</template> par nuit.</template>
                            <template v-else>Vayla ne prélève rien à la réservation : le voyageur vous règle sur place.</template>
                        </p>
                        <p v-if="form.errors.price" class="fm__err">{{ form.errors.price }}</p>
                    </div>

                    <div class="fm__field">
                        <label class="fm__label" for="min_nights">Nuits au minimum</label>
                        <input id="min_nights" v-model.number="form.min_nights" type="number" min="1" max="90"
                               inputmode="numeric" class="fm__input">
                    </div>
                </div>

                <div class="fm__pair">
                    <div class="fm__field">
                        <label class="fm__label" for="check_in_from">Arrivée à partir de</label>
                        <input id="check_in_from" v-model="form.check_in_from" type="time" class="fm__input">
                    </div>
                    <div class="fm__field">
                        <label class="fm__label" for="check_out_before">Départ avant</label>
                        <input id="check_out_before" v-model="form.check_out_before" type="time" class="fm__input">
                    </div>
                </div>

                <fieldset class="fm__rules">
                    <legend class="fm__label">Règles de la maison</legend>
                    <label class="fm__check"><input v-model="form.pets_allowed" type="checkbox"><span>Animaux acceptés</span></label>
                    <label class="fm__check"><input v-model="form.smoking_allowed" type="checkbox"><span>Fumeurs acceptés</span></label>
                    <label class="fm__check"><input v-model="form.events_allowed" type="checkbox"><span>Fêtes et événements autorisés</span></label>
                </fieldset>
            </section>

            <!-- 4 ────────────────────────────────────────────────── -->
            <section v-show="etape === 3" class="fm__block" data-etape="equipements">
                <h2 class="espace__section fm__h">Équipements</h2>
                <p class="fm__sub">
                    Cochez ce que le logement possède vraiment. Vayla vérifie sur place ou en visio —
                    un équipement coché qui n'existe pas fait annuler la vérification.
                </p>
                <p class="fm__count">
                    <strong>{{ choisis.size }}</strong> coché{{ choisis.size > 1 ? 's' : '' }}
                    <template v-if="nbVedettes"> · {{ nbVedettes }} mis en avant sur votre carte</template>
                </p>

                <div class="fm__groups">
                    <div v-for="g in amenityGroups" :key="g.key" class="fm__group">
                        <button
                            type="button"
                            class="fm__gtoggle"
                            :aria-expanded="ouverte === g.key"
                            @click="ouverte = ouverte === g.key ? null : g.key"
                        >
                            <span class="fm__gname">{{ g.label }}</span>
                            <span v-if="nbCoches(g)" class="fm__gn num">{{ nbCoches(g) }}</span>
                            <span class="fm__gchev" :class="{ 'is-open': ouverte === g.key }" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                                     stroke-linecap="round" stroke-linejoin="round"><path d="m5 9 7 7 7-7" /></svg>
                            </span>
                        </button>

                        <ul v-if="ouverte === g.key" class="fm__items">
                            <li v-for="a in g.amenities" :key="a.id">
                                <label class="fm__item" :class="{ 'is-on': choisis.has(a.id) }">
                                    <input
                                        type="checkbox"
                                        :checked="choisis.has(a.id)"
                                        :disabled="!modifiable"
                                        @change="basculer(a.id)"
                                    >
                                    <AmenityIcon :name="a.icon" class="fm__ico" />
                                    <span class="fm__iname">{{ a.label }}</span>
                                </label>
                                <button
                                    v-if="choisis.has(a.id)"
                                    type="button"
                                    class="fm__star"
                                    :class="{ 'is-on': choisis.get(a.id) }"
                                    :disabled="!modifiable"
                                    :title="choisis.get(a.id) ? 'Retirer de la carte' : 'Mettre en avant sur la carte'"
                                    @click="vedette(a.id)"
                                >
                                    {{ choisis.get(a.id) ? '★ En avant' : '☆ Mettre en avant' }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- 5 ────────────────────────────────────────────────── -->
            <section v-if="!creation" v-show="etape === 4" class="fm__block" data-etape="photos">
                <h2 class="espace__section fm__h">Photos</h2>
                <PhotoManager :slug="listing.slug" :photos="listing.photos" :modifiable="modifiable" />
            </section>
            <!-- En création, l'étape existe déjà : elle dit ce qui vient, et
                 porte le bouton qui crée. Les photos s'attachent à un
                 logement — il faut qu'il existe. La création ramène ensuite
                 **ici même**, sur cette étape, prête à recevoir les photos. -->
            <section v-else v-show="etape === 4" class="fm__block fm__block--muted" data-etape="photos">
                <h2 class="espace__section fm__h">Photos</h2>
                <p class="fm__sub fm__sub--seul">
                    Les photos s'ajoutent une fois le logement créé. Créez-le : vous
                    reviendrez directement sur cette étape pour les téléverser.
                </p>
            </section>

            <!-- **Collée en bas, sur toutes les étapes** : un bouton qu'il
                 faut aller chercher est un formulaire qu'on quitte sans
                 enregistrer. À gauche on revient, à droite on avance — et la
                 seule action pleine de terre est celle qui termine. -->
            <div class="fm__bar">
                <button v-if="etape > 0" type="button" class="btn btn--outline fm__prec" @click="aller(etape - 1)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5.5 8.5 12l6.5 6.5" /></svg>
                    Précédent
                </button>

                <span class="fm__pas num">Étape {{ etape + 1 }} sur {{ ETAPES.length }}</span>

                <!-- En modification, on enregistre depuis n'importe quelle
                     étape : c'est la quinzième correction qu'on protège. -->
                <button v-if="!creation" type="submit" class="btn btn--outline" :disabled="form.processing">
                    {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                </button>

                <button v-if="etape < DERNIERE" type="button" class="btn btn--ink fm__suiv" @click="aller(etape + 1)">
                    Suivant
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 5.5 6.5 6.5L9 18.5" /></svg>
                </button>

                <button
                    v-else-if="creation"
                    type="submit"
                    class="btn btn--terre"
                    :disabled="form.processing || manques.length > 0"
                >
                    {{ form.processing ? 'Création…' : 'Créer le logement' }}
                </button>

                <button
                    v-else-if="listing.status === 'draft'"
                    type="button"
                    class="btn btn--terre"
                    :disabled="envoiVerif || modifie"
                    @click="soumettre"
                >
                    {{ envoiVerif ? 'Envoi…' : 'Envoyer à Vayla' }}
                </button>
            </div>

            <!-- Un bouton désactivé dit pourquoi, et mène à ce qui manque. -->
            <p v-if="creation && etape === DERNIERE && manques.length" class="fm__manque">
                Pour créer le logement, il manque
                <template v-for="(m, i) in manques" :key="m.texte">
                    <button type="button" class="fm__lien" @click="aller(m.etape)">{{ m.texte }}</button><template
                        v-if="i < manques.length - 2">, </template><template v-else-if="i === manques.length - 2"> et </template>
                </template>.
            </p>
            <p v-if="!creation && etape === DERNIERE && listing.status === 'draft' && modifie" class="fm__manque">
                Enregistrez d'abord vos modifications : Vayla vérifie la fiche telle qu'elle est enregistrée.
            </p>
        </form>
        </div>
    </OwnerShell>
</template>

<style scoped>
.fm__head { margin-bottom: 1.5rem; }
.fm__eyebrow { margin: 0 0 .3rem; font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--terre-600); }
.fm__consigne { margin: .4rem 0 0; max-width: 56ch; font-size: .92rem; line-height: 1.55; color: var(--text-2); }
.fm__renvoi {
    max-width: 60ch;
    margin-top: .9rem;
    padding: .8rem 1rem;
    border: 1px solid var(--terre-200);
    border-left: 3px solid var(--terre-500);
    border-radius: var(--r-sm);
    background: var(--terre-050);
}
.fm__renvoi-t { margin: 0; font-size: .74rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--terre-700); }
.fm__renvoi-p { margin: .25rem 0 0; font-size: .92rem; line-height: 1.55; color: var(--ink); white-space: pre-line; }

.fm__locked {
    margin: 0 0 1.75rem;
    padding: .95rem 1.15rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--white);
    font-size: .88rem;
    line-height: 1.6;
    color: var(--text-2);
}
.fm__locked strong { color: var(--ink); }

.fm { display: grid; gap: clamp(1.75rem, 4vw, 2.5rem); }

.fm__block {
    padding: clamp(1.1rem, 3vw, 1.6rem);
    border: 1px solid var(--line-2);
    border-radius: var(--r-lg);
    background: var(--white);
}
.fm__block--muted { background: var(--off); }

.fm__h { margin: 0 0 1rem; }
.fm__sub { margin: -.5rem 0 1rem; max-width: 60ch; font-size: .88rem; line-height: 1.55; color: var(--text-2); }
.fm__sub--seul { margin-bottom: 0; }

.fm__field { display: grid; gap: .3rem; margin-bottom: 1.1rem; }
.fm__pair { display: grid; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); gap: 0 1rem; }
.fm__grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr)); gap: 0 1rem; }

.fm__label { font-size: .82rem; font-weight: 700; color: var(--ink); }

.fm__input {
    width: 100%;
    min-height: 2.75rem;
    padding: .65rem .85rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--white);
    font: inherit;
    font-size: .98rem;
    color: var(--ink);
}
.fm__input:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 1px; }
/* Un champ désactivé se lit, il ne s'efface pas : à 30 % d'opacité il
   disparaît au lieu de dire « celui-ci ne bouge plus ». */
.fm__input:disabled { background: var(--off); color: var(--text-2); cursor: not-allowed; }
.fm__area { resize: vertical; line-height: 1.6; }

.fm__help { margin: 0; font-size: .78rem; line-height: 1.5; color: var(--text-3); }
.fm__err { margin: 0; font-size: .8rem; font-weight: 600; color: var(--terre-700); }

.fm__rules { margin: 0; padding: 0; border: 0; display: grid; gap: .55rem; }
.fm__check, .fm__item { display: flex; align-items: center; gap: .6rem; font-size: .92rem; color: var(--text-2); cursor: pointer; }
.fm__check input { width: 1.15rem; height: 1.15rem; accent-color: var(--terre-500); }

.fm__count { margin: 0 0 1rem; font-size: .86rem; color: var(--text-2); }
.fm__count strong { color: var(--ink); }

.fm__groups { display: grid; gap: .5rem; }
.fm__group { border: 1px solid var(--line-2); border-radius: var(--r-md); overflow: hidden; }

.fm__gtoggle {
    display: flex;
    align-items: center;
    gap: .6rem;
    width: 100%;
    min-height: 2.75rem;
    padding: .7rem .95rem;
    border: 0;
    background: var(--white);
    font: inherit;
    font-size: .95rem;
    font-weight: 700;
    color: var(--ink);
    text-align: left;
    cursor: pointer;
}
.fm__gtoggle:hover { background: var(--off); }
.fm__gname { flex: 1; }
.fm__gn {
    display: grid;
    place-items: center;
    min-width: 1.5rem;
    height: 1.5rem;
    padding: 0 .4rem;
    border-radius: var(--r-pill);
    background: var(--ink);
    font-size: .76rem;
    font-weight: 800;
    color: var(--white);
}
.fm__gchev svg { width: 1.1rem; height: 1.1rem; transition: transform .25s var(--ease); }
.fm__gchev.is-open svg { transform: rotate(180deg); }

.fm__items { display: grid; gap: .3rem; margin: 0; padding: .3rem .95rem .9rem; list-style: none; border-top: 1px solid var(--line); }
.fm__items li { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }

.fm__item { flex: 1 1 14rem; min-height: 2.5rem; padding: .3rem 0; }
.fm__item input { width: 1.15rem; height: 1.15rem; accent-color: var(--terre-500); }
.fm__item.is-on .fm__iname { font-weight: 700; color: var(--ink); }
.fm__ico { width: 1.25rem; height: 1.25rem; color: var(--text-3); }

.fm__star {
    flex: none;
    padding: .3rem .7rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font: inherit;
    font-size: .74rem;
    font-weight: 700;
    color: var(--text-3);
    cursor: pointer;
}
.fm__star.is-on { border-color: var(--ink); background: var(--ink); color: var(--white); }

.fm__bar {
    position: sticky;
    bottom: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .6rem;
    padding: .9rem 0;
    background: linear-gradient(to top, var(--off) 70%, transparent);
}

/* Le compteur pousse les actions vers la droite : on revient à gauche, on
   avance à droite — le sens de lecture. */
.fm__pas { margin-right: auto; font-size: .82rem; font-weight: 700; color: var(--text-3); }
.fm__prec + .fm__pas { margin-left: .4rem; }

.fm__prec svg,
.fm__suiv svg { width: 1.05rem; height: 1.05rem; }

.fm__manque {
    margin: -.4rem 0 0;
    font-size: .86rem;
    line-height: 1.55;
    text-align: right;
    color: var(--text-2);
}

/* Un lien qui ressemble à un lien : souligné, en terre, et qui mène à
   l'étape où se remplit ce qui manque. */
.fm__lien {
    padding: 0;
    border: 0;
    background: none;
    font: inherit;
    font-weight: 700;
    color: var(--terre-600);
    text-decoration: underline;
    text-underline-offset: .2em;
    cursor: pointer;
}

@media (max-width: 560px) {
    .fm__pas { order: -1; flex-basis: 100%; }
    .fm__manque { text-align: left; }
}
</style>
