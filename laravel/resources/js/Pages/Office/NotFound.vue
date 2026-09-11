<script setup>
/**
 * Une adresse que le back-office ne connaît pas.
 *
 * Elle existe surtout pour que l'hôte du back-office **ne serve jamais le site
 * public** : sans elle, `office.…/logements` ouvrait le catalogue, avec le menu
 * du compte d'un voyageur, dans l'outil de l'équipe. Même décor que la
 * connexion : on est toujours chez l'équipe, pas sur le site.
 */
import OfficeGate from '@/Components/Office/OfficeGate.vue'
</script>

<template>
    <OfficeGate
        titre-page="Page introuvable — Back-office Vayla"
        titre="Rien à cette adresse."
        lede="Le back-office n'a pas de page ici. Le site public vit sur une autre adresse : les boutons « Voir sur le site » des fiches y mènent."
    >
        <div class="nf" data-og-champ>
            <p class="of-kicker">Erreur 404</p>
            <p class="nf__t">Revenez au tableau de bord : c'est de là que partent toutes les files.</p>
            <!-- Un lien nu vers `/` : sans session, le garde renvoie sur la
                 connexion, et c'est justement là qu'il faut aller. -->
            <a href="/" class="btn btn--ink nf__go">Tableau de bord</a>
        </div>
    </OfficeGate>
</template>

<style scoped>
.nf { display: grid; gap: .8rem; }
.nf__t { margin: 0; font-size: .95rem; line-height: 1.55; color: var(--text-2); }
.nf__go { width: 100%; }
</style>
