<?php

namespace App\Support;

use Stringable;

/**
 * Un numéro de téléphone, normalisé une fois pour toutes.
 *
 * **C'est la clé de connexion des propriétaires**, et personne ne le retape
 * deux fois de la même façon : « 034 00 000 01 », « +261 34 00 000 01 » et
 * « 0340000001 » sont le même numéro. La comparaison se faisait jusqu'ici sur
 * les **neuf derniers chiffres** — ça marchait, mais ça reposait sur une
 * coïncidence de longueur plutôt que sur une règle : deux numéros de pays
 * différents finissant pareil auraient ouvert le même compte.
 *
 * Ici on ramène tout à une forme canonique (E.164, `+261340000001`) et on
 * compare **des numéros**, pas des fins de chaîne.
 *
 * **Les numéros étrangers sont acceptés, et ce n'est pas une concession.** Une
 * part des propriétaires malgaches vit en France ou à La Réunion et loue une
 * maison à Nosy Be : refuser un `+33` écarterait exactement les gens qui ont
 * les moyens d'équiper un logement. On reconnaît et on met en forme les
 * numéros malgaches ; les autres sont conservés tels quels, en E.164.
 *
 * **Aucune dépendance ajoutée.** `giggsey/libphonenumber-for-php` pèse plus de
 * dix mégaoctets de métadonnées mondiales pour un produit qui sert un pays et
 * quelques diasporas — et il faudrait de toute façon écrire les règles
 * d'affichage malgaches à la main par-dessus.
 */
final class Telephone implements Stringable
{
    /**
     * Les préfixes malgaches, après l'indicatif pays.
     *
     * Mobiles : Orange (32), Airtel (33), Telma (34 et 38). Fixes : 20. Seuls
     * les mobiles sont joignables sur WhatsApp, qui est **le** canal de Vayla —
     * d'où `estMobile()`, et le refus d'un fixe là où on enverra un lien.
     */
    private const OPERATEURS = [
        '32' => 'Orange',
        '33' => 'Airtel',
        '34' => 'Telma',
        '38' => 'Telma',
    ];

    private const FIXE = '20';

    private const MADAGASCAR = '261';

    private function __construct(
        /** Chiffres seuls, indicatif pays compris. */
        public readonly string $chiffres,
    ) {}

    /**
     * Normalise une saisie, ou renvoie `null` si elle ne désigne aucun numéro.
     *
     * Cinq écritures traitées, et elles viennent toutes du terrain : la forme
     * internationale, le `00` du clavier fixe, le zéro national, l'indicatif
     * sans `+` (ce que produit un copier-coller depuis WhatsApp), et le numéro
     * nu à neuf chiffres.
     */
    public static function depuis(?string $brut): ?self
    {
        if ($brut === null) {
            return null;
        }

        $plus = str_starts_with(ltrim($brut), '+');
        $chiffres = preg_replace('/\D+/', '', $brut) ?? '';

        if ($chiffres === '') {
            return null;
        }

        // Forme internationale explicite : on fait confiance à l'indicatif.
        if ($plus) {
            return self::valide($chiffres) ? new self($chiffres) : null;
        }

        if (str_starts_with($chiffres, '00')) {
            $chiffres = substr($chiffres, 2);

            return self::valide($chiffres) ? new self($chiffres) : null;
        }

        if (str_starts_with($chiffres, self::MADAGASCAR) && strlen($chiffres) === 12) {
            return new self($chiffres);
        }

        // Zéro national : « 034 00 000 01 ». Dix chiffres, le premier à zéro.
        if (strlen($chiffres) === 10 && str_starts_with($chiffres, '0')) {
            return new self(self::MADAGASCAR.substr($chiffres, 1));
        }

        // Numéro nu, tel qu'on le dicte : « 34 00 000 01 ».
        if (strlen($chiffres) === 9 && self::prefixeConnu(substr($chiffres, 0, 2))) {
            return new self(self::MADAGASCAR.$chiffres);
        }

        return self::valide($chiffres) ? new self($chiffres) : null;
    }

    /** E.164 : la forme de stockage et de comparaison. `+261340000001`. */
    public function e164(): string
    {
        return '+'.$this->chiffres;
    }

    /**
     * La forme lisible. Un numéro malgache se lit par groupes — « +261 34 00
     * 000 01 » — parce que c'est comme ça qu'on se le dicte. Un numéro
     * étranger reste en E.164 : inventer un groupement pour chaque pays
     * produirait des numéros faux à l'œil de leur propriétaire.
     */
    public function lisible(): string
    {
        if (! $this->estMalgache()) {
            return $this->e164();
        }

        $n = $this->national();

        return sprintf('+%s %s %s %s %s',
            self::MADAGASCAR,
            substr($n, 0, 2), substr($n, 2, 2), substr($n, 4, 3), substr($n, 7, 2));
    }

    /** Les neuf chiffres après l'indicatif, pour un numéro malgache. */
    public function national(): string
    {
        return $this->estMalgache() ? substr($this->chiffres, 3) : $this->chiffres;
    }

    public function estMalgache(): bool
    {
        return str_starts_with($this->chiffres, self::MADAGASCAR) && strlen($this->chiffres) === 12;
    }

    /**
     * Joignable sur WhatsApp. Un fixe malgache ne l'est pas — et c'est par là
     * que passe le lien d'accès au compte.
     */
    public function estMobile(): bool
    {
        if (! $this->estMalgache()) {
            // Hors de Madagascar on ne sait pas distinguer : on ne prétend pas.
            return true;
        }

        return isset(self::OPERATEURS[substr($this->national(), 0, 2)]);
    }

    /** L'opérateur, quand on peut le nommer. Sert à l'écran, jamais à décider. */
    public function operateur(): ?string
    {
        return $this->estMalgache()
            ? (self::OPERATEURS[substr($this->national(), 0, 2)] ?? null)
            : null;
    }

    public function equivaut(?self $autre): bool
    {
        return $autre !== null && $this->chiffres === $autre->chiffres;
    }

    public function __toString(): string
    {
        return $this->e164();
    }

    private static function prefixeConnu(string $deuxChiffres): bool
    {
        return isset(self::OPERATEURS[$deuxChiffres]) || $deuxChiffres === self::FIXE;
    }

    /**
     * Un numéro international plausible. On ne vérifie pas l'indicatif contre
     * une table mondiale : elle serait fausse le jour où un pays en ouvre un
     * nouveau, et le vrai contrôle est ailleurs — le lien qui arrive, ou non.
     */
    private static function valide(string $chiffres): bool
    {
        $n = strlen($chiffres);

        if ($n < 8 || $n > 15) {
            return false;
        }

        // Un numéro malgache complet doit porter un préfixe qu'on connaît :
        // « +261 99 … » n'existe pas, et le laisser passer ferait échouer un
        // envoi WhatsApp sans que personne ne sache pourquoi.
        if (str_starts_with($chiffres, self::MADAGASCAR)) {
            return $n === 12 && self::prefixeConnu(substr($chiffres, 3, 2));
        }

        return true;
    }
}
