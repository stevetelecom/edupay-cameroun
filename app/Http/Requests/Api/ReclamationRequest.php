<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ReclamationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paiement_id'  => ['nullable', 'integer', 'exists:paiements,id'],
            'sujet'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'sujet.required'       => 'Le sujet est obligatoire.',
            'description.required' => 'La description est obligatoire.',
        ];
    }
}
