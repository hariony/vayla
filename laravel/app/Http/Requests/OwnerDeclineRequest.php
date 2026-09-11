<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Refuser une demande. Le motif est facultatif, et il part au voyageur. */
class OwnerDeclineRequest extends FormRequest
{
    public function rules(): array
    {
        return ['reason' => ['nullable', 'string', 'max:1000']];
    }

    public function motif(): ?string
    {
        return trim($this->string('reason')->toString()) ?: null;
    }
}
