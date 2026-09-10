<script setup>
/**
 * Décor du hero. Plus d'encre : un blanc traversé de nappes fuchsia et
 * un jeu de houles au trait. Tout est en dégradés et en traits — rien
 * n'est plein, pour que le titre reste le seul objet dense de l'écran.
 */
</script>

<template>
    <div class="bd" aria-hidden="true">
        <div class="bd__mesh"></div>
        <div class="bd__grid"></div>

        <svg class="bd__scene" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMax slice" fill="none">
            <defs>
                <linearGradient id="bd-line" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%" stop-color="var(--terre-200)" stop-opacity="0" />
                    <stop offset="22%" stop-color="var(--terre-400)" stop-opacity=".9" />
                    <stop offset="78%" stop-color="var(--terre-300)" stop-opacity=".8" />
                    <stop offset="100%" stop-color="var(--terre-200)" stop-opacity="0" />
                </linearGradient>
            </defs>

            <!-- Houle au trait : trois passes décalées, elles dérivent en boucle -->
            <g stroke="url(#bd-line)" stroke-linecap="round" fill="none">
                <path class="bd__wave" d="M-200 784 q 180 -52 360 0 t 360 0 t 360 0 t 360 0 t 360 0" stroke-width="2" opacity=".6" />
                <path class="bd__wave bd__wave--2" d="M-200 832 q 200 -44 400 0 t 400 0 t 400 0 t 400 0" stroke-width="1.5" opacity=".42" />
                <path class="bd__wave bd__wave--3" d="M-200 876 q 160 -38 320 0 t 320 0 t 320 0 t 320 0 t 320 0" stroke-width="1.1" opacity=".3" />
            </g>
        </svg>

        <div class="bd__fade"></div>
    </div>
</template>

<style scoped>
.bd {
    position: absolute;
    inset: 0;
    overflow: hidden;
    background: var(--white);
}

/* Nappes de couleur : deux foyers fuchsia et un rappel chaud, très dilués */
.bd__mesh {
    position: absolute;
    inset: -20% -10%;
    background:
        radial-gradient(46% 44% at 84% 6%, rgba(201, 69, 42, .16) 0%, transparent 62%),
        radial-gradient(40% 40% at 96% 36%, rgba(224, 106, 75, .14) 0%, transparent 66%),
        radial-gradient(52% 46% at 6% 12%, rgba(247, 194, 172, .34) 0%, transparent 64%),
        radial-gradient(38% 34% at 26% 78%, rgba(251, 225, 214, .6) 0%, transparent 68%);
    animation: bd-drift-mesh 24s ease-in-out infinite alternate;
}

@keyframes bd-drift-mesh {
    from { transform: translate3d(0, 0, 0) scale(1); }
    to   { transform: translate3d(-2.5%, 1.5%, 0) scale(1.06); }
}

/* Trame verticale : le seul rappel de la grille du studio */
.bd__grid {
    position: absolute;
    inset: 0;
    background-image: linear-gradient(to right, var(--line) 1px, transparent 1px);
    background-size: clamp(80px, 8vw, 128px) 100%;
    mask-image: linear-gradient(180deg, rgba(0, 0, 0, .85) 0%, transparent 70%);
    -webkit-mask-image: linear-gradient(180deg, rgba(0, 0, 0, .85) 0%, transparent 70%);
}

.bd__scene {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.bd__wave { animation: bd-drift 26s linear infinite; }
.bd__wave--2 { animation-duration: 19s; animation-direction: reverse; }
.bd__wave--3 { animation-duration: 32s; }

@keyframes bd-drift {
    from { transform: translateX(0); }
    to   { transform: translateX(-720px); }
}

/* Le décor se fond dans le blanc de la section suivante */
.bd__fade {
    position: absolute;
    inset: auto 0 0 0;
    height: 34%;
    background: linear-gradient(180deg, transparent, var(--white) 82%);
}

@media (prefers-reduced-motion: reduce) {
    .bd__wave, .bd__mesh { animation: none; }
}
</style>
