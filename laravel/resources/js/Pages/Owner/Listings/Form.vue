<script setup>
/**
 * La fiche du logement, saisie par son propriétaire.
 *
 * **Un formulaire long à sections, pas un assistant en cinq étapes.** Un
 * assistant est plus doux à la première saisie et insupportable à la
 * quinzième correction — or on corrige un tarif dix fois pour une création.
 * Les sections sont numérotées et la progression se lit en haut : on sait où
 * l'on en est sans avoir à cliquer « suivant ».
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
import { computed, ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'

import OwnerShell from '../Partials/OwnerShell.vue'
import AmenityIcon from '@/Components/AmenityIcon.vue'
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
        ? form.post('/proprietaire/logements')
        : form.post(`/proprietaire/logements/${props.listing.slug}`, { preserveScroll: true })
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
            <h1 class="fm__title">{{ creation ? 'Décrivez votre logement' : listing.title }}</h1>
            <p v-if="!creation" class="fm__consigne">{{ listing.consigne }}</p>
        </header>

        <!-- Une fiche vérifiée n'est pas figée, elle est **partiellement**
             figée : on dit lesquels des champs bougent encore, plutôt que de
             griser sans expliquer. -->
        <p v-if="!creation && !modifiable" class="fm__locked">
            Cette annonce a été vérifiée par Vayla. Vous pouvez encore changer le
            <strong>tarif</strong>, la <strong>description</strong> et les <strong>règles de séjour</strong>.
            Pour la capacité, les équipements ou les photos, écrivez-nous : nous revérifions avec vous.
        </p>

        <form class="fm" @submit.prevent="enregistrer">
            <!-- 1 ────────────────────────────────────────────────── -->
            <section class="fm__block">
                <h2 class="fm__h"><span class="fm__n">1</span> L'essentiel</h2>

                <div class="fm__field">
                    <label class="fm__label" for="title">Nom du logement</label>
                    <input id="title" v-model="form.title" type="text" class="fm__input"
                           :disabled="!modifiable" placeholder="Villa vue lagon, Ambatoloaka" maxlength="120">
                    <p class="fm__help">Le type et le lieu. C'est ce que le voyageur lit en premier.</p>
                    <p v-if="form.errors.title" class="fm__err">{{ form.errors.title }}</p>
                </div>

                <div class="fm__pair">
                    <div class="fm__field">
                        <label class="fm__label" for="destination_id">Destination</label>
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
            <section class="fm__block">
                <h2 class="fm__h"><span class="fm__n">2</span> Capacité</h2>
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
            <section class="fm__block">
                <h2 class="fm__h"><span class="fm__n">3</span> Tarif et séjour</h2>

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
            <section class="fm__block">
                <h2 class="fm__h"><span class="fm__n">4</span> Équipements</h2>
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
            <section v-if="!creation" class="fm__block">
                <h2 class="fm__h"><span class="fm__n">5</span> Photos</h2>
                <PhotoManager :slug="listing.slug" :photos="listing.photos" :modifiable="modifiable" />
            </section>
            <section v-else class="fm__block fm__block--muted">
                <h2 class="fm__h"><span class="fm__n">5</span> Photos</h2>
                <p class="fm__sub">
                    Enregistrez d'abord la fiche : vous pourrez ensuite ajouter vos photos.
                </p>
            </section>

            <!-- Barre d'action collée en bas : sur un formulaire de cette
                 longueur, un bouton qu'il faut aller chercher tout en bas est
                 un formulaire qu'on quitte sans enregistrer. -->
            <div class="fm__bar">
                <button type="submit" class="btn btn--ink btn--lg" :disabled="form.processing">
                    {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                </button>

                <button
                    v-if="!creation && listing.status === 'draft'"
                    type="button"
                    class="btn btn--terre btn--lg"
                    :disabled="envoiVerif"
                    @click="soumettre"
                >
                    {{ envoiVerif ? 'Envoi…' : 'Envoyer à Vayla pour vérification' }}
                </button>

                <Link href="/proprietaire/logements" class="fm__cancel">Retour</Link>
            </div>
        </form>
    </OwnerShell>
</template>

<style scoped>
.fm__head { margin-bottom: 1.5rem; }
.fm__eyebrow { margin: 0 0 .3rem; font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--terre-600); }
.fm__title { margin: 0; font-size: clamp(1.6rem, 4vw, 2.1rem); font-weight: 800; letter-spacing: -.045em; color: var(--ink); }
.fm__consigne { margin: .4rem 0 0; max-width: 56ch; font-size: .92rem; line-height: 1.55; color: var(--text-2); }

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

.fm__h { display: flex; align-items: center; gap: .6rem; margin: 0 0 1rem; font-size: 1.15rem; font-weight: 800; letter-spacing: -.03em; color: var(--ink); }
.fm__n {
    display: grid;
    place-items: center;
    width: 1.7rem;
    height: 1.7rem;
    border-radius: var(--r-pill);
    background: var(--terre-500);
    font-size: .82rem;
    font-weight: 800;
    color: var(--white);
}
.fm__sub { margin: -.5rem 0 1rem; max-width: 60ch; font-size: .88rem; line-height: 1.55; color: var(--text-2); }

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
    background: linear-gradient(to top, var(--off) 65%, transparent);
}
.fm__cancel { margin-left: auto; font-size: .86rem; font-weight: 600; color: var(--text-2); }
</style>
