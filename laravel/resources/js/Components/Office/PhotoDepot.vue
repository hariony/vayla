<script setup>
/**
 * Déposer une photo : un clic pour la choisir, ou la glisser depuis le
 * bureau. **La photo est préparée avant de partir** (`preparerPhoto`) : un
 * original de 12 Mo arrive en 2, réduit à ce que le serveur garde vraiment —
 * et l'écran le dit, poids avant et après, pour qu'on sache ce qui part.
 *
 * **L'envoi se voit avancer** : sur une connexion lente, un bouton figé
 * pendant quarante secondes se prend pour une panne, et on recommence. La
 * barre suit l'envoi, puis dit « Recadrage et compression… » pendant que le
 * serveur produit les trois tailles.
 *
 * La zone est un vrai contrôle — bordée, avec sa consigne écrite —, jamais un
 * lien discret : c'est la règle de tout le produit.
 */
import { onBeforeUnmount, ref, watch } from 'vue'

import OfficeIcon from '@/Components/OfficeIcon.vue'
import { poids } from '@/Support/format.js'
import { preparerPhoto } from '@/Support/preparerPhoto.js'

const props = defineProps({
    /** Le fichier prêt à partir, ou `null`. */
    modelValue: { type: Object, default: null },
    /** Vrai pendant l'envoi. */
    envoi: { type: Boolean, default: false },
    /** 0 à 100 pendant l'envoi (`form.progress?.percentage`). */
    progression: { type: Number, default: null },
    id: { type: String, default: 'photo-depot' },
})

const emit = defineEmits(['update:modelValue'])

const champ = ref(null)
const apercu = ref(null)
const survol = ref(false)
const preparation = ref(false)
const origine = ref(null)
const refus = ref('')

const liberer = () => {
    if (apercu.value) URL.revokeObjectURL(apercu.value)
    apercu.value = null
}

onBeforeUnmount(liberer)

// Le formulaire remis à zéro après l'envoi vide aussi la zone.
watch(() => props.modelValue, (f) => {
    if (f) return
    liberer()
    origine.value = null
    if (champ.value) champ.value.value = ''
})

const prendre = async (f) => {
    refus.value = ''
    if (!f || props.envoi) return

    if (!/^image\/(jpeg|png|webp)$/.test(f.type)) {
        refus.value = 'Ce fichier n’est pas une photo JPEG, PNG ou WebP.'
        return
    }

    preparation.value = true
    origine.value = { nom: f.name, poids: f.size }
    const prete = await preparerPhoto(f)
    preparation.value = false

    liberer()
    apercu.value = URL.createObjectURL(prete)
    emit('update:modelValue', prete)
}

const deposer = (e) => {
    survol.value = false
    prendre(e.dataTransfer?.files?.[0])
}
</script>

<template>
    <div class="pd">
        <label
            class="pd__zone"
            :class="{ 'is-survol': survol, 'is-plein': apercu, 'is-occupe': envoi }"
            :for="id"
            @dragenter.prevent="survol = true"
            @dragover.prevent="survol = true"
            @dragleave.prevent="survol = false"
            @drop.prevent="deposer"
        >
            <input :id="id" ref="champ" class="sr-only" type="file" accept="image/jpeg,image/png,image/webp" :disabled="envoi" @change="prendre($event.target.files?.[0])">
            <img v-if="apercu" :src="apercu" alt="">
            <span v-else class="pd__invite">
                <OfficeIcon name="photo" />
                <strong>Choisir une photo</strong>
                <span>ou la glisser ici</span>
                <small>JPEG, PNG ou WebP · 1 200 px de large au moins · recadrée en 4/3 · jusqu'à 40 Mo</small>
            </span>
            <span v-if="apercu && !envoi" class="pd__changer">Changer de photo</span>
        </label>

        <p v-if="preparation" class="pd__etat" role="status">Préparation de la photo…</p>
        <p v-else-if="origine && modelValue && !envoi" class="pd__etat">
            <span class="pd__nom">{{ origine.nom }}</span>
            <template v-if="modelValue.size < origine.poids"> — {{ poids(origine.poids) }}, réduite à <strong>{{ poids(modelValue.size) }}</strong> avant l'envoi.</template>
            <template v-else> — {{ poids(origine.poids) }}.</template>
        </p>

        <template v-if="envoi">
            <div class="pd__barre" role="progressbar" :aria-valuenow="progression ?? 0" aria-valuemin="0" aria-valuemax="100" aria-label="Envoi de la photo">
                <span :style="{ width: `${progression ?? 0}%` }" />
            </div>
            <p class="pd__etat" role="status">{{ (progression ?? 0) < 100 ? `Envoi : ${progression ?? 0} %` : 'Recadrage et compression en trois tailles…' }}</p>
        </template>

        <p v-if="refus" class="of-err">{{ refus }}</p>
    </div>
</template>

<style scoped>
.pd { display: grid; gap: .45rem; }
.pd__zone {
    position: relative;
    display: grid;
    place-items: center;
    aspect-ratio: 4 / 3;
    overflow: hidden;
    border: 1.5px dashed var(--line-2);
    border-radius: var(--r-sm);
    background: var(--white);
    text-align: center;
    cursor: pointer;
    transition: border-color .15s var(--ease), background-color .15s var(--ease);
}
.pd__zone:hover { border-color: var(--ink); }
.pd__zone:focus-within { outline: 2px solid var(--terre-500); outline-offset: 2px; }
.pd__zone.is-survol { border-color: var(--terre-500); background: var(--terre-050); }
.pd__zone.is-plein { border-style: solid; }
.pd__zone.is-occupe { cursor: progress; }
.pd__zone img { width: 100%; height: 100%; object-fit: cover; }
.pd__invite { display: grid; justify-items: center; gap: .2rem; padding: 1rem; font-size: .82rem; color: var(--text-2); }
.pd__invite strong { font-size: .92rem; color: var(--ink); }
.pd__invite small { max-width: 20rem; margin-top: .25rem; font-size: .72rem; color: var(--text-3); }
.pd__invite .oi { width: 1.7rem; height: 1.7rem; color: var(--ink); }
.pd__changer {
    position: absolute;
    right: .5rem;
    bottom: .5rem;
    padding: .35rem .75rem;
    border: 1px solid var(--line-2);
    border-radius: var(--r-pill);
    background: rgba(255, 255, 255, .95);
    font-size: .74rem;
    font-weight: 700;
    color: var(--ink);
}
.pd__etat { margin: 0; font-size: .76rem; line-height: 1.45; color: var(--text-2); }
.pd__etat strong { color: var(--ink); }
.pd__nom { overflow-wrap: anywhere; }
.pd__barre { height: .45rem; overflow: hidden; border-radius: var(--r-pill); background: var(--off-2); }
.pd__barre span { display: block; height: 100%; border-radius: inherit; background: var(--terre-500); transition: width .2s linear; }
</style>
