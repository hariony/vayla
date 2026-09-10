<script setup>
/**
 * La capacité, en tête de fiche et avant la photo.
 *
 * **Deux chiffres décident, trois renseignent.** « Est-ce que ça nous
 * loge ? » se répond avec la capacité d'accueil et le nombre de chambres :
 * ce sont eux qu'on cherche en premier, et c'est le seul critère qui écarte
 * un logement quelles que soient les photos. Les couchages, les salles d'eau
 * et la surface précisent, ils ne décident pas — d'où deux tailles, pas cinq
 * tuiles identiques.
 *
 * **« 6 personnes max. », pas « 6 voyageurs ».** `listings.guests` est un
 * plafond, pas un effectif attendu, et c'est exactement l'information dont on
 * a besoin : « est-ce qu'on rentre à cinq ? ». « Voyageurs » énonçait un
 * nombre sans dire qu'il s'agissait d'une limite — deux lectures possibles
 * pour une donnée qui n'en admet qu'une. Le compte de chambres, lui, reste un
 * compte : il ne prend pas de « max. ».
 *
 * Ces cinq nombres tenaient auparavant sur **une ligne de texte gris**, sous
 * la galerie. C'était une erreur d'arbitrage de ma part : j'avais optimisé la
 * place verticale au détriment de la question la plus posée.
 *
 * **Le bloc ne coûte rien en hauteur** : il occupe la place laissée vide à
 * droite du titre, qui s'arrête à 46 rem dans une coquille de 1 300 px.
 * Descendre la photographie de soixante pixels pour l'y loger aurait échangé
 * un problème contre un autre.
 *
 * **Neutre, jamais coloré.** La terre porte la marque et l'action, le lagon
 * ne dit que « vérifié ». Une capacité n'est ni l'un ni l'autre : encre sur
 * fond cassé. Colorer ce bloc aurait affaibli l'échelle de confiance, qui est
 * la seule chose que la couleur a le droit de signifier ici.
 */
import { computed } from 'vue'

const props = defineProps({
    listing: { type: Object, required: true },
})

const accord = (n, un, plusieurs) => `${n > 1 ? plusieurs : un}`

/** Ce qui précise, sans décider. La surface manque parfois : on ne l'invente pas. */
const details = computed(() => {
    const v = props.listing
    const out = [
        `${v.beds} ${accord(v.beds, 'couchage', 'couchages')}`,
        `${v.bathrooms} ${accord(v.bathrooms, "salle d'eau", "salles d'eau")}`,
    ]

    if (v.surface) out.push(`${v.surface} m²`)

    return out
})
</script>

<template>
    <div class="cap">
        <div class="cap__pair">
            <p class="cap__stat">
                <span class="cap__ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="7.5" r="3.2" />
                        <path d="M2.8 20c0-3.4 2.8-5.6 6.2-5.6s6.2 2.2 6.2 5.6" />
                        <path d="M16.4 5.2a3.2 3.2 0 0 1 0 6.1M17.6 14.9c2.2.6 3.6 2.5 3.6 5.1" />
                    </svg>
                </span>
                <span class="cap__n num">{{ listing.guests }}</span>
                <span class="cap__lab">{{ accord(listing.guests, 'personne max.', 'personnes max.') }}</span>
            </p>

            <span class="cap__rule" aria-hidden="true"></span>

            <p class="cap__stat">
                <span class="cap__ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 19v-7.5h18V19M3 19v1.5M21 19v1.5M3 12V5.5h18V12" />
                        <path d="M7.5 12V9h9v3" />
                    </svg>
                </span>
                <span class="cap__n num">{{ listing.bedrooms }}</span>
                <span class="cap__lab">{{ accord(listing.bedrooms, 'chambre', 'chambres') }}</span>
            </p>
        </div>

        <p class="cap__more">
            <template v-for="(d, i) in details" :key="d">
                <span v-if="i" aria-hidden="true"> · </span>{{ d }}
            </template>
        </p>
    </div>
</template>

<style scoped>
/* Une carte blanche posée sur le blanc, avec une ombre : c'est le seul
   objet en relief au-dessus de la photographie, et c'est ce qui le fait
   voir. Un aplat gris cassé se fondait dans la page. */
.cap {
    display: inline-flex;
    flex-direction: column;
    gap: .7rem;
    padding: 1rem 1.4rem 1.05rem;
    border: 1px solid var(--line);
    border-radius: var(--r-lg);
    background: var(--white);
    box-shadow: 0 2px 14px -8px rgba(23, 20, 28, .35);
}

.cap__pair { display: flex; align-items: center; gap: 1.4rem; }

.cap__rule {
    width: 1px;
    align-self: stretch;
    background: var(--line-2);
}

/* Le chiffre d'abord, gros ; le mot dessous, petit. L'inverse — un libellé
   en capitales surmontant un chiffre — se lit comme un tableau de bord, pas
   comme une réponse à « combien de personnes ». */
.cap__stat {
    display: grid;
    grid-template-columns: auto auto;
    grid-template-rows: auto auto;
    align-items: center;
    gap: 0 .5rem;
    margin: 0;
}

.cap__ico {
    grid-row: 1 / 3;
    display: grid;
    place-items: center;
    width: 1.75rem;
    color: var(--text-2);
}
.cap__ico svg { width: 1.5rem; height: 1.5rem; }

.cap__n {
    font-size: 2.3rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -.055em;
    color: var(--ink);
}

.cap__lab {
    grid-column: 2;
    margin-top: .15rem;
    font-size: .86rem;
    font-weight: 700;
    letter-spacing: -.015em;
    color: var(--text-2);
}

.cap__more {
    margin: 0;
    padding-top: .6rem;
    border-top: 1px solid var(--line);
    font-size: .84rem;
    font-weight: 500;
    color: var(--text-2);
}

@media (max-width: 560px) {
    .cap { display: flex; width: 100%; }
    .cap__pair { justify-content: space-around; gap: .75rem; }
    .cap__n { font-size: 2rem; }
}
</style>
