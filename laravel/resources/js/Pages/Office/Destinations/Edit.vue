<script setup>
/**
 * Une destination.
 *
 * **« Y aller » est de la donnée, pas de la prose** : sept champs typés, et le
 * formulaire les écrit comme la page les lira — « 1 h de vol », « RN6 »,
 * « 3 à 4 h ». Une durée reste une fourchette : les routes malgaches n'ont pas
 * la précision qu'un entier laisserait croire.
 *
 * **Les photos viennent de la photothèque des lieux ou d'un téléversement,
 * jamais d'une image générée** : une destination est un lieu réel. Chaque
 * photo porte son auteur et sa licence — ce sont eux qui s'affichent dans les
 * crédits du site. Cliquer une photo la montre en grand ; la glisser la range.
 *
 * **L'adresse de la page ne change jamais** : elle naît du nom à la création,
 * puis elle est figée. Renommer une destination garde ses liens.
 */
import { computed, ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import PhotoDepot from '@/Components/Office/PhotoDepot.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { useRangement } from '@/Composables/useRangement.js'
import { photoSrc } from '@/Support/photo.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    destination: { type: Object, default: null },
    zones: { type: Array, required: true },
    scenes: { type: Array, required: true },
    photos: { type: Array, default: () => [] },
    galerie: { type: Array, default: () => [] },
    licences: { type: Array, default: () => [] },
})

const racine = ref(null)
useOfficeMotion(racine)

const creation = computed(() => ! props.destination)
const d = props.destination ?? {}

const form = useForm({
    name: d.name ?? '',
    region: d.region ?? '',
    tagline: d.tagline ?? '',
    climate_zone: d.climate_zone ?? 'hautes-terres',
    scene: d.scene ?? 'lagoon',
    featured: !! d.featured,
    airport_code: d.airport_code ?? '',
    airport_name: d.airport_name ?? '',
    flight_from_tana: d.flight_from_tana ?? '',
    road_route: d.road_route ?? '',
    road_km: d.road_km ?? '',
    road_hours: d.road_hours ?? '',
    road_note: d.road_note ?? '',
})

const vide = (v) => (v === '' ? null : v)

const enregistrer = () => form
    .transform((x) => Object.fromEntries(Object.entries(x).map(([k, v]) => [k, vide(v)])))
    .post(creation.value ? '/destinations' : `/destinations/${d.id}`, {
        preserveScroll: true,
        onSuccess: () => form.defaults(),
    })



const suppression = ref(false)
const supprimer = () => router.post(`/destinations/${d.id}/supprimer`)

// ── Téléverser une photo ────────────────────────────────────────────────
// Elle part seule et devient aussitôt la photo de la destination : c'est ce
// qu'on vient faire. Choisir dans la photothèque, lui, attend « Enregistrer ».
// `PhotoDepot` la prépare avant l'envoi (réduite à ce que le serveur garde)
// et montre l'envoi avancer.
const ajout = useForm({ photo: null, caption: '', author: '', licence: 'vayla', source_url: '', declaration: false })
const ajoutOuvert = ref(false)

const televerser = () => ajout.post(`/destinations/${d.id}/photos`, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
        ajout.reset()
        ajoutOuvert.value = false
    },
})

const pret = computed(() => ajout.photo && ajout.caption.trim() && ajout.author.trim() && ajout.declaration)

const aRetirer = ref(null)
const retirer = (p) => router.post(`/phototheque/${p.id}/retirer`, {}, { preserveScroll: true, onFinish: () => { aRetirer.value = null } })

// ── La galerie ──────────────────────────────────────────────────────────
// Chaque geste part aussitôt : ranger vingt photos ne doit pas finir par un
// « Enregistrer » oublié. La première est la couverture — atlas et en-tête.
const liste = ref(null)
const { ordre, glisse, annonce, deplacer, saisir } = useRangement(() => props.galerie, {
    conteneur: liste,
    enregistrer: (ids) => router.post(`/destinations/${d.id}/photos/ordre`, { ids }, { preserveScroll: true }),
})

// ── Le gros plan ────────────────────────────────────────────────────────
// Une vignette de 7 rem ne dit pas si la photo est nette, ni ce qu'elle
// cadre : cliquer une photo — de la galerie comme de la photothèque — la
// montre en grand, avec ses crédits et ce qu'on peut en faire. Les gestes
// vivent là, pas sur chaque vignette : la grille reste une grille de photos,
// qu'on range à la main. Sans choix, c'est la couverture qui est montrée.
const vue = ref(null)

const scene = computed(() => {
    if (vue.value?.source === 'phototheque') {
        const p = props.photos.find((x) => x.id === vue.value.id)
        if (p) return { ...p, rang: null }
    }
    const i = vue.value?.source === 'galerie' ? ordre.value.findIndex((x) => x.id === vue.value.id) : -1
    const rang = i === -1 ? 0 : i
    return ordre.value[rang] ? { ...ordre.value[rang], rang } : null
})

const aDetacher = ref(null)

const montrer = (source, p) => {
    vue.value = { source, id: p.id }
    aDetacher.value = null
    aRetirer.value = null
}
const estVue = (source, p) => scene.value?.id === p.id && (scene.value.rang !== null) === (source === 'galerie')

const detacher = (p) => router.post(`/destinations/${d.id}/photos/${p.id}/retirer`, {}, { preserveScroll: true, onFinish: () => { aDetacher.value = null } })
const ajouterDeLaPhototheque = (p) => router.post(`/destinations/${d.id}/photos/ajouter`, { photo_id: p.id }, {
    preserveScroll: true,
    onSuccess: () => montrer('galerie', p),
})
</script>

<template>
    <Head :title="creation ? 'Nouvelle destination — Back-office' : `${d.name} — Back-office`" />

    <div ref="racine">
        <OfficeHead :back="{ href: '/destinations', label: 'Destinations' }" kicker="Contenu" :titre="creation ? 'Nouvelle destination' : d.name">
            <template #lede>
                <template v-if="creation">L'adresse de sa page naîtra du nom, et ne changera plus.</template>
                <template v-else>Page publique : <span class="of-num">/destinations/{{ d.slug }}</span> — elle ne change pas si vous renommez.</template>
            </template>
            <template #actions>
                <a v-if="!creation" :href="d.publicUrl" target="_blank" rel="noopener" class="btn btn--sm btn--outline"><OfficeIcon name="externe" /> Voir la page</a>
            </template>
        </OfficeHead>

        <form class="de" novalidate @submit.prevent="enregistrer">
            <div class="de__col">
                <section class="of-card" data-reveal>
                    <header class="of-card__h"><h2 class="of-card__t">Le lieu</h2></header>
                    <div class="of-card__b de__champs">
                        <div class="of-field">
                            <label class="of-label" for="name">Nom</label>
                            <input id="name" v-model="form.name" class="of-input" maxlength="60" required>
                            <p v-if="form.errors.name" class="of-err" role="alert">{{ form.errors.name }}</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="region">Région</label>
                            <input id="region" v-model="form.region" class="of-input" maxlength="60" placeholder="Diana, Analamanga…" required>
                            <p v-if="form.errors.region" class="of-err" role="alert">{{ form.errors.region }}</p>
                        </div>
                        <div class="of-field de__large">
                            <label class="of-label" for="tagline">Accroche</label>
                            <input id="tagline" v-model="form.tagline" class="of-input" maxlength="120" required>
                            <p class="of-help">Une ligne qui dit le lieu, pas un slogan : « L'île aux baleines, au large de la côte est ».</p>
                            <p v-if="form.errors.tagline" class="of-err" role="alert">{{ form.errors.tagline }}</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="zone">Façade climatique</label>
                            <select id="zone" v-model="form.climate_zone" class="of-input">
                                <option v-for="z in zones" :key="z.value" :value="z.value">{{ z.label }}</option>
                            </select>
                            <p class="of-help">Elle décide du calendrier des saisons affiché sur la page — cyclones, baleines, fraîcheur.</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="scene">Illustration de repli</label>
                            <select id="scene" v-model="form.scene" class="of-input">
                                <option v-for="s in scenes" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                            <p class="of-help">Dessinée, elle ne s'affiche que faute de photo.</p>
                        </div>
                        <label class="de__case de__large">
                            <input v-model="form.featured" type="checkbox">
                            À la une de l'atlas
                        </label>
                    </div>
                </section>

                <section class="of-card" data-reveal>
                    <header class="of-card__h"><h2 class="of-card__t">Y aller depuis Tana</h2></header>
                    <div class="of-card__b de__champs">
                        <div class="of-field">
                            <label class="of-label" for="ac">Code aéroport</label>
                            <input id="ac" v-model="form.airport_code" class="of-input of-num de__code" maxlength="3" placeholder="NOS">
                            <p v-if="form.errors.airport_code" class="of-err" role="alert">{{ form.errors.airport_code }}</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="an">Aéroport</label>
                            <input id="an" v-model="form.airport_name" class="of-input" maxlength="60" placeholder="Fascene">
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="fl">Vol depuis Tana</label>
                            <input id="fl" v-model="form.flight_from_tana" class="of-input" maxlength="30" placeholder="1 h 20">
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="rr">Route</label>
                            <input id="rr" v-model="form.road_route" class="of-input" maxlength="120" placeholder="RN6, puis bac">
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="rk">Kilomètres</label>
                            <input id="rk" v-model.number="form.road_km" class="of-input of-num" type="number" min="1" max="3000">
                            <p v-if="form.errors.road_km" class="of-err" role="alert">{{ form.errors.road_km }}</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="rh">Durée par la route</label>
                            <input id="rh" v-model="form.road_hours" class="of-input" maxlength="60" placeholder="3 à 4 h">
                            <p class="of-help">Une fourchette, pas un chiffre : c'est la vérité des routes.</p>
                        </div>
                        <div class="of-field de__large">
                            <label class="of-label" for="rn">Note</label>
                            <input id="rn" v-model="form.road_note" class="of-input" maxlength="200" placeholder="Piste impraticable en saison des pluies.">
                        </div>
                        <p class="of-help de__large">Tout est facultatif : une case vide ne s'affiche pas. La page rappelle toujours que les durées sont indicatives.</p>
                    </div>
                </section>
            </div>

            <div class="de__col">
                <section class="of-card" data-reveal>
                    <header class="of-card__h">
                        <h2 class="of-card__t">Photos <span class="de__n of-num">{{ ordre.length }}</span></h2>
                    </header>
                    <div class="of-card__b">
                        <template v-if="creation">
                            <p class="of-help">Créez la destination d'abord : sa galerie se compose ensuite.</p>
                        </template>

                        <template v-else>
                            <!-- Le gros plan : la photo cliquée, en grand, avec ses
                                 crédits et tout ce qu'on peut en faire. -->
                            <figure v-if="scene" class="sc">
                                <div class="sc__cadre">
                                    <Transition name="sc">
                                        <img :key="scene.id" :src="photoSrc(scene, 1600)" :alt="scene.caption ?? ''" decoding="async">
                                    </Transition>
                                    <span v-if="scene.rang === 0" class="sc__etiquette">Couverture</span>
                                    <span v-else-if="scene.rang !== null" class="sc__etiquette sc__etiquette--clair">Photo {{ scene.rang + 1 }} sur {{ ordre.length }}</span>
                                    <span v-else class="sc__etiquette sc__etiquette--clair">Photothèque — pas encore dans la galerie</span>
                                </div>

                                <figcaption class="sc__legende">
                                    <strong>{{ scene.caption }}</strong>
                                    <span>{{ scene.author }} · {{ scene.licence }} · <Link :href="`/phototheque?photo=${scene.id}`" class="of-card__lien">Corriger le crédit dans la photothèque</Link></span>
                                </figcaption>

                                <div v-if="scene.rang !== null" class="sc__gestes">
                                    <button type="button" class="btn btn--sm btn--outline" :disabled="scene.rang === 0" @click="deplacer(scene.rang, scene.rang - 1)"><OfficeIcon name="retour" /> Avancer</button>
                                    <button type="button" class="btn btn--sm btn--outline" :disabled="scene.rang === ordre.length - 1" @click="deplacer(scene.rang, scene.rang + 1)">Reculer <OfficeIcon name="avant" /></button>
                                    <button v-if="scene.rang > 0" type="button" class="btn btn--sm btn--outline" @click="deplacer(scene.rang, 0)">Mettre en couverture</button>
                                    <span class="sc__pousse" />
                                    <button v-if="aDetacher !== scene.id" type="button" class="btn btn--sm btn--ghost" @click="aDetacher = scene.id">Retirer</button>
                                    <button v-else type="button" class="btn btn--sm sc__ko" @click="detacher(scene)">{{ scene.televersee ? 'Confirmer la suppression' : 'Confirmer le retrait' }}</button>
                                </div>
                                <div v-else class="sc__gestes">
                                    <button type="button" class="btn btn--sm btn--ink" @click="ajouterDeLaPhototheque(scene)"><OfficeIcon name="plus" /> Ajouter à la galerie</button>
                                    <span class="sc__pousse" />
                                    <template v-if="scene.televersee && !scene.utilisee">
                                        <button v-if="aRetirer !== scene.id" type="button" class="btn btn--sm btn--ghost" @click="aRetirer = scene.id">Supprimer</button>
                                        <button v-else type="button" class="btn btn--sm sc__ko" @click="retirer(scene)">Confirmer la suppression</button>
                                    </template>
                                </div>

                                <p v-if="scene.rang !== null && aDetacher === scene.id" class="sc__avert">
                                    {{ scene.televersee ? 'Retirée de la galerie, puis supprimée avec ses fichiers si aucune autre destination ne la montre.' : 'Retirée de la galerie ; la photographie reste dans la photothèque.' }}
                                </p>
                                <p v-else-if="scene.rang === null && aRetirer === scene.id" class="sc__avert">Supprimée pour de bon, avec ses fichiers : aucune destination ne la montre.</p>
                                <p v-else-if="scene.rang === null && scene.utilisee" class="of-help sc__note">Déjà dans la galerie de {{ scene.utilisee }} : une même photographie peut illustrer deux lieux, avec un seul crédit.</p>
                            </figure>

                            <p class="of-help de__consigne">
                                <strong>Glissez une photo pour la ranger</strong> — les autres s'écartent pour lui faire place. La première est la
                                couverture, sur l'atlas et en tête de la page. Cliquez une photo pour la voir en grand. Chaque geste est enregistré aussitôt.
                            </p>

                            <!-- La galerie : on attrape une photo, les autres s'écartent.
                                 Au doigt, par la poignée ; au clavier, par les boutons
                                 du gros plan. -->
                            <ol v-if="ordre.length" ref="liste" class="ga">
                                <li
                                    v-for="(p, i) in ordre"
                                    :key="p.id"
                                    class="ga__item"
                                    :class="{ 'is-glisse': glisse === p.id, 'is-vue': estVue('galerie', p) }"
                                    data-flip
                                    :data-id="p.id"
                                    @pointerdown="saisir(p, $event)"
                                >
                                    <button
                                        type="button"
                                        class="ga__vignette"
                                        data-prise
                                        :aria-label="`Voir en grand « ${p.caption} », position ${i + 1} sur ${ordre.length}`"
                                        :aria-pressed="estVue('galerie', p)"
                                        @click="montrer('galerie', p)"
                                    >
                                        <img :src="photoSrc(p, 800)" alt="" draggable="false" loading="lazy" decoding="async">
                                    </button>
                                    <span class="ga__poignee" data-poignee title="Glisser pour ranger" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.6" /><circle cx="15" cy="6" r="1.6" /><circle cx="9" cy="12" r="1.6" /><circle cx="15" cy="12" r="1.6" /><circle cx="9" cy="18" r="1.6" /><circle cx="15" cy="18" r="1.6" /></svg>
                                    </span>
                                    <span v-if="i === 0" class="ga__couv">Couverture</span>
                                    <span v-else class="ga__rang of-num" aria-hidden="true">{{ i + 1 }}</span>
                                </li>
                            </ol>
                            <p v-else class="de__sans">Aucune photo : l'illustration de repli s'affiche à la place, sur l'atlas et sur la page.</p>
                            <p class="sr-only" aria-live="polite">{{ annonce }}</p>

                            <!-- Ajouter une photo : un vrai contrôle, bordé, pas un lien discret. -->
                            <button v-if="!ajoutOuvert" type="button" class="btn btn--sm btn--ink de__ajouter" @click="ajoutOuvert = true">
                                <OfficeIcon name="photo" /> Téléverser une photo
                            </button>

                            <!-- Un groupe, pas un <form> : imbriqué dans le formulaire de la
                                 destination, son « submit » remontait jusqu'à lui — l'enregistrement
                                 de la destination partait aussi, et Inertia annulait l'envoi de la
                                 photo en cours de route. Entrée n'enregistre donc pas la destination ici. -->
                            <div v-else class="de__ajout" role="group" aria-label="Nouvelle photo" @keydown.enter.prevent="pret && !ajout.processing && televerser()">
                                <p class="of-kicker">Nouvelle photo de {{ d.name }}</p>

                                <PhotoDepot id="de-fichier" v-model="ajout.photo" :envoi="ajout.processing" :progression="ajout.progress?.percentage ?? null" />
                                <p v-if="ajout.errors.photo" class="of-err" role="alert">{{ ajout.errors.photo }}</p>

                                <div class="of-field">
                                    <label class="of-label" for="cap">Ce que montre la photo, et où</label>
                                    <input id="cap" v-model="ajout.caption" class="of-input" maxlength="160" placeholder="Plage d’Ambondrona au coucher du soleil, Nosy Be">
                                    <p v-if="ajout.errors.caption" class="of-err" role="alert">{{ ajout.errors.caption }}</p>
                                </div>
                                <div class="de__deux">
                                    <div class="of-field">
                                        <label class="of-label" for="aut">Auteur</label>
                                        <input id="aut" v-model="ajout.author" class="of-input" maxlength="120" placeholder="Prénom Nom">
                                        <p v-if="ajout.errors.author" class="of-err" role="alert">{{ ajout.errors.author }}</p>
                                    </div>
                                    <div class="of-field">
                                        <label class="of-label" for="lic">Licence</label>
                                        <select id="lic" v-model="ajout.licence" class="of-input">
                                            <option v-for="l in licences" :key="l.value" :value="l.value">{{ l.label }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="of-field">
                                    <label class="of-label" for="src">Page d'origine <span class="de__opt">— facultatif</span></label>
                                    <input id="src" v-model="ajout.source_url" class="of-input" type="url" maxlength="255" placeholder="https://commons.wikimedia.org/wiki/File:…">
                                    <p v-if="ajout.errors.source_url" class="of-err" role="alert">{{ ajout.errors.source_url }}</p>
                                </div>

                                <!-- La règle photo, déclarée à chaque fois : une plage des
                                     Maldives étiquetée « Nosy Be » ferait exactement ce que
                                     Vayla reproche aux annonces volées. -->
                                <label class="de__declaration">
                                    <input v-model="ajout.declaration" type="checkbox">
                                    <span>C'est une <strong>vraie photographie de ce lieu</strong> — ni image générée, ni photo prise ailleurs — et Vayla a le droit de la publier.</span>
                                </label>
                                <p v-if="ajout.errors.declaration" class="of-err" role="alert">{{ ajout.errors.declaration }}</p>

                                <p class="of-help">Elle arrive au bout de la galerie. L'auteur et la licence sont crédités au pied de chaque page du site.</p>

                                <div class="de__boutons">
                                    <button type="button" @click="televerser" class="btn btn--sm btn--terre" :disabled="!pret || ajout.processing">
                                        {{ ajout.processing ? 'Envoi…' : 'Téléverser' }}
                                    </button>
                                    <button type="button" class="btn btn--sm btn--ghost" :disabled="ajout.processing" @click="ajout.reset(); ajoutOuvert = false">Annuler</button>
                                </div>
                                <p v-if="!pret" class="de__why">Il faut une photo, sa légende, son auteur, et la case cochée.</p>
                            </div>

                            <p class="of-label de__biblio">Ajouter depuis la photothèque <Link href="/phototheque" class="of-card__lien de__gerer">Gérer la photothèque</Link></p>
                            <p class="of-help">Seulement de vraies photographies de Madagascar, créditées. Cliquez-en une pour la voir en grand, puis ajoutez-la : elle arrive au bout de la galerie.</p>
                            <ul v-if="photos.length" class="de__phototheque">
                                <li v-for="p in photos" :key="p.id">
                                    <button
                                        type="button"
                                        class="de__vignette"
                                        :class="{ 'is-vue': estVue('phototheque', p) }"
                                        :aria-label="`Voir en grand « ${p.caption} »`"
                                        :aria-pressed="estVue('phototheque', p)"
                                        @click="montrer('phototheque', p)"
                                    >
                                        <img :src="photoSrc(p, 800)" alt="" loading="lazy" decoding="async">
                                        <span v-if="p.televersee" class="de__marque">Téléversée</span>
                                    </button>
                                </li>
                            </ul>
                            <p v-else class="of-help">Toute la photothèque est déjà dans cette galerie.</p>
                        </template>
                    </div>
                </section>

                <section v-if="!creation" class="of-card de__danger" data-reveal>
                    <div class="of-card__b">
                        <p v-if="d.listings" class="of-help">{{ d.listings }} logement{{ d.listings > 1 ? 's' : '' }} ici : la destination ne se supprime pas tant qu'ils y sont rattachés.</p>
                        <template v-else>
                            <button v-if="!suppression" type="button" class="btn btn--sm btn--outline" @click="suppression = true">Supprimer la destination</button>
                            <div v-else class="de__confirm">
                                <p class="of-help">Sa page disparaîtra du site. Aucun logement n'y est rattaché.</p>
                                <button type="button" class="btn btn--sm btn--ink" @click="supprimer">Confirmer la suppression</button>
                                <button type="button" class="btn btn--sm btn--ghost" @click="suppression = false">Annuler</button>
                            </div>
                        </template>
                    </div>
                </section>
            </div>

            <div class="de__barre" :class="{ 'is-dirty': form.isDirty || creation }">
                <p class="de__etat" role="status">{{ creation ? 'La destination n’existe pas encore.' : (form.isDirty ? 'Des modifications ne sont pas enregistrées.' : 'Tout est enregistré.') }}</p>
                <button v-if="form.isDirty && !creation" type="button" class="btn btn--sm btn--ghost" @click="form.reset()">Annuler</button>
                <button type="submit" class="btn btn--terre" :disabled="form.processing || (!creation && !form.isDirty)">
                    {{ form.processing ? 'Enregistrement…' : (creation ? 'Créer la destination' : 'Enregistrer') }}
                </button>
            </div>
        </form>
    </div>
</template>

<style scoped>
.de { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); align-items: start; gap: 1rem; }
.de__col { display: grid; gap: 1rem; min-width: 0; }
.de__champs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
.de__large { grid-column: 1 / -1; }
.de__code { text-transform: uppercase; letter-spacing: .1em; }
.de__case { display: inline-flex; align-items: center; gap: .55rem; min-height: 2.5rem; font-size: .9rem; font-weight: 600; color: var(--ink); cursor: pointer; }
.de__case input { width: 1.15rem; height: 1.15rem; accent-color: var(--ink); }

.de__n { margin-left: .3rem; font-size: .8rem; font-weight: 700; color: var(--text-3); }
.de__consigne { margin: 0 0 .8rem; }
.de__consigne strong { color: var(--ink); }

/* ── Le gros plan ───────────────────────────────────────────────────────
   La photo cliquée, à la largeur de la carte : c'est là qu'on juge la
   netteté et le cadrage, et là que vivent les gestes. */
.sc { margin: 0 0 1.1rem; }
.sc__cadre { position: relative; aspect-ratio: 4 / 3; overflow: hidden; border-radius: var(--r-sm); background: var(--off-2); }
.sc__cadre img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.sc-enter-active, .sc-leave-active { transition: opacity .28s var(--ease); }
.sc-enter-from, .sc-leave-to { opacity: 0; }
.sc__etiquette { position: absolute; left: .6rem; top: .6rem; z-index: 1; padding: .22rem .7rem; border-radius: var(--r-pill); background: var(--ink); font-size: .72rem; font-weight: 700; color: var(--white); }
.sc__etiquette--clair { border: 1px solid var(--line-2); background: rgba(255, 255, 255, .95); color: var(--ink); }
.sc__legende { display: grid; gap: .15rem; margin-top: .6rem; }
.sc__legende strong { font-size: .88rem; font-weight: 700; line-height: 1.4; color: var(--ink); }
.sc__legende span { font-size: .76rem; color: var(--text-3); }
.sc__gestes { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin-top: .75rem; }
.sc__gestes .oi { width: 1rem; height: 1rem; }
.sc__pousse { flex: 1; }
.sc__ko { border: 1px solid var(--terre-500); background: var(--terre-050); color: var(--terre-700); }
.sc__avert { margin: .5rem 0 0; font-size: .76rem; line-height: 1.45; color: var(--terre-700); }
.sc__note { margin: .5rem 0 0; }
@media (prefers-reduced-motion: reduce) {
    .sc-enter-active, .sc-leave-active { transition: none; }
}

/* ── La galerie ─────────────────────────────────────────────────────────
   Une grille de photos qu'on attrape. Celle qu'on tient suit le pointeur
   (une copie, `is-fantome`) ; à sa place reste un emplacement en pointillé de
   terre — c'est là qu'elle tombera. */
.ga { display: grid; grid-template-columns: repeat(auto-fill, minmax(6.75rem, 1fr)); gap: .5rem; margin: 0 0 1.1rem; padding: 0; list-style: none; }
.ga__item {
    position: relative;
    overflow: hidden;
    border: 2px solid transparent;
    border-radius: var(--r-sm);
    background: var(--off-2);
    cursor: grab;
    user-select: none;
    touch-action: manipulation;
    transition: border-color .15s var(--ease), box-shadow .2s var(--ease);
}
.ga__item:hover { box-shadow: var(--sh-1); }
.ga__item.is-vue { border-color: var(--ink); }
.ga__item.is-glisse { border: 2px dashed var(--terre-500); background: var(--terre-050); box-shadow: none; }
.ga__item.is-glisse > * { visibility: hidden; }
.ga__item.is-fantome { border-color: var(--ink); background: var(--white); cursor: grabbing; }
.ga__vignette { display: block; width: 100%; padding: 0; border: 0; background: none; cursor: inherit; }
.ga__vignette:focus-visible { outline: 2px solid var(--terre-500); outline-offset: -4px; }
.ga__vignette img { display: block; width: 100%; aspect-ratio: 4 / 3; object-fit: cover; pointer-events: none; -webkit-user-drag: none; }
.ga__poignee {
    position: absolute;
    right: .3rem;
    top: .3rem;
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
.ga__poignee svg { width: 1rem; height: 1rem; }
.ga__couv, .ga__rang { position: absolute; left: .3rem; top: .3rem; pointer-events: none; }
.ga__couv { padding: .12rem .5rem; border-radius: var(--r-pill); background: var(--ink); font-size: .64rem; font-weight: 700; color: var(--white); }
.ga__rang { display: grid; place-items: center; min-width: 1.4rem; height: 1.4rem; padding: 0 .3rem; border-radius: var(--r-pill); background: rgba(255, 255, 255, .94); font-size: .7rem; font-weight: 800; color: var(--ink); }

.de__sans { margin: 0 0 .8rem; padding: 1.2rem; border: 1px dashed var(--line-2); border-radius: var(--r-sm); font-size: .84rem; text-align: center; color: var(--text-3); }
.de__ajouter { margin-bottom: 1.1rem; }
.de__ajouter .oi { width: 1rem; height: 1rem; }
.de__ajout { display: grid; gap: .9rem; margin-bottom: 1.2rem; padding: 1rem; border: 1px solid var(--line-2); border-radius: var(--r-sm); background: var(--off); }
.de__deux { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
.de__opt { font-weight: 500; color: var(--text-3); }
.de__declaration { display: flex; align-items: flex-start; gap: .6rem; font-size: .86rem; line-height: 1.45; color: var(--ink); cursor: pointer; }
.de__declaration input { flex: none; width: 1.15rem; height: 1.15rem; margin-top: .15rem; accent-color: var(--ink); }
.de__boutons { display: flex; flex-wrap: wrap; gap: .4rem; }
.de__why { margin: -.4rem 0 0; font-size: .76rem; color: var(--text-3); }
.de__biblio { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .3rem 1rem; margin-top: .4rem; }
.de__gerer { font-weight: 600; }

.de__phototheque { display: grid; grid-template-columns: repeat(auto-fill, minmax(5.5rem, 1fr)); gap: .45rem; max-height: 26rem; margin: .7rem 0 0; padding: 0; overflow-y: auto; list-style: none; }
.de__marque { position: absolute; left: .25rem; top: .25rem; padding: .05rem .4rem; border-radius: var(--r-pill); background: rgba(26, 21, 18, .8); font-size: .6rem; font-weight: 700; color: var(--white); }
.de__vignette { position: relative; display: block; width: 100%; padding: 0; overflow: hidden; border: 2px solid transparent; border-radius: var(--r-xs); background: var(--off-2); cursor: zoom-in; }
.de__vignette img { display: block; width: 100%; aspect-ratio: 4 / 3; object-fit: cover; }
.de__vignette:hover { border-color: var(--line-2); }
.de__vignette.is-vue { border-color: var(--ink); }
.de__vignette:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 1px; }


.de__danger { border-style: dashed; }
.de__confirm { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; }
.de__confirm .of-help { flex-basis: 100%; }

.de__barre {
    grid-column: 1 / -1;
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
}
.de__barre.is-dirty { border-color: var(--terre-300); }
.de__etat { flex: 1; margin: 0; font-size: .84rem; color: var(--text-2); }
.de__barre.is-dirty .de__etat { font-weight: 700; color: var(--terre-700); }

@media (max-width: 960px) {
    .de { grid-template-columns: minmax(0, 1fr); }
    .de__champs { grid-template-columns: minmax(0, 1fr); }
}
</style>
