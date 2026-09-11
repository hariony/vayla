<?php

namespace App\Http\Requests\Office;

use App\Contracts\Photos\PhotoProcessor;
use App\DTOs\Photos\PhotoCreditDto;
use App\DTOs\Photos\UploadTeamPhotoDto;
use App\Enums\PhotoLicence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Une photo de destination, téléversée par l'équipe.
 *
 * **Une destination est un lieu réel** : sa photo est une vraie photographie
 * de Madagascar, jamais une image générée ni une plage d'ailleurs étiquetée
 * « Nosy Be ». La case à cocher le fait déclarer, et le crédit — légende,
 * auteur, licence — est obligatoire : il s'affiche au pied de chaque page.
 */
class OfficeDestinationPhotoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.PhotoProcessor::POIDS_MAX_KO],
            'caption' => ['required', 'string', 'min:4', 'max:160'],
            'author' => ['required', 'string', 'min:2', 'max:120'],
            'licence' => ['required', Rule::enum(PhotoLicence::class)],
            'source_url' => ['nullable', 'url:https,http', 'max:255'],
            'declaration' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Choisissez une photo.',
            'photo.image' => 'Ce fichier n’est pas une image.',
            'photo.mimes' => 'Formats acceptés : JPEG, PNG ou WebP.',
            'photo.max' => 'Cette photo dépasse 40 Mo : exportez-la en qualité normale, ou en 6 000 pixels de large au plus.',
            'photo.uploaded' => 'La photo n’est pas arrivée : elle dépasse 40 Mo, ou la connexion a coupé pendant l’envoi. Réessayez.',
            'caption.required' => 'Dites ce que montre la photo, et où : c’est aussi le texte lu aux malvoyants.',
            'author.required' => 'Le nom de l’auteur : il est crédité au pied de chaque page.',
            'licence.required' => 'Choisissez la licence de la photo.',
            'declaration.accepted' => 'Confirmez que c’est une vraie photographie de ce lieu, que Vayla a le droit de publier.',
        ];
    }

    /** La photo et son crédit, pour la galerie de `destinationId` — ou aucune. */
    public function toDto(?int $destinationId = null): UploadTeamPhotoDto
    {
        return new UploadTeamPhotoDto($this->file('photo'), $this->credit(), $destinationId);
    }

    protected function credit(): PhotoCreditDto
    {
        return new PhotoCreditDto(
            caption: trim($this->string('caption')->toString()),
            author: trim($this->string('author')->toString()),
            licence: $this->enum('licence', PhotoLicence::class),
            sourceUrl: $this->filled('source_url') ? $this->string('source_url')->toString() : null,
        );
    }
}
