import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],

    /*
     * Le serveur de dev tourne dans le conteneur `node` : il doit écouter sur
     * toutes les interfaces, mais s'annoncer au navigateur en `localhost`.
     *
     * **5174 et non 5173, dedans comme dehors.** Une autre application tourne
     * sur la même machine et publie déjà 5173. Docker laissait alors partir
     * `vayla-node` sans publier son port : Vite écrivait quand même
     * `public/hot` avec `http://localhost:5173`, et la page allait chercher
     * ses modules chez **l'autre projet** — assets d'une application
     * étrangère, ou page blanche, sans qu'aucun message ne dise pourquoi.
     *
     * Le port est le même des deux côtés : publier `5174:5173` aurait fait
     * écrire `localhost:5173` dans `public/hot`, ce qui ramène au problème.
     */
    server: {
        host: '0.0.0.0',
        port: 5174,
        strictPort: true,
        origin: 'http://localhost:5174',
        cors: true,
        hmr: { host: 'localhost', protocol: 'ws', clientPort: 5174 },
        watch: { usePolling: true, interval: 400 },
    },
})
