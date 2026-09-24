<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->targetUser();

        return $target ? ($this->user()?->can('update', $target) ?? false) : false;
    }

    public function rules(): array
    {
        $userId = $this->targetUser()?->id;

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'identifier' => ['nullable', 'string', 'max:50', Rule::unique('users', 'identifier')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'role_id' => ['sometimes', 'required', Rule::exists('roles', 'id')],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive', 'suspended', 'pending'])],
            'password' => ['nullable', 'min:8'],
        ];
    }

    protected function targetUser(): ?User
    {
        $route = $this->route('user') ?? $this->route('student') ?? $this->route('instructor');

        return $route instanceof User ? $route : null;
    }
}
