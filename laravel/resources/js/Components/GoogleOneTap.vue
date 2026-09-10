<script setup>
/**
 * L'invite Google en haut à droite — *One Tap*.
 *
 * **Elle ne remplace pas les boutons, elle les devance.** Celui qui a une
 * session Google ouverte voit son nom et son visage, et entre d'un clic sans
 * jamais atteindre un écran de connexion. Les autres ne voient rien : Google
 * n'affiche l'invite que s'il a quelque chose à proposer.
 *
 * **Elle ne s'affiche jamais à quelqu'un de connecté.** Proposer d'entrer à
 * qui est déjà entré est le faux signal le plus déroutant qui soit — on se
 * demande si on a été déconnecté.
 *
 * **Une seule instance, quelle que soit la page.** Le composant est monté par
 * l'en-tête *et* par le cadre des écrans d'accès ; deux appels à `prompt()`
 * dans la même page font clignoter l'invite et Google finit par l'étouffer.
 * Le drapeau de module tient cette unicité — un `ref` ne survivrait pas au
 * démontage d'une des deux instances.
 *
 * **Ce qui revient est un jeton signé, pas une identité.** Il part au serveur,
 * qui vérifie la signature, le destinataire et l'émetteur avant de croire quoi
 * que ce soit. Le navigateur ne décide de rien — c'est tout l'intérêt.
 *
 * **Google impose son propre dessin** : ni couleur, ni typographie, ni
 * position ne nous appartiennent. C'est la contrepartie de l'invite native, et
 * la raison pour laquelle elle vit à l'écart de notre système visuel plutôt
 * qu'au milieu de nos écrans.
 */
import { onMounted, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const SCRIPT = 'https://accounts.google.com/gsi/client'

/**
 * Une seule invite par page, et une seule tentative par visite.
 *
 * Au niveau du module, donc partagé par toutes les instances : c'est
 * exactement ce qu'on veut, et un état de composant ne l'aurait pas donné.
 */
let occupe = false

const page = usePage()

const charger = () => new Promise((resolve, reject) => {
    if (window.google?.accounts?.id) {
        resolve()

        return
    }

    const balise = document.createElement('script')
    balise.src = SCRIPT
    balise.async = true
    balise.defer = true
    balise.onload = resolve
    // Un bloqueur de publicité suffit à faire échouer ce chargement. Ce n'est
    // pas une panne : les boutons et l'entrée par adresse restent là.
    balise.onerror = reject
    document.head.appendChild(balise)
})

/**
 * Le jeton part au serveur par une visite Inertia : elle porte le jeton CSRF
 * de Laravel, et la réponse redirige comme n'importe quelle connexion.
 */
const recevoir = ({ credential }) => {
    if (! credential) {
        return
    }

    router.post('/auth/google/one-tap', { credential }, { preserveScroll: true })
}

onMounted(async () => {
    const client = page.props.google_client_id

    // Trois raisons de ne rien faire, et aucune n'est une erreur : Google
    // n'est pas configuré, quelqu'un est déjà connecté, ou une autre instance
    // s'en charge déjà.
    if (! client || page.props.auth?.user || page.props.auth?.owner || occupe) {
        return
    }

    occupe = true

    try {
        await charger()
    } catch {
        occupe = false

        return
    }

    window.google.accounts.id.initialize({
        client_id: client,
        callback: recevoir,
        // FedCM est le mécanisme du navigateur qui remplace l'ancienne iframe
        // tierce ; sans lui, Chrome finira par ne plus rien afficher.
        use_fedcm_for_prompt: true,
        // On ne referme pas l'invite au moindre clic ailleurs : sur une page
        // qu'on est en train de lire, ce serait la faire disparaître avant
        // d'avoir été vue.
        cancel_on_tap_outside: false,
    })

    window.google.accounts.id.prompt()
})

onUnmounted(() => {
    // Quitter la page annule l'invite : la laisser vivre sur l'écran suivant
    // proposerait de se connecter par-dessus autre chose.
    window.google?.accounts?.id?.cancel?.()
    occupe = false
})
</script>

<template>
    <!--
        Rien à rendre : l'invite est dessinée par Google, dans son propre
        conteneur, en haut à droite de la fenêtre. Le composant n'existe que
        pour la déclencher et la retirer au bon moment.
    -->
</template>
