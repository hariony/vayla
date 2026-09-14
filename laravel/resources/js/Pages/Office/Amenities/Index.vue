<script setup>
/**
 * Le vocabulaire des équipements — ce que les propriétaires cochent, et ce
 * que le panneau « Ce qui tient quand la ville lâche » lit.
 *
 * **Ajouter une ligne est une opération de donnée** : la liste s'allonge au
 * contact des logements réels, sans toucher au code. Ce qui ne bouge pas :
 *
 * - **les onze rubriques**, un enum — le sens d'une rubrique ne glisse pas sous
 *   les annonces déjà remplies ;
 * - **la clé**, qui est un filtre de recherche et un mot de l'API mobile ;
 * - **un équipement coché ne se supprime pas** : ce serait effacer en silence
 *   la déclaration de propriétaires. On le renomme.
 *
 * Le pictogramme se choisit parmi ceux qui sont dessinés : un nom inventé
 * n'aurait pas de tracé. « Dans la recherche » donne une case au filtre du
 * catalogue — personne ne cherche un logement par grille-pain.
 */
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'

import AmenityIcon from '@/Components/AmenityIcon.vue'
import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    rubriques: { type: Array, required: true },
    icones: { type: Array, required: true },
    total: { type: Number, default: 0 },
})

const racine = ref(null)
useOfficeMotion(racine)

const edition = ref(null)
const form = useForm({ label: '', group: '', icon: 'dot', filterable: false })

const modifier = (x) => {
    edition.value = x.id
    form.defaults({ label: x.label, group: x.group, icon: x.icon, filterable: x.filterable })
    form.reset()
    form.clearErrors()
}
const enregistrer = (x) => form.post(`/equipements/${x.id}`, { preserveScroll: true, onSuccess: () => { edition.value = null } })

const ajoutDans = ref(null)
const ajout = useForm({ label: '', group: '', icon: 'dot', filterable: false })
const ouvrirAjout = (g) => {
    ajoutDans.value = g.value
    ajout.reset()
    ajout.group = g.value
}
const ajouter = () => ajout.post('/equipements', { preserveScroll: true, onSuccess: () => { ajoutDans.value = null; ajout.reset() } })

const deplacer = (x, sens) => router.post(`/equipements/${x.id}/deplacer`, { sens }, { preserveScroll: true })

const aSupprimer = ref(null)
const supprimer = (x) => router.post(`/equipements/${x.id}/supprimer`, {}, { preserveScroll: true, onFinish: () => { aSupprimer.value = null } })
</script>

<template>
    <Head title="Équipements — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Contenu" titre="Équipements">
            <template #lede>
                <span class="of-num">{{ total }}</span> équipements en onze rubriques. Ce que les propriétaires cochent sur leur fiche —
                et ce qui décide, ici, d'un séjour : le groupe électrogène, la moustiquaire, la piste en 4×4.
            </template>
        </OfficeHead>

        <section v-for="g in rubriques" :key="g.value" class="of-card am__rub" data-reveal :aria-labelledby="`r-${g.value}`">
            <header class="of-card__h">
                <h2 :id="`r-${g.value}`" class="of-card__t">{{ g.label }} <span class="am__n of-num">{{ g.equipements.length }}</span></h2>
                <button type="button" class="btn btn--sm btn--outline" @click="ouvrirAjout(g)"><OfficeIcon name="plus" /> Ajouter</button>
            </header>

            <ol class="am__liste">
                <li v-for="(x, i) in g.equipements" :key="x.id">
                    <div v-if="edition !== x.id" class="of-row am__row">
                        <span class="am__fleches">
                            <button type="button" class="am__fl" :disabled="i === 0" aria-label="Monter" @click="deplacer(x, 'haut')"><OfficeIcon name="haut" /></button>
                            <button type="button" class="am__fl" :disabled="i === g.equipements.length - 1" aria-label="Descendre" @click="deplacer(x, 'bas')"><OfficeIcon name="bas" /></button>
                        </span>
                        <AmenityIcon :name="x.icon" class="am__ico" />
                        <span class="am__txt">
                            <span class="of-row__t">{{ x.label }}</span>
                            <span class="of-row__s"><span class="of-num">{{ x.key }}</span> · {{ x.listings ? `coché par ${x.listings} logement${x.listings > 1 ? 's' : ''}` : 'coché par aucun logement' }}</span>
                        </span>
                        <span class="am__puces"><span v-if="x.filterable" class="of-chip of-chip--actif">Dans la recherche</span></span>
                        <span class="am__gestes">
                            <button type="button" class="btn btn--sm btn--outline" @click="modifier(x)">Modifier</button>
                            <template v-if="!x.listings">
                                <button v-if="aSupprimer !== x.id" type="button" class="btn btn--sm btn--outline" @click="aSupprimer = x.id">Supprimer</button>
                                <button v-else type="button" class="btn btn--sm btn--ink" @click="supprimer(x)">Confirmer</button>
                            </template>
                        </span>
                    </div>

                    <form v-else class="am__form" @submit.prevent="enregistrer(x)">
                        <div class="am__champs">
                            <div class="of-field">
                                <label class="of-label" :for="`l-${x.id}`">Libellé</label>
                                <input :id="`l-${x.id}`" v-model="form.label" class="of-input" maxlength="60" required>
                                <p v-if="form.errors.label" class="of-err" role="alert">{{ form.errors.label }}</p>
                            </div>
                            <div class="of-field">
                                <label class="of-label" :for="`g-${x.id}`">Rubrique</label>
                                <select :id="`g-${x.id}`" v-model="form.group" class="of-input">
                                    <option v-for="r in rubriques" :key="r.value" :value="r.value">{{ r.label }}</option>
                                </select>
                            </div>
                        </div>
                        <fieldset class="am__icones">
                            <legend class="of-label">Pictogramme</legend>
                            <button v-for="ic in icones" :key="ic" type="button" class="am__choix" :class="{ 'is-on': form.icon === ic }" :aria-pressed="form.icon === ic" :title="ic" @click="form.icon = ic">
                                <AmenityIcon :name="ic" /><span class="sr-only">{{ ic }}</span>
                            </button>
                        </fieldset>
                        <label class="am__case"><input v-model="form.filterable" type="checkbox"> Une case dans la recherche du catalogue</label>
                        <div class="am__boutons">
                            <button type="submit" class="btn btn--sm btn--ink" :disabled="form.processing">Enregistrer</button>
                            <button type="button" class="btn btn--sm btn--ghost" @click="edition = null">Annuler</button>
                        </div>
                    </form>
                </li>
            </ol>

            <form v-if="ajoutDans === g.value" class="am__form am__form--ajout" @submit.prevent="ajouter">
                <p class="of-kicker">Nouvel équipement — {{ g.label }}</p>
                <div class="of-field">
                    <label class="of-label" :for="`n-${g.value}`">Libellé</label>
                    <input :id="`n-${g.value}`" v-model="ajout.label" class="of-input" maxlength="60" required>
                    <p class="of-help">Sa clé naîtra du libellé, puis ne changera plus. Il arrive en bas de la rubrique.</p>
                    <p v-if="ajout.errors.label" class="of-err" role="alert">{{ ajout.errors.label }}</p>
                </div>
                <fieldset class="am__icones">
                    <legend class="of-label">Pictogramme</legend>
                    <button v-for="ic in icones" :key="ic" type="button" class="am__choix" :class="{ 'is-on': ajout.icon === ic }" :aria-pressed="ajout.icon === ic" :title="ic" @click="ajout.icon = ic">
                        <AmenityIcon :name="ic" /><span class="sr-only">{{ ic }}</span>
                    </button>
                </fieldset>
                <label class="am__case"><input v-model="ajout.filterable" type="checkbox"> Une case dans la recherche du catalogue</label>
                <div class="am__boutons">
                    <button type="submit" class="btn btn--sm btn--ink" :disabled="ajout.processing || !ajout.label.trim()">Ajouter</button>
                    <button type="button" class="btn btn--sm btn--ghost" @click="ajoutDans = null">Annuler</button>
                </div>
            </form>

            <p v-if="!g.equipements.length && ajoutDans !== g.value" class="of-vide">Aucun équipement dans cette rubrique.</p>
        </section>
    </div>
</template>

<style scoped>
.am__rub { margin-bottom: .9rem; }
.am__n { margin-left: .3rem; font-size: .8rem; font-weight: 700; color: var(--text-3); }
.am__rub .of-card__h { align-items: center; }
.am__rub .btn .oi { width: .95rem; height: .95rem; }

.am__liste { margin: 0; padding: 0; list-style: none; }
.am__liste > li + li { border-top: 1px solid var(--line); }
.am__liste .of-row { border-top: 0; min-height: 3.1rem; padding-block: .45rem; }
.am__row { grid-template-columns: auto auto minmax(0, 1fr) auto auto; }
.am__fleches { display: flex; gap: .2rem; }
.am__fl { display: grid; place-items: center; width: 2.3rem; height: 2.3rem; border: 1px solid var(--line-2); border-radius: var(--r-xs); background: var(--white); color: var(--ink); cursor: pointer; }
.am__fl .oi { width: .95rem; height: .95rem; }
.am__fl:hover:not(:disabled) { border-color: var(--ink); }
.am__fl:disabled { background: var(--off-2); color: var(--ink-3); cursor: not-allowed; }
.am__ico { width: 1.3rem; height: 1.3rem; color: var(--ink); }
.am__txt { min-width: 0; }
.am__gestes { display: flex; gap: .3rem; }

.am__form { display: grid; gap: .9rem; padding: 1rem 1.15rem; background: var(--off); }
.am__form--ajout { border-top: 1px solid var(--line); }
.am__champs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
.am__icones { display: flex; flex-wrap: wrap; gap: .3rem; max-height: 9.5rem; margin: 0; padding: 0; overflow-y: auto; border: 0; }
.am__icones legend { width: 100%; margin-bottom: .35rem; }
.am__choix { display: grid; place-items: center; width: 2.75rem; height: 2.75rem; border: 1px solid var(--line-2); border-radius: var(--r-sm); background: var(--white); color: var(--text-2); cursor: pointer; }
.am__choix :deep(svg) { width: 1.25rem; height: 1.25rem; }
.am__choix:hover { border-color: var(--ink); }
.am__choix:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }
.am__choix.is-on { border-color: var(--ink); background: var(--ink); color: var(--white); }
.am__case { display: inline-flex; align-items: center; gap: .55rem; min-height: 2.5rem; font-size: .88rem; color: var(--ink); cursor: pointer; }
.am__case input { width: 1.15rem; height: 1.15rem; accent-color: var(--ink); }
.am__boutons { display: flex; gap: .4rem; }

@media (max-width: 760px) {
    .am__row { grid-template-columns: auto minmax(0, 1fr); }
    .am__ico { display: none; }
    .am__puces, .am__gestes { grid-column: 2; }
    .am__champs { grid-template-columns: minmax(0, 1fr); }
}
</style>
