<?php

namespace App\Http\Requests;

use App\Data\SejourData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Les dates qu'une fiche peut recevoir du moteur de recherche. **Une
 * suggestion, pas un critère** : elles pré-remplissent le calendrier pour que
 * le voyageur ne les ressaisisse pas, et elles ne sont donc pas validées —
 * `SejourData::depuis()` rend `null` sur n'importe quoi. Une suggestion
 * irrecevable ne pré-remplit rien ; elle ne fait jamais échouer la page.
 */
class ListingShowRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function sejour(): ?SejourData
    {
        $jour = fn (string $cle) => is_string($v = $this->query($cle)) ? $v : null;

        return SejourData::depuis($jour('arrival'), $jour('departure'));
    }
}
