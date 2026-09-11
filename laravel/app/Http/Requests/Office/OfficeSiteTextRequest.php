<?php

namespace App\Http\Requests\Office;

use App\DTOs\Content\SiteTextsDto;
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

    /**
     * Les espaces en trop et les fins de ligne Windows ne sont pas du texte ;
     * un champ vidé vaut « rétablir l'original ».
     */
    public function toDto(): SiteTextsDto
    {
        // Seules les clés du groupe : ce sont les seules que les règles ont bornées.
        $saisis = array_intersect_key((array) $this->validated('textes'), $this->definitions());

        $textes = array_map(
            fn (?string $v) => trim(preg_replace("/[ \t]+\n/", "\n", str_replace("\r\n", "\n", (string) $v))),
            $saisis,
        );

        return new SiteTextsDto((string) $this->validated('groupe'), $textes);
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
