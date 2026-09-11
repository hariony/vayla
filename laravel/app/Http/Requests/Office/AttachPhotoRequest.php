<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;

/** Une photo de la photothèque à ajouter à une galerie. Ce qu'elle a le droit d'illustrer, le service le vérifie. */
class AttachPhotoRequest extends FormRequest
{
    public function rules(): array
    {
        return ['photo_id' => ['required', 'integer']];
    }

    public function photoId(): int
    {
        return $this->integer('photo_id');
    }
}
