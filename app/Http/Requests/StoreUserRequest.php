<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        $isStudent = $this->role_id && \App\Models\Role::find($this->role_id)?->slug === 'student';

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'identifier' => ['nullable', 'string', 'max:50', 'unique:users,identifier'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'role_id' => ['required', Rule::exists('roles', 'id')],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'program_id' => ['nullable', Rule::exists('programs', 'id')],
            'section_id' => [$isStudent ? 'required' : 'nullable', Rule::exists('sections', 'id')],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended', 'pending'])],
            'password' => ['required', 'min:8'],
        ];
    }
}
