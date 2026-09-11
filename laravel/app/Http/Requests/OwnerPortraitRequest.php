<?php

namespace App\Http\Requests;

use App\Contracts\Photos\PhotoProcessor;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Le téléversement d'un portrait.
 *
 * **Douze mégaoctets**, comme pour une photo d'annonce : un téléphone récent
 * produit des fichiers de huit à dix, et refuser à cinq obligerait à passer
 * par une conversation WhatsApp pour réduire l'image.
 *
 * Le côté minimal en pixels, lui, est vérifié par `OwnerPortraitService` : il
 * ne se lit pas dans les métadonnées d'un formulaire.
 */
class OwnerPortraitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'portrait' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.PhotoProcessor::POIDS_MAX_KO],
        ];
    }

    public function messages(): array
    {
        return [
            'portrait.required' => 'Choisissez une photo.',
            'portrait.image' => 'Ce fichier n’est pas une image.',
            'portrait.mimes' => 'Formats acceptés : JPEG, PNG ou WebP.',
            'portrait.max' => 'Cette photo dépasse 40 Mo : exportez-la en qualité normale, ou en 6 000 pixels de large au plus.',
            'portrait.uploaded' => 'La photo n’est pas arrivée : elle dépasse 40 Mo, ou la connexion a coupé pendant l’envoi. Réessayez.',
        ];
    }
}
