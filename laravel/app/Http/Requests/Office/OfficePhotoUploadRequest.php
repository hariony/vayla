<?php

namespace App\Http\Requests\Office;

use App\DTOs\Photos\UploadTeamPhotoDto;

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

    public function toDto(?int $destinationId = null): UploadTeamPhotoDto
    {
        return parent::toDto($destinationId ?? ($this->filled('destination_id') ? $this->integer('destination_id') : null));
    }
}
