<script setup>
/**
 * La photothèque : toutes les photographies du site, leurs crédits, et où
 * chacune apparaît.
 *
 * **Une grille à gauche, la photo choisie en grand à droite** — la même
 * lecture que la galerie d'une destination. Une vignette ne dit ni si la photo
 * est nette, ni ce qu'elle cadre, ni qui l'a prise : le gros plan dit tout ça,
 * et c'est là que le crédit se corrige, parce que c'est lui qui s'affiche au
 * pied de chaque page du site.
 *
 * **Chaque photo dit où elle apparaît**, avec un lien vers la destination ou
 * l'annonce : c'est ce qui manquait pour savoir si on peut la retirer. Et ce
 * qu'on n'a pas le droit de faire (supprimer une photo de Commons, changer sa
 * licence) n'est pas un bouton grisé : c'est une phrase qui dit pourquoi.
 *
 * **Le poids se voit** — par photo et pour toute la photothèque : c'est le
 * stockage qu'on paie, et chaque photo téléversée l'est en trois tailles.
 */
import { computed, nextTick, ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import OfficePager from '@/Components/Office/OfficePager.vue'
import OfficeSearch from '@/Components/Office/OfficeSearch.vue'
import OfficeTabs from '@/Components/Office/OfficeTabs.vue'
import PhotoDepot from '@/Components/Office/PhotoDepot.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { nombre, poids } from '@/Support/format.js'
import { photoSrc, photoUrl } from '@/Support/photo.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    photos: { type: Array, required: true },
    ouverte: { type: Object, default: null },
    meta: { type: Object, required: true },
    onglets: { type: Array, required: true },
    filtre: { type: Object, required: true },
    disque: { type: Object, required: true },
    licences: { type: Array, required: true },
    destinations: { type: Array, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

// ── La photo choisie ────────────────────────────────────────────────────
// Elle vit dans l'adresse (`?photo=`) : on la retrouve après un
// enregistrement, et le journal peut y renvoyer.
const choisieId = ref(props.ouverte?.id ?? null)
const panneau = ref(props.ouverte ? 'photo' : null)
const aside = ref(null)

watch(() => props.ouverte?.id, (id) => {
    if (id) {
        choisieId.value = id
        panneau.value = 'photo'
    }
})

const choisie = computed(() => props.photos.find((p) => p.id === choisieId.value)
    ?? (props.ouverte?.id === choisieId.value ? props.ouverte : null))

const adresse = (id) => {
    const url = new URL(window.location.href)
    if (id) url.searchParams.set('photo', id)
    else url.searchParams.delete('photo')
    // `history.state` porte la page d'Inertia : le garder, sinon le retour
    // arrière casse.
    window.history.replaceState(window.history.state, '', url)
}

const montrer = async (p) => {
    choisieId.value = p.id
    panneau.value = 'photo'
    confirmer.value = false
    adresse(p.id)
    // Sous 1 100 px le gros plan passe sous la grille : on y descend.
    await nextTick()
    if (window.matchMedia('(max-width: 1100px)').matches) aside.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const fermer = () => {
    choisieId.value = null
    panneau.value = null
    adresse(null)
}

// ── Le crédit ───────────────────────────────────────────────────────────
const credit = useForm({ caption: '', author: '', source_url: '', licence: '' })

const remplir = (p) => {
    credit.defaults({
        caption: p?.caption ?? '',
        author: p?.author ?? '',
        source_url: p?.source_url ?? '',
        licence: p?.licenceCle ?? '',
    })
    credit.reset()
    credit.clearErrors()
}

watch(choisie, remplir, { immediate: true })

const enregistrer = () => {
    const p = choisie.value
    credit
        .transform((x) => ({
            caption: x.caption,
            ...(p.peut.credit ? { author: x.author, source_url: x.source_url || null } : {}),
            ...(p.peut.licence ? { licence: x.licence } : {}),
        }))
        .post(`/phototheque/${p.id}`, { preserveScroll: true, preserveState: true })
}

// ── Supprimer ───────────────────────────────────────────────────────────
const confirmer = ref(false)
const supprimer = (p) => router.post(`/phototheque/${p.id}/retirer`, {}, {
    preserveScroll: true,
    onSuccess: () => fermer(),
    onFinish: () => { confirmer.value = false },
})

// ── Téléverser ──────────────────────────────────────────────────────────
const ajout = useForm({ photo: null, caption: '', author: '', licence: 'vayla', source_url: '', destination_id: '', declaration: false })

const ouvrirAjout = async () => {
    panneau.value = 'ajout'
    await nextTick()
    aside.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const pret = computed(() => ajout.photo && ajout.caption.trim().length >= 4 && ajout.author.trim() && ajout.declaration)

const televerser = () => ajout
    .transform((x) => ({ ...x, destination_id: x.destination_id || null, source_url: x.source_url || null }))
    .post('/phototheque', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => ajout.reset(),
    })

// ── Lecture ─────────────────────────────────────────────────────────────
const nomOnglet = computed(() => props.onglets.find((o) => o.cle === props.filtre.onglet)?.label ?? '')
const dimensions = (p) => `${nombre(p.width)} × ${nombre(Math.round(p.width * 3 / 4))} px`
const court = { commons: 'Commons', equipe: 'Équipe', proprietaire: 'Propriétaire', demonstration: 'Démo', generee: 'Générée' }
const change = computed(() => credit.isDirty)
</script>

<template>
    <Head title="Photothèque — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Contenu" titre="Photothèque">
            <template #lede>
                Les photographies du site et leurs crédits — ceux-ci s'affichent au pied de chaque page.
                <span class="of-num">{{ poids(disque.total) }}</span> sur le disque, chaque photo en trois tailles.
            </template>
            <template #actions>
                <button type="button" class="btn btn--sm btn--ink" @click="ouvrirAjout"><OfficeIcon name="plus" /> Téléverser une photo</button>
            </template>
        </OfficeHead>

        <OfficeTabs :onglets="onglets" :actif="filtre.onglet" base="/phototheque" param="onglet" defaut="lieux" :q="filtre.q" />
        <OfficeSearch base="/phototheque" :q="filtre.q" :params="filtre.onglet !== 'lieux' ? { onglet: filtre.onglet } : {}" placeholder="Légende, auteur…" label="Rechercher une photo" />

        <div class="ph">
            <section class="ph__grille" :aria-label="nomOnglet">
                <ul v-if="photos.length" class="ph__photos" data-reveal>
                    <li v-for="p in photos" :key="p.id">
                        <button
                            type="button"
                            class="ph__carte"
                            :class="{ 'is-vue': p.id === choisieId && panneau === 'photo' }"
                            :aria-pressed="p.id === choisieId && panneau === 'photo'"
                            @click="montrer(p)"
                        >
                            <span class="ph__image">
                                <img :src="photoSrc(p, 800)" alt="" loading="lazy" decoding="async">
                                <span class="ph__origine" :class="`ph__origine--${p.provenance}`">{{ court[p.provenance] }}</span>
                            </span>
                            <span class="ph__legende">{{ p.caption }}</span>
                            <span class="ph__usage">
                                <template v-if="p.usages.length">{{ p.usages.length }} usage{{ p.usages.length > 1 ? 's' : '' }}</template>
                                <strong v-else class="ph__libre">Inutilisée</strong>
                                · {{ poids(p.poids) }}
                            </span>
                        </button>
                    </li>
                </ul>
                <div v-else class="of-vide" data-reveal>
                    <strong>{{ filtre.q ? 'Aucune photo ne correspond.' : 'Rien dans cet onglet.' }}</strong>
                    <template v-if="filtre.onglet === 'inutilisees'">Toutes les photos de lieux illustrent quelque chose.</template>
                    <template v-else-if="filtre.onglet === 'televersees'">Aucune photo téléversée par l'équipe pour l'instant.</template>
                </div>

                <OfficePager :meta="meta" unite="photo" />
            </section>

            <aside ref="aside" class="ph__panneau" aria-live="polite">
                <!-- ── Téléverser ─────────────────────────────────── -->
                <form v-if="panneau === 'ajout'" class="of-card ph__carte-p" novalidate @submit.prevent="televerser">
                    <header class="of-card__h">
                        <h2 class="of-card__t">Nouvelle photo</h2>
                        <button type="button" class="ph__fermer" aria-label="Fermer" @click="panneau = choisie ? 'photo' : null">✕</button>
                    </header>
                    <div class="of-card__b ph__champs">
                        <PhotoDepot id="ph-fichier" v-model="ajout.photo" :envoi="ajout.processing" :progression="ajout.progress?.percentage ?? null" />
                        <p v-if="ajout.errors.photo" class="of-err">{{ ajout.errors.photo }}</p>

                        <div class="of-field">
                            <label class="of-label" for="ph-cap">Ce que montre la photo, et où</label>
                            <input id="ph-cap" v-model="ajout.caption" class="of-input" maxlength="160" placeholder="Plage d’Ambondrona au coucher du soleil, Nosy Be">
                            <p v-if="ajout.errors.caption" class="of-err">{{ ajout.errors.caption }}</p>
                        </div>
                        <div class="ph__deux">
                            <div class="of-field">
                                <label class="of-label" for="ph-aut">Auteur</label>
                                <input id="ph-aut" v-model="ajout.author" class="of-input" maxlength="120" placeholder="Prénom Nom">
                                <p v-if="ajout.errors.author" class="of-err">{{ ajout.errors.author }}</p>
                            </div>
                            <div class="of-field">
                                <label class="of-label" for="ph-lic">Licence</label>
                                <select id="ph-lic" v-model="ajout.licence" class="of-input">
                                    <option v-for="l in licences" :key="l.value" :value="l.value">{{ l.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="ph-src">Page d'origine <span class="ph__opt">— facultatif</span></label>
                            <input id="ph-src" v-model="ajout.source_url" class="of-input" type="url" maxlength="255" placeholder="https://commons.wikimedia.org/wiki/File:…">
                            <p v-if="ajout.errors.source_url" class="of-err">{{ ajout.errors.source_url }}</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="ph-dest">L'ajouter à une destination <span class="ph__opt">— facultatif</span></label>
                            <select id="ph-dest" v-model="ajout.destination_id" class="of-input">
                                <option value="">Non, seulement à la photothèque</option>
                                <option v-for="d in destinations" :key="d.value" :value="d.value">{{ d.label }}</option>
                            </select>
                            <p class="of-help">Elle arrive au bout de sa galerie. Sans destination, elle attend dans la photothèque — et n'est créditée au pied de page qu'une fois publiée quelque part.</p>
                        </div>

                        <!-- La règle photo, déclarée à chaque fois. -->
                        <label class="ph__declaration">
                            <input v-model="ajout.declaration" type="checkbox">
                            <span>C'est une <strong>vraie photographie de Madagascar</strong> — ni image générée, ni photo prise ailleurs — et Vayla a le droit de la publier.</span>
                        </label>
                        <p v-if="ajout.errors.declaration" class="of-err">{{ ajout.errors.declaration }}</p>
                        <p v-if="ajout.errors.destination_id" class="of-err">{{ ajout.errors.destination_id }}</p>

                        <div class="ph__boutons">
                            <button type="submit" class="btn btn--sm btn--terre" :disabled="!pret || ajout.processing">
                                {{ ajout.processing ? 'Envoi…' : 'Téléverser' }}
                            </button>
                            <button type="button" class="btn btn--sm btn--ghost" :disabled="ajout.processing" @click="ajout.reset(); panneau = choisie ? 'photo' : null">Annuler</button>
                        </div>
                        <p v-if="!pret && !ajout.processing" class="ph__why">Il faut une photo, sa légende, son auteur, et la case cochée.</p>
                    </div>
                </form>

                <!-- ── Le gros plan ───────────────────────────────── -->
                <article v-else-if="panneau === 'photo' && choisie" class="of-card ph__carte-p">
                    <div class="ph__cadre">
                        <Transition name="ph">
                            <img :key="choisie.id" :src="photoSrc(choisie, 1600)" :alt="choisie.caption ?? ''" decoding="async">
                        </Transition>
                        <span class="ph__provenance">{{ choisie.provenanceLabel }}</span>
                        <button type="button" class="ph__fermer ph__fermer--image" aria-label="Fermer le gros plan" @click="fermer">✕</button>
                    </div>

                    <div class="of-card__b ph__fiche">
                        <p class="ph__technique of-num">
                            {{ dimensions(choisie) }} · {{ choisie.paliers.length }} taille{{ choisie.paliers.length > 1 ? 's' : '' }} ({{ choisie.paliers.join(', ') }} px) · <strong>{{ poids(choisie.poids) }}</strong>
                            · <a :href="photoUrl(choisie, choisie.paliers.at(-1) ?? 800)" target="_blank" rel="noopener" class="of-card__lien">Ouvrir en taille réelle <OfficeIcon name="externe" /></a>
                        </p>

                        <section class="ph__bloc">
                            <h3 class="of-label">Où elle apparaît</h3>
                            <ul v-if="choisie.usages.length" class="ph__usages">
                                <li v-for="u in choisie.usages" :key="u.href + u.label">
                                    <Link :href="u.href" class="ph__lienusage">
                                        <OfficeIcon :name="u.type === 'destination' ? 'destinations' : 'annonces'" />
                                        <span>{{ u.type === 'destination' ? 'Destination' : 'Annonce' }} — {{ u.label }}</span>
                                    </Link>
                                </li>
                            </ul>
                            <p v-else class="of-help">
                                Nulle part pour l'instant.
                                <template v-if="choisie.provenance === 'equipe'">Elle n'est pas créditée au pied de page tant qu'elle n'illustre rien. Ajoutez-la depuis la page d'une destination.</template>
                            </p>
                        </section>

                        <!-- Le crédit : ce qui s'affiche au pied de chaque page. -->
                        <form v-if="choisie.peut.legende" class="ph__bloc ph__champs" novalidate @submit.prevent="enregistrer">
                            <h3 class="of-label">{{ choisie.peut.credit ? 'Légende et crédit' : 'Légende' }}</h3>
                            <div class="of-field">
                                <label class="of-label ph__sous" for="ph-e-cap">Ce que montre la photo, et où</label>
                                <textarea id="ph-e-cap" v-model="credit.caption" class="of-input" rows="2" maxlength="160" />
                                <p class="of-help">{{ choisie.provenance === 'proprietaire' ? 'C’est le texte lu aux malvoyants sur la fiche de l’annonce.' : 'C’est aussi le texte lu aux malvoyants.' }}</p>
                                <p v-if="credit.errors.caption" class="of-err">{{ credit.errors.caption }}</p>
                            </div>
                            <template v-if="choisie.peut.credit">
                                <div class="of-field">
                                    <label class="of-label ph__sous" for="ph-e-aut">Auteur</label>
                                    <input id="ph-e-aut" v-model="credit.author" class="of-input" maxlength="120">
                                    <p v-if="credit.errors.author" class="of-err">{{ credit.errors.author }}</p>
                                </div>
                                <div class="of-field">
                                    <label class="of-label ph__sous" for="ph-e-src">Page d'origine</label>
                                    <input id="ph-e-src" v-model="credit.source_url" class="of-input" type="url" maxlength="255">
                                    <p v-if="credit.errors.source_url" class="of-err">{{ credit.errors.source_url }}</p>
                                </div>
                                <div v-if="choisie.peut.licence" class="of-field">
                                    <label class="of-label ph__sous" for="ph-e-lic">Licence</label>
                                    <select id="ph-e-lic" v-model="credit.licence" class="of-input">
                                        <option v-for="l in licences" :key="l.value" :value="l.value">{{ l.label }}</option>
                                    </select>
                                </div>
                                <p v-else class="of-help">
                                    Licence : <a v-if="choisie.licence_url" :href="choisie.licence_url" target="_blank" rel="noopener" class="of-card__lien">{{ choisie.licence }}</a><template v-else>{{ choisie.licence }}</template>
                                    — celle que l'auteur a choisie sur Commons ; elle ne se change pas. Ne corrigez l'auteur que d'après la page d'origine.
                                </p>
                            </template>
                            <div class="ph__boutons">
                                <button type="submit" class="btn btn--sm btn--terre" :disabled="!change || credit.processing">{{ credit.processing ? 'Enregistrement…' : 'Enregistrer' }}</button>
                                <button v-if="change" type="button" class="btn btn--sm btn--ghost" @click="credit.reset()">Annuler</button>
                            </div>
                        </form>
                        <section v-else class="ph__bloc">
                            <h3 class="of-label">Crédit</h3>
                            <p class="ph__credit">« {{ choisie.caption }} »<template v-if="choisie.author"> — {{ choisie.author }}<template v-if="choisie.licence">, {{ choisie.licence }}</template></template></p>
                            <p class="of-help">Elle illustre les annonces de démonstration et disparaîtra avec elles : son crédit ne se corrige pas.</p>
                        </section>

                        <section class="ph__bloc ph__danger">
                            <template v-if="choisie.peut.supprimer">
                                <button v-if="!confirmer" type="button" class="btn btn--sm btn--outline" @click="confirmer = true">Supprimer la photo</button>
                                <div v-else class="ph__confirm">
                                    <p class="of-help">Supprimée pour de bon, avec ses {{ choisie.paliers.length }} fichiers ({{ poids(choisie.poids) }}) : elle n'illustre rien.</p>
                                    <button type="button" class="btn btn--sm btn--ink" @click="supprimer(choisie)">Confirmer la suppression</button>
                                    <button type="button" class="btn btn--sm btn--ghost" @click="confirmer = false">Annuler</button>
                                </div>
                            </template>
                            <p v-else class="of-help">{{ choisie.pourquoiPasSupprimer }}</p>
                        </section>
                    </div>
                </article>

                <div v-else class="ph__attente">
                    <OfficeIcon name="photo" />
                    <p><strong>Cliquez une photo</strong> pour la voir en grand, savoir où elle apparaît et corriger son crédit.</p>
                    <button type="button" class="btn btn--sm btn--outline" @click="ouvrirAjout"><OfficeIcon name="plus" /> Téléverser une photo</button>
                </div>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.ph { display: grid; grid-template-columns: minmax(0, 1fr) minmax(22rem, 30rem); align-items: start; gap: 1rem; margin-top: 1rem; }

/* ── La grille ──────────────────────────────────────────────────────── */
.ph__photos { display: grid; grid-template-columns: repeat(auto-fill, minmax(10.5rem, 1fr)); gap: .6rem; margin: 0 0 1rem; padding: 0; list-style: none; }
.ph__carte {
    display: grid;
    gap: .3rem;
    width: 100%;
    padding: .35rem .35rem .5rem;
    border: 1px solid var(--line);
    border-radius: var(--r-sm);
    background: var(--white);
    font: inherit;
    text-align: left;
    cursor: zoom-in;
    transition: border-color .15s var(--ease), box-shadow .2s var(--ease);
}
.ph__carte:hover { border-color: var(--line-2); box-shadow: var(--sh-1); }
.ph__carte:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }
.ph__carte.is-vue { border-color: var(--ink); box-shadow: 0 0 0 1px var(--ink); }
.ph__image { position: relative; display: block; overflow: hidden; border-radius: var(--r-xs); background: var(--off-2); }
.ph__image img { display: block; width: 100%; aspect-ratio: 4 / 3; object-fit: cover; }
.ph__origine { position: absolute; left: .3rem; top: .3rem; padding: .08rem .45rem; border-radius: var(--r-pill); background: rgba(255, 255, 255, .94); font-size: .62rem; font-weight: 700; color: var(--ink); }
.ph__origine--equipe { background: var(--ink); color: var(--white); }
.ph__origine--demonstration, .ph__origine--generee { border: 1px dashed var(--line-2); color: var(--text-3); }
.ph__legende { display: -webkit-box; overflow: hidden; -webkit-line-clamp: 2; -webkit-box-orient: vertical; padding: 0 .15rem; font-size: .78rem; font-weight: 600; line-height: 1.35; color: var(--ink); }
.ph__usage { padding: 0 .15rem; font-size: .7rem; color: var(--text-3); }
.ph__libre { font-weight: 700; color: var(--terre-700); }

/* ── Le panneau ─────────────────────────────────────────────────────── */
.ph__panneau { position: sticky; top: 1rem; display: grid; gap: 1rem; min-width: 0; scroll-margin-top: 1rem; }
.ph__carte-p { overflow: hidden; }
.ph__cadre { position: relative; aspect-ratio: 4 / 3; overflow: hidden; background: var(--off-2); }
.ph__cadre img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.ph-enter-active, .ph-leave-active { transition: opacity .28s var(--ease); }
.ph-enter-from, .ph-leave-to { opacity: 0; }
.ph__provenance { position: absolute; left: .6rem; top: .6rem; z-index: 1; padding: .22rem .7rem; border: 1px solid var(--line-2); border-radius: var(--r-pill); background: rgba(255, 255, 255, .95); font-size: .72rem; font-weight: 700; color: var(--ink); }
.ph__fermer {
    display: grid;
    place-items: center;
    width: 2.25rem;
    height: 2.25rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font: inherit;
    font-size: .85rem;
    font-weight: 700;
    color: var(--ink);
    cursor: pointer;
}
.ph__fermer:hover { border-color: var(--ink); }
.ph__fermer--image { position: absolute; right: .6rem; top: .6rem; z-index: 1; background: rgba(255, 255, 255, .95); }

.ph__fiche { display: grid; gap: 1.1rem; }
.ph__technique { margin: 0; font-size: .76rem; line-height: 1.6; color: var(--text-2); }
.ph__technique strong { color: var(--ink); }
.ph__technique .oi { width: .8rem; height: .8rem; vertical-align: -.1em; }
.ph__bloc { display: grid; gap: .5rem; margin: 0; }
.ph__bloc h3 { margin: 0; }
.ph__sous { font-weight: 600; }
.ph__usages { display: grid; gap: .3rem; margin: 0; padding: 0; list-style: none; }
.ph__lienusage {
    display: flex;
    align-items: center;
    gap: .5rem;
    min-height: 2.5rem;
    padding: .3rem .7rem;
    border: 1px solid var(--line);
    border-radius: var(--r-xs);
    font-size: .82rem;
    color: var(--ink);
    text-decoration: none;
}
.ph__lienusage:hover { border-color: var(--ink); }
.ph__lienusage .oi { flex: none; width: 1rem; height: 1rem; color: var(--text-2); }
.ph__credit { margin: 0; font-size: .84rem; line-height: 1.5; color: var(--ink); }
.ph__danger { padding-top: .9rem; border-top: 1px dashed var(--line-2); }
.ph__confirm { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; }
.ph__confirm .of-help { flex-basis: 100%; margin: 0; }

.ph__champs { display: grid; gap: .85rem; }
.ph__deux { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
.ph__opt { font-weight: 500; color: var(--text-3); }
.ph__declaration { display: flex; align-items: flex-start; gap: .6rem; font-size: .84rem; line-height: 1.45; color: var(--ink); cursor: pointer; }
.ph__declaration input { flex: none; width: 1.15rem; height: 1.15rem; margin-top: .15rem; accent-color: var(--ink); }
.ph__boutons { display: flex; flex-wrap: wrap; gap: .4rem; }
.ph__why { margin: -.3rem 0 0; font-size: .76rem; color: var(--text-3); }

.ph__attente { display: grid; justify-items: center; gap: .6rem; padding: 2rem 1.4rem; border: 1px dashed var(--line-2); border-radius: var(--r-md); text-align: center; color: var(--text-2); }
.ph__attente .oi { width: 1.8rem; height: 1.8rem; color: var(--ink); }
.ph__attente p { margin: 0; max-width: 20rem; font-size: .86rem; line-height: 1.5; }
.ph__attente strong { color: var(--ink); }

@media (max-width: 1100px) {
    .ph { grid-template-columns: minmax(0, 1fr); }
    .ph__panneau { position: static; order: -1; }
    .ph__attente { display: none; }
}
@media (max-width: 560px) {
    .ph__deux { grid-template-columns: minmax(0, 1fr); }
    .ph__photos { grid-template-columns: repeat(auto-fill, minmax(8.5rem, 1fr)); }
}
@media (prefers-reduced-motion: reduce) {
    .ph-enter-active, .ph-leave-active { transition: none; }
}
</style>
