<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'is_active' => ['nullable', 'boolean'],
            'role_id' => ['required', 'uuid', 'exists:roles,id'],
            'role_organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
        ];
    }
}
