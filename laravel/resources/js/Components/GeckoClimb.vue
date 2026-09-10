<script setup>
/**
 * Phelsuma — gecko diurne endémique, vu de dessus, sur la tige de confiance.
 *
 * Vue dorsale et non de profil : à 50 px, seule une silhouette symétrique
 * à quatre pattes écartées reste lisible. Un caméléon de profil redressé
 * ne donnait qu'une masse ovale.
 *
 * Démarche diagonale : avant-gauche avec arrière-droite, puis l'inverse.
 *
 * Il est en lagon, pas en terre : il grimpe l'échelle de confiance,
 * il appartient donc au vocabulaire de la vérification.
 */
</script>

<template>
    <svg class="gk" viewBox="0 0 64 132" aria-hidden="true">
        <!-- Queue, effilée -->
        <path class="gk__tail" d="M28,68 C25,88 26,108 30,128 C34,108 35,88 36,68 Z" />

        <!-- Pattes arrière -->
        <g class="gk__leg gk__leg--hl">
            <path class="gk__limb gk__edge" d="M25,60 C17,64 11,72 8,80" />
            <path class="gk__limb" d="M25,60 C17,64 11,72 8,80" />
            <path class="gk__toes gk__edge" d="M8,80 l-5,-2 M8,80 l-1,-6 M8,80 l4,-4" />
            <path class="gk__toes" d="M8,80 l-5,-2 M8,80 l-1,-6 M8,80 l4,-4" />
        </g>
        <g class="gk__leg gk__leg--hr">
            <path class="gk__limb gk__edge" d="M39,60 C47,64 53,72 56,80" />
            <path class="gk__limb" d="M39,60 C47,64 53,72 56,80" />
            <path class="gk__toes gk__edge" d="M56,80 l5,-2 M56,80 l1,-6 M56,80 l-4,-4" />
            <path class="gk__toes" d="M56,80 l5,-2 M56,80 l1,-6 M56,80 l-4,-4" />
        </g>

        <!-- Pattes avant -->
        <g class="gk__leg gk__leg--fl">
            <path class="gk__limb gk__edge" d="M25,35 C17,33 11,27 7,20" />
            <path class="gk__limb" d="M25,35 C17,33 11,27 7,20" />
            <path class="gk__toes gk__edge" d="M7,20 l-5,-1 M7,20 l0,-6 M7,20 l5,-3" />
            <path class="gk__toes" d="M7,20 l-5,-1 M7,20 l0,-6 M7,20 l5,-3" />
        </g>
        <g class="gk__leg gk__leg--fr">
            <path class="gk__limb gk__edge" d="M39,35 C47,33 53,27 57,20" />
            <path class="gk__limb" d="M39,35 C47,33 53,27 57,20" />
            <path class="gk__toes gk__edge" d="M57,20 l5,-1 M57,20 l0,-6 M57,20 l-5,-3" />
            <path class="gk__toes" d="M57,20 l5,-1 M57,20 l0,-6 M57,20 l-5,-3" />
        </g>

        <!-- Tronc -->
        <path class="gk__body"
              d="M32,27 C40,29 44,40 43,52 C42,62 37,70 32,72
                 C27,70 22,62 21,52 C20,40 24,29 32,27 Z" />

        <!-- Tête -->
        <path class="gk__head"
              d="M32,4 C40,6 45,13 44,21 C43,28 37,32 32,32
                 C27,32 21,28 20,21 C19,13 24,6 32,4 Z" />

        <circle class="gk__eye" cx="25.5" cy="16" r="3.4" />
        <circle class="gk__eye" cx="38.5" cy="16" r="3.4" />
        <circle class="gk__pupil" cx="25.5" cy="16" r="1.4" />
        <circle class="gk__pupil" cx="38.5" cy="16" r="1.4" />
    </svg>
</template>

<style scoped>
.gk {
    display: block;
    width: 52px;
    height: auto;
    overflow: visible;
    pointer-events: none;
}

.gk__body,
.gk__head,
.gk__tail {
    fill: var(--lagon-500);
    stroke: var(--lagon-700);
    stroke-width: 1.2;
    stroke-opacity: .6;
    stroke-linejoin: round;
}

.gk__limb {
    fill: none;
    stroke: var(--lagon-500);
    stroke-width: 4.6;
    stroke-linecap: round;
}

.gk__toes {
    fill: none;
    stroke: var(--lagon-500);
    stroke-width: 2;
    stroke-linecap: round;
}

/* Le liseré blanc détache la bête de la tige et de la page */
.gk__edge {
    stroke: #fff;
    stroke-opacity: .95;
}
.gk__limb.gk__edge { stroke-width: 7.4; }
.gk__toes.gk__edge { stroke-width: 4.4; }

.gk__eye {
    fill: #fff;
    stroke: var(--lagon-700);
    stroke-width: 1.2;
    stroke-opacity: .5;
}

.gk__pupil { fill: var(--ink); }

@media (max-width: 760px) {
    .gk { width: 40px; }
}
</style>
