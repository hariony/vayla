<?php

namespace App\Http\Requests;

use App\Contracts\Photos\PhotoProcessor;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Le téléversement d'une photo.
 *
 * **Quarante mégaoctets** (`PhotoProcessor::POIDS_MAX_KO`) : un téléphone
 * récent produit des fichiers de huit à quinze, un appareil de 48 Mpx vingt
 * et plus. Refuser trop bas obligerait le propriétaire à passer par une
 * conversation WhatsApp pour réduire l'image, et une photo compressée par
 * WhatsApp fait mille pixels de large — trop peu pour la photo de tête. Le
 * navigateur réduit d'ordinaire la photo avant l'envoi : la borne sert quand il
 * n'a pas pu.
 *
 * La taille minimale en pixels, elle, est vérifiée par `PhotoUploadService` :
 * elle ne se lit pas dans les métadonnées d'un formulaire.
 */
class OwnerPhotoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.PhotoProcessor::POIDS_MAX_KO],
            'caption' => ['nullable', 'string', 'max:160'],
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
        ];
    }
}
