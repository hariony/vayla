import '../css/app.scss'

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

const pages = import.meta.glob('./Pages/**/*.vue')

createInertiaApp({
    title: (title) => (title ? `${title} · Vayla` : 'Vayla'),

    resolve: (name) => {
        const chemin = `./Pages/${name}.vue`

        // En développement seulement : la liste de pages que Vite calcule pour
        // `import.meta.glob` peut être périmée — un fichier ajouté pendant que
        // le serveur tourne n'y entre pas toujours —, et la page restait
        // blanche sur « Page not found ». Le serveur de développement sert
        // n'importe quel fichier par son chemin : on va le chercher
        // directement, et on le dit dans la console. Le build, lui, calcule la
        // liste une fois pour toutes et n'a pas besoin de ce détour.
        if (import.meta.env.DEV && ! pages[chemin]) {
            console.warn(`[vayla] « ${chemin} » manque à la liste de pages de Vite : chargement direct. Si ça se répète, redémarrez Vite (make npm-dev).`)
            return import(/* @vite-ignore */ `/resources/js/Pages/${name}.vue`)
        }

        return resolvePageComponent(chemin, pages)
    },

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },

    progress: {
        color: '#12968A',
        showSpinner: false,
    },
})
