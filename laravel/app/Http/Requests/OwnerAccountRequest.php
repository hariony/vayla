<?php

namespace App\Http\Requests;

use App\Rules\TelephoneValide;
use App\Support\Telephone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ce qu'un propriétaire peut corriger lui-même sur son compte.
 *
 * **Cinq champs, et aucun d'eux n'ouvre une porte.** Le nom, le numéro
 * WhatsApp, la ville, l'adresse exacte, et le compte mobile money vers lequel
 * il règle sa commission.
 *
 * **L'adresse exacte ne s'affiche nulle part côté public**, et l'écran le dit.
 * Elle sert à deux choses : la facture de fin de mois, qui doit désigner
 * quelqu'un pour être payable, et la vérification — le correspondant local qui
 * passe voir un logement doit savoir où aller. La ville reste à côté : elle est
 * approximative et suffit à tout le reste. L'**adresse e-mail n'y est pas** : elle est devenue
 * l'identifiant de connexion, et la changer depuis un formulaire ouvert
 * reviendrait à donner le compte à qui a laissé une session ouverte sur un
 * téléphone. Elle se change en écrivant à Vayla, et l'écran le dit.
 *
 * **Le numéro est normalisé avant la validation**, jamais après. `unique`
 * compare des chaînes : « +261 34 00 000 01 » ne ressemble pas au
 * « +261340000001 » stocké, et la règle laisserait passer un second compte sur
 * le même numéro écrit autrement — deux comptes indiscernables au téléphone,
 * dont un seul recevrait le lien d'accès.
 *
 * **`ignore` sur soi-même** : sans lui, enregistrer sans toucher au numéro
 * échouerait sur « ce numéro est déjà pris » — par soi.
 *
 * **Le mobile money n'est qu'un numéro de téléphone.** Aucun jeton, aucun
 * moyen de débiter quoi que ce soit : c'est le compte vers lequel le
 * propriétaire *pousse* son règlement. Il est facultatif — on ne bloque pas
 * une fiche parce que quelqu'un n'a pas encore décidé par où il paiera.
 */
class OwnerAccountRequest extends FormRequest
{
    /**
     * Les trois opérateurs qui existent à Madagascar, et rien d'autre.
     *
     * **Ce sont les libellés eux-mêmes, pas des clés.** La colonne porte déjà
     * « MVola » et « Orange Money » — ceux que le seeder écrit et que la
     * facture affiche telle quelle. Introduire des clés ici aurait obligé à
     * une table de correspondance et à une migration des lignes existantes,
     * pour trois valeurs qui ne changeront pas.
     */
    public const OPERATEURS = ['MVola', 'Orange Money', 'Airtel Money'];

    public function rules(): array
    {
        $moi = $this->user('proprietaire')?->id;

        return [
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'phone' => [
                'required', 'string', 'max:40', TelephoneValide::mobile(),
                Rule::unique('owners', 'phone')->ignore($moi),
            ],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:200'],
            'mobile_money' => ['nullable', 'string', 'max:40', TelephoneValide::mobile()],
            // Une liste, pas un champ libre : un opérateur écrit de trois
            // façons est un opérateur qu'on ne peut plus regrouper.
            'mobile_money_operator' => ['nullable', Rule::in(self::OPERATEURS)],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['phone', 'mobile_money'] as $champ) {
            if ($this->filled($champ) && $numero = Telephone::depuis($this->input($champ))) {
                $this->merge([$champ => $numero->e164()]);
            }
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Votre nom, celui que verront les voyageurs.',
            'phone.required' => 'Votre numéro WhatsApp : c’est par là que Vayla vous joint.',
            'phone.unique' => 'Ce numéro est déjà celui d’un autre compte Vayla.',
            'mobile_money_operator.in' => 'Choisissez un opérateur dans la liste.',
        ];
    }
}
