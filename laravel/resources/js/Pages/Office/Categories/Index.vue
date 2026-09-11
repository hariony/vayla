<script setup>
/**
 * Les catégories du rail « Les envies du moment ».
 *
 * **Le rail est une position qui se vend** — à un propriétaire, à un office de
 * tourisme. Son titre reste éditorial, jamais statistique ; et une catégorie
 * achetée porte « Sponsorisé » sur le site, en toutes lettres. L'ordre ici est
 * l'ordre du rail : on le règle avec deux flèches bordées, pas en glissant —
 * agréable à la souris, pénible au doigt, impossible au clavier.
 *
 * **« Tout » et « Séjour confirmé » ne sont pas des étiquettes** : l'un est le
 * rail sans filtre, l'autre se déduit du niveau 4. Ils se renomment et se
 * déplacent, mais ne se suppriment pas et ne se posent sur aucune annonce.
 *
 * **La clé ne change jamais** : c'est le filtre de l'adresse
 * (`/logements?category=mer`) et un mot de l'application mobile.
 */
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { ICONES_CATEGORIES } from '@/Support/categoryIcons.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    categories: { type: Array, required: true },
    icones: { type: Array, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

const edition = ref(null)
const form = useForm({ label: '', icon: 'sparkle', sponsored: false })

const modifier = (c) => {
    edition.value = c.id
    form.defaults({ label: c.label, icon: c.icon, sponsored: c.sponsored })
    form.reset()
    form.clearErrors()
}

const enregistrer = (c) => form.post(`/categories/${c.id}`, { preserveScroll: true, onSuccess: () => { edition.value = null } })

const ajout = useForm({ label: '', icon: 'sparkle', sponsored: false })
const ajouter = () => ajout.post('/categories', { preserveScroll: true, onSuccess: () => ajout.reset() })

const deplacer = (c, sens) => router.post(`/categories/${c.id}/deplacer`, { sens }, { preserveScroll: true })

const aSupprimer = ref(null)
const supprimer = (c) => router.post(`/categories/${c.id}/supprimer`, {}, { preserveScroll: true, onFinish: () => { aSupprimer.value = null } })
</script>

<template>
    <Head title="Catégories — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Contenu" titre="Catégories" lede="Le rail « Les envies du moment », dans l'ordre où il s'affiche. Un titre éditorial, jamais « les plus populaires » : ce serait un chiffre qu'on ne montre pas." />

        <!-- L'aperçu du rail, tel que le voyageur le verra. -->
        <div class="ca__apercu" data-reveal aria-hidden="true">
            <span v-for="c in categories" :key="c.id" class="ca__pastille">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path :d="ICONES_CATEGORIES[c.icon] ?? ICONES_CATEGORIES.sparkle" /></svg>
                {{ c.label }}
                <small v-if="c.sponsored">Sponsorisé</small>
            </span>
        </div>

        <section class="of-card" data-reveal aria-labelledby="ca-t">
            <header class="of-card__h"><h2 id="ca-t" class="of-card__t">L'ordre du rail</h2></header>
            <ol class="of-rows ca__liste">
                <li v-for="(c, i) in categories" :key="c.id" class="ca__ligne">
                    <div v-if="edition !== c.id" class="of-row ca__row">
                        <span class="ca__fleches">
                            <button type="button" class="ca__fl" :disabled="i === 0" aria-label="Monter" @click="deplacer(c, 'haut')"><OfficeIcon name="haut" /></button>
                            <button type="button" class="ca__fl" :disabled="i === categories.length - 1" aria-label="Descendre" @click="deplacer(c, 'bas')"><OfficeIcon name="bas" /></button>
                        </span>
                        <svg class="ca__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="ICONES_CATEGORIES[c.icon] ?? ICONES_CATEGORIES.sparkle" /></svg>
                        <span class="ca__txt">
                            <span class="of-row__t">{{ c.label }}</span>
                            <span class="of-row__s"><span class="of-num">{{ c.key }}</span> · {{ c.listings }} logement{{ c.listings > 1 ? 's' : '' }}<template v-if="c.structurelle"> · filtre du rail, pas une étiquette</template></span>
                        </span>
                        <span class="ca__puces">
                            <span v-if="c.sponsored" class="of-chip of-chip--attente">Sponsorisé</span>
                        </span>
                        <span class="ca__gestes">
                            <button type="button" class="btn btn--sm btn--outline" @click="modifier(c)">Modifier</button>
                            <template v-if="!c.structurelle">
                                <button v-if="aSupprimer !== c.id" type="button" class="btn btn--sm btn--outline" @click="aSupprimer = c.id">Supprimer</button>
                                <button v-else type="button" class="btn btn--sm btn--ink" @click="supprimer(c)">Confirmer</button>
                            </template>
                        </span>
                    </div>

                    <form v-else class="ca__edition" @submit.prevent="enregistrer(c)">
                        <div class="of-field">
                            <label class="of-label" :for="`l-${c.id}`">Libellé</label>
                            <input :id="`l-${c.id}`" v-model="form.label" class="of-input" maxlength="40" required>
                            <p v-if="form.errors.label" class="of-err">{{ form.errors.label }}</p>
                        </div>
                        <fieldset class="ca__icones">
                            <legend class="of-label">Pictogramme</legend>
                            <button v-for="ic in icones" :key="ic.value" type="button" class="ca__choix" :class="{ 'is-on': form.icon === ic.value }" :aria-pressed="form.icon === ic.value" :title="ic.label" @click="form.icon = ic.value">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="ICONES_CATEGORIES[ic.value]" /></svg>
                                <span class="sr-only">{{ ic.label }}</span>
                            </button>
                        </fieldset>
                        <label v-if="c.key !== 'verifie'" class="ca__case">
                            <input v-model="form.sponsored" type="checkbox">
                            Place achetée — « Sponsorisé » s'affichera sous le libellé
                        </label>
                        <p v-if="form.errors.sponsored" class="of-err">{{ form.errors.sponsored }}</p>
                        <div class="ca__boutons">
                            <button type="submit" class="btn btn--sm btn--ink" :disabled="form.processing">Enregistrer</button>
                            <button type="button" class="btn btn--sm btn--ghost" @click="edition = null">Annuler</button>
                        </div>
                    </form>
                </li>
            </ol>
        </section>

        <section class="of-card ca__ajout" data-reveal aria-labelledby="aj-t">
            <header class="of-card__h"><h2 id="aj-t" class="of-card__t">Ajouter une catégorie</h2></header>
            <form class="of-card__b ca__edition" @submit.prevent="ajouter">
                <div class="of-field">
                    <label class="of-label" for="nl">Libellé</label>
                    <input id="nl" v-model="ajout.label" class="of-input" maxlength="40" placeholder="Pieds dans l'eau" required>
                    <p class="of-help">Sa clé d'adresse naîtra du libellé, puis ne changera plus. Elle arrive au bout du rail.</p>
                    <p v-if="ajout.errors.label" class="of-err">{{ ajout.errors.label }}</p>
                </div>
                <fieldset class="ca__icones">
                    <legend class="of-label">Pictogramme</legend>
                    <button v-for="ic in icones" :key="ic.value" type="button" class="ca__choix" :class="{ 'is-on': ajout.icon === ic.value }" :aria-pressed="ajout.icon === ic.value" :title="ic.label" @click="ajout.icon = ic.value">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="ICONES_CATEGORIES[ic.value]" /></svg>
                        <span class="sr-only">{{ ic.label }}</span>
                    </button>
                </fieldset>
                <label class="ca__case"><input v-model="ajout.sponsored" type="checkbox"> Place achetée</label>
                <div class="ca__boutons">
                    <button type="submit" class="btn btn--sm btn--ink" :disabled="ajout.processing || !ajout.label.trim()">Ajouter au rail</button>
                </div>
            </form>
        </section>
    </div>
</template>

<style scoped>
.ca__apercu {
    display: flex;
    gap: .3rem;
    margin-bottom: 1rem;
    padding: .6rem .8rem;
    overflow-x: auto;
    border: 1px solid var(--line);
    border-radius: var(--r-md);
    background: var(--white);
    scrollbar-width: none;
}
.ca__pastille { display: grid; justify-items: center; gap: .2rem; flex: none; padding: .4rem .7rem; font-size: .72rem; font-weight: 700; color: var(--text-3); white-space: nowrap; }
.ca__pastille svg { width: 1.3rem; height: 1.3rem; }
.ca__pastille small { font-size: .56rem; letter-spacing: .06em; text-transform: uppercase; }

.ca__liste > li + li { border-top: 1px solid var(--line); }
.ca__liste .of-row { border-top: 0; }
.ca__row { grid-template-columns: auto auto minmax(0, 1fr) auto auto; }
.ca__fleches { display: flex; gap: .2rem; }
.ca__fl {
    display: grid;
    place-items: center;
    width: 2.4rem;
    height: 2.4rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-xs);
    background: var(--white);
    color: var(--ink);
    cursor: pointer;
}
.ca__fl .oi { width: 1rem; height: 1rem; }
.ca__fl:hover:not(:disabled) { border-color: var(--ink); }
.ca__fl:disabled { background: var(--off-2); color: var(--ink-3); cursor: not-allowed; }
.ca__ico { width: 1.4rem; height: 1.4rem; color: var(--ink); }
.ca__txt { min-width: 0; }
.ca__gestes { display: flex; gap: .3rem; }

.ca__edition { display: grid; gap: .9rem; padding: 1rem 1.15rem; background: var(--off); }
.ca__ajout .ca__edition { background: none; }
.ca__icones { display: flex; flex-wrap: wrap; gap: .35rem; margin: 0; padding: 0; border: 0; }
.ca__icones legend { width: 100%; margin-bottom: .35rem; }
.ca__choix {
    display: grid;
    place-items: center;
    width: 2.75rem;
    height: 2.75rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-sm);
    background: var(--white);
    color: var(--text-2);
    cursor: pointer;
}
.ca__choix svg { width: 1.3rem; height: 1.3rem; }
.ca__choix:hover { border-color: var(--ink); }
.ca__choix:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }
.ca__choix.is-on { border-color: var(--ink); background: var(--ink); color: var(--white); }
.ca__case { display: inline-flex; align-items: center; gap: .55rem; min-height: 2.5rem; font-size: .88rem; color: var(--ink); cursor: pointer; }
.ca__case input { width: 1.15rem; height: 1.15rem; accent-color: var(--ink); }
.ca__boutons { display: flex; gap: .4rem; }
.ca__ajout { margin-top: 1rem; }

@media (max-width: 720px) {
    .ca__row { grid-template-columns: auto minmax(0, 1fr); }
    .ca__ico { display: none; }
    .ca__puces, .ca__gestes { grid-column: 2; }
}
</style>
