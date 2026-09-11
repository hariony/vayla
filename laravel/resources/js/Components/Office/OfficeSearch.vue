<script setup>
/**
 * La recherche d'une liste. **Elle part à la validation, pas à chaque touche** :
 * une recherche qui recharge la liste pendant qu'on tape déplace les lignes sous
 * le curseur au moment où l'on s'apprête à cliquer. Le bouton est visible —
 * un champ seul ne dit pas comment lancer la recherche à qui n'a pas le
 * réflexe de la touche Entrée.
 */
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

import OfficeIcon from '@/Components/OfficeIcon.vue'

const props = defineProps({
    base: { type: String, required: true },
    q: { type: String, default: '' },
    /** Les autres paramètres de la liste, gardés pendant la recherche. */
    params: { type: Object, default: () => ({}) },
    placeholder: { type: String, default: 'Rechercher…' },
    label: { type: String, default: 'Rechercher dans la liste' },
})

const saisie = ref(props.q)
watch(() => props.q, (v) => { saisie.value = v })

const chercher = (valeur = saisie.value) => {
    const q = valeur.trim()

    router.get(props.base, { ...props.params, ...(q ? { q } : {}) }, { preserveState: true, preserveScroll: true, replace: true })
}

const effacer = () => {
    saisie.value = ''
    chercher('')
}
</script>

<template>
    <form class="os" role="search" data-reveal @submit.prevent="chercher()">
        <label class="sr-only" for="of-recherche">{{ label }}</label>
        <span class="os__ico" aria-hidden="true"><OfficeIcon name="recherche" /></span>
        <input
            id="of-recherche"
            v-model="saisie"
            class="os__input"
            type="search"
            :placeholder="placeholder"
            autocomplete="off"
            enterkeyhint="search"
        >
        <button v-if="q" type="button" class="os__effacer" @click="effacer">Effacer</button>
        <button type="submit" class="btn btn--sm btn--ink os__go">Chercher</button>
    </form>
</template>

<style scoped>
.os {
    position: relative;
    display: flex;
    align-items: center;
    gap: .4rem;
    width: 100%;
    max-width: 30rem;
    margin-bottom: 1rem;
    padding: .25rem .25rem .25rem 2.6rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    transition: border-color .2s var(--ease);
}
.os:focus-within { border-color: var(--ink); }

.os__ico { position: absolute; left: .95rem; top: 50%; display: grid; color: var(--text-3); transform: translateY(-50%); }
.os__ico .oi { width: 1.05rem; height: 1.05rem; }

.os__input {
    flex: 1;
    min-width: 0;
    min-height: 2.4rem;
    border: 0;
    background: transparent;
    font: inherit;
    font-size: .92rem;
    color: var(--ink);
    outline: none;
}
.os__input::-webkit-search-cancel-button { display: none; }

.os__effacer {
    flex: none;
    min-height: 2.2rem;
    padding: 0 .6rem;
    border: 0;
    border-radius: var(--r-pill);
    background: none;
    font: inherit;
    font-size: .8rem;
    font-weight: 700;
    color: var(--text-2);
    cursor: pointer;
}
.os__effacer:hover { background: var(--off-2); color: var(--ink); }
.os__go { flex: none; min-height: 2.4rem; }
</style>
