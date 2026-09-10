<?php

namespace App\Services\Currency;

/**
 * D'où vient le taux ariary → euro.
 *
 * **Une interface pour une seule valeur**, et c'est délibéré : le taux vient
 * aujourd'hui de la configuration, il viendra demain d'une API de change.
 * Le jour où ce sera le cas, seule la liaison d'`AppServiceProvider` change —
 * aucune ligne d'affichage, aucun contrôleur, aucun composant.
 *
 * L'implémentation à venir devra respecter deux choses que
 * `ConfigExchangeRate` tient gratuitement :
 *
 * 1. **Ne jamais lever ni bloquer une page.** Un prix converti est un
 *    confort ; une fiche de logement qui échoue parce qu'un service de
 *    change ne répond pas serait une panne pour un agrément. Une
 *    implémentation réseau met en cache et retombe sur la dernière valeur
 *    connue — jamais sur zéro, jamais sur une exception.
 * 2. **Toujours savoir de quand date le taux.** Un montant converti sans sa
 *    date est invérifiable, et sur un site dont l'argument est la
 *    vérification, un chiffre invérifiable est un chiffre de trop.
 */
interface ExchangeRateProvider
{
    /** Combien d'ariary pour un euro. Zéro ou négatif = pas de conversion affichable. */
    public function ariaryParEuro(): float;

    /** Le jour où ce taux a été relevé, `AAAA-MM-JJ`. */
    public function releveLe(): string;
}
