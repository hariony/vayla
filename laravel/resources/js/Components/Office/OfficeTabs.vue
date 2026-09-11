<script setup>
/**
 * Les onglets d'une liste, **avec leur compte**.
 *
 * Un onglet sans chiffre oblige à cliquer pour savoir s'il y a quelque chose
 * derrière ; avec, on sait où aller avant de bouger. Ce sont des liens, pas des
 * boutons : le filtre vit dans l'adresse, il se partage et survit au retour
 * arrière — la règle du catalogue public.
 *
 * La recherche en cours est gardée d'un onglet à l'autre : chercher « Rabe »
 * puis passer de « À vérifier » à « En ligne » ne doit pas l'effacer.
 */
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

import { nombre } from '@/Support/format.js'

const props = defineProps({
    /** `[{ cle, label, nombre }]` */
    onglets: { type: Array, required: true },
    actif: { type: String, required: true },
    /** Le chemin de la liste. */
    base: { type: String, required: true },
    /** Le nom du paramètre d'adresse. */
    param: { type: String, required: true },
    /** L'onglet qui n'a pas besoin de paramètre. */
    defaut: { type: String, default: '' },
    q: { type: String, default: '' },
})

const href = (cle) => {
    const params = new URLSearchParams()

    if (cle !== props.defaut) params.set(props.param, cle)
    if (props.q) params.set('q', props.q)

    const chaine = params.toString()

    return chaine ? `${props.base}?${chaine}` : props.base
}

const liste = computed(() => props.onglets)
</script>

<template>
    <nav class="ot" aria-label="Filtrer la liste" data-reveal>
        <Link
            v-for="o in liste"
            :key="o.cle"
            :href="href(o.cle)"
            class="ot__o"
            :class="{ 'is-on': o.cle === actif }"
            :aria-current="o.cle === actif ? 'page' : undefined"
            preserve-scroll
        >
            {{ o.label }}
            <span v-if="o.nombre !== undefined" class="ot__n of-num">{{ nombre(o.nombre) }}</span>
        </Link>
    </nav>
</template>

<style scoped>
.ot {
    display: flex;
    gap: .3rem;
    margin-bottom: 1rem;
    padding: .25rem;
    overflow-x: auto;
    border: 1px solid var(--line);
    border-radius: var(--r-pill);
    background: var(--white);
    scrollbar-width: none;
    width: fit-content;
    max-width: 100%;
}
.ot::-webkit-scrollbar { display: none; }

.ot__o {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    flex: none;
    min-height: 2.4rem;
    padding: .35rem .95rem;
    border-radius: var(--r-pill);
    font-size: .84rem;
    font-weight: 600;
    color: var(--text-2);
    text-decoration: none;
    white-space: nowrap;
    transition: background-color .2s var(--ease), color .2s var(--ease);
}
.ot__o:hover { background: var(--off-2); color: var(--ink); }
.ot__o:focus-visible { outline: 2px solid var(--terre-500); outline-offset: -2px; }

/* L'onglet où l'on est prend l'encre pleine : la terre est gardée aux gestes
   qui font avancer le travail. */
.ot__o.is-on { background: var(--ink); color: var(--white); font-weight: 700; }

.ot__n {
    min-width: 1.3rem;
    padding: .05rem .38rem;
    border-radius: var(--r-pill);
    background: var(--off-2);
    font-size: .72rem;
    font-weight: 800;
    text-align: center;
    color: var(--text-2);
}
.ot__o.is-on .ot__n { background: rgba(255, 255, 255, .16); color: var(--white); }
</style>
