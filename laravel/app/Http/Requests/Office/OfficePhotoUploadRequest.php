<?php

namespace App\Http\Requests\Office;

/**
 * Une photo téléversée depuis la photothèque : les mêmes exigences que depuis
 * une destination — une vraie photographie de lieu, créditée, déclarée —, et
 * une destination facultative à qui l'ajouter aussitôt.
 */
class OfficePhotoUploadRequest extends OfficeDestinationPhotoRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'destination_id' => ['nullable', 'integer', 'exists:destinations,id'],
        ];
    }
}
