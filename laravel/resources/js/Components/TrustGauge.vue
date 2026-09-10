<script setup>
/**
 * Jauge de confiance : quatre segments, un par niveau de vérification.
 * C'est la signature de Vayla — elle apparaît sur chaque annonce, dans
 * l'échelle détaillée, et partout où un logement est nommé.
 */
import { computed } from 'vue'

const props = defineProps({
    level: { type: Number, default: 1 },
    light: { type: Boolean, default: false },
    large: { type: Boolean, default: false },
    label: { type: String, default: '' },
})

const NAMES = {
    1: 'Annonce déclarée',
    2: 'Contact confirmé',
    3: 'Logement visité',
    4: 'Séjour confirmé',
}

const readable = computed(() => props.label || NAMES[props.level] || NAMES[1])
</script>

<template>
    <span
        class="gauge"
        :class="{ 'gauge--light': light, 'gauge--lg': large }"
        role="img"
        :aria-label="`Confiance niveau ${level} sur 4 : ${readable}`"
    >
        <span
            v-for="n in 4"
            :key="n"
            class="gauge__seg"
            :class="{ 'is-on': n <= level }"
        ></span>
    </span>
</template>
