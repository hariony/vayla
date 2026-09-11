<?php

namespace App\Http\Requests\Office;

use App\DTOs\Photos\PhotoLibraryFilterDto;
use App\Enums\PhotoLibraryTab;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Les paramètres de `/phototheque`. **Une adresse ne fait jamais échouer
 * l'écran** : un onglet inconnu retombe sur « Photos de lieux », une photo
 * illisible est oubliée — le lien vient peut-être du journal, d'une version
 * plus ancienne.
 */
class PhotoLibraryIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'onglet' => ['nullable', 'string', 'max:30'],
            'q' => ['nullable', 'string', 'max:100'],
            'photo' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! ctype_digit((string) $this->query('photo', ''))) {
            $this->query->remove('photo');
        }

        $this->query->set('q', mb_substr(trim((string) $this->query('q', '')), 0, 100));
    }

    public function toDto(): PhotoLibraryFilterDto
    {
        return new PhotoLibraryFilterDto(
            onglet: PhotoLibraryTab::tryFrom((string) $this->query('onglet')) ?? PhotoLibraryTab::Lieux,
            recherche: (string) $this->query('q', ''),
            photoOuverte: $this->filled('photo') ? $this->integer('photo') : null,
        );
    }
}
