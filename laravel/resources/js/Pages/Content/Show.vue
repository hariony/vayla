<script setup>
/**
 * Une page éditoriale : « Comment ça marche », les tarifs, les pages légales.
 *
 * **Une page de lecture, pas une page de vente.** Le titre prend l'échelle
 * moyenne (`.display--md`), le texte une mesure de lecture — soixante-dix
 * caractères par ligne au plus, au-delà l'œil perd le début de la suivante —
 * et un sommaire accompagne les pages longues : les pages légales se
 * consultent, elles ne se lisent pas d'une traite.
 *
 * **Le contenu arrive en HTML déjà sûr** : écrit en Markdown depuis le
 * back-office, rendu côté serveur, HTML brut et liens `javascript:` retirés
 * (`PageRenderer::rendre`). C'est ce qui permet le `v-html` ici.
 *
 * La date de mise à jour est écrite : sur une page de conditions ou de tarifs,
 * « depuis quand ce texte vaut » fait partie du texte.
 */
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

import SiteFooter from '@/Components/SiteFooter.vue'
import SiteHeader from '@/Components/SiteHeader.vue'
import { formatLong } from '@/Composables/useStayDates.js'

const props = defineProps({
    page: { type: Object, required: true },
})

const avecSommaire = computed(() => props.page.sommaire.length >= 3)
</script>

<template>
    <Head :title="page.titre">
        <meta v-if="page.description" name="description" :content="page.description">
    </Head>

    <div class="page">
        <SiteHeader :search="false" />

        <main class="cp">
            <div class="shell">
                <header class="cp__tete">
                    <p v-if="page.groupe" class="eyebrow">{{ page.groupe }}</p>
                    <h1 class="display display--md cp__titre">{{ page.titre }}</h1>
                    <p v-if="page.accroche" class="lede cp__accroche">{{ page.accroche }}</p>
                    <p v-if="page.misAJour" class="cp__date">Mis à jour le {{ formatLong(page.misAJour) }}</p>
                </header>

                <div class="cp__corps" :class="{ 'cp__corps--sommaire': avecSommaire }">
                    <nav v-if="avecSommaire" class="cp__sommaire" aria-label="Sommaire">
                        <p class="cp__sommaire-t">Sur cette page</p>
                        <ol>
                            <li v-for="s in page.sommaire" :key="s.id"><a :href="`#${s.id}`">{{ s.label }}</a></li>
                        </ol>
                    </nav>

                    <!-- eslint-disable-next-line vue/no-v-html — HTML rendu et nettoyé par le serveur. -->
                    <article class="cp__texte" v-html="page.html" />
                </div>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>

<style scoped>
.page { overflow-x: clip; }

.cp { padding-block: calc(var(--header-h) + clamp(2rem, 5vw, 3.5rem)) clamp(3rem, 7vw, 5rem); }

.cp__tete { max-width: 46rem; margin-bottom: clamp(1.75rem, 4vw, 2.75rem); }
.cp__titre { margin: .4rem 0 0; }
.cp__accroche { margin: .9rem 0 0; }
.cp__date { margin: .9rem 0 0; font-size: .82rem; color: var(--text-3); }

.cp__corps { display: grid; grid-template-columns: minmax(0, 44rem); gap: 3rem; }
.cp__corps--sommaire { grid-template-columns: minmax(0, 44rem) minmax(12rem, 16rem); }

/* Le sommaire à droite, collé : il suit la lecture sans la couper. */
.cp__sommaire { order: 2; position: sticky; top: calc(var(--header-h) + 1.5rem); align-self: start; padding-left: 1.1rem; border-left: 2px solid var(--line); }
.cp__sommaire-t { margin: 0 0 .5rem; font-size: .72rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--text-3); }
.cp__sommaire ol { display: grid; gap: .2rem; margin: 0; padding: 0; list-style: none; }
.cp__sommaire a { display: block; padding: .3rem 0; font-size: .86rem; font-weight: 600; line-height: 1.35; color: var(--text-2); text-decoration: none; }
.cp__sommaire a:hover { color: var(--terre-600); }

/* ── Le texte ─────────────────────────────────────────────────────────── */
.cp__texte { font-size: 1.02rem; line-height: 1.75; color: var(--text-2); }
.cp__texte :deep(h2) {
    margin: 2.4rem 0 .7rem;
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -.025em;
    line-height: 1.25;
    color: var(--ink);
    scroll-margin-top: calc(var(--header-h) + 1rem);
}
.cp__texte :deep(h2:first-child) { margin-top: 0; }
.cp__texte :deep(h3) { margin: 1.8rem 0 .5rem; font-size: 1.05rem; font-weight: 800; letter-spacing: -.015em; color: var(--ink); }
.cp__texte :deep(p) { margin: 0 0 1rem; }
.cp__texte :deep(strong) { font-weight: 700; color: var(--ink); }
.cp__texte :deep(ul), .cp__texte :deep(ol) { margin: 0 0 1.1rem; padding-left: 1.3rem; }
.cp__texte :deep(li) { margin-bottom: .45rem; }
.cp__texte :deep(li::marker) { color: var(--terre-500); font-weight: 700; }
.cp__texte :deep(a) { font-weight: 600; color: var(--terre-600); text-decoration: underline; text-underline-offset: .2em; }
.cp__texte :deep(blockquote) { margin: 1.2rem 0; padding: .7rem 1.1rem; border-left: 3px solid var(--terre-500); background: var(--terre-050); border-radius: 0 var(--r-sm) var(--r-sm) 0; color: var(--ink); }
.cp__texte :deep(blockquote p:last-child) { margin-bottom: 0; }
.cp__texte :deep(hr) { margin: 2rem 0; border: 0; border-top: 1px solid var(--line); }

@media (max-width: 980px) {
    .cp__corps, .cp__corps--sommaire { grid-template-columns: minmax(0, 1fr); gap: 1.5rem; }
    .cp__sommaire { order: 0; position: static; }
}
</style>
