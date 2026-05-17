<?php

namespace App\Http\Requests\Approval;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:APPROVED,REJECTED,SKIPPED'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
