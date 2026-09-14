<script setup>
/**
 * Écrire une page du site.
 *
 * **Le texte à gauche, la page à droite** — l'aperçu est rendu **par le
 * serveur**, avec le même moteur que la page publiée : ce qu'on voit est ce qui
 * sortira. Il suit la frappe, à une demi-seconde près.
 *
 * **Du Markdown, et une barre pour ne pas avoir à l'apprendre** : intertitre,
 * gras, lien, liste, citation. Les boutons écrivent la marque autour de la
 * sélection ; celui qui connaît le Markdown tape directement. Pas d'éditeur
 * « visuel » : ils produisent un HTML qu'on ne maîtrise pas, et le HTML brut
 * est de toute façon retiré à l'affichage.
 *
 * **Publier est un geste à part d'enregistrer** : on enregistre un brouillon
 * vingt fois, on publie une fois. Et une page qui porte encore
 * « [à compléter] » refuse de se publier — les pages légales naissent ainsi.
 *
 * **L'adresse se corrige jusqu'à la première publication**, puis elle est
 * figée : elle a pu être partagée ou indexée.
 */
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { ilYA } from '@/Support/format.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    page: { type: Object, default: null },
    groupes: { type: Array, required: true },
    commission: { type: String, default: '' },
    site: { type: String, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

const creation = computed(() => ! props.page)
const p = props.page ?? {}
const pg = computed(() => props.page ?? {})

const form = useForm({
    title: p.title ?? '',
    slug: p.slug ?? '',
    lede: p.lede ?? '',
    body: p.body ?? '',
    seo_description: p.seo_description ?? '',
    footer_group: p.footer_group ?? '',
    internal_note: p.internal_note ?? '',
})

// L'adresse suit le titre tant qu'on ne l'a pas écrite soi-même — et tant
// qu'elle n'est pas figée.
const slugTouche = ref(!! p.slug)
const versSlug = (s) => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80)
watch(() => form.title, (v) => { if (! slugTouche.value && ! pg.value.adresseFigee) form.slug = versSlug(v) })

// ── L'aperçu ────────────────────────────────────────────────────────────
const apercu = ref('')
const sommaire = ref([])
const chargement = ref(false)
let minuteur

const jeton = () => decodeURIComponent(document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '')

const previsualiser = async () => {
    chargement.value = true
    try {
        const r = await fetch('/pages/apercu', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': jeton(), 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ body: form.body }),
        })
        if (r.ok) {
            const d = await r.json()
            apercu.value = d.html
            sommaire.value = d.sommaire
        }
    } finally {
        chargement.value = false
    }
}

watch(() => form.body, () => { clearTimeout(minuteur); minuteur = setTimeout(previsualiser, 450) })
onMounted(previsualiser)
onUnmounted(() => clearTimeout(minuteur))

// ── La barre d'outils ───────────────────────────────────────────────────
const zone = ref(null)

/** Entoure la sélection (ou un texte d'exemple) de deux marques. */
const entourer = (avant, apres, exemple) => {
    const el = zone.value
    const [a, b] = [el.selectionStart, el.selectionEnd]
    const choisi = form.body.slice(a, b) || exemple
    form.body = form.body.slice(0, a) + avant + choisi + apres + form.body.slice(b)
    requestAnimationFrame(() => { el.focus(); el.setSelectionRange(a + avant.length, a + avant.length + choisi.length) })
}

/** Préfixe chaque ligne touchée par la sélection — intertitre, liste, citation. */
const prefixer = (prefixe, exemple) => {
    const el = zone.value
    const debut = form.body.lastIndexOf('\n', el.selectionStart - 1) + 1
    let fin = form.body.indexOf('\n', el.selectionEnd)
    if (fin === -1) fin = form.body.length
    const bloc = form.body.slice(debut, fin) || exemple
    const nouveau = bloc.split('\n')
        .map((l, i) => (typeof prefixe === 'function' ? prefixe(i) : prefixe) + l.replace(/^(#{1,3} |[-*] |\d+\. |> )/, ''))
        .join('\n')
    form.body = form.body.slice(0, debut) + nouveau + form.body.slice(fin)
    requestAnimationFrame(() => { el.focus(); el.setSelectionRange(debut, debut + nouveau.length) })
}

const lien = () => entourer('[', '](https://)', 'texte du lien')

const OUTILS = [
    { label: 'Intertitre', aide: 'Un intertitre — il entre dans le sommaire', faire: () => prefixer('## ', 'Intertitre') },
    { label: 'Sous-titre', aide: 'Un sous-intertitre', faire: () => prefixer('### ', 'Sous-titre') },
    { label: 'Gras', aide: 'Mettre en gras', faire: () => entourer('**', '**', 'texte important') },
    { label: 'Italique', aide: 'Mettre en italique', faire: () => entourer('*', '*', 'texte') },
    { label: 'Lien', aide: 'Un lien', faire: lien },
    { label: 'Liste', aide: 'Une liste à puces', faire: () => prefixer('- ', 'élément') },
    { label: 'Liste numérotée', aide: 'Une liste numérotée', faire: () => prefixer((i) => `${i + 1}. `, 'étape') },
    { label: 'Citation', aide: 'Un encadré', faire: () => prefixer('> ', 'À retenir') },
]

// ── Les gestes ──────────────────────────────────────────────────────────
const vide = (v) => (v === '' ? null : v)
const enregistrer = () => form
    .transform((d) => ({ ...d, lede: vide(d.lede), seo_description: vide(d.seo_description), footer_group: vide(d.footer_group), internal_note: vide(d.internal_note), slug: vide(d.slug) }))
    .post(creation.value ? '/pages' : `/pages/${p.id}`, { preserveScroll: true, onSuccess: () => form.defaults() })

const publier = () => router.post(`/pages/${p.id}/publier`, {}, { preserveScroll: true })
const depublier = () => router.post(`/pages/${p.id}/depublier`, {}, { preserveScroll: true })

const suppression = ref(false)
const supprimer = () => router.post(`/pages/${p.id}/supprimer`)

const aCompleter = computed(() => /\[à compléter[^\]]*\]/i.test(`${form.body} ${form.lede}`))
</script>

<template>
    <Head :title="creation ? 'Nouvelle page — Back-office' : `${pg.title} — Back-office`" />

    <div ref="racine">
        <OfficeHead :back="{ href: '/pages', label: 'Pages' }" :kicker="creation ? 'Nouvelle page' : (pg.publiee ? 'En ligne' : 'Brouillon')" :titre="creation ? 'Nouvelle page' : pg.title">
            <template #lede>
                <template v-if="creation">Elle naît en brouillon : rien n'apparaît sur le site avant « Publier ».</template>
                <template v-else>Adresse : <span class="of-num">/{{ pg.slug }}</span><template v-if="pg.adresseFigee"> — figée, la page a déjà été publiée</template> · modifiée {{ ilYA(pg.misAJour) }}</template>
            </template>
            <template #actions>
                <template v-if="!creation">
                    <a v-if="pg.publiee" :href="pg.url" target="_blank" rel="noopener" class="btn btn--sm btn--outline"><OfficeIcon name="externe" /> Voir la page</a>
                    <button v-if="pg.publiee" type="button" class="btn btn--sm btn--outline" @click="depublier">Retirer du site</button>
                    <button v-else type="button" class="btn btn--sm btn--terre" :disabled="form.isDirty || aCompleter" :title="form.isDirty ? 'Enregistrez d’abord' : ''" @click="publier">Publier</button>
                </template>
            </template>
        </OfficeHead>

        <p v-if="!creation && !pg.publiee && (aCompleter || form.isDirty)" class="pe__pourquoi" data-reveal>
            {{ aCompleter ? 'La page contient encore des « [à compléter] » : remplissez-les pour pouvoir la publier.' : 'Enregistrez vos modifications avant de publier.' }}
        </p>

        <div v-if="pg.internal_note" class="pe__note" role="note" data-reveal>
            <p class="of-kicker">Note de l'équipe — jamais affichée sur le site</p>
            <p>{{ pg.internal_note }}</p>
        </div>

        <form class="pe" novalidate @submit.prevent="enregistrer">
            <div class="pe__edition">
                <section class="of-card" data-reveal>
                    <div class="of-card__b pe__champs">
                        <div class="of-field">
                            <label class="of-label" for="title">Titre</label>
                            <input id="title" v-model="form.title" class="of-input pe__titre" maxlength="120" required>
                            <p v-if="form.errors.title" class="of-err" role="alert">{{ form.errors.title }}</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="slug">Adresse</label>
                            <div class="pe__adresse">
                                <span>{{ site.replace(/^https?:\/\//, '') }}/</span>
                                <input id="slug" v-model="form.slug" class="of-input of-num" maxlength="80" :disabled="pg.adresseFigee" @input="slugTouche = true">
                            </div>
                            <p class="of-help">{{ pg.adresseFigee ? 'Figée : la page a déjà été en ligne, son lien a pu être partagé.' : 'Minuscules, chiffres et tirets. Elle se fige à la première publication.' }}</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="lede">Chapeau <span class="pe__opt">— sous le titre, facultatif</span></label>
                            <textarea id="lede" v-model="form.lede" class="of-input pe__chapeau" rows="2" maxlength="300" />
                        </div>
                    </div>
                </section>

                <section class="of-card pe__texte" data-reveal>
                    <div class="pe__outils" role="toolbar" aria-label="Mise en forme">
                        <button v-for="o in OUTILS" :key="o.label" type="button" class="pe__outil" :title="o.aide" @click="o.faire">{{ o.label }}</button>
                    </div>
                    <label class="sr-only" for="body">Contenu de la page</label>
                    <textarea id="body" ref="zone" v-model="form.body" class="pe__zone" spellcheck="true" />
                    <p class="pe__astuce">
                        <strong>{commission}</strong> s'écrit « {{ commission }} » sur la page : le taux du réglage, jamais recopié.
                        Le HTML tapé à la main est retiré à l'affichage.
                    </p>
                    <p v-if="form.errors.body" class="of-err pe__err" role="alert">{{ form.errors.body }}</p>
                </section>

                <section class="of-card" data-reveal>
                    <header class="of-card__h"><h2 class="of-card__t">Rangement et moteurs</h2></header>
                    <div class="of-card__b pe__champs">
                        <div class="of-field">
                            <label class="of-label" for="groupe">Colonne du pied de page</label>
                            <select id="groupe" v-model="form.footer_group" class="of-input">
                                <option value="">Hors du pied de page</option>
                                <option v-for="g in groupes" :key="g.value" :value="g.value">{{ g.label }}</option>
                            </select>
                            <p class="of-help">Le lien n'apparaît que lorsque la page est publiée.</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="seo">Description pour les moteurs de recherche</label>
                            <textarea id="seo" v-model="form.seo_description" class="of-input" rows="2" maxlength="160" />
                            <p class="of-help of-num">{{ form.seo_description.length }} / 160 — au-delà, les moteurs coupent au milieu d'un mot. Vide : le chapeau sert.</p>
                            <p v-if="form.errors.seo_description" class="of-err" role="alert">{{ form.errors.seo_description }}</p>
                        </div>
                        <div class="of-field">
                            <label class="of-label" for="note">Note pour l'équipe <span class="pe__opt">— jamais affichée</span></label>
                            <textarea id="note" v-model="form.internal_note" class="of-input" rows="2" maxlength="2000" placeholder="Ex. : à faire relire par un juriste avant publication." />
                        </div>
                    </div>
                </section>

                <section v-if="!creation && !pg.systeme" class="of-card pe__danger" data-reveal>
                    <div class="of-card__b">
                        <button v-if="!suppression" type="button" class="btn btn--sm btn--outline" @click="suppression = true">Supprimer la page</button>
                        <span v-else class="pe__confirm">
                            <button type="button" class="btn btn--sm btn--ink" @click="supprimer">Confirmer la suppression</button>
                            <button type="button" class="btn btn--sm btn--ghost" @click="suppression = false">Annuler</button>
                        </span>
                    </div>
                </section>
                <p v-else-if="!creation" class="of-help pe__systeme">Page attendue par le site (pied de page, obligations légales) : elle se retire du site, elle ne se supprime pas.</p>
            </div>

            <!-- L'aperçu : la page telle qu'elle sortira. -->
            <aside class="pe__apercu" aria-label="Aperçu de la page">
                <div class="pe__apercu-barre">
                    <span class="of-kicker">Aperçu</span>
                    <span class="pe__etat" :class="{ 'is-on': chargement }">{{ chargement ? 'mise à jour…' : 'à jour' }}</span>
                </div>
                <div class="pe__feuille">
                    <h1 class="pe__h1">{{ form.title || 'Sans titre' }}</h1>
                    <p v-if="form.lede" class="pe__lede">{{ form.lede }}</p>
                    <nav v-if="sommaire.length >= 3" class="pe__sommaire">
                        <p class="of-kicker">Sur cette page</p>
                        <ol><li v-for="s in sommaire" :key="s.id">{{ s.label }}</li></ol>
                    </nav>
                    <!-- HTML rendu et nettoyé par le serveur, comme sur le site. -->
                    <div class="pe__rendu" v-html="apercu" />
                </div>
            </aside>

            <div class="pe__barre" :class="{ 'is-dirty': form.isDirty || creation }">
                <p class="pe__statut" role="status">{{ creation ? 'La page n’existe pas encore.' : (form.isDirty ? 'Des modifications ne sont pas enregistrées.' : (pg.publiee ? 'Enregistrée — la page en ligne est à jour.' : 'Brouillon enregistré.')) }}</p>
                <button v-if="form.isDirty && !creation" type="button" class="btn btn--sm btn--ghost" @click="form.reset()">Annuler</button>
                <button type="submit" class="btn btn--ink" :disabled="form.processing || (!creation && !form.isDirty)">
                    {{ form.processing ? 'Enregistrement…' : (creation ? 'Créer le brouillon' : 'Enregistrer') }}
                </button>
            </div>
        </form>
    </div>
</template>

<style scoped>
.pe { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); align-items: start; gap: 1rem; }
.pe__edition { display: grid; gap: 1rem; min-width: 0; }
.pe__champs { display: grid; gap: 1rem; }
.pe__titre { font-size: 1.15rem; font-weight: 700; }
.pe__opt { font-weight: 500; color: var(--text-3); }
.pe__chapeau { min-height: 0; }

.pe__adresse { display: flex; align-items: center; border: 1px solid var(--line-2); border-radius: var(--r-sm); background: var(--off); overflow: hidden; }
.pe__adresse span { padding: 0 .1rem 0 .8rem; font-size: .82rem; color: var(--text-3); white-space: nowrap; }
.pe__adresse .of-input { border: 0; border-radius: 0; background: var(--white); }
.pe__adresse .of-input:disabled { background: var(--off-2); color: var(--ink-3); }

.pe__pourquoi { margin: 0 0 1rem; font-size: .84rem; font-weight: 600; color: var(--terre-700); }
.pe__note { margin-bottom: 1rem; padding: .8rem 1rem; border: 1px dashed var(--line-2); border-radius: var(--r-sm); background: var(--white); }
.pe__note p:last-child { margin: .25rem 0 0; font-size: .88rem; color: var(--ink); }

.pe__texte { overflow: hidden; }
.pe__outils { display: flex; flex-wrap: wrap; gap: .3rem; padding: .55rem; border-bottom: 1px solid var(--line); background: var(--off); }
.pe__outil {
    min-height: 2.3rem;
    padding: 0 .7rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-xs);
    background: var(--white);
    font: inherit;
    font-size: .78rem;
    font-weight: 700;
    color: var(--ink);
    cursor: pointer;
}
.pe__outil:hover { border-color: var(--ink); }
.pe__outil:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 1px; }
.pe__zone {
    display: block;
    width: 100%;
    min-height: 32rem;
    padding: 1rem 1.15rem;
    border: 0;
    resize: vertical;
    font-family: ui-monospace, 'SF Mono', Menlo, Consolas, monospace;
    font-size: .88rem;
    line-height: 1.7;
    color: var(--ink);
    background: var(--white);
}
.pe__zone:focus-visible { outline: 2px solid var(--terre-500); outline-offset: -2px; }
.pe__astuce { margin: 0; padding: .6rem 1.15rem .8rem; border-top: 1px solid var(--line); font-size: .76rem; line-height: 1.5; color: var(--text-3); }
.pe__astuce strong { font-family: ui-monospace, Menlo, monospace; color: var(--ink); }
.pe__err { padding: 0 1.15rem .8rem; }

.pe__danger { border-style: dashed; }
.pe__confirm { display: flex; gap: .4rem; }
.pe__systeme { margin: 0; }

/* L'aperçu : une feuille blanche, avec la typographie de la page publiée. */
.pe__apercu { position: sticky; top: 1.25rem; display: grid; gap: .5rem; min-width: 0; }
.pe__apercu-barre { display: flex; align-items: center; justify-content: space-between; }
.pe__etat { font-size: .74rem; color: var(--text-3); }
.pe__etat.is-on { color: var(--terre-600); }
.pe__feuille {
    max-height: calc(100vh - 6rem);
    overflow-y: auto;
    padding: clamp(1.25rem, 3vw, 2.25rem);
    border: 1px solid var(--line);
    border-radius: var(--r-md);
    background: var(--white);
    box-shadow: var(--sh-1);
}
.pe__h1 { margin: 0; font-size: 1.8rem; font-weight: 800; letter-spacing: -.035em; line-height: 1.1; color: var(--ink); }
.pe__lede { margin: .7rem 0 0; font-size: 1.02rem; line-height: 1.6; color: var(--text-2); }
.pe__sommaire { margin: 1.25rem 0 0; padding-left: .9rem; border-left: 2px solid var(--line); }
.pe__sommaire ol { margin: .3rem 0 0; padding-left: 1.1rem; font-size: .84rem; color: var(--text-2); }

.pe__rendu { margin-top: 1.5rem; font-size: .96rem; line-height: 1.75; color: var(--text-2); }
.pe__rendu :deep(h2) { margin: 2rem 0 .6rem; font-size: 1.2rem; font-weight: 800; letter-spacing: -.02em; color: var(--ink); }
.pe__rendu :deep(h2:first-child) { margin-top: 0; }
.pe__rendu :deep(h3) { margin: 1.5rem 0 .4rem; font-size: 1rem; font-weight: 800; color: var(--ink); }
.pe__rendu :deep(p) { margin: 0 0 .9rem; }
.pe__rendu :deep(strong) { color: var(--ink); }
.pe__rendu :deep(ul), .pe__rendu :deep(ol) { padding-left: 1.3rem; }
.pe__rendu :deep(li::marker) { color: var(--terre-500); font-weight: 700; }
.pe__rendu :deep(a) { color: var(--terre-600); font-weight: 600; }
.pe__rendu :deep(blockquote) { margin: 1rem 0; padding: .6rem 1rem; border-left: 3px solid var(--terre-500); background: var(--terre-050); }

.pe__barre {
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
.pe__barre.is-dirty { border-color: var(--terre-300); }
.pe__statut { flex: 1; margin: 0; font-size: .84rem; color: var(--text-2); }
.pe__barre.is-dirty .pe__statut { font-weight: 700; color: var(--terre-700); }

@media (max-width: 1100px) {
    .pe { grid-template-columns: minmax(0, 1fr); }
    .pe__apercu { position: static; }
    .pe__feuille { max-height: none; }
}
</style>
