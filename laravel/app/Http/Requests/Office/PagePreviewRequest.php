<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;

/** Le Markdown à prévisualiser, même borne que la page elle-même. */
class PagePreviewRequest extends FormRequest
{
    public function rules(): array
    {
        return ['body' => ['nullable', 'string', 'max:60000']];
    }

    public function body(): string
    {
        return $this->string('body')->toString();
    }
}
