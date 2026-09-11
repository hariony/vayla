<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;

/** Les paramètres de `/whatsapp` : ce qui reste à envoyer, ou ce qui est parti. */
class WhatsAppQueueRequest extends FormRequest
{
    public function rules(): array
    {
        return ['onglet' => ['nullable', 'string', 'max:20']];
    }

    public function envoyes(): bool
    {
        return $this->query('onglet') === 'envoyes';
    }
}
