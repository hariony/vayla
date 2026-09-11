<script setup>
/**
 * La barre d'étapes de la fiche logement.
 *
 * **Chaque étape se clique** : c'est ce qui rend l'assistant tenable. Un
 * assistant qu'on ne peut traverser que dans l'ordre est doux à la première
 * saisie et insupportable à la quinzième correction — on corrige un tarif dix
 * fois pour une création, et personne ne doit repasser par « L'essentiel »
 * pour y arriver.
 *
 * **Trois états, trois formes — pas trois nuances d'une même couleur :**
 *
 * - **l'étape où l'on est** : rond plein de terre, libellé à l'encre — la
 *   terre dit « vous êtes là », comme la rubrique active des espaces ;
 * - **une étape remplie** : rond d'encre coché. Pas de vert : l'exception du
 *   lagon est réservée à la coche du message de retour, et une barre d'étapes
 *   verte se lirait comme une échelle de vérification ;
 * - **une étape en erreur** : rond cerclé de terre portant « ! », libellé en
 *   terre foncée. Plein pour « ici », cerclé pour « à reprendre » : on ne les
 *   confond pas même en noir et blanc.
 *
 * **Les libellés sont écrits, toujours.** Sur un téléphone la rangée défile
 * plutôt que de réduire les étapes à des numéros — « 3 » ne dit pas « Tarif et
 * séjour » — et l'étape courante se ramène d'elle-même dans le champ de vue.
 */
import { nextTick, ref, watch } from 'vue'

const props = defineProps({
    /** `[{ cle, label, etat }]` — `etat` : `fait`, `erreur` ou `vide`. */
    etapes: { type: Array, required: true },
    courante: { type: Number, required: true },
})

const emit = defineEmits(['aller'])

const liste = ref(null)

// L'étape courante revient dans le champ de vue quand la rangée défile — sur
// un téléphone, l'étape 5 serait sinon hors de l'écran au moment où on y est.
watch(() => props.courante, async (i) => {
    await nextTick()
    liste.value?.querySelectorAll('[data-etape-btn]')[i]
        ?.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' })
})
</script>

<template>
    <nav class="st" aria-label="Étapes de la fiche">
        <ol ref="liste" class="st__liste">
            <li
                v-for="(e, i) in etapes"
                :key="e.cle"
                class="st__item"
                :class="[`is-${e.etat}`, { 'is-courante': i === courante }]"
            >
                <button
                    type="button"
                    class="st__btn"
                    :aria-current="i === courante ? 'step' : undefined"
                    data-etape-btn
                    @click="emit('aller', i)"
                >
                    <span class="st__rond" aria-hidden="true">
                        <svg v-if="e.etat === 'fait' && i !== courante" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6.5 12.4l3.6 3.6 7.4-8" />
                        </svg>
                        <template v-else-if="e.etat === 'erreur' && i !== courante">!</template>
                        <span v-else class="num">{{ i + 1 }}</span>
                    </span>
                    <span class="st__label">{{ e.label }}</span>
                    <!-- L'état en toutes lettres pour qui n'a pas le rond sous
                         les yeux. -->
                    <span class="sr-only">
                        <template v-if="e.etat === 'fait'"> — remplie</template>
                        <template v-else-if="e.etat === 'erreur'"> — à corriger</template>
                    </span>
                </button>
            </li>
        </ol>
    </nav>
</template>

<style scoped>
.st { margin-bottom: 1.25rem; }

.st__liste {
    display: flex;
    margin: 0;
    padding: 0;
    list-style: none;
    overflow-x: auto;
    scrollbar-width: none;
}
.st__liste::-webkit-scrollbar { display: none; }

/* Un filet relie les ronds : il dit que ce sont les pas d'un même chemin, pas
   cinq boutons indépendants. Il part du rond et va jusqu'au suivant. */
.st__item {
    position: relative;
    flex: 1 0 auto;
    min-width: 6.5rem;
}
.st__item:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 1.35rem;
    left: calc(50% + 1.1rem);
    right: calc(-50% + 1.1rem);
    height: 2px;
    border-radius: 2px;
    background: var(--line-2);
}
.st__item.is-fait:not(:last-child)::after { background: var(--ink); }

/* La cible entière — rond et libellé — se clique, sur 2,75 rem de haut au
   moins : on vise une étape au doigt, pas un rond de 28 px. */
.st__btn {
    display: grid;
    justify-items: center;
    gap: .4rem;
    width: 100%;
    min-height: 2.75rem;
    padding: .25rem .35rem .4rem;
    border: 0;
    border-radius: var(--r-md);
    background: none;
    font: inherit;
    cursor: pointer;
    transition: background-color .2s var(--ease);
}
.st__btn:hover { background: var(--off-2); }
.st__btn:focus-visible { outline: 2px solid var(--terre-500); outline-offset: 2px; }

.st__rond {
    position: relative;
    z-index: 1;
    display: grid;
    place-items: center;
    width: 2.2rem;
    height: 2.2rem;
    border: 2px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font-size: .84rem;
    font-weight: 800;
    color: var(--text-3);
    transition: background-color .25s var(--ease), border-color .25s var(--ease), color .25s var(--ease);
}
.st__rond svg { width: 1rem; height: 1rem; }

.st__label {
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: -.012em;
    white-space: nowrap;
    color: var(--text-3);
}

.st__item.is-fait .st__rond { border-color: var(--ink); background: var(--ink); color: var(--white); }
.st__item.is-fait .st__label { color: var(--text-2); }

.st__item.is-erreur .st__rond { border-color: var(--terre-500); color: var(--terre-600); }
.st__item.is-erreur .st__label { color: var(--terre-700); }

/* L'étape où l'on est, en dernier : elle l'emporte sur « remplie ». */
.st__item.is-courante .st__rond {
    border-color: var(--terre-500);
    background: var(--terre-500);
    color: var(--white);
    box-shadow: 0 0 0 4px rgba(201, 69, 42, .14);
}
.st__item.is-courante .st__label { color: var(--ink); font-weight: 800; }

@media (prefers-reduced-motion: reduce) {
    .st__rond, .st__btn { transition: none; }
}
</style>
