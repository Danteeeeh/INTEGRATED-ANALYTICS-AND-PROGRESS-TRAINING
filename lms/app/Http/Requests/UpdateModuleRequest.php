<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $module = $this->route('module');

        return $module && ($this->user()?->can('update', $module) ?? false);
    }

    public function rules(): array
    {
        return [
            'course_id' => 'sometimes|required|exists:courses,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'objectives' => 'nullable|string',
            'position' => 'nullable|integer|min:0',
            'is_required' => 'nullable|boolean',
            'status' => 'sometimes|required|in:draft,published,archived',
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.exists' => 'Selected course does not exist',
            'title.required' => 'Module title is required',
            'status.in' => 'Status must be draft, published, or archived',
        ];
    }
}
