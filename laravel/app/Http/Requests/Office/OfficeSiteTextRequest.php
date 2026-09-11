<?php

namespace App\Http\Requests\Office;

use App\Support\SiteTextCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Les textes d'un groupe. **Chaque texte a sa borne**, écrite dans le catalogue
 * avec sa raison — la ligne soulignée de l'accueil déborde d'un téléphone
 * au-delà de vingt caractères.
 *
 * **Les points des clés sont échappés** (`accueil\.hero\.titre`) : sans ça,
 * Laravel lit `textes.accueil.hero.titre` comme un tableau imbriqué, ne trouve
 * rien à cette adresse, et la borne ne s'applique jamais — le texte trop long
 * passait. Un test poste vingt et un caractères sur la ligne soulignée.
 */
class OfficeSiteTextRequest extends FormRequest
{
    public function rules(): array
    {
        $regles = ['groupe' => ['required', Rule::in(array_keys(SiteTextCatalog::GROUPES))], 'textes' => ['required', 'array']];

        foreach ($this->definitions() as $cle => $def) {
            $regles['textes.'.$this->echapper($cle)] = ['nullable', 'string', 'max:'.$def['max']];
        }

        return $regles;
    }

    public function messages(): array
    {
        $messages = [];

        foreach ($this->definitions() as $cle => $def) {
            $messages['textes.'.$this->echapper($cle).'.max'] = "{$def['label']} : {$def['max']} caractères au plus.";
            $messages['textes.'.$this->echapper($cle).'.string'] = "{$def['label']} : un texte est attendu.";
        }

        return $messages;
    }

    /** @return array<string, array<string, mixed>> */
    private function definitions(): array
    {
        return SiteTextCatalog::GROUPES[(string) $this->input('groupe')]['textes'] ?? [];
    }

    private function echapper(string $cle): string
    {
        return str_replace('.', '\.', $cle);
    }
}
