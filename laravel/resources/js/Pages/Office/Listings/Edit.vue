<script setup>
/**
 * Le contenu d'une annonce, côté Vayla — et la saisie d'une annonce pour un
 * propriétaire qui la dicte au téléphone.
 *
 * **Tout est modifiable ici**, y compris ce que le propriétaire ne peut plus
 * toucher après vérification : c'est le rôle de Vayla de corriger une capacité
 * mal saisie ou de remplacer une photo floue. Le journal garde chaque champ
 * changé. Ce qui n'y est pas, c'est ce qui a ses propres gestes : le niveau de
 * confiance et le statut restent sur la fiche de modération.
 *
 * **Un formulaire long à sections, pas un assistant** : l'équipe corrige, elle
 * ne découvre pas. Les sections sont listées à gauche et se rejoignent d'un
 * clic ; la barre d'enregistrement est collée en bas et dit combien de
 * changements attendent — un bouton qu'il faut aller chercher est un
 * formulaire qu'on quitte sans enregistrer.
 *
 * **Les photos partent seules, dès qu'elles sont choisies** : un fichier de
 * dix mégaoctets qui repartirait à chaque correction de tarif serait une minute
 * d'attente. Elles ne sont donc disponibles qu'une fois l'annonce créée.
 */
import { computed, nextTick, onMounted, ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import AmenityIcon from '@/Components/AmenityIcon.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { useRangement } from '@/Composables/useRangement.js'
import { photoSrc } from '@/Support/photo.js'
import { preparerPhoto } from '@/Support/preparerPhoto.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    annonce: { type: Object, default: null },
    proprietaire: { type: Object, default: null },
    vocabulaire: { type: Object, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

const creation = computed(() => ! props.annonce)
// `a` fige les valeurs de départ du formulaire ; `an` suit les props — après
// l'ajout d'une photo, Inertia remplace l'annonce et la galerie doit suivre.
const a = props.annonce ?? {}
const an = computed(() => props.annonce ?? {})

const form = useForm({
    title: a.title ?? '',
    destination_id: a.destination_id ?? '',
    kind: a.kind ?? 'maison',
    summary: a.summary ?? '',
    description: a.description ?? '',
    guests: a.guests ?? 2,
    bedrooms: a.bedrooms ?? 1,
    beds: a.beds ?? 1,
    bathrooms: a.bathrooms ?? 1,
    surface: a.surface ?? '',
    price: a.price ?? '',
    min_nights: a.min_nights ?? 1,
    max_nights: a.max_nights ?? '',
    check_in_from: a.check_in_from ?? '14:00',
    check_out_before: a.check_out_before ?? '11:00',
    pets_allowed: !! a.pets_allowed,
    smoking_allowed: !! a.smoking_allowed,
    events_allowed: !! a.events_allowed,
    featured: !! a.featured,
    categories: [...(a.categories ?? [])],
    amenities: (a.amenities ?? []).map((x) => ({ id: x.id, highlight: !! x.highlight })),
})

// ── Les sections ────────────────────────────────────────────────────────
const SECTIONS = computed(() => [
    { id: 'essentiel', label: 'L’essentiel', champs: ['title', 'destination_id', 'kind', 'summary', 'description'] },
    { id: 'capacite', label: 'Capacité', champs: ['guests', 'bedrooms', 'beds', 'bathrooms', 'surface'] },
    { id: 'sejour', label: 'Tarif et séjour', champs: ['price', 'min_nights', 'max_nights', 'check_in_from', 'check_out_before'] },
    { id: 'avant', label: 'Mise en avant', champs: ['featured', 'categories'] },
    { id: 'equipements', label: 'Équipements', champs: ['amenities'] },
    ...(creation.value ? [] : [{ id: 'photos', label: 'Photos', champs: [] }]),
])

const enErreur = (section) => section.champs.some((c) => Object.keys(form.errors).some((e) => e === c || e.startsWith(`${c}.`)))

const aller = (id) => {
    const cible = document.getElementById(id)
    if (! cible) return
    const haut = cible.getBoundingClientRect().top + window.scrollY - 16
    window.scrollTo({ top: haut, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })
}

onMounted(() => {
    const section = new URLSearchParams(window.location.search).get('section')
    if (section) nextTick(() => aller(section))
})

// ── Les équipements ─────────────────────────────────────────────────────
const coche = (id) => form.amenities.some((x) => x.id === id)
const enAvant = (id) => form.amenities.find((x) => x.id === id)?.highlight ?? false

const basculer = (id) => {
    form.amenities = coche(id)
        ? form.amenities.filter((x) => x.id !== id)
        : [...form.amenities, { id, highlight: false }]
}

const mettreEnAvant = (id) => {
    form.amenities = form.amenities.map((x) => (x.id === id ? { ...x, highlight: ! x.highlight } : x))
}

const cochesDans = (groupe) => groupe.amenities.filter((x) => coche(x.id)).length
const enAvantTotal = computed(() => form.amenities.filter((x) => x.highlight).length)

// ── Les catégories ──────────────────────────────────────────────────────
const categorie = (id) => form.categories.includes(id)
const basculerCategorie = (id) => {
    form.categories = categorie(id) ? form.categories.filter((c) => c !== id) : [...form.categories, id]
}

// ── Enregistrer ─────────────────────────────────────────────────────────
const enregistrer = () => {
    const url = creation.value ? `/proprietaires/${props.proprietaire.id}/annonces` : `/annonces/${an.value.id}/modifier`

    form.transform((d) => ({
        ...d,
        surface: d.surface === '' ? null : d.surface,
        max_nights: d.max_nights === '' ? null : d.max_nights,
    })).post(url, {
        preserveScroll: true,
        onSuccess: () => { if (! creation.value) form.defaults() },
        onError: () => {
            const premiere = SECTIONS.value.find(enErreur)
            if (premiere) nextTick(() => aller(premiere.id))
        },
    })
}

// ── Les photos ──────────────────────────────────────────────────────────
const fichier = ref(null)
const envoi = ref(false)
/** Où en est l'envoi : `null`, « prépare », puis 0 à 100. */
const progression = ref(null)
const aRetirer = ref(null)

// La photo est réduite avant de partir (`preparerPhoto`) : un original de
// 12 Mo arrive en 2, et l'envoi se voit avancer.
const televerser = async (e) => {
    const f = e.target.files?.[0]
    if (! f) return
    envoi.value = true
    progression.value = 'prepare'
    const photo = await preparerPhoto(f)
    progression.value = 0
    router.post(`/annonces/${an.value.id}/photos`, { photo }, {
        forceFormData: true,
        preserveScroll: true,
        onProgress: (p) => { progression.value = Math.round(p?.percentage ?? 0) },
        onFinish: () => { envoi.value = false; progression.value = null; if (fichier.value) fichier.value.value = '' },
    })
}

const etatEnvoi = computed(() => {
    if (progression.value === 'prepare') return 'Préparation de la photo…'
    if (progression.value === null) return 'Ajouter une photo'
    return progression.value < 100 ? `Envoi : ${progression.value} %` : 'Recadrage et compression…'
})

// Ranger : on attrape une photo et les autres s'écartent — à la souris, ou au
// doigt par la poignée ; les flèches restent pour le clavier. La même
// mécanique que la galerie des destinations (`useRangement`).
const pellicule = ref(null)
const { ordre: photosEnOrdre, glisse, annonce, deplacer, saisir } = useRangement(() => an.value.photos ?? [], {
    conteneur: pellicule,
    enregistrer: (ids) => router.post(`/annonces/${an.value.id}/photos/ordre`, { ids }, { preserveScroll: true }),
})

const retirer = (p) => router.post(`/annonces/${an.value.id}/photos/${p.id}/retirer`, {}, {
    preserveScroll: true,
    onFinish: () => { aRetirer.value = null },
})

const retour = computed(() => (creation.value
    ? { href: `/proprietaires/${props.proprietaire.id}`, label: props.proprietaire.name }
    : { href: `/annonces/${an.value.id}`, label: 'Modération de l’annonce' }))

const changements = computed(() => (form.isDirty ? 'Des modifications ne sont pas enregistrées.' : 'Tout est enregistré.'))
</script>

<template>
    <Head :title="creation ? 'Nouvelle annonce — Back-office' : `${an.title} — Contenu — Back-office`" />

    <div ref="racine" class="ed">
        <OfficeHead
            :back="retour"
            :kicker="creation ? `Pour ${proprietaire?.name}` : `Contenu · ${an.statusLabel}`"
            :titre="creation ? 'Nouvelle annonce' : an.title"
        >
            <template #lede>
                <template v-if="creation">Elle naît en brouillon, au niveau 1 : la saisir n'est pas la vérifier. Les photos s'ajoutent juste après.</template>
                <template v-else>Tout le contenu se corrige ici, même après vérification. Chaque champ modifié est écrit au journal.</template>
            </template>
            <template #actions>
                <a v-if="!creation && an.status === 'published'" :href="an.publicUrl" target="_blank" rel="noopener" class="btn btn--sm btn--outline">
                    <OfficeIcon name="externe" /> Voir sur le site
                </a>
            </template>
        </OfficeHead>

        <div class="ed__grille">
            <!-- Le sommaire : on rejoint une section d'un clic, et celles en
                 erreur le disent. -->
            <nav class="ed__sommaire" aria-label="Sections de la fiche" data-reveal>
                <button v-for="s in SECTIONS" :key="s.id" type="button" class="ed__lien" :class="{ 'is-ko': enErreur(s) }" @click="aller(s.id)">
                    {{ s.label }}
                    <span v-if="enErreur(s)" class="ed__ko" aria-label="à corriger">!</span>
                </button>
            </nav>

            <form class="ed__form" novalidate @submit.prevent="enregistrer">
                <!-- ── L'essentiel ─────────────────────────────────── -->
                <section id="essentiel" class="of-card ed__sec" data-reveal>
                    <header class="of-card__h"><h2 class="of-card__t">L'essentiel</h2></header>
                    <div class="of-card__b ed__champs">
                        <div class="of-field ed__large">
                            <label class="of-label" for="title">Nom du logement</label>
                            <input id="title" v-model="form.title" class="of-input" maxlength="120" required>
                            <p class="of-help">Le type et le lieu : « Villa vue lagon, Ambatoloaka ».</p>
                            <p v-if="form.errors.title" class="of-err" role="alert">{{ form.errors.title }}</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="destination">Destination</label>
                            <select id="destination" v-model="form.destination_id" class="of-input" required>
                                <option value="" disabled>Choisir…</option>
                                <option v-for="d in vocabulaire.destinations" :key="d.id" :value="d.id">{{ d.label }}</option>
                            </select>
                            <p v-if="form.errors.destination_id" class="of-err" role="alert">{{ form.errors.destination_id }}</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="kind">Type</label>
                            <select id="kind" v-model="form.kind" class="of-input">
                                <option v-for="k in vocabulaire.kinds" :key="k.value" :value="k.value">{{ k.label }}</option>
                            </select>
                        </div>
                        <div class="of-field ed__large">
                            <label class="of-label" for="summary">Accroche <span class="ed__opt">— une phrase, sous le titre</span></label>
                            <input id="summary" v-model="form.summary" class="of-input" maxlength="200">
                            <p class="ed__compte of-num">{{ form.summary.length }} / 200</p>
                            <p v-if="form.errors.summary" class="of-err" role="alert">{{ form.errors.summary }}</p>
                        </div>
                        <div class="of-field ed__large">
                            <label class="of-label" for="description">Description</label>
                            <textarea id="description" v-model="form.description" class="of-input ed__texte" rows="9" maxlength="4000" />
                            <p class="ed__compte of-num">{{ form.description.length }} / 4000 <span v-if="form.description.length < 120"> — cent vingt au moins pour envoyer la fiche</span></p>
                            <p v-if="form.errors.description" class="of-err" role="alert">{{ form.errors.description }}</p>
                        </div>
                    </div>
                </section>

                <!-- ── Capacité ────────────────────────────────────── -->
                <section id="capacite" class="of-card ed__sec" data-reveal>
                    <header class="of-card__h"><h2 class="of-card__t">Capacité</h2></header>
                    <div class="of-card__b ed__champs ed__champs--4">
                        <div v-for="c in [
                            ['guests', 'Personnes max.', 1, 30],
                            ['bedrooms', 'Chambres', 0, 20],
                            ['beds', 'Couchages', 1, 40],
                            ['bathrooms', 'Salles d’eau', 1, 20],
                        ]" :key="c[0]" class="of-field">
                            <label class="of-label" :for="c[0]">{{ c[1] }}</label>
                            <input :id="c[0]" v-model.number="form[c[0]]" class="of-input of-num" type="number" inputmode="numeric" :min="c[2]" :max="c[3]">
                            <p v-if="form.errors[c[0]]" class="of-err" role="alert">{{ form.errors[c[0]] }}</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="surface">Surface <span class="ed__opt">— m², facultatif</span></label>
                            <input id="surface" v-model.number="form.surface" class="of-input of-num" type="number" inputmode="numeric" min="8" max="2000">
                            <p v-if="form.errors.surface" class="of-err" role="alert">{{ form.errors.surface }}</p>
                        </div>
                    </div>
                </section>

                <!-- ── Tarif et séjour ─────────────────────────────── -->
                <section id="sejour" class="of-card ed__sec" data-reveal>
                    <header class="of-card__h"><h2 class="of-card__t">Tarif et séjour</h2></header>
                    <div class="of-card__b ed__champs ed__champs--4">
                        <div class="of-field ed__demi">
                            <label class="of-label" for="price">Tarif d'une nuit</label>
                            <div class="ed__unite">
                                <input id="price" v-model.number="form.price" class="of-input of-num" type="number" inputmode="numeric" min="5000" step="1000">
                                <span>Ar</span>
                            </div>
                            <p class="of-help">Les réservations déjà faites gardent leur prix : il est figé à la demande.</p>
                            <p v-if="form.errors.price" class="of-err" role="alert">{{ form.errors.price }}</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="min_nights">Nuits minimum</label>
                            <input id="min_nights" v-model.number="form.min_nights" class="of-input of-num" type="number" min="1" max="90">
                            <p v-if="form.errors.min_nights" class="of-err" role="alert">{{ form.errors.min_nights }}</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="max_nights">Nuits maximum <span class="ed__opt">— facultatif</span></label>
                            <input id="max_nights" v-model.number="form.max_nights" class="of-input of-num" type="number" min="1" max="365">
                            <p v-if="form.errors.max_nights" class="of-err" role="alert">{{ form.errors.max_nights }}</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="in">Arrivée à partir de</label>
                            <input id="in" v-model="form.check_in_from" class="of-input of-num" type="time">
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="out">Départ avant</label>
                            <input id="out" v-model="form.check_out_before" class="of-input of-num" type="time">
                        </div>
                        <fieldset class="ed__regles ed__large">
                            <legend class="of-label">Règles du séjour</legend>
                            <label class="ed__case"><input v-model="form.pets_allowed" type="checkbox"> Animaux acceptés</label>
                            <label class="ed__case"><input v-model="form.smoking_allowed" type="checkbox"> Fumeurs acceptés</label>
                            <label class="ed__case"><input v-model="form.events_allowed" type="checkbox"> Fêtes et événements</label>
                        </fieldset>
                    </div>
                </section>

                <!-- ── Mise en avant ───────────────────────────────── -->
                <section id="avant" class="of-card ed__sec" data-reveal>
                    <header class="of-card__h"><h2 class="of-card__t">Mise en avant</h2></header>
                    <div class="of-card__b">
                        <label class="ed__case ed__case--fort">
                            <input v-model="form.featured" type="checkbox">
                            Mettre en avant à l'accueil
                        </label>
                        <p class="of-help ed__aide">Réservé à Vayla : le propriétaire ne le voit ni ne le règle.</p>

                        <p class="of-label ed__cat-t">Catégories du rail « Les envies du moment »</p>
                        <div class="ed__cats">
                            <button
                                v-for="c in vocabulaire.categories"
                                :key="c.id"
                                type="button"
                                class="ed__cat"
                                :class="{ 'is-on': categorie(c.id) }"
                                :aria-pressed="categorie(c.id)"
                                @click="basculerCategorie(c.id)"
                            >
                                <OfficeIcon v-if="categorie(c.id)" name="coche" />
                                {{ c.label }}
                                <span v-if="c.sponsored" class="ed__sponso">sponsorisée</span>
                            </button>
                        </div>
                        <p class="of-help">« Séjour confirmé » n'est pas ici : elle se déduit du niveau 4.</p>
                    </div>
                </section>

                <!-- ── Équipements ─────────────────────────────────── -->
                <section id="equipements" class="of-card ed__sec" data-reveal>
                    <header class="of-card__h">
                        <h2 class="of-card__t">Équipements</h2>
                        <span class="ed__total of-num">{{ form.amenities.length }} coché{{ form.amenities.length > 1 ? 's' : '' }} · {{ enAvantTotal }} en avant</span>
                    </header>
                    <div class="of-card__b ed__groupes">
                        <p class="of-help">Les équipements « en avant » deviennent les arguments de la carte, dans l'ordre des rubriques. Trois suffisent.</p>
                        <details v-for="g in vocabulaire.amenityGroups" :key="g.key" class="ed__groupe" :open="cochesDans(g) > 0">
                            <summary class="ed__groupe-t">
                                {{ g.label }}
                                <span class="ed__groupe-n of-num">{{ cochesDans(g) }} / {{ g.amenities.length }}</span>
                            </summary>
                            <ul class="ed__equips">
                                <li v-for="x in g.amenities" :key="x.id" class="ed__equip" :class="{ 'is-on': coche(x.id) }">
                                    <label class="ed__equip-c">
                                        <input type="checkbox" :checked="coche(x.id)" @change="basculer(x.id)">
                                        <AmenityIcon :name="x.icon" class="ed__equip-i" />
                                        {{ x.label }}
                                    </label>
                                    <button
                                        v-if="coche(x.id)"
                                        type="button"
                                        class="ed__etoile"
                                        :class="{ 'is-on': enAvant(x.id) }"
                                        :aria-pressed="enAvant(x.id)"
                                        @click="mettreEnAvant(x.id)"
                                    >
                                        {{ enAvant(x.id) ? 'En avant' : 'Mettre en avant' }}
                                    </button>
                                </li>
                            </ul>
                        </details>
                    </div>
                </section>

                <!-- ── Photos ──────────────────────────────────────── -->
                <section v-if="!creation" id="photos" class="of-card ed__sec" data-reveal>
                    <header class="of-card__h">
                        <h2 class="of-card__t">Photos</h2>
                        <span class="ed__total of-num">{{ an.photos.length }} · la première est la couverture · glissez pour ranger</span>
                    </header>
                    <div class="of-card__b">
                        <label class="ed__depot" :class="{ 'is-busy': envoi }">
                            <input ref="fichier" class="sr-only" type="file" accept="image/jpeg,image/png,image/webp" :disabled="envoi" @change="televerser">
                            <OfficeIcon name="photo" />
                            <span>{{ etatEnvoi }}</span>
                            <span class="ed__depot-s">JPEG, PNG ou WebP · 1 200 px de large au moins · recadrée en 4/3</span>
                        </label>

                        <ol v-if="photosEnOrdre.length" ref="pellicule" class="ed__photos">
                            <li
                                v-for="(p, i) in photosEnOrdre"
                                :key="p.id"
                                class="ed__photo"
                                :class="{ 'is-glisse': glisse === p.id }"
                                data-flip
                                :data-id="p.id"
                                @pointerdown="saisir(p, $event)"
                            >
                                <img :src="photoSrc(p, 800)" :alt="p.caption ?? ''" draggable="false" loading="lazy" decoding="async">
                                <span class="ed__poignee" data-poignee title="Glisser pour ranger" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.6" /><circle cx="15" cy="6" r="1.6" /><circle cx="9" cy="12" r="1.6" /><circle cx="15" cy="12" r="1.6" /><circle cx="9" cy="18" r="1.6" /><circle cx="15" cy="18" r="1.6" /></svg>
                                </span>
                                <span v-if="i === 0" class="ed__couv">Couverture</span>
                                <div class="ed__photo-g">
                                    <button type="button" class="ed__pb" :disabled="i === 0" aria-label="Avancer" @click="deplacer(i, i - 1)"><OfficeIcon name="haut" /></button>
                                    <button type="button" class="ed__pb" :disabled="i === photosEnOrdre.length - 1" aria-label="Reculer" @click="deplacer(i, i + 1)"><OfficeIcon name="bas" /></button>
                                    <button v-if="aRetirer !== p.id" type="button" class="ed__pb ed__pb--txt" @click="aRetirer = p.id">Retirer</button>
                                    <button v-else type="button" class="ed__pb ed__pb--txt ed__pb--ko" @click="retirer(p)">Confirmer</button>
                                </div>
                            </li>
                        </ol>
                        <p v-else class="of-vide">Aucune photo. Il en faut trois au moins pour que le propriétaire puisse envoyer sa fiche.</p>
                        <p class="sr-only" aria-live="polite">{{ annonce }}</p>
                    </div>
                </section>

                <!-- ── La barre ────────────────────────────────────── -->
                <div class="ed__barre" :class="{ 'is-dirty': form.isDirty || creation }">
                    <p class="ed__etat" role="status">{{ creation ? 'L’annonce n’existe pas encore.' : changements }}</p>
                    <button v-if="form.isDirty && !creation" type="button" class="btn btn--sm btn--ghost" @click="form.reset()">Annuler</button>
                    <button type="submit" class="btn btn--terre" :disabled="form.processing || (!creation && !form.isDirty)">
                        {{ form.processing ? 'Enregistrement…' : (creation ? 'Créer l’annonce' : 'Enregistrer') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.ed__grille {
    display: grid;
    grid-template-columns: 11rem minmax(0, 1fr);
    align-items: start;
    gap: 1.25rem;
}

.ed__sommaire { position: sticky; top: 1.25rem; display: grid; gap: .15rem; }
.ed__lien {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 2.5rem;
    padding: .3rem .75rem;
    border: 0;
    border-radius: var(--r-xs);
    background: none;
    font: inherit;
    font-size: .86rem;
    font-weight: 600;
    text-align: left;
    color: var(--text-2);
    cursor: pointer;
}
.ed__lien:hover { background: var(--off-2); color: var(--ink); }
.ed__lien:focus-visible { outline: 2px solid var(--terre-500); outline-offset: -2px; }
.ed__lien.is-ko { color: var(--terre-700); }
.ed__ko {
    display: grid;
    place-items: center;
    width: 1.2rem;
    height: 1.2rem;
    border: 1.5px solid var(--terre-500);
    border-radius: 50%;
    font-size: .7rem;
    font-weight: 800;
}

.ed__form { display: grid; gap: 1rem; min-width: 0; }
.ed__sec { scroll-margin-top: 1rem; }

.ed__champs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
.ed__champs--4 { grid-template-columns: repeat(auto-fill, minmax(9rem, 1fr)); }
.ed__large { grid-column: 1 / -1; }
.ed__demi { grid-column: span 2; }
.ed__opt { font-weight: 500; color: var(--text-3); }
.ed__texte { min-height: 12rem; }
.ed__compte { margin: 0; font-size: .74rem; color: var(--text-3); }

.ed__unite { position: relative; }
.ed__unite span { position: absolute; right: .85rem; top: 50%; font-size: .86rem; font-weight: 700; color: var(--text-3); transform: translateY(-50%); }
.ed__unite .of-input { padding-right: 2.6rem; }

.ed__regles { display: flex; flex-wrap: wrap; gap: .5rem 1.25rem; margin: 0; padding: 0; border: 0; }
.ed__regles legend { width: 100%; margin-bottom: .35rem; }

.ed__case { display: inline-flex; align-items: center; gap: .55rem; min-height: 2.5rem; font-size: .9rem; color: var(--ink); cursor: pointer; }
.ed__case input { width: 1.15rem; height: 1.15rem; accent-color: var(--ink); }
.ed__case--fort { font-weight: 700; }
.ed__aide { margin: 0 0 1.1rem; }

.ed__cat-t { display: block; margin: 0 0 .5rem; }
.ed__cats { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .5rem; }
.ed__cat {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    min-height: 2.5rem;
    padding: .35rem .9rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font: inherit;
    font-size: .84rem;
    font-weight: 600;
    color: var(--text-2);
    cursor: pointer;
}
.ed__cat:hover { border-color: var(--ink); color: var(--ink); }
.ed__cat:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }
.ed__cat.is-on { border-color: var(--ink); background: var(--ink); color: var(--white); }
.ed__cat .oi { width: .95rem; height: .95rem; stroke-width: 2.2; }
.ed__sponso { font-size: .66rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; opacity: .7; }

.ed__total { font-size: .78rem; font-weight: 700; color: var(--text-3); }
.ed__groupes { display: grid; gap: .5rem; }
.ed__groupe { border: 1px solid var(--line); border-radius: var(--r-sm); }
.ed__groupe-t {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 2.9rem;
    padding: .5rem .9rem;
    font-size: .9rem;
    font-weight: 700;
    color: var(--ink);
    cursor: pointer;
}
.ed__groupe-n { font-size: .78rem; color: var(--text-3); }
.ed__groupe[open] .ed__groupe-t { border-bottom: 1px solid var(--line); }
.ed__equips {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr));
    gap: .15rem .75rem;
    margin: 0;
    padding: .5rem .6rem .7rem;
    list-style: none;
}
.ed__equip { display: flex; align-items: center; justify-content: space-between; gap: .4rem; border-radius: var(--r-xs); }
.ed__equip.is-on { background: var(--off); }
.ed__equip-c { display: flex; align-items: center; gap: .5rem; min-height: 2.5rem; padding: 0 .4rem; font-size: .86rem; color: var(--text-2); cursor: pointer; }
.ed__equip.is-on .ed__equip-c { color: var(--ink); font-weight: 600; }
.ed__equip-c input { width: 1.05rem; height: 1.05rem; accent-color: var(--ink); }
.ed__equip-i { width: 1.1rem; height: 1.1rem; color: var(--text-3); }

.ed__etoile {
    flex: none;
    min-height: 2rem;
    margin-right: .3rem;
    padding: 0 .6rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font: inherit;
    font-size: .7rem;
    font-weight: 700;
    color: var(--text-2);
    cursor: pointer;
}
.ed__etoile.is-on { border-color: var(--terre-500); background: var(--terre-050); color: var(--terre-700); }

.ed__depot {
    display: grid;
    justify-items: center;
    gap: .25rem;
    padding: 1.25rem;
    border: 1.5px dashed var(--line-2);
    border-radius: var(--r-sm);
    background: var(--off);
    font-size: .9rem;
    font-weight: 700;
    color: var(--ink);
    text-align: center;
    cursor: pointer;
}
.ed__depot:hover { border-color: var(--ink); }
.ed__depot:focus-within { outline: 2px solid var(--terre-500); outline-offset: 2px; }
.ed__depot.is-busy { cursor: progress; opacity: .8; }
.ed__depot .oi { width: 1.5rem; height: 1.5rem; }
.ed__depot-s { font-size: .74rem; font-weight: 500; color: var(--text-3); }

.ed__photos {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr));
    gap: .6rem;
    margin: 1rem 0 0;
    padding: 0;
    list-style: none;
}
.ed__photo { position: relative; overflow: hidden; border: 1px solid var(--line); border-radius: var(--r-sm); background: var(--white); cursor: grab; user-select: none; touch-action: manipulation; transition: border-color .15s var(--ease); }
/* Celle qu'on tient suit le pointeur (`is-fantome`) ; à sa place reste un
   emplacement en pointillé de terre — c'est là qu'elle tombera. */
.ed__photo.is-glisse { border: 1.5px dashed var(--terre-500); background: var(--terre-050); }
.ed__photo.is-glisse > * { visibility: hidden; }
.ed__photo.is-fantome { border-color: var(--ink); cursor: grabbing; }
.ed__photo img { display: block; width: 100%; aspect-ratio: 4 / 3; object-fit: cover; background: var(--off-2); pointer-events: none; -webkit-user-drag: none; }
.ed__poignee {
    position: absolute;
    right: .4rem;
    top: .4rem;
    display: grid;
    place-items: center;
    width: 2rem;
    height: 2rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-xs);
    background: rgba(255, 255, 255, .94);
    color: var(--ink);
    touch-action: none;
}
.ed__poignee svg { width: 1rem; height: 1rem; }
.ed__couv {
    position: absolute;
    left: .4rem;
    top: .4rem;
    padding: .15rem .5rem;
    border-radius: var(--r-pill);
    background: rgba(26, 21, 18, .8);
    font-size: .66rem;
    font-weight: 700;
    color: var(--white);
}
.ed__photo-g { display: flex; gap: .25rem; padding: .35rem; }
.ed__pb {
    display: grid;
    place-items: center;
    min-width: 2.4rem;
    min-height: 2.4rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-xs);
    background: var(--white);
    color: var(--ink);
    cursor: pointer;
}
.ed__pb .oi { width: 1rem; height: 1rem; }
.ed__pb:hover:not(:disabled) { border-color: var(--ink); }
.ed__pb:disabled { background: var(--off-2); color: var(--ink-3); cursor: not-allowed; }
.ed__pb--txt { flex: 1; padding: 0 .5rem; font: inherit; font-size: .76rem; font-weight: 700; }
.ed__pb--ko { border-color: var(--terre-500); background: var(--terre-050); color: var(--terre-700); }

/* La barre d'enregistrement, collée en bas : elle dit s'il reste quelque chose
   à enregistrer, et le bouton plein ne s'allume que dans ce cas. */
.ed__barre {
    position: sticky;
    bottom: .75rem;
    z-index: 5;
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .6rem .6rem .6rem 1rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: rgba(255, 255, 255, .96);
    box-shadow: var(--sh-2);
    backdrop-filter: blur(8px);
}
.ed__barre.is-dirty { border-color: var(--terre-300); }
.ed__etat { flex: 1; margin: 0; font-size: .84rem; color: var(--text-2); }
.ed__barre.is-dirty .ed__etat { font-weight: 700; color: var(--terre-700); }

@media (max-width: 900px) {
    .ed__grille { grid-template-columns: minmax(0, 1fr); }
    .ed__sommaire { position: static; display: flex; overflow-x: auto; scrollbar-width: none; }
    .ed__lien { flex: none; }
    .ed__champs { grid-template-columns: minmax(0, 1fr); }
    .ed__demi { grid-column: auto; }
}
</style>
