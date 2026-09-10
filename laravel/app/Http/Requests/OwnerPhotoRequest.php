<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Le téléversement d'une photo.
 *
 * **Douze mégaoctets**, parce qu'un téléphone récent produit des fichiers de
 * huit à dix : refuser à cinq obligerait le propriétaire à passer par une
 * conversation WhatsApp pour réduire l'image, et une photo compressée par
 * WhatsApp fait mille pixels de large — trop peu pour la photo de tête.
 *
 * La taille minimale en pixels, elle, est vérifiée par `PhotoUploadService` :
 * elle ne se lit pas dans les métadonnées d'un formulaire.
 */
class OwnerPhotoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:12288'],
            'caption' => ['nullable', 'string', 'max:160'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Choisissez une photo.',
            'photo.image' => 'Ce fichier n’est pas une image.',
            'photo.mimes' => 'Formats acceptés : JPEG, PNG ou WebP.',
            'photo.max' => 'Cette photo dépasse 12 Mo. Prenez-la en qualité normale plutôt qu’en maximum.',
        ];
    }
}
