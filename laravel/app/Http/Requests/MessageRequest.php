<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Un message dans le fil d'une réservation.
 *
 * Deux mille caractères : au-delà, ce n'est plus un message mais un document,
 * et ça se lit mal sur un téléphone. En dessous de deux, c'est un envoi
 * accidentel — le clavier tactile en produit.
 */
class MessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Écrivez votre message.',
            'body.min' => 'Écrivez votre message.',
            'body.max' => 'Message trop long : deux mille caractères au maximum.',
        ];
    }

    public function body(): string
    {
        return $this->string('body')->toString();
    }
}
