{{-- Le code d'abord, l'explication ensuite : on lit un e-mail de
     vérification dans une notification, pas dans une boîte ouverte. --}}
<x-mail::message>
# Votre code Vayla

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

Ce code unique vous permet d'ouvrir votre compte Vayla **sans mot de passe**.
Il est valable **{{ $minutes }} minutes** et ne sert qu'une fois.

**Ne le partagez avec personne.** Vayla ne vous le demandera jamais, ni par
téléphone, ni sur WhatsApp.

Si vous n'avez rien demandé sur Vayla, ignorez ce message : sans ce code,
personne ne peut ouvrir de compte avec votre adresse.

{{-- Aucun lien cliquable, à dessein : un e-mail de compte qui apprend à
     cliquer est un e-mail qui prépare l'hameçonnage suivant. --}}
Vayla — locations meublées vérifiées à Madagascar
</x-mail::message>
