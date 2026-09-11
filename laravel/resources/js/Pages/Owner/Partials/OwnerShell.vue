<script setup>
/**
 * L'espace propriétaire — la barre latérale et ses rubriques.
 *
 * Le cadre lui-même (barre, contenu, retour, messages de retour, compte,
 * sortie) vit dans `Components/SpaceShell.vue`, partagé avec l'espace client :
 * deux barres recopiées auraient divergé au premier ajustement. Ce fichier ne
 * décide plus que d'une chose — **ce qu'il y a dedans**.
 *
 * **Les rubriques étaient des onglets en travers du haut.** Ça tenait à trois ;
 * ça ne tient plus dès qu'arrivent la facturation, les messages et le profil.
 * Passé quatre ou cinq, une rangée horizontale défile, et ce qui dépasse de
 * l'écran n'existe plus pour celui qui ne pense pas à faire glisser.
 *
 * **La liste elle-même vit dans `Support/espaces.js`**, avec celle du client :
 * la barre latérale, le menu du compte et le tiroir mobile la lisent tous les
 * trois, et le menu du compte avait déjà divergé — il ignorait des rubriques
 * que la barre affichait.
 *
 * **L'en-tête du site, comme l'espace client.** L'espace propriétaire n'en
 * avait pas, et pour une raison précise : la barre **recrutait**, et « Devenir
 * hôte » n'a aucun sens pour quelqu'un qui l'est déjà. Cette raison a disparu
 * — l'en-tête suit la session, le bouton se retire pour un propriétaire, et
 * « Connexion » est devenu le menu de son compte, où vit la déconnexion. Deux
 * barres différentes pour deux espaces du même produit obligeaient à
 * réapprendre où sont les choses en changeant de casquette.
 *
 * `:search="false"` : la forme compacte de l'en-tête efface la navigation pour
 * y encastrer le moteur de recherche. Sans moteur à encastrer, elle laisse une
 * barre vide au premier défilement.
 */
import SiteHeader from '@/Components/SiteHeader.vue'
import SpaceShell from '@/Components/SpaceShell.vue'
import { ACTION_PROPRIETAIRE, RUBRIQUES_PROPRIETAIRE } from '@/Support/espaces.js'

defineProps({
    /** Le lien de retour, quand l'écran est en dessous d'une rubrique. */
    back: { type: Object, default: null },
})
</script>

<template>
    <SiteHeader :search="false" />

    <SpaceShell
        espace="Espace propriétaire"
        :groupes="RUBRIQUES_PROPRIETAIRE"
        :action="ACTION_PROPRIETAIRE"
        :back="back"
        garde="proprietaire"
    >
        <slot />

        <template #pied>
            Une question, un changement à faire vérifier ? Écrivez à Vayla sur WhatsApp.
            Pensez à vous déconnecter si ce téléphone n'est pas le vôtre.
        </template>
    </SpaceShell>
</template>
