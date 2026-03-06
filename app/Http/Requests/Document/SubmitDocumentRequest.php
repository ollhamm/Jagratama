<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class SubmitDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signature_value' => ['required', 'string', 'max:5000'],
        ];
    }
}
