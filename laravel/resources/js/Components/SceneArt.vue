<script setup>
/**
 * Illustrations vectorielles pour les destinations.
 * Pas de photo stock : des scènes générées, cohérentes entre elles,
 * remplaçables plus tard par les vraies photos des propriétaires.
 */
import { computed, useId } from 'vue'

const props = defineProps({
    variant: { type: String, default: 'lagoon' },
})

const uid = useId()

const palettes = {
    // Le lagon et la forêt gardent leurs vrais tons ; le couchant et le lac
    // passent en latérite plutôt qu'en magenta — c'est la couleur du sol
    // malgache, et ça accorde les vignettes au reste de la page.
    lagoon:   { sky: ['#A9F0E4', '#0FA096'], near: '#0B5A53', far: '#12968A', accent: '#FFF4E8' },
    lake:     { sky: ['#FFDCB6', '#C9452A'], near: '#4A2018', far: '#8E3A22', accent: '#FFEBCB' },
    sunset:   { sky: ['#FFCFA0', '#B93A1E'], near: '#3A1A10', far: '#7E2C15', accent: '#FFE3B4' },
    highland: { sky: ['#DCEAFF', '#5B86CE'], near: '#7C3E24', far: '#A85E3B', accent: '#FFF3E2' },
    beach:    { sky: ['#BFF4EC', '#17B0A2'], near: '#EFDFCB', far: '#0F8478', accent: '#FFF6E4' },
    forest:   { sky: ['#DEF3B6', '#3AA06A'], near: '#123A2A', far: '#217A50', accent: '#F6FFE2' },
}

const p = computed(() => palettes[props.variant] ?? palettes.lagoon)

const id = (key) => `${key}-${uid}`
</script>

<template>
    <svg
        class="scene"
        viewBox="0 0 400 300"
        preserveAspectRatio="xMidYMid slice"
        aria-hidden="true"
        focusable="false"
    >
        <defs>
            <linearGradient :id="id('sky')" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" :stop-color="p.sky[0]" />
                <stop offset="70%" :stop-color="p.sky[1]" />
                <stop offset="100%" :stop-color="p.sky[1]" />
            </linearGradient>

            <!-- Le sol s'assombrit vers le bas : pas de bande plate à l'horizon. -->
            <linearGradient :id="id('near')" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" :stop-color="p.far" />
                <stop offset="100%" :stop-color="p.near" />
            </linearGradient>

            <radialGradient :id="id('glow')" cx="50%" cy="50%" r="50%">
                <stop offset="0%" :stop-color="p.accent" stop-opacity=".95" />
                <stop offset="100%" :stop-color="p.accent" stop-opacity="0" />
            </radialGradient>

            <!-- Brume qui adoucit la ligne d'horizon -->
            <linearGradient :id="id('haze')" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" :stop-color="p.accent" stop-opacity="0" />
                <stop offset="55%" :stop-color="p.accent" stop-opacity=".28" />
                <stop offset="100%" :stop-color="p.accent" stop-opacity="0" />
            </linearGradient>
        </defs>

        <rect width="400" height="300" :fill="`url(#${id('sky')})`" />

        <!-- Soleil, commun à toutes les scènes -->
        <circle cx="298" cy="96" r="86" :fill="`url(#${id('glow')})`" />
        <circle cx="298" cy="96" r="25" :fill="p.accent" opacity=".92" />

        <!-- ————— Lagon : îlots et eau calme ————— -->
        <g v-if="variant === 'lagoon'">
            <path d="M0 198 q 66 -26 132 -8 q 74 20 148 -10 q 62 -25 120 -2 L400 300 L0 300 Z" :fill="p.far" opacity=".9" />
            <ellipse cx="92" cy="196" rx="54" ry="13" :fill="p.far" />
            <path d="M92 196 q -5 -32 3 -46 q 12 14 9 46 Z" :fill="p.near" opacity=".9" />
            <path d="M0 226 q 96 -18 192 2 q 104 22 208 -6 L400 300 L0 300 Z" :fill="`url(#${id('near')})`" />
            <rect y="190" width="400" height="46" :fill="`url(#${id('haze')})`" />
            <g opacity=".38" stroke="#EAFBF5" stroke-width="2.2" stroke-linecap="round" fill="none">
                <path d="M38 250 h46" /><path d="M152 266 h62" /><path d="M266 246 h48" /><path d="M96 284 h74" />
            </g>
        </g>

        <!-- ————— Lac : reflet du couchant sur l'eau ————— -->
        <g v-else-if="variant === 'lake'">
            <path d="M0 182 L78 134 L142 182 L212 124 L296 182 L360 148 L400 176 L400 300 L0 300 Z" :fill="p.far" opacity=".75" />
            <path d="M0 214 q 100 -12 200 0 q 100 12 200 0 L400 300 L0 300 Z" :fill="`url(#${id('near')})`" />
            <rect y="192" width="400" height="44" :fill="`url(#${id('haze')})`" />
            <g opacity=".5" :fill="p.accent">
                <rect x="284" y="222" width="28" height="5" rx="2.5" />
                <rect x="290" y="238" width="18" height="4" rx="2" />
                <rect x="280" y="254" width="36" height="4" rx="2" />
                <rect x="292" y="270" width="14" height="3" rx="1.5" />
            </g>
            <g opacity=".3" stroke="#FFE6CC" stroke-width="2" stroke-linecap="round" fill="none">
                <path d="M38 240 h54" /><path d="M118 264 h72" />
            </g>
        </g>

        <!-- ————— Coucher de soleil et baobabs ————— -->
        <g v-else-if="variant === 'sunset'">
            <g opacity=".2" :fill="p.accent">
                <rect x="0" y="52" width="400" height="9" rx="4.5" />
                <rect x="0" y="86" width="400" height="6" rx="3" />
                <rect x="0" y="116" width="400" height="4" rx="2" />
            </g>
            <path d="M0 200 q 110 -14 210 2 q 96 15 190 -4 L400 300 L0 300 Z" :fill="`url(#${id('near')})`" />
            <rect y="178" width="400" height="46" :fill="`url(#${id('haze')})`" />

            <!-- Baobabs : tronc épais, couronne plate -->
            <g :fill="p.near">
                <path d="M56 202 q 3 -44 0 -66 q -4 -12 6 -12 q 10 0 6 12 q -3 22 0 66 Z" />
                <path d="M38 128 q 12 -9 26 -8 q 14 -1 26 8 q -14 -2 -26 -1 q -12 -1 -26 1 Z" />
                <path d="M62 122 q -6 -12 -18 -17 M62 122 q 8 -13 21 -16" stroke-width="3" stroke-linecap="round" :stroke="p.near" fill="none" />
            </g>
            <g :fill="p.near" opacity=".78" transform="translate(112 80) scale(.62)">
                <path d="M56 202 q 3 -44 0 -66 q -4 -12 6 -12 q 10 0 6 12 q -3 22 0 66 Z" />
                <path d="M40 130 q 11 -8 24 -7 q 13 -1 24 7 q -13 -2 -24 -1 q -11 -1 -24 1 Z" />
            </g>
        </g>

        <!-- ————— Hautes terres : reliefs étagés ————— -->
        <g v-else-if="variant === 'highland'">
            <path d="M0 194 L86 108 L150 172 L206 122 L268 186 L332 142 L400 200 L400 300 L0 300 Z" :fill="p.far" opacity=".62" />
            <path d="M86 108 L110 140 L62 140 Z" :fill="p.accent" opacity=".6" />
            <path d="M206 122 L226 150 L186 150 Z" :fill="p.accent" opacity=".42" />
            <path d="M0 226 L68 158 L140 220 L212 166 L286 228 L354 186 L400 224 L400 300 L0 300 Z" :fill="`url(#${id('near')})`" />
            <rect y="150" width="400" height="70" :fill="`url(#${id('haze')})`" />
            <g opacity=".28" fill="#FFFFFF">
                <ellipse cx="118" cy="72" rx="42" ry="11" />
                <ellipse cx="174" cy="58" rx="26" ry="8" />
            </g>
        </g>

        <!-- ————— Plage : sable, récif, palmier ————— -->
        <g v-else-if="variant === 'beach'">
            <path d="M0 172 q 100 -12 200 2 q 100 14 200 -4 L400 216 L0 216 Z" :fill="p.far" opacity=".85" />
            <path d="M0 212 q 118 -16 238 6 q 84 15 162 -2 L400 300 L0 300 Z" :fill="`url(#${id('near')})`" />
            <rect y="168" width="400" height="52" :fill="`url(#${id('haze')})`" />
            <g opacity=".62" stroke="#FFFFFF" stroke-width="2.6" stroke-linecap="round" fill="none">
                <path d="M32 192 q 17 -8 34 0 t 34 0" />
                <path d="M186 202 q 17 -8 34 0 t 34 0" />
            </g>
            <g>
                <path d="M82 218 q 7 -48 3 -70" stroke="#6E4F31" stroke-width="6" fill="none" stroke-linecap="round" />
                <g fill="#1F7A5C">
                    <path d="M85 148 q -36 -8 -46 14 q 27 -6 46 -2 Z" />
                    <path d="M85 148 q 36 -10 48 12 q -29 -6 -48 -2 Z" />
                    <path d="M85 148 q -15 -32 8 -42 q 7 25 -2 44 Z" />
                    <path d="M85 148 q 22 -28 44 -22 q -20 10 -42 26 Z" />
                </g>
            </g>
        </g>

        <!-- ————— Forêt : canopée ————— -->
        <g v-else-if="variant === 'forest'">
            <path d="M0 186 q 58 -34 116 -2 q 62 34 124 -4 q 58 -34 160 2 L400 300 L0 300 Z" :fill="p.far" opacity=".72" />
            <path d="M0 224 q 100 -14 200 2 q 100 16 200 -4 L400 300 L0 300 Z" :fill="`url(#${id('near')})`" />
            <rect y="176" width="400" height="58" :fill="`url(#${id('haze')})`" />
            <g :fill="p.near" opacity=".92">
                <path d="M54 232 q 12 -34 30 -72 q 18 38 30 72 Z" />
                <path d="M148 230 q 10 -26 24 -56 q 14 30 24 56 Z" />
                <path d="M240 234 q 14 -38 34 -80 q 20 42 34 80 Z" />
            </g>
            <g opacity=".3" :fill="p.accent">
                <circle cx="196" cy="112" r="3.5" /><circle cx="240" cy="88" r="2.6" />
                <circle cx="140" cy="98" r="2.6" /><circle cx="98" cy="122" r="3" />
            </g>
        </g>
    </svg>
</template>

<style scoped>
.scene {
    display: block;
    width: 100%;
    height: 100%;
}
</style>
