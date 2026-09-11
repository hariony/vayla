<script setup>
/**
 * Les chiffres de la période, en tête d'écran. `compte` fait monter le nombre
 * à l'arrivée (`useOfficeMotion`) ; un taux, une durée ou un montant ne
 * montent pas — un compteur qui défile sur « 50 % » raconterait une course.
 */
defineProps({
    /** `[{ label, valeur, compte? }]` — `valeur` déjà mise en forme. */
    tuiles: { type: Array, required: true },
})
</script>

<template>
    <dl class="st__chiffres" data-reveal>
        <div v-for="t in tuiles" :key="t.label" class="st__c">
            <dt>{{ t.label }}</dt>
            <dd class="of-num" :data-count="t.compte">{{ t.valeur }}</dd>
        </div>
    </dl>
</template>

<style scoped>
.st__chiffres {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
    gap: 1px;
    margin: 0 0 1rem;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: var(--r-md);
    background: var(--line);
}
.st__c { padding: .9rem 1rem; background: var(--white); }
.st__c dt { font-size: .74rem; font-weight: 600; line-height: 1.3; color: var(--text-3); }
.st__c dd { margin: .25rem 0 0; font-size: 1.55rem; font-weight: 800; letter-spacing: -.04em; line-height: 1.1; color: var(--ink); }
</style>
