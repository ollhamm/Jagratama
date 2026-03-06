<?php

namespace App\Http\Requests\Signature;

use Illuminate\Foundation\Http\FormRequest;

class StoreSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_approval_id' => ['required', 'uuid', 'exists:document_approvals,id'],
            'signature_type' => ['required', 'string', 'in:BARCODE'],
            'signature_value' => ['nullable', 'string'],
        ];
    }
}
