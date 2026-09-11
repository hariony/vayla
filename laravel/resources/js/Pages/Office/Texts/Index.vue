<script setup>
/**
 * Les textes du site : l'accueil, section par section, et le pied de page.
 *
 * **L'original ne se perd jamais.** Un texte modifié le dit (« modifié »), et
 * « Rétablir l'original » le remet d'un geste : l'original vit dans le code
 * (`SiteTextCatalog`), la base ne garde que ce qui a été réécrit.
 *
 * **Chaque texte porte sa borne, et sa raison sous le champ** — la ligne
 * soulignée de l'accueil déborde d'un téléphone au-delà de vingt caractères.
 * Le compteur passe à la terre à l'approche de la limite, pas après.
 *
 * **Un groupe s'enregistre d'un bouton**, et se relit sur le site d'un lien
 * qui ouvre la section concernée. Mise en forme permise : `**gras**` et le
 * retour à la ligne — tout le reste est affiché tel quel, jamais interprété.
 */
import { reactive, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'

import OfficeShell from '@/Components/OfficeShell.vue'
import OfficeHead from '@/Components/Office/OfficeHead.vue'
import OfficeIcon from '@/Components/OfficeIcon.vue'
import { useOfficeMotion } from '@/Composables/useOfficeMotion.js'
import { ilYA } from '@/Support/format.js'

defineOptions({ layout: OfficeShell })

const props = defineProps({
    groupes: { type: Array, required: true },
})

const racine = ref(null)
useOfficeMotion(racine)

// Une copie de travail par groupe : ce qu'on tape, et ce qui est enregistré.
const brouillons = reactive(Object.fromEntries(props.groupes.map((g) => [g.cle, Object.fromEntries(g.textes.map((t) => [t.cle, t.valeur]))])))
const erreurs = ref({})
const envoi = ref(null)

const enregistre = (g, t) => props.groupes.find((x) => x.cle === g.cle).textes.find((x) => x.cle === t.cle).valeur
const change = (g) => g.textes.some((t) => brouillons[g.cle][t.cle] !== enregistre(g, t))

const retablir = (g, t) => { brouillons[g.cle][t.cle] = t.defaut }
const annuler = (g) => g.textes.forEach((t) => { brouillons[g.cle][t.cle] = enregistre(g, t) })

const enregistrer = (g) => {
    envoi.value = g.cle
    router.post('/textes', { groupe: g.cle, textes: { ...brouillons[g.cle] } }, {
        preserveScroll: true,
        onError: (e) => { erreurs.value = e },
        onSuccess: () => {
            erreurs.value = {}
            // Les props sont revenues : la copie de travail repart de ce qui
            // est maintenant enregistré.
            props.groupes.forEach((x) => x.textes.forEach((t) => { brouillons[x.cle][t.cle] = t.valeur }))
        },
        onFinish: () => { envoi.value = null },
    })
}

const presque = (t, v) => (v?.length ?? 0) > t.max * 0.9
</script>

<template>
    <Head title="Textes du site — Back-office" />

    <div ref="racine">
        <OfficeHead kicker="Contenu" titre="Textes du site">
            <template #lede>
                Les textes de l'accueil et du pied de page. Enregistrés, ils sont en ligne aussitôt ; l'original reste à un clic.
                Mise en forme permise : <span class="of-num">**gras**</span> et le retour à la ligne.
            </template>
        </OfficeHead>

        <section v-for="g in groupes" :id="g.cle" :key="g.cle" class="of-card tx" :class="{ 'is-dirty': change(g) }" data-reveal>
            <header class="of-card__h tx__h">
                <h2 class="of-card__t">{{ g.titre }}</h2>
                <a :href="g.lien" target="_blank" rel="noopener" class="of-card__lien tx__voir">Voir sur le site <OfficeIcon name="externe" /></a>
            </header>

            <p v-if="g.note" class="tx__note">{{ g.note }}</p>

            <div class="of-card__b tx__champs">
                <div v-for="t in g.textes" :key="t.cle" class="of-field tx__champ" :class="{ 'tx__champ--large': t.type === 'texte' }">
                    <div class="tx__tete">
                        <label class="of-label" :for="t.cle">{{ t.label }}</label>
                        <span v-if="t.modifie && brouillons[g.cle][t.cle] === t.valeur" class="of-chip of-chip--attente">Modifié {{ ilYA(t.le) }}</span>
                        <span v-else-if="brouillons[g.cle][t.cle] !== enregistre(g, t)" class="of-chip">Non enregistré</span>
                    </div>

                    <input v-if="t.type === 'ligne'" :id="t.cle" v-model="brouillons[g.cle][t.cle]" class="of-input" :maxlength="t.max">
                    <textarea v-else :id="t.cle" v-model="brouillons[g.cle][t.cle]" class="of-input" :rows="t.type === 'titre' ? 2 : 3" :maxlength="t.max" />

                    <div class="tx__pied">
                        <p v-if="t.aide" class="of-help">{{ t.aide }}</p>
                        <span class="tx__compte of-num" :class="{ 'is-presque': presque(t, brouillons[g.cle][t.cle]) }">{{ brouillons[g.cle][t.cle]?.length ?? 0 }} / {{ t.max }}</span>
                    </div>

                    <p v-if="brouillons[g.cle][t.cle] !== t.defaut" class="tx__origine">
                        <span>Original : « {{ t.defaut }} »</span>
                        <button type="button" class="tx__retablir" @click="retablir(g, t)">Rétablir l'original</button>
                    </p>

                    <p v-if="erreurs[`textes.${t.cle}`]" class="of-err">{{ erreurs[`textes.${t.cle}`] }}</p>
                </div>
            </div>

            <footer class="tx__barre">
                <p class="tx__etat" role="status">{{ change(g) ? 'Des modifications ne sont pas enregistrées.' : 'Tout est enregistré.' }}</p>
                <button v-if="change(g)" type="button" class="btn btn--sm btn--ghost" @click="annuler(g)">Annuler</button>
                <button type="button" class="btn btn--sm btn--terre" :disabled="!change(g) || envoi === g.cle" @click="enregistrer(g)">
                    {{ envoi === g.cle ? 'Enregistrement…' : 'Enregistrer et publier' }}
                </button>
            </footer>
        </section>
    </div>
</template>

<style scoped>
.tx { margin-bottom: 1rem; transition: border-color .2s var(--ease); }
.tx.is-dirty { border-color: var(--terre-300); }
.tx__h { align-items: center; }
.tx__voir { display: inline-flex; align-items: center; gap: .3rem; }
.tx__voir .oi { width: .9rem; height: .9rem; }
.tx__note { margin: 0; padding: .6rem 1.15rem; border-bottom: 1px solid var(--line); background: var(--off); font-size: .8rem; line-height: 1.5; color: var(--text-2); }

.tx__champs { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 22rem), 1fr)); gap: 1.1rem 1.5rem; }
.tx__champ--large { grid-column: 1 / -1; }
.tx__tete { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .4rem; }
.tx__pied { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.tx__compte { flex: none; margin-left: auto; font-size: .72rem; font-weight: 700; color: var(--text-3); }
.tx__compte.is-presque { color: var(--terre-700); }

.tx__origine { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem .8rem; margin: 0; font-size: .76rem; color: var(--text-3); }
.tx__origine span { flex: 1 1 16rem; min-width: 0; }
.tx__retablir {
    min-height: 2.2rem;
    padding: 0 .75rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font: inherit;
    font-size: .74rem;
    font-weight: 700;
    color: var(--ink);
    cursor: pointer;
}
.tx__retablir:hover { border-color: var(--ink); }

.tx__barre { display: flex; align-items: center; gap: .5rem; padding: .7rem 1.15rem; border-top: 1px solid var(--line); background: var(--off); border-radius: 0 0 var(--r-md) var(--r-md); }
.tx__etat { flex: 1; margin: 0; font-size: .82rem; color: var(--text-2); }
.tx.is-dirty .tx__etat { font-weight: 700; color: var(--terre-700); }
</style>
