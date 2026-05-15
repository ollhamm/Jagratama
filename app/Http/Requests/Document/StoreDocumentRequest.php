<?php

namespace App\Http\Requests\Document;

use App\Models\Organization;
use App\Models\Workflow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDocumentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $workflowId = $this->input('workflow_id');

        if (blank($workflowId)) {
            return;
        }

        $workflow = Workflow::query()->find($workflowId);
        if (! $workflow) {
            return;
        }

        $this->merge([
            'document_type_id' => $workflow->document_type_id,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'workflow_id' => ['nullable', 'uuid', 'exists:workflows,id'],
            'document_type_id' => ['required_without:workflow_id', 'nullable', 'uuid', 'exists:document_types,id'],
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'attachment' => ['required', 'file', 'mimes:doc,docx', 'max:10240'],
            'attachments' => ['nullable', 'array', 'max:1'],
            'attachments.*' => ['file', 'mimes:doc,docx', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $workflowId = $this->input('workflow_id');
            $organizationId = $this->input('organization_id');

            if (blank($workflowId) || blank($organizationId)) {
                return;
            }

            $workflow = Workflow::query()->find($workflowId);
            $organization = Organization::query()->find($organizationId);

            if (! $workflow || ! $organization) {
                return;
            }

            $workflowType = (string) ($workflow->organization_type->value ?? $workflow->organization_type);
            $organizationType = (string) ($organization->type->value ?? $organization->type);

            if ($workflowType !== $organizationType) {
                $validator->errors()->add('organization_id', 'Organisasi tidak sesuai dengan tipe alur yang dipilih.');
            }
        });
    }
}
