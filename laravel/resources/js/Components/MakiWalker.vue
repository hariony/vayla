<script setup>
/**
 * Maki catta (lemur catta) — il arpente la branche de la canopée.
 *
 * Rendu comme un <g> : il doit vivre dans le même repère SVG que la branche
 * pour que MotionPathPlugin l'y accroche. Les membres sont des groupes
 * distincts, pivotés par GSAP autour de leur attache (svgOrigin).
 *
 * Repère local : pattes au sol à y = 0, museau vers +x.
 */
</script>

<template>
    <g class="maki" aria-hidden="true">
        <!-- Queue annelée : un trait clair, un trait sombre pointillé par-dessus -->
        <g class="maki__tail">
            <path class="maki__tail-base" d="M22,-30 C4,-35 -13,-47 -13,-63 C-13,-75 -2,-81 9,-78" />
            <path class="maki__tail-rings" d="M22,-30 C4,-35 -13,-47 -13,-63 C-13,-75 -2,-81 9,-78" />
        </g>

        <!-- Membres du fond, plus sombres -->
        <g class="maki__leg maki__leg--far">
            <path d="M60,-27 C57,-17 56,-9 55,-1" />
        </g>
        <g class="maki__leg maki__leg--far">
            <path d="M25,-28 C21,-18 20,-9 19,-1" />
        </g>

        <ellipse class="maki__body" cx="44" cy="-30" rx="23" ry="13.5" transform="rotate(-5 44 -30)" />

        <!-- Membres de devant -->
        <g class="maki__leg maki__leg--fore">
            <path d="M63,-26 C66,-16 66,-8 66,-1" />
        </g>
        <g class="maki__leg maki__leg--hind">
            <path d="M28,-27 C31,-17 32,-9 32,-1" />
        </g>

        <g class="maki__head">
            <path class="maki__ear" d="M67,-51 L64,-62 L74,-55 Z" />
            <path class="maki__ear" d="M83,-52 L86,-63 L89,-54 Z" />
            <circle class="maki__skull" cx="76" cy="-44" r="11" />
            <path class="maki__muzzle" d="M84,-46 Q96,-43 93,-36 Q86,-33 81,-38 Z" />
            <circle class="maki__eye" cx="80" cy="-47" r="2.3" />
        </g>
    </g>
</template>

<style scoped>
.maki { pointer-events: none; }

.maki__body,
.maki__skull,
.maki__muzzle,
.maki__ear {
    fill: var(--ink);
    stroke: var(--terre-400);
    stroke-width: 1.2;
    stroke-opacity: .55;
}

.maki__leg path {
    fill: none;
    stroke: var(--ink);
    stroke-width: 5;
    stroke-linecap: round;
}

.maki__leg--far path { stroke: var(--ink-3); stroke-width: 4.4; }

/* Queue annelée : un trait fuchsia recouvert du pointillé d'encre */
.maki__tail-base {
    fill: none;
    stroke: var(--terre-400);
    stroke-width: 7;
    stroke-linecap: round;
    stroke-opacity: .95;
}

.maki__tail-rings {
    fill: none;
    stroke: var(--ink);
    stroke-width: 7.4;
    stroke-linecap: butt;
    stroke-dasharray: 7 7.5;
}

.maki__eye {
    fill: var(--terre-300);
    stroke: none;
}
</style>
