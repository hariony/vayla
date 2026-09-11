<script setup>
/**
 * Les photos d'une annonce.
 *
 * **La première photo est la couverture, et c'est écrit.** Il n'y a pas de
 * bouton « définir comme couverture » séparé : la position 0 du pivot **est**
 * la couverture, et une colonne à côté aurait permis qu'une couverture
 * n'appartienne pas à la galerie. On réordonne, donc, et on le dit.
 *
 * **Le réordonnancement se fait par deux flèches, pas par glisser-déposer.**
 * Le glisser-déposer est agréable à la souris et pénible au doigt, et
 * inaccessible au clavier. Deux boutons bordés à 2,75 rem se comprennent sans
 * qu'on les explique — c'est la même règle que les flèches du calendrier.
 *
 * **Un refus dit pourquoi.** « Cette photo fait 900 pixels de large, il en
 * faut 1200 » se corrige ; « fichier invalide » se subit. Le message vient du
 * serveur, qui seul connaît la taille réelle.
 */
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'

import { photoSrc, photoSrcset } from '@/Support/photo.js'
import { preparerPhoto } from '@/Support/preparerPhoto.js'

const props = defineProps({
    slug: { type: String, required: true },
    photos: { type: Array, default: () => [] },
    modifiable: { type: Boolean, default: true },
})

const champ = ref(null)
const form = useForm({ photo: null, caption: '' })
const preparation = ref(false)

// La photo est réduite avant de partir, à ce que le serveur garde vraiment :
// sur une connexion mobile, un original de 12 Mo était une minute d'attente —
// souvent coupée avant la fin.
async function choisir(evenement) {
    const fichier = evenement.target.files?.[0]
    if (!fichier) return

    preparation.value = true
    form.photo = await preparerPhoto(fichier)
    preparation.value = false
    form.post(`/proprietaire/logements/${props.slug}/photos`, {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            form.reset()
            if (champ.value) champ.value.value = ''
        },
    })
}

const retirer = (id) => router.post(
    `/proprietaire/logements/${props.slug}/photos/${id}/retirer`, {}, { preserveScroll: true }
)

/** Déplace une photo d'un cran et renvoie l'ordre complet : le serveur ne devine rien. */
function deplacer(index, pas) {
    const ordre = props.photos.map((p) => p.id)
    const cible = index + pas
    if (cible < 0 || cible >= ordre.length) return

    ;[ordre[index], ordre[cible]] = [ordre[cible], ordre[index]]

    router.post(`/proprietaire/logements/${props.slug}/photos/ordre`, { ids: ordre }, { preserveScroll: true })
}
</script>

<template>
    <div class="pm">
        <p class="pm__lede">
            Trois photos au minimum. <strong>La première est la couverture</strong> — c'est elle
            qu'on voit dans les résultats de recherche.
        </p>

        <ul v-if="photos.length" class="pm__list">
            <li v-for="(p, i) in photos" :key="p.id" class="pm__item">
                <div class="pm__frame">
                    <img
                        :src="photoSrc(p, 800)"
                        :srcset="photoSrcset(p)"
                        sizes="200px"
                        :alt="p.caption"
                        width="800"
                        height="600"
                        loading="lazy"
                        decoding="async"
                    >
                    <span v-if="i === 0" class="pm__cover">Couverture</span>
                </div>

                <div v-if="modifiable" class="pm__acts">
                    <button type="button" class="pm__arrow" :disabled="i === 0"
                            title="Avancer" @click="deplacer(i, -1)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                             stroke-linecap="round" stroke-linejoin="round"><path d="M15 5.5 8.5 12l6.5 6.5" /></svg>
                    </button>
                    <button type="button" class="pm__arrow" :disabled="i === photos.length - 1"
                            title="Reculer" @click="deplacer(i, 1)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                             stroke-linecap="round" stroke-linejoin="round"><path d="m9 5.5 6.5 6.5L9 18.5" /></svg>
                    </button>
                    <button type="button" class="pm__del" @click="retirer(p.id)">Retirer</button>
                </div>
            </li>
        </ul>

        <p v-else class="pm__empty">Aucune photo pour l'instant.</p>

        <div v-if="modifiable" class="pm__add">
            <label class="btn btn--outline pm__pick">
                <input ref="champ" type="file" accept="image/jpeg,image/png,image/webp"
                       :disabled="form.processing || preparation" @change="choisir">
                <template v-if="preparation">Préparation de la photo…</template>
                <template v-else-if="form.processing">{{ (form.progress?.percentage ?? 0) < 100 ? `Envoi : ${Math.round(form.progress?.percentage ?? 0)} %` : 'Recadrage…' }}</template>
                <template v-else>Ajouter une photo</template>
            </label>
            <p class="pm__hint">
                JPEG, PNG ou WebP, 1200 pixels de large au minimum.
                Prenez-la avec l'appareil photo du téléphone : une image récupérée dans une
                conversation WhatsApp est trop petite.
            </p>
            <p v-if="form.errors.photo" class="pm__err" role="alert">{{ form.errors.photo }}</p>
        </div>
    </div>
</template>

<style scoped>
.pm__lede { margin: -.5rem 0 1rem; max-width: 60ch; font-size: .88rem; line-height: 1.55; color: var(--text-2); }
.pm__lede strong { color: var(--ink); }

.pm__list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr));
    gap: .9rem;
    margin: 0 0 1.25rem;
    padding: 0;
    list-style: none;
}

.pm__frame {
    position: relative;
    aspect-ratio: 4 / 3;
    border-radius: var(--r-md);
    overflow: hidden;
    background: var(--off-2);
}
.pm__frame img { width: 100%; height: 100%; object-fit: cover; display: block; }

.pm__cover {
    position: absolute;
    top: .5rem;
    left: .5rem;
    padding: .2rem .6rem;
    border-radius: var(--r-pill);
    background: var(--ink);
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .02em;
    text-transform: uppercase;
    color: var(--white);
}

.pm__acts { display: flex; align-items: center; gap: .35rem; margin-top: .45rem; }

/* Un contour franc et une cible de 2,75 rem : sans ça, deux chevrons gris de
   seize pixels ne se lisent pas comme des boutons. */
.pm__arrow {
    display: grid;
    place-items: center;
    width: 2.75rem;
    height: 2.75rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-md);
    background: var(--white);
    color: var(--ink);
    cursor: pointer;
}
.pm__arrow svg { width: 1.15rem; height: 1.15rem; }
.pm__arrow:hover:not(:disabled) { border-color: var(--ink); }
/* Un état désactivé se lit, il ne s'efface pas. */
.pm__arrow:disabled { background: var(--off); color: var(--line-2); cursor: not-allowed; }

.pm__del {
    margin-left: auto;
    padding: .45rem .75rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: var(--white);
    font: inherit;
    font-size: .76rem;
    font-weight: 700;
    color: var(--text-2);
    cursor: pointer;
}
.pm__del:hover { border-color: var(--terre-300); color: var(--terre-700); }

.pm__empty {
    margin: 0 0 1.25rem;
    padding: 1.5rem;
    border: 1px dashed var(--line-2);
    border-radius: var(--r-md);
    text-align: center;
    font-size: .9rem;
    color: var(--text-3);
}

.pm__pick { display: inline-block; cursor: pointer; }
.pm__pick input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }

.pm__hint { margin: .6rem 0 0; max-width: 56ch; font-size: .78rem; line-height: 1.5; color: var(--text-3); }

.pm__err {
    margin: .7rem 0 0;
    padding: .7rem 1rem;
    border: 1px solid var(--terre-300);
    border-radius: var(--r-md);
    background: var(--terre-050);
    font-size: .86rem;
    font-weight: 600;
    line-height: 1.5;
    color: var(--terre-700);
}
</style>
